<?php

declare(strict_types=1);

namespace App\Services\Susdat;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

/**
 * Builds the plan that copies the final legacy (NDS1, susdat.susdat) values into
 * susdat_substances, field by field, wherever they really differ. Pure PHP, no database
 * access: it works on rows already decoded from the JSONL exports that
 * App\Console\Commands\SusdatLegacySyncPlan reads.
 *
 * The id is internal and the code is public ("NS" + code): rows are paired by code and
 * neither is ever planned. A cell somebody changed in NDS2.0 (found in the audits), even by
 * filling an empty value, is held back unless it is approved by hand.
 * SusdatLegacyValuesSyncSeeder applies the plan.
 */
final class LegacySyncPlanBuilder
{
    public const PLAN_KIND = 'susdat_legacy_values_sync_plan';

    public const PLAN_FORMAT = 1;

    public const ENVIRONMENTS = ['production', 'local'];

    /** Legacy column => susdat_substances column. */
    public const TEXT_COLUMNS = [
        'sus_name' => 'name',
        'Name Dashboard' => 'name_dashboard',
        'Name ChemSpider' => 'name_chemspider',
        'Name IUPAC' => 'name_iupac',
        'SMILES' => 'smiles',
        'SMILES Dashboard' => 'smiles_dashboard',
        'StdInChI' => 'stdinchi',
        'StdInChIKey' => 'stdinchikey',
        'PubChem_CID' => 'pubchem_cid',
        'ChemSpiderID' => 'chemspider_id',
        'DTXSID' => 'dtxid',
        'Molecular_Formula' => 'molecular_formula',
    ];

    public const CAS_LEGACY_COLUMN = 'sus_cas';

    public const CAS_COLUMN = 'cas_number';

    /** Legacy column => susdat_substances column. */
    public const MASS_COLUMNS = [
        'Monoiso_Mass' => 'mass_iso',
        'average_mass' => 'average_mass',
    ];

    /** susdat_substances JSON column => legacy columns kept as its keys. */
    public const METADATA_COLUMNS = [
        'metadata_synonyms' => [
            'Synonyms ChemSpider',
            'Reliability of Synonyms ChemSpider',
        ],
        'metadata_cas' => [
            'CAS_RN Dashboard',
            'CAS_RN PubChem',
            'CAS_RN Cactus',
            'CAS_RN ChemSpider',
            'Reliability of CAS_ChemSpider',
        ],
        'metadata_ms_ready' => [
            'MS_Ready_SMILES',
            'MS_Ready_StdInChI',
            'MS_Ready_StdInChIKey',
        ],
        'metadata_general' => [
            '[M+H]+',
            '[M-H]-',
            'Pred_RTI_Positive_ESI',
            'Uncertainty_RTI_pos',
            'Pred_RTI_Negative_ESI',
            'Uncertainty_RTI_neg',
            'Tetrahymena_pyriformis_toxicity',
            'IGC50_48_hr_ug/L',
            'Uncertainty_Tetrahymena_pyriformis_toxicity',
            'Daphnia_toxicity',
            'LC50_48_hr_ug/L',
            'Uncertainty_Daphnia_toxicity',
            'Algae_toxicity',
            'EC50_72_hr_ug/L',
            'Uncertainty_Algae_toxicity',
            'Pimephales_promelas_toxicity',
            'LC50_96_hr_ug/L',
            'Uncertainty_Pimephales_promelas_toxicity',
            'logKow_EPISuite',
            'Exp_logKow_EPISuite',
            'ChemSpider ID based on InChIKey_19032018',
            'alogp_ChemSpider',
            'xlogp_ChemSpider',
            'Lowest P-PNEC (QSAR) [ug/L]',
            'Species',
            'Uncertainty',
            'ExposureScore_Water_KEMI',
            'HazScore_EcoChronic_KEMI',
            'ValidationLevel_KEMI',
            'Prob_of_GC',
            'Prob_RPLC',
            'Pred_Chromatography',
            'Prob_of_both_Ionization_Source',
            'Prob_EI',
            'Prob_ESI',
            'Pred_Ionization_source',
            'Prob_both_ESI_mode',
            'Prob_plusESI',
            'Prob_minusESI',
            'Pred_ESI_mode',
            'Preferable_Platform_by_decision_Tree',
        ],
    ];

    private const TRIM = " \t\r\n";

    private const CAS_PREFIX_PATTERN = '/^CAS[ _-]?RN\s*:\s*/i';

    private const MASS_TOLERANCE = 1e-6;

