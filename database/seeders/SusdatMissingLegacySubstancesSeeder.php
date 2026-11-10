<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;

/**
 * Issue 2.1 of the SUSDAT review: 37 substances of legacy NDS1 (susdat.susdat) are
 * missing from susdat_substances.
 *
 * Adds them from the frozen legacy export EXPORT_FILE, mapped the way the first load
 * (SusdatSusdatMigrator) mapped legacy columns. Each keeps its legacy SUSID as code and
 * its legacy created_at (a date, stored at 00:00:00), and gets a new id from the id
 * sequence. Their legacy source links (one: 00134943 to S116) are added to
 * susdat_source_substance. Legacy cca_source has no column here and is not copied.
 *
 * Existing rows of susdat_substances are never changed: the run checks that the
 * id/code fingerprint of every existing row is the same afterwards. Everything that
 * writes runs in one transaction, so any failure changes nothing (only the id sequence
 * may have moved, which is harmless). A record of the added rows is written to
 * RECORD_DIRECTORY; SusdatMissingLegacySubstancesRestoreSeeder removes exactly those.
 *
 * Not listed in DatabaseSeeder. Run by hand only:
 * php artisan db:seed --class=SusdatMissingLegacySubstancesSeeder --force
 */
class SusdatMissingLegacySubstancesSeeder extends Seeder
{
    public const RECORD_DIRECTORY = 'app/susdat_missing_legacy';

    public const RECORD_PREFIX = 'susdat_missing_legacy_added_';

    /** How long to wait for a table lock before giving up; nothing changes then. */
    public const LOCK_TIMEOUT = '10s';

    private const EXPORT_FILE = 'seeders/seeds/susdat/susdat_missing_legacy_substances_20261110T074631Z.json';

    private const EXPORT_SHA256 = '88857b5139923fd7cfa6a861450f86676e6d3557f55b8f7e65b1ba5264151c71';

    private const EXPORT_SUBSTANCES = 37;

    private const EXPORT_LINKS = 1;

    /** Measured on production on 2026-11-10 at 07:47 UTC. */
    private const EXPECTED_ROWS_BEFORE = 126497;

    private const EXPECTED_MAX_ID_BEFORE = 126497;

    private const EXPECTED_ID_CODE_FINGERPRINT = '490a5f0518d36b4611bb4cd1a3f83d75';

    private const EXPECTED_LINKS_BEFORE = 341611;

    private const CAS_PREFIX = 'CAS_RN: ';