    private const METADATA_TOLERANCE = 1e-9;

    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    /** Default escaping, as the first load (SusdatSusdatMigrator) wrote the metadata. */
    private const METADATA_JSON_FLAGS = JSON_THROW_ON_ERROR;

    /**
     * The 15 columns shown on the substance page, in a fixed order.
     *
     * @return list<string>
     */
    public static function pageColumns(): array
    {
        return array_merge(
            array_values(self::TEXT_COLUMNS),
            [self::CAS_COLUMN],
            array_values(self::MASS_COLUMNS),
        );
    }

    /**
     * @return list<string>
     */
    public static function metadataColumns(): array
    {
        return array_keys(self::METADATA_COLUMNS);
    }

    /**
     * The 19 columns a plan may contain.
     *
     * @return list<string>
     */
    public static function syncColumns(): array
    {
        return array_merge(self::pageColumns(), self::metadataColumns());
    }

    /**
     * Legacy column => susdat_substances column, for the 15 page columns.
     *
     * @return array<string, string>
     */
    public static function pageColumnMap(): array
    {
        return self::TEXT_COLUMNS + [self::CAS_LEGACY_COLUMN => self::CAS_COLUMN] + self::MASS_COLUMNS;
    }

    /**
     * The legacy columns an export has to contain, sus_id first.
     *
     * @return list<string>
     */
    public static function legacyColumns(): array
    {
        return array_merge(
            ['sus_id'],
            array_keys(self::pageColumnMap()),
            array_merge(...array_values(self::METADATA_COLUMNS)),
        );
    }

    /**
     * @return list<string>
     */
    public static function massColumns(): array
    {
        return array_values(self::MASS_COLUMNS);
    }

    /**
     * @param  list<array<string, mixed>>  $legacyRows
     * @param  list<array<string, mixed>>  $productionRows
     * @param  list<array<string, mixed>>  $auditRows
     * @param  array{environment: string, approvals?: list<string>, inputs: array<string, array{file: string, sha256: string, rows: int}>, created_at_utc?: string}  $options
     * @return array{plan: array<string, mixed>, summary: list<string>}
     */
    public function build(array $legacyRows, array $productionRows, array $auditRows, array $options): array
    {
        $environment = (string) ($options['environment'] ?? '');

        if (! in_array($environment, self::ENVIRONMENTS, true)) {
            throw new InvalidArgumentException('Environment must be production or local.');
        }

        $approvals = $this->parseApprovals($options['approvals'] ?? []);
        $legacy = $this->indexLegacy($legacyRows);
        $production = $this->indexProduction($productionRows);

        $auditsById = [];

        foreach ($auditRows as $audit) {
            $auditsById[(int) $audit['auditable_id']][] = $audit;
        }

        $cells = [];
        $heldBack = [];
        $approvedUsed = [];
        $nonNumeric = [];

        foreach ($production['unique'] as $code => $row) {
            $code = (string) $code;

            if (! isset($legacy[$code])) {
                continue;
            }

            $planned = $this->planRow($code, $row, $legacy[$code]);

            foreach ($planned['non_numeric'] as $item) {
                $nonNumeric[] = $item;
            }

            foreach ($planned['cells'] as $cell) {
                $audit = $this->lastChange($auditsById[$cell['id']] ?? [], $cell['column']);

                if ($audit !== null) {
                    $key = $code.':'.$cell['column'];

                    if (! isset($approvals[$key])) {
                        $heldBack[] = [
                            'code' => $code,
                            'id' => $cell['id'],
                            'column' => $cell['column'],
                            'production' => $cell['from'],
                            'legacy' => $cell['to'],
                            'audit_id' => (int) $audit['id'],
                            'audit_at' => (string) ($audit['created_at'] ?? ''),
                            'audit_user_id' => isset($audit['user_id']) ? (int) $audit['user_id'] : null,
                        ];

                        continue;
                    }

                    $approvals[$key] = true;
                    $approvedUsed[] = $key;
                }

                $cells[] = $cell;
            }
        }

        $unused = array_keys(array_filter($approvals, fn (bool $used): bool => ! $used));

        if ($unused !== []) {
            throw new RuntimeException('Approval matches no held back cell: '.implode(', ', $unused).'.');
        }

        usort($cells, fn (array $a, array $b): int => $a['id'] <=> $b['id'] ?: strcmp($a['column'], $b['column']));
        usort($heldBack, fn (array $a, array $b): int => $a['id'] <=> $b['id'] ?: strcmp($a['column'], $b['column']));
        sort($approvedUsed, SORT_STRING);

        $onlyInLegacy = array_map('strval', array_values(array_diff(array_keys($legacy), array_keys($production['unique']), $production['duplicates'])));
        $onlyInNds2 = count(array_diff_key($production['unique'], $legacy)) + $production['without_code'];

        $perColumn = array_fill_keys(self::syncColumns(), 0);

        foreach ($cells as $cell) {
            $perColumn[$cell['column']]++;
        }

        $plan = [
            'format' => self::PLAN_FORMAT,
            'kind' => self::PLAN_KIND,
            'created_at_utc' => (string) ($options['created_at_utc'] ?? gmdate('Y-m-d H:i:s')),
            'environment' => $environment,
            'inputs' => $options['inputs'],
            'expected' => $this->expectedState($productionRows),
            'approved_used' => $approvedUsed,
            'counts' => [
                'columns' => $perColumn,
                'total_cells' => count($cells),
                'rows' => count(array_unique(array_column($cells, 'id'))),
                'held_back' => count($heldBack),
                'non_numeric_mass' => count($nonNumeric),
            ],
            'cells_sha256' => hash('sha256', json_encode($cells, self::JSON_FLAGS)),
            'cells' => $cells,
            'held_back' => $heldBack,
            'skipped' => [
                'duplicate_codes' => $production['duplicates'],
                'only_in_nds2' => $onlyInNds2,
                'only_in_legacy' => $onlyInLegacy,
                'non_numeric_mass' => $nonNumeric,
            ],
        ];

        return ['plan' => $plan, 'summary' => $this->summary($plan)];
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return list<string>
     */
    private function summary(array $plan): array
    {
        $lines = [
            "Plan for {$plan['environment']}: {$plan['counts']['total_cells']} cells in {$plan['counts']['rows']} rows.",
        ];

        foreach ($plan['counts']['columns'] as $column => $count) {
            if ($count > 0) {
                $lines[] = "  {$column}: {$count}";
            }
        }

        $lines[] = "Held back (changed in NDS2.0): {$plan['counts']['held_back']}; approved and planned: ".count($plan['approved_used']).'.';
        $lines[] = 'Skipped: '.count($plan['skipped']['duplicate_codes']).' duplicate codes, '
            .$plan['skipped']['only_in_nds2'].' only in NDS2.0, '
            .count($plan['skipped']['only_in_legacy']).' only in legacy, '
            .$plan['counts']['non_numeric_mass'].' non-numeric legacy masses.';
        $lines[] = "Expected production state: {$plan['expected']['rows']} rows, max id {$plan['expected']['max_id']}, "
            ."{$plan['expected']['active']} active, {$plan['expected']['merged']} merged, id/code fingerprint {$plan['expected']['id_code_fingerprint']}.";

        return $lines;
    }

    /**
     * @param  list<string>  $approvals
     * @return array<string, bool>
     */
    private function parseApprovals(array $approvals): array
    {
        $parsed = [];

        foreach ($approvals as $approval) {
            $parts = explode(':', trim($approval), 2);

            if (count($parts) !== 2 || preg_match('/^\d{8}$/', $parts[0]) !== 1 || ! in_array($parts[1], self::syncColumns(), true)) {
                throw new InvalidArgumentException("Approval '{$approval}' is not CODE:column with one of the sync columns.");
            }

            $parsed[$parts[0].':'.$parts[1]] = false;
        }

        return $parsed;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, array<string, mixed>>
     */
    private function indexLegacy(array $rows): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            $missing = array_diff(self::legacyColumns(), array_keys($row));

            if ($missing !== []) {
                throw new RuntimeException('A legacy row lacks the columns: '.implode(', ', $missing).'.');
            }

            $code = trim((string) ($row['sus_id'] ?? ''), self::TRIM);

            if ($code === '') {
                throw new RuntimeException('A legacy row has no sus_id.');
            }

            if (isset($indexed[$code])) {
                throw new RuntimeException("Legacy sus_id {$code} occurs more than once.");
            }

            $indexed[$code] = $row;
        }

        return $indexed;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{unique: array<string, array<string, mixed>>, duplicates: list<string>, without_code: int}
     */
    private function indexProduction(array $rows): array
    {
        $byCode = [];
        $ids = [];
        $withoutCode = 0;

        foreach ($rows as $row) {
            $missing = array_diff(array_merge(['id', 'code', 'status'], self::syncColumns()), array_keys($row));

            if ($missing !== []) {
                throw new RuntimeException('A production row lacks the columns: '.implode(', ', $missing).'.');
            }

            $id = (int) $row['id'];

            if (isset($ids[$id])) {
                throw new RuntimeException("Production id {$id} occurs more than once.");
            }

            $ids[$id] = true;
            $code = trim((string) ($row['code'] ?? ''), self::TRIM);

            if ($code === '') {
                $withoutCode++;

                continue;
            }

            $byCode[$code][] = $row;
        }

        $unique = [];
        $duplicates = [];

        foreach ($byCode as $code => $group) {
            $code = (string) $code;

            if (count($group) > 1) {
                $duplicates[] = $code;
            } else {
                $unique[$code] = $group[0];
            }
        }

        sort($duplicates, SORT_STRING);

        return ['unique' => $unique, 'duplicates' => $duplicates, 'without_code' => $withoutCode];
    }

    /**
     * md5 of the ids and raw codes exactly as PostgreSQL computes
     * md5(coalesce(string_agg(id::text || ':' || code, ',' ORDER BY id), '')).
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{rows: int, max_id: int, active: int, merged: int, id_code_fingerprint: string}
     */
    private function expectedState(array $rows): array
    {
        usort($rows, fn (array $a, array $b): int => (int) $a['id'] <=> (int) $b['id']);

        $parts = [];
        $active = 0;
        $merged = 0;

        foreach ($rows as $row) {
            if (($row['code'] ?? null) !== null) {
                $parts[] = (int) $row['id'].':'.$row['code'];
            }

            $active += ($row['status'] ?? null) === 'active' ? 1 : 0;
            $merged += ($row['status'] ?? null) === 'merged' ? 1 : 0;
        }

        return [
            'rows' => count($rows),
            'max_id' => $rows === [] ? 0 : (int) end($rows)['id'],
            'active' => $active,
            'merged' => $merged,
            'id_code_fingerprint' => md5(implode(',', $parts)),
        ];
    }

    /**
     * @param  array<string, mixed>  $production
     * @param  array<string, mixed>  $legacy
     * @return array{cells: list<array<string, mixed>>, non_numeric: list<array{code: string, column: string, legacy: string}>}
     */
    private function planRow(string $code, array $production, array $legacy): array
    {
        $id = (int) $production['id'];
        $cells = [];
        $nonNumeric = [];

        foreach (self::pageColumnMap() as $legacyColumn => $column) {
            $current = $production[$column] ?? null;
            $incoming = $legacy[$legacyColumn] ?? null;

            if (in_array($column, self::massColumns(), true)) {
                $to = $this->normalizeText($incoming);

                if ($to !== null && ! is_numeric($to)) {
                    $nonNumeric[] = ['code' => $code, 'column' => $column, 'legacy' => $to];

                    continue;
                }

                if ($this->massesEqual($this->normalizeScalar($current), $to)) {
                    continue;
                }

                $cells[] = ['id' => $id, 'code' => $code, 'column' => $column, 'from' => $this->rawText($current), 'to' => $to];

                continue;
            }

            $to = $this->normalizePageText($column, $incoming);

            if ($this->normalizePageText($column, $current) === $to) {
                continue;
            }

            $cells[] = ['id' => $id, 'code' => $code, 'column' => $column, 'from' => $this->rawText($current), 'to' => $to];
        }

        foreach (self::METADATA_COLUMNS as $column => $keys) {
            $cell = $this->metadataCell($code, $column, $keys, $legacy, $production[$column] ?? null);

            if ($cell !== null) {
                $cells[] = ['id' => $id, 'code' => $code, 'column' => $column] + $cell;
            }
        }

        return ['cells' => $cells, 'non_numeric' => $nonNumeric];
    }

    /**
     * @param  list<string>  $keys
     * @param  array<string, mixed>  $legacy
     * @return array{from: ?string, to: string, keys: list<array{key: string, from: ?string, to: ?string}>}|null
     */
    private function metadataCell(string $code, string $column, array $keys, array $legacy, mixed $stored): ?array
    {
        $text = $stored === null ? null : (string) $stored;
        $object = $this->decodeStoredMetadata($text, "{$code} {$column}") ?? [];
        $differing = [];

        foreach ($keys as $key) {
            $to = $this->normalizeScalar($legacy[$key] ?? null);
            $from = $this->normalizeScalar($object[$key] ?? null);

            if ($this->metadataValuesEqual($from, $to)) {
                continue;
            }

            $differing[] = ['key' => $key, 'from' => $from, 'to' => $to];
            $object[$key] = $to;
        }

        if ($differing === []) {
            return null;
        }

        return ['from' => $text, 'to' => json_encode($object, self::METADATA_JSON_FLAGS), 'keys' => $differing];
    }

    /**
     * @return array<string|int, mixed>|null
     */
    private function decodeStoredMetadata(?string $text, string $context): ?array
    {
        if ($text === null || trim($text, self::TRIM) === '') {
            return null;
        }

        try {
            $value = json_decode($text, true, 512, JSON_THROW_ON_ERROR);

            if (is_string($value)) {
                $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            }
        } catch (JsonException $exception) {
            throw new RuntimeException("Stored metadata of {$context} is not valid JSON: {$exception->getMessage()}");
        }

        if ($value !== null && ! is_array($value)) {
            throw new RuntimeException("Stored metadata of {$context} is neither an object nor null.");
        }

        return $value;
    }

    /**
     * The latest audit in which somebody changed the column in NDS2.0, including filling an
     * empty value; a change of encoding only does not count.
     *
     * @param  list<array<string, mixed>>  $audits
     * @return array<string, mixed>|null
     */
    private function lastChange(array $audits, string $column): ?array
    {
        $found = null;

        foreach ($audits as $audit) {
            $old = $this->decodeAuditValues($audit['old_values'] ?? null);
            $new = $this->decodeAuditValues($audit['new_values'] ?? null);

            if (! array_key_exists($column, $old) || ! array_key_exists($column, $new)) {
                continue;
            }

            if (! $this->auditValueChanged($column, $old[$column], $new[$column])) {
                continue;
            }

            if ($found === null || (int) $audit['id'] > (int) $found['id']) {
                $found = $audit;
            }
        }

        return $found;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeAuditValues(mixed $values): array
    {
        if (is_array($values)) {
            return $values;
        }

        if (! is_string($values) || trim($values, self::TRIM) === '') {
            return [];
        }

        $decoded = json_decode($values, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function auditValueChanged(string $column, mixed $old, mixed $new): bool
    {
        if (isset(self::METADATA_COLUMNS[$column])) {
            $a = $this->decodeAuditMetadata($old) ?? [];
            $b = $this->decodeAuditMetadata($new) ?? [];

            foreach (array_unique(array_merge(array_keys($a), array_keys($b))) as $key) {
                if (! $this->metadataValuesEqual($this->normalizeScalar($a[$key] ?? null), $this->normalizeScalar($b[$key] ?? null))) {
                    return true;
                }
            }

            return false;
        }

        if (in_array($column, self::massColumns(), true)) {
            return ! $this->massesEqual($this->normalizeScalar($old), $this->normalizeScalar($new));
        }

        return $this->normalizePageText($column, $old) !== $this->normalizePageText($column, $new);
    }

    /**
     * Audit metadata is a JSON string, sometimes double-encoded; a value that cannot be
     * decoded is kept as one raw entry so that it still counts as a value.
     *
     * @return array<string|int, mixed>|null
     */
    private function decodeAuditMetadata(mixed $value): ?array
    {
        for ($pass = 0; $pass < 2 && is_string($value); $pass++) {
            if (trim($value, self::TRIM) === '') {
                return null;
            }

            $decoded = json_decode($value, true);

            if ($decoded === null && strtolower(trim($value, self::TRIM)) !== 'null') {
                return ['__raw' => $value];
            }

            $value = $decoded;
        }

        if ($value === null) {
            return null;
        }

        return is_array($value) ? $value : ['__raw' => (string) $value];
    }

    private function normalizeScalar(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = match (true) {
            is_string($value) => $value,
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => (string) $value,
            default => json_encode($value, self::JSON_FLAGS),
        };

        return $this->normalizeText($text);
    }

    private function normalizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value, self::TRIM);

        return $text === '' ? null : $text;
    }

    private function normalizePageText(string $column, mixed $value): ?string
    {
        $text = $this->normalizeScalar($value);

        if ($text === null || $column !== self::CAS_COLUMN) {
            return $text;
        }

        return $this->normalizeText(preg_replace(self::CAS_PREFIX_PATTERN, '', $text));
    }

    private function rawText(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    private function massesEqual(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        if (! is_numeric($a) || ! is_numeric($b)) {
            return $a === $b;
        }

        return abs((float) $a - (float) $b) <= self::MASS_TOLERANCE;
    }

    private function metadataValuesEqual(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        if (is_numeric($a) && is_numeric($b)) {
            $x = (float) $a;
            $y = (float) $b;

            return abs($x - $y) <= self::METADATA_TOLERANCE * max(abs($x), abs($y));
        }

        return $a === $b;
    }
}