    /** Legacy column => susdat_substances column. */
    private const TEXT_COLUMNS = [
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

    /** Legacy columns kept as JSON, as in SusdatSusdatMigrator. */
    private const METADATA_COLUMNS = [
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

    public function run(): void
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        DB::disableQueryLog();

        $export = self::readExport();
        $this->say('Export '.self::exportPath().': SHA-256 and contents as documented.');

        $planned = array_map(fn (array $row): array => $this->mapSubstance($row), $export['substances']);
        $sourceIds = $this->sourceIdsFor($export['source_links']);

        $this->assertNoneExists(array_column($planned, 'code'));
        $this->assertProductionState();

        File::ensureDirectoryExists(storage_path(self::RECORD_DIRECTORY));

        $this->say(sprintf(
            'Checks passed, nothing changed yet: %d substances and %d source links to add.',
            count($planned),
            count($export['source_links']),
        ));

        $result = DB::transaction(fn (): array => $this->add($planned, $export['source_links'], $sourceIds));
        $committedBy = self::utcNow();

        $this->report([
            'susdat missing legacy substances: environment='.app()->environment().' exact_counts='.(self::enforcesProductionCounts() ? 'yes' : 'no'),
            "transaction started {$result['started_at']} UTC, work finished {$result['finished_at']} UTC, committed by {$committedBy} UTC",
            "substances before {$result['rows_before']}, after {$result['rows_after']}, max id before {$result['max_id_before']}, new ids {$result['first_id']} to {$result['last_id']}",
            "id sequence before {$result['sequence_before']}, moved forward: {$result['sequence_moved']}",
            "id/code fingerprint of the existing rows {$result['id_code_fingerprint']} before and after",
            "source links before {$result['links_before']}, after {$result['links_after']}",
            "record {$result['record_path']}: SHA-256 {$result['record_sha256']}",
        ]);
    }

    /**
     * The fingerprint of every id and code in susdat_substances, or of the rows up to an id.
     */
    public static function idCodeFingerprint(?int $upToId = null): string
    {
        $where = $upToId === null ? '' : ' WHERE id <= '.$upToId;

        return (string) DB::scalar(
            "SELECT md5(coalesce(string_agg(id::text || ':' || code, ',' ORDER BY id), '')) FROM susdat_substances".$where
        );
    }

    public static function utcNow(): string
    {
        return (string) DB::scalar("SELECT to_char(clock_timestamp() AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI:SS')");
    }

    /**
     * Exact production counts are checked everywhere except on a local or test
     * database, whose substances may differ from production.
     */
    public static function enforcesProductionCounts(): bool
    {
        return ! app()->environment('local', 'testing');
    }

    /**
     * The codes of the export, in the order of the file.
     *
     * @return list<string>
     */
    public static function exportCodes(): array
    {
        return array_map(fn (array $row): string => (string) $row['sus_id'], self::readExport()['substances']);
    }

    public static function exportPath(): string
    {
        return database_path(self::EXPORT_FILE);
    }

    /**
     * @return array{substances: list<array<string, string|null>>, source_links: list<array{sus_id: string, ss_id: string}>}
     */
    private static function readExport(): array
    {
        $path = self::exportPath();

        if (! is_file($path)) {
            throw new RuntimeException("Export file not found: {$path}. Nothing was changed.");
        }

        if (hash_file('sha256', $path) !== self::EXPORT_SHA256) {
            throw new RuntimeException("Export file has an unexpected SHA-256: {$path}. Nothing was changed.");
        }

        try {
            $export = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Export file is not valid JSON: {$exception->getMessage()}. Nothing was changed.");
        }

        $substances = $export['substances'] ?? [];
        $links = $export['source_links'] ?? [];
        $codes = array_map(fn (array $row): string => (string) ($row['sus_id'] ?? ''), $substances);

        self::check('Substances in the export', count($substances), self::EXPORT_SUBSTANCES);
        self::check('Source links in the export', count($links), self::EXPORT_LINKS);
        self::check('Distinct codes in the export', count(array_unique($codes)), self::EXPORT_SUBSTANCES);

        foreach ($codes as $code) {
            if (preg_match('/^\d{8}$/', $code) !== 1) {
                throw new RuntimeException("Export code '{$code}' is not eight digits. Nothing was changed.");
            }
        }

        foreach ($links as $link) {
            if (! in_array($link['sus_id'] ?? null, $codes, true)) {
                throw new RuntimeException('Export source link for a code outside the export. Nothing was changed.');
            }
        }

        return ['substances' => $substances, 'source_links' => $links];
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array<string, string|float|null>
     */
    private function mapSubstance(array $row): array
    {
        $createdAt = self::text($row['created_at'] ?? null);

        if ($createdAt === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $createdAt) !== 1 || $createdAt === '0000-00-00') {
            throw new RuntimeException("Legacy created_at of {$row['sus_id']} is not a date. Nothing was changed.");
        }

        if (self::text($row['sus_name'] ?? null) === null) {
            throw new RuntimeException("Legacy name of {$row['sus_id']} is empty. Nothing was changed.");
        }

        $substance = ['code' => (string) $row['sus_id']];

        foreach (self::TEXT_COLUMNS as $legacy => $column) {
            $substance[$column] = self::text($row[$legacy] ?? null);
        }

        $cas = self::text($row['sus_cas'] ?? null);
        $substance['cas_number'] = $cas !== null && str_starts_with($cas, self::CAS_PREFIX)
            ? self::text(substr($cas, strlen(self::CAS_PREFIX)))
            : $cas;
        $substance['mass_iso'] = self::number($row['Monoiso_Mass'] ?? null, (string) $row['sus_id']);
        $substance['average_mass'] = self::number($row['average_mass'] ?? null, (string) $row['sus_id']);

        foreach (self::METADATA_COLUMNS as $column => $keys) {
            $values = [];

            foreach ($keys as $key) {
                $values[$key] = self::text($row[$key] ?? null);
            }

            $substance[$column] = array_filter($values, fn (?string $value): bool => $value !== null) === []
                ? null
                : json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }

        $substance['created_at'] = $createdAt.' 00:00:00';

        return $substance;
    }

    /**
     * Runs inside the transaction.
     *
     * @param  list<array<string, string|float|null>>  $planned
     * @param  list<array{sus_id: string, ss_id: string}>  $links
     * @param  array<string, int>  $sourceIds
     * @return array<string, int|string>
     */
    private function add(array $planned, array $links, array $sourceIds): array
    {
        DB::statement("SET LOCAL lock_timeout = '".self::LOCK_TIMEOUT."'");
        DB::statement('LOCK TABLE susdat_substances IN SHARE ROW EXCLUSIVE MODE');
        DB::statement('LOCK TABLE susdat_source_substance IN SHARE ROW EXCLUSIVE MODE');

        $startedAt = (string) DB::scalar("SELECT to_char(transaction_timestamp() AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI:SS')");

        $this->assertNoneExists(array_column($planned, 'code'));
        $this->assertProductionState();

        $rowsBefore = DB::table('susdat_substances')->count();
        $maxIdBefore = (int) DB::table('susdat_substances')->max('id');
        $fingerprintBefore = self::idCodeFingerprint();
        $linksBefore = DB::table('susdat_source_substance')->count();
        $sequence = $this->moveSequenceToMaxId($maxIdBefore);

        $ids = [];

        foreach ($planned as $substance) {
            $id = (int) DB::table('susdat_substances')->insertGetId($substance + [
                'status' => 'active',
                'added_by' => null,
                'updated_at' => $startedAt,
            ]);

            if ($id <= $maxIdBefore) {
                throw new RuntimeException("New id {$id} is not above the highest existing id {$maxIdBefore}. Nothing was changed.");
            }

            $ids[(string) $substance['code']] = $id;
        }

        $addedLinks = [];

        foreach ($links as $link) {
            $addedLinks[] = [
                'source_id' => $sourceIds[$link['ss_id']],
                'substance_id' => $ids[$link['sus_id']],
                'created_at' => $startedAt,
                'updated_at' => $startedAt,
            ];
        }

        DB::table('susdat_source_substance')->insert($addedLinks);

        $rowsAfter = DB::table('susdat_substances')->count();
        $linksAfter = DB::table('susdat_source_substance')->count();

        self::check('Substances after', $rowsAfter, $rowsBefore + count($planned));
        self::check('Source links after', $linksAfter, $linksBefore + count($addedLinks));
        self::check('id/code fingerprint of the existing rows', self::idCodeFingerprint($maxIdBefore), $fingerprintBefore);
        $this->assertStored($planned, $ids, $startedAt);

        $record = $this->writeRecord($startedAt, $maxIdBefore, $fingerprintBefore, $ids, $addedLinks);

        return [
            'started_at' => $startedAt,
            'finished_at' => self::utcNow(),
            'rows_before' => $rowsBefore,
            'rows_after' => $rowsAfter,
            'max_id_before' => $maxIdBefore,
            'first_id' => min($ids),
            'last_id' => max($ids),
            'sequence_before' => $sequence['before'],
            'sequence_moved' => $sequence['moved'] ? 'yes' : 'no',
            'id_code_fingerprint' => $fingerprintBefore,
            'links_before' => $linksBefore,
            'links_after' => $linksAfter,
            'record_path' => $record['path'],
            'record_sha256' => $record['sha256'],
        ];
    }

    /**
     * Moves the id sequence forward to the highest id when it is behind, so new ids
     * never collide. Never moves it back.
     *
     * @return array{before: string, moved: bool}
     */
    private function moveSequenceToMaxId(int $maxId): array
    {
        $sequence = (string) DB::scalar("SELECT pg_get_serial_sequence('susdat_substances', 'id')");
        $state = DB::selectOne("SELECT last_value, is_called FROM {$sequence}");
        $next = $state->is_called ? (int) $state->last_value + 1 : (int) $state->last_value;
        $before = $state->last_value.($state->is_called ? '' : ' (not called)');

        if ($next > $maxId) {
            return ['before' => $before, 'moved' => false];
        }

        DB::statement('SELECT setval(?, ?, true)', [$sequence, $maxId]);

        return ['before' => $before, 'moved' => true];
    }

    /**
     * Reads the new rows back and compares them with the plan, column by column.
     *
     * @param  list<array<string, string|float|null>>  $planned
     * @param  array<string, int>  $ids
     */
    private function assertStored(array $planned, array $ids, string $startedAt): void
    {
        $stored = DB::table('susdat_substances')->whereIn('id', array_values($ids))->get()->keyBy('id');

        self::check('New rows read back', $stored->count(), count($planned));

        foreach ($planned as $substance) {
            $row = (array) $stored[$ids[(string) $substance['code']]];
            $expected = $substance + ['updated_at' => $startedAt, 'status' => 'active'];

            foreach ($expected as $column => $value) {
                $actual = $row[$column];
                $same = in_array($column, ['mass_iso', 'average_mass'], true)
                    ? ($value === null ? $actual === null : $actual !== null && abs((float) $actual - (float) $value) < 1e-9)
                    : ($value === null ? $actual === null : (string) $actual === (string) $value);

                if (! $same) {
                    throw new RuntimeException("Stored {$column} of {$substance['code']} differs from the export. Nothing was changed.");
                }
            }

            if ($row['canonical_id'] !== null || $row['deleted_at'] !== null) {
                throw new RuntimeException("Stored row of {$substance['code']} is not a plain active row. Nothing was changed.");
            }
        }
    }

    /**
     * @param  array<string, int>  $ids
     * @param  list<array{source_id: int, substance_id: int, created_at: string, updated_at: string}>  $links
     * @return array{path: string, sha256: string}
     */
    private function writeRecord(string $startedAt, int $maxIdBefore, string $fingerprintBefore, array $ids, array $links): array
    {
        $path = storage_path(self::RECORD_DIRECTORY.'/'.self::RECORD_PREFIX
            .str_replace(['-', ' ', ':'], ['', 'T', ''], $startedAt).'Z.json');
        $handle = @fopen($path, 'x');

        if ($handle === false) {
            throw new RuntimeException("Cannot create the record file {$path}. Nothing was changed.");
        }

        $record = [
            'seeder' => self::class,
            'environment' => app()->environment(),
            'started_at_utc' => $startedAt,
            'export_sha256' => self::EXPORT_SHA256,
            'max_id_before' => $maxIdBefore,
            'id_code_fingerprint_before' => $fingerprintBefore,
            'substances' => array_map(
                fn (string $code, int $id): array => ['id' => $id, 'code' => $code],
                array_keys($ids),
                array_values($ids),
            ),
            'source_links' => array_map(
                fn (array $link): array => ['source_id' => $link['source_id'], 'substance_id' => $link['substance_id']],
                $links,
            ),
        ];

        fwrite($handle, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
        fclose($handle);

        return ['path' => $path, 'sha256' => (string) hash_file('sha256', $path)];
    }

    /**
     * Codes are compared trimmed, because some end in a carriage return (issue 3.1).
     *
     * @param  list<string|float|null>  $codes
     */
    private function assertNoneExists(array $codes): void
    {
        $existing = DB::table('susdat_substances')
            ->whereIn(DB::raw("trim(both E'\\r\\n ' from code)"), $codes)
            ->pluck('code')
            ->all();

        if ($existing !== []) {
            throw new RuntimeException('Already in susdat_substances: '.implode(', ', $existing).'. Nothing was changed.');
        }
    }

    private function assertProductionState(): void
    {
        if (! self::enforcesProductionCounts()) {
            return;
        }

        self::check('Substances before', DB::table('susdat_substances')->count(), self::EXPECTED_ROWS_BEFORE);
        self::check('Highest id before', (int) DB::table('susdat_substances')->max('id'), self::EXPECTED_MAX_ID_BEFORE);
        self::check('id/code fingerprint before', self::idCodeFingerprint(), self::EXPECTED_ID_CODE_FINGERPRINT);
        self::check('Source links before', DB::table('susdat_source_substance')->count(), self::EXPECTED_LINKS_BEFORE);
    }

    /**
     * @param  list<array{sus_id: string, ss_id: string}>  $links
     * @return array<string, int>
     */
    private function sourceIdsFor(array $links): array
    {
        $ids = [];

        foreach (array_unique(array_column($links, 'ss_id')) as $code) {
            $sources = DB::table('suspect_list_exchange_sources')->where('code', $code)->pluck('id');
            self::check("Sources with code {$code}", $sources->count(), 1);
            $ids[$code] = (int) $sources->first();
        }

        return $ids;
    }

    private static function text(?string $value): ?string
    {
        return $value === null || $value === '' ? null : $value;
    }

    private static function number(?string $value, string $code): ?float
    {
        $value = self::text($value);

        if ($value !== null && ! is_numeric($value)) {
            throw new RuntimeException("Legacy mass '{$value}' of {$code} is not a number. Nothing was changed.");
        }

        return $value === null ? null : (float) $value;
    }

    private static function check(string $description, int|string $actual, int|string $expected): void
    {
        if ($actual !== $expected) {
            throw new RuntimeException("{$description}: expected {$expected}, found {$actual}. Nothing was changed.");
        }
    }

    private function say(string $line): void
    {
        $this->command?->info($line);
    }

    /**
     * @param  list<string>  $lines
     */
    private function report(array $lines): void
    {
        foreach ($lines as $line) {
            $this->say($line);
            Log::info($line);
        }
    }
}
