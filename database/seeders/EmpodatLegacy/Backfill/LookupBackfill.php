<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use RuntimeException;
use Throwable;

/**
 * Analytical methods, data sources (with their organisations and
 * laboratories) and list_matrices referenced by PG empodat_main (existing rows,
 * inside the id window when one is set) or by the legacy rows about to be
 * copied, that PG lacks and legacy has. Insert-only.
 *
 * Differences from the delta importer, on purpose:
 *   - an existing PG organisation matched by (name, acronym, city) is REUSED
 *     but never UPDATEd (the delta importer stamps legacy_data_organisation_id
 *     onto it); only organisations that match nothing are inserted;
 *   - a laboratory that matches no PG laboratory by (name, city, country) is
 *     INSERTED instead of being set to NULL, so no laboratory of a copied data
 *     source is lost; where several PG laboratories share a signature the
 *     lowest id is reused;
 *   - no cutoff: the candidate set is "referenced and missing", not "id > X".
 */
final class LookupBackfill
{
    private const STEP_METHODS = 'Backfill analytical methods';

    private const STEP_SOURCES = 'Backfill data sources';

    private const STEP_ORGS = 'Backfill data source organisations';

    private const STEP_LABS = 'Backfill data source laboratories';

    private const STEP_MATRICES = 'Backfill list_matrices';

    /** Columns written to empodat_analytical_methods. */
    public const METHOD_COLUMNS = [
        'id', 'lod', 'loq', 'uncertainty_loq', 'coverage_factor_id', 'sample_preparation_method_id',
        'sample_preparation_method_other', 'analytical_method_id', 'analytical_method_other',
        'standardised_method_id', 'standardised_method_other', 'standardised_method_number',
        'validated_method_id', 'corrected_recovery_id', 'field_blank_id', 'iso_id', 'given_analyte_id',
        'legacy_given_analyte_id', 'laboratory_participate_id', 'legacy_laboratory_participate_id',
        'summary_performance_id', 'control_charts_id', 'internal_standards_id', 'authority_id', 'rating',
        'remark', 'foa', 'created_at', 'updated_at',
    ];

    /** Columns written to empodat_data_sources. */
    public const SOURCE_COLUMNS = [
        'id', 'type_data_source_id', 'type_data_source_other', 'type_monitoring_id', 'type_monitoring_other',
        'data_accessibility_id', 'data_accessibility_other', 'project_title', 'organisation_id',
        'laboratory1_id', 'laboratory2_id', 'author', 'email', 'reference1', 'reference2', 'data_source_url',
        'bibliographic_source', 'identifier_url', 'orcid_no', 'data_provider', 'data_source_remark',
        'created_at', 'updated_at',
    ];

    /** Columns written to list_matrices. */
    public const MATRIX_COLUMNS = [
        'id', 'title', 'subtitle', 'type', 'name', 'dct_name', 'unit', 'empodat_matrix_link', 'created_at', 'updated_at',
    ];

    /** Columns written to list_data_source_organisations (id is generated). */
    public const ORG_COLUMNS = ['name', 'acronym', 'city', 'country_id', 'legacy_data_organisation_id', 'created_at', 'updated_at'];

    /** Columns written to list_data_source_laboratories (id is generated). */
    public const LAB_COLUMNS = ['name', 'city', 'country_id', 'created_at', 'updated_at'];

    /** @var array<string, int>|null laboratory signature => id (placeholders are negative) */
    private ?array $labCache = null;

    private int $labsAmbiguousInPg = 0;

    private int $labPlaceholder = 0;

    /** @var array{matched: int, new: int, references: int} */
    private array $labStats = ['matched' => 0, 'new' => 0, 'references' => 0];

    /** @var list<int> laboratory ids inserted by the current transaction */
    private array $newLabIds = [];

    /** @var array<string, int>|null */
    private ?array $countryIdByCode = null;

    public function __construct(private readonly BackfillContext $ctx) {}

    /**
     * Distinct non-NULL values of an empodat_main column: an index loose scan
     * over the whole table, or a plain DISTINCT over the PK range of the id
     * window when one is set.
     *
     * @param  'method_id'|'data_source_id'|'matrix_id'  $column
     * @return list<int>
     */
    public function distinctInPgMain(string $column): array
    {
        if (! in_array($column, ['method_id', 'data_source_id', 'matrix_id'], true)) {
            throw new RuntimeException('Unsupported loose-scan column '.$column);
        }

        $options = $this->ctx->options;
        if ($options->hasWindow()) {
            return array_map('intval', $this->ctx->pg->table('empodat_main')
                ->when($options->idFrom !== null, fn ($q) => $q->where('id', '>=', $options->idFrom))
                ->when($options->idTo !== null, fn ($q) => $q->where('id', '<=', $options->idTo))
                ->whereNotNull($column)
                ->distinct()
                ->pluck($column)
                ->all());
        }

        $rows = $this->ctx->pg->select(sprintf(
            'WITH RECURSIVE t AS ('
            .' SELECT min(%1$s) AS v FROM empodat_main'
            .' UNION ALL'
            .' SELECT (SELECT min(%1$s) FROM empodat_main WHERE %1$s > t.v) FROM t WHERE t.v IS NOT NULL'
            .') SELECT v FROM t WHERE v IS NOT NULL',
            $column,
        ));

        return array_map(static fn (object $r): int => (int) $r->v, $rows);
    }

    /**
     * @param  array{method: array<int, int>, dataSource: array<int, int>, matrix: array<int, int>}  $scan
     * @return array<string, array<string, mixed>>
     */
    public function plan(array $scan): array
    {
        $out = [];

        $methodIds = $this->candidates($this->distinctInPgMain('method_id'), $scan['method']);
        $out['methods'] = $this->classify('empodat_analytical_methods', 'dct_analytical_method', 'id_method', $methodIds, $scan['method']);

        $sourceIds = $this->candidates($this->distinctInPgMain('data_source_id'), $scan['dataSource']);
        $out['dataSources'] = $this->classify('empodat_data_sources', 'dct_data_source', 'id_data', $sourceIds, $scan['dataSource']);

        $matrixIds = $this->candidates($this->distinctInPgMain('matrix_id'), $scan['matrix']);
        $out['matrices'] = $this->classify('list_matrices', 'data_matrice', 'matrice_id', $matrixIds, $scan['matrix']);

        $legacySources = $this->legacySourceRows($out['dataSources']['toInsert']);
        $orgIds = $this->legacyOrganisationIds($legacySources);
        $out['organisations'] = $this->resolveOrganisations($orgIds, false)['stats'];

        foreach ($legacySources as $r) {
            $this->resolveLab($r->laboratory_name, $r->laboratory_city, $r->laboratory_country, false);
            $this->resolveLab($r->laboratory_name_2, $r->laboratory_city_2, $r->laboratory_country_2, false);
        }
        $out['laboratories'] = $this->labStats + ['ambiguousInPg' => $this->labsAmbiguousInPg];

        return $out;
    }

    /**
     * @param  array{method: array<int, int>, dataSource: array<int, int>, matrix: array<int, int>}  $scan
     */
    public function execute(array $scan): void
    {
        $audit = $this->ctx->audit ?? throw new RuntimeException('LookupBackfill::execute() without an audit run.');

        $this->guarded(self::STEP_METHODS, 'empodat_analytical_methods', function () use ($scan, $audit): void {
            $ids = $this->classify(
                'empodat_analytical_methods',
                'dct_analytical_method',
                'id_method',
                $this->candidates($this->distinctInPgMain('method_id'), $scan['method']),
                $scan['method'],
            )['toInsert'];
            $this->insertMethods($ids, $audit);
        });

        $this->guarded(self::STEP_SOURCES, 'empodat_data_sources', function () use ($scan, $audit): void {
            $ids = $this->classify(
                'empodat_data_sources',
                'dct_data_source',
                'id_data',
                $this->candidates($this->distinctInPgMain('data_source_id'), $scan['dataSource']),
                $scan['dataSource'],
            )['toInsert'];
            $this->insertDataSources($ids, $audit);
        });

        $this->guarded(self::STEP_MATRICES, 'list_matrices', function () use ($scan, $audit): void {
            $ids = $this->classify(
                'list_matrices',
                'data_matrice',
                'matrice_id',
                $this->candidates($this->distinctInPgMain('matrix_id'), $scan['matrix']),
                $scan['matrix'],
            )['toInsert'];
            $this->insertMatrices($ids, $audit);
        });
    }

    /**
     * @param  list<int>  $fromPg
     * @param  array<int, int>  $fromLegacy  id => rows
     * @return list<int>
     */
    private function candidates(array $fromPg, array $fromLegacy): array
    {
        $all = array_fill_keys($fromPg, true) + array_fill_keys(array_keys($fromLegacy), true);
        $ids = array_keys($all);
        sort($ids);

        return $ids;
    }

    /**
     * @param  list<int>  $candidateIds
     * @param  array<int, int>  $legacyRowCounts  rows of the copied delta referencing each id
     * @return array{candidates: int, missingInPg: int, toInsert: list<int>, unresolvable: list<int>, unresolvableRows: int}
     */
    private function classify(string $pgTable, string $legacyTable, string $legacyIdColumn, array $candidateIds, array $legacyRowCounts): array
    {
        $present = array_flip(array_map('intval', $this->ctx->pg->table($pgTable)->pluck('id')->all()));
        $missing = array_values(array_filter($candidateIds, static fn (int $id): bool => ! isset($present[$id])));

        $inLegacy = [];
        foreach (array_chunk($missing, 5000) as $chunk) {
            foreach ($this->ctx->legacy->table($legacyTable)->whereIn($legacyIdColumn, $chunk)->pluck($legacyIdColumn) as $id) {
                $inLegacy[(int) $id] = true;
            }
            $this->ctx->pauseLegacy();
        }

        $toInsert = array_values(array_filter($missing, static fn (int $id): bool => isset($inLegacy[$id])));
        $unresolvable = array_values(array_filter($missing, static fn (int $id): bool => ! isset($inLegacy[$id])));
        $unresolvableRows = 0;
        foreach ($unresolvable as $id) {
            $unresolvableRows += $legacyRowCounts[$id] ?? 0;
        }

        return [
            'candidates' => count($candidateIds),
            'missingInPg' => count($missing),
            'toInsert' => $toInsert,
            'unresolvable' => $unresolvable,
            'unresolvableRows' => $unresolvableRows,
        ];
    }

    /**
     * @param  list<int>  $ids
     */
    private function insertMethods(array $ids, Audit $audit): void
    {
        $started = microtime(true);
        $now = now()->toDateTimeString();
        $rows = [];

        foreach (array_chunk($ids, 5000) as $chunk) {
            foreach ($this->ctx->legacy->table('dct_analytical_method')->whereIn('id_method', $chunk)->orderBy('id_method')->get() as $r) {
                $rows[] = $this->methodRow($r, $now);
            }
            $this->ctx->pauseLegacy();
        }

        $inserted = $this->ctx->pg->transaction(function () use ($rows, $audit, $started): array {
            $insertedIds = BoundInsert::ignoreConflicts($this->ctx->pg, 'empodat_analytical_methods', $rows);
            $audit->chunk(self::STEP_METHODS, 'empodat_analytical_methods', $insertedIds, (int) ((microtime(true) - $started) * 1000));

            return $insertedIds;
        });

        $this->ctx->emit(sprintf('  analytical methods: candidates to insert %d, inserted %d', count($ids), count($inserted)));
    }

    /**
     * @param  list<int>  $ids
     */
    private function insertDataSources(array $ids, Audit $audit): void
    {
        $started = microtime(true);
        $legacyRows = $this->legacySourceRows($ids);

        $inserted = $this->ctx->pg->transaction(function () use ($legacyRows, $audit, $started): array {
            $orgMap = $this->resolveOrganisations($this->legacyOrganisationIds($legacyRows), true)['map'];
            $now = now()->toDateTimeString();
            $this->newLabIds = [];
            $rows = [];
            foreach ($legacyRows as $r) {
                $rows[] = $this->dataSourceRow($r, $orgMap, $now, true);
            }
            if ($this->newLabIds !== []) {
                $audit->chunk(self::STEP_LABS, 'list_data_source_laboratories', $this->newLabIds, 0);
            }

            $insertedIds = BoundInsert::ignoreConflicts($this->ctx->pg, 'empodat_data_sources', $rows);
            $audit->chunk(self::STEP_SOURCES, 'empodat_data_sources', $insertedIds, (int) ((microtime(true) - $started) * 1000));

            return $insertedIds;
        });

        $this->ctx->emit(sprintf(
            '  data sources: candidates to insert %d, inserted %d; laboratories reused %d, created %d',
            count($ids),
            count($inserted),
            $this->labStats['matched'],
            $this->labStats['new'],
        ));
    }

    /**
     * Read-only twin of insertDataSources(): the empodat_data_sources rows the
     * backfill mapping builds for the given legacy ids, without writing anything.
     * With $failOnNew it throws when a row would need a new organisation or
     * laboratory; without it such an organisation or laboratory stays null.
     *
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    public function dataSourceRowsForIds(array $ids, bool $failOnNew = true): array
    {
        $legacyRows = $this->legacySourceRows($ids);
        $orgMap = $this->resolveOrganisations($this->legacyOrganisationIds($legacyRows), false)['map'];
        $now = now()->toDateTimeString();

        $rows = [];
        foreach ($legacyRows as $r) {
            $row = $this->dataSourceRow($r, $orgMap, $now, false);
            $id = (int) $r->id_data;

            if ($r->organisation !== null && ($row['organisation_id'] ?? 0) < 1) {
                if ($failOnNew) {
                    throw new RuntimeException('Data source '.$id.' needs a new organisation (legacy organisation '.$r->organisation.'); not created by this seeder.');
                }
                $row['organisation_id'] = null;
            }
            if ($failOnNew) {
                foreach (['laboratory1_id' => $r->laboratory_name, 'laboratory2_id' => $r->laboratory_name_2] as $column => $name) {
                    if ($this->blankToNull($name) !== null && $row[$column] === null) {
                        throw new RuntimeException('Data source '.$id.' needs a new laboratory ('.$column.', "'.trim((string) $name).'"); not created by this seeder.');
                    }
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Read-only twin of insertMethods(): the empodat_analytical_methods rows the
     * backfill mapping builds for the given legacy ids.
     *
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    public function methodRowsForIds(array $ids): array
    {
        $now = now()->toDateTimeString();
        $rows = [];

        foreach (array_chunk($ids, 2000) as $chunk) {
            foreach ($this->ctx->legacy->table('dct_analytical_method')->whereIn('id_method', $chunk)->orderBy('id_method')->get() as $r) {
                $rows[] = $this->methodRow($r, $now);
            }
        }

        return $rows;
    }

    /**
     * @param  list<int>  $ids
     */
    private function insertMatrices(array $ids, Audit $audit): void
    {
        $started = microtime(true);
        $now = now()->toDateTimeString();
        $rows = [];

        foreach ($this->ctx->legacy->table('data_matrice')->whereIn('matrice_id', $ids)->get() as $r) {
            $rows[] = [
                'id' => (int) $r->matrice_id,
                'title' => (string) $r->matrice_title1,
                'subtitle' => (string) $r->matrice_title2,
                'type' => (string) $r->matrice_title3,
                'name' => (string) $r->matrice_title,
                'dct_name' => (string) $r->matrice_dct_name,
                'unit' => (string) $r->matrice_unit,
                'empodat_matrix_link' => 'empodat_matrix_'.strtolower(str_replace(' ', '_', (string) $r->matrice_dct_name)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $inserted = $this->ctx->pg->transaction(function () use ($rows, $audit, $started): array {
            $insertedIds = BoundInsert::ignoreConflicts($this->ctx->pg, 'list_matrices', $rows);
            $audit->chunk(self::STEP_MATRICES, 'list_matrices', $insertedIds, (int) ((microtime(true) - $started) * 1000));

            return $insertedIds;
        });

        $this->ctx->emit(sprintf('  list_matrices: candidates to insert %d, inserted %d', count($ids), count($inserted)));
    }

    /**
     * @param  list<int>  $ids
     * @return list<object>
     */
    private function legacySourceRows(array $ids): array
    {
        $rows = [];
        foreach (array_chunk($ids, 2000) as $chunk) {
            foreach ($this->ctx->legacy->table('dct_data_source')->whereIn('id_data', $chunk)->orderBy('id_data')->get() as $r) {
                $rows[] = $r;
            }
        }

        return $rows;
    }

    /**
     * @param  list<object>  $legacySources
     * @return list<int>
     */
    private function legacyOrganisationIds(array $legacySources): array
    {
        $ids = [];
        foreach ($legacySources as $r) {
            if ($r->organisation !== null) {
                $ids[(int) $r->organisation] = true;
            }
        }

        return array_keys($ids);
    }

    /**
     * Legacy data_organisation id => PG list_data_source_organisations id.
     * Lookup order: already stamped with legacy_data_organisation_id; else a
     * UNIQUE (name, acronym, city) match (reused, not updated); else insert.
     * When $write is false new organisations get negative placeholder ids.
     *
     * @param  list<int>  $legacyOrgIds
     * @return array{map: array<int, int>, stats: array{requested: int, byLegacyId: int, bySignature: int, new: int}}
     */
    private function resolveOrganisations(array $legacyOrgIds, bool $write): array
    {
        $stats = ['requested' => count($legacyOrgIds), 'byLegacyId' => 0, 'bySignature' => 0, 'new' => 0];
        if ($legacyOrgIds === []) {
            return ['map' => [], 'stats' => $stats];
        }

        $pg = $this->ctx->pg;
        $map = $pg->table('list_data_source_organisations')
            ->whereIn('legacy_data_organisation_id', $legacyOrgIds)
            ->pluck('id', 'legacy_data_organisation_id')
            ->map(static fn ($v): int => (int) $v)
            ->all();
        $stats['byLegacyId'] = count($map);

        $unmapped = array_values(array_diff($legacyOrgIds, array_keys($map)));
        if ($unmapped === []) {
            return ['map' => $map, 'stats' => $stats];
        }

        $legacyOrgs = $this->ctx->legacy->table('data_organisation')
            ->whereIn('id', $unmapped)
            ->get(['id', 'organisation_name_en', 'acronym', 'city', 'country']);

        $signatureToIds = [];
        foreach ($pg->table('list_data_source_organisations')->whereNull('legacy_data_organisation_id')->get(['id', 'name', 'acronym', 'city']) as $po) {
            $signatureToIds[$this->orgSignature($po->name, $po->acronym, $po->city)][] = (int) $po->id;
        }

        $countries = $this->countries();
        $now = now()->toDateTimeString();
        $placeholder = 0;
        $newIds = [];

        foreach ($legacyOrgs as $lo) {
            $matches = $signatureToIds[$this->orgSignature($lo->organisation_name_en, $lo->acronym, $lo->city)] ?? [];
            if (count($matches) === 1) {
                $map[(int) $lo->id] = $matches[0];
                $stats['bySignature']++;

                continue;
            }

            $stats['new']++;
            if (! $write) {
                $map[(int) $lo->id] = --$placeholder;

                continue;
            }

            $created = BoundInsert::returningIds($pg, 'list_data_source_organisations', [[
                'name' => $lo->organisation_name_en !== '' ? $lo->organisation_name_en : null,
                'acronym' => $lo->acronym !== '' ? $lo->acronym : null,
                'city' => $lo->city !== '' ? $lo->city : null,
                'country_id' => ($lo->country !== null && $lo->country !== '') ? ($countries[$lo->country] ?? null) : null,
                'legacy_data_organisation_id' => (int) $lo->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]]);
            $map[(int) $lo->id] = $created[0];
            $newIds[] = $created[0];
        }

        if ($write && $newIds !== [] && $this->ctx->audit !== null) {
            $this->ctx->audit->chunk(self::STEP_ORGS, 'list_data_source_organisations', $newIds, 0);
        }

        return ['map' => $map, 'stats' => $stats];
    }

    /**
     * @param  array<int, int>  $orgMap
     * @return array<string, mixed>
     */
    private function dataSourceRow(object $r, array $orgMap, string $now, bool $write): array
    {
        return [
            'id' => (int) $r->id_data,
            'type_data_source_id' => $r->dts_id !== null ? (int) $r->dts_id : null,
            'type_data_source_other' => $this->blankToNull($r->dts_other),
            'type_monitoring_id' => $r->dtm_id !== null ? (int) $r->dtm_id : null,
            'type_monitoring_other' => $this->blankToNull($r->dtm_other),
            'data_accessibility_id' => $r->dda_id !== null ? (int) $r->dda_id : null,
            'data_accessibility_other' => $this->blankToNull($r->dda_other),
            'project_title' => $this->blankToNull($r->title_project),
            'organisation_id' => $r->organisation !== null ? ($orgMap[(int) $r->organisation] ?? null) : null,
            'laboratory1_id' => $this->resolveLab($r->laboratory_name, $r->laboratory_city, $r->laboratory_country, $write),
            'laboratory2_id' => $this->resolveLab($r->laboratory_name_2, $r->laboratory_city_2, $r->laboratory_country_2, $write),
            'author' => $this->blankToNull($r->author),
            'email' => $this->blankToNull($r->email),
            'reference1' => $this->blankToNull($r->literature1),
            'reference2' => $this->blankToNull($r->literature2),
            'data_source_url' => $this->blankToNull($r->data_source_link),
            'bibliographic_source' => $this->blankToNull($r->bibliographic_source),
            'identifier_url' => $this->blankToNull($r->identifier),
            'orcid_no' => $this->blankToNull($r->orcid_no),
            'data_provider' => $this->blankToNull($r->data_provider),
            'data_source_remark' => $this->blankToNull($r->data_source_remark),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function methodRow(object $r, string $now): array
    {
        [$givenAnalyteId, $legacyGivenAnalyteId] = $this->splitYesNoZero($r->am_given_analyte);
        [$labParticipateId, $legacyLabParticipateId] = $this->splitYesNoZero($r->am_laboratory_participate);

        return [
            'id' => (int) $r->id_method,
            'lod' => $r->am_lod !== null ? (float) $r->am_lod : null,
            'loq' => $r->am_loq !== null ? (float) $r->am_loq : null,
            'uncertainty_loq' => $this->castNumeric($r->am_uncertainty_loq),
            'coverage_factor_id' => $this->intOrNull($r->dcf_id),
            'sample_preparation_method_id' => $this->intOrNull($r->dpm_id),
            'sample_preparation_method_other' => $this->blankToNull($r->dpm_other),
            'analytical_method_id' => $this->intOrNull($r->dam_id),
            'analytical_method_other' => $this->blankToNull($r->dam_other),
            'standardised_method_id' => $this->intOrNull($r->dsm_id),
            'standardised_method_other' => $this->blankToNull($r->dsm_other),
            'standardised_method_number' => $this->blankToNull($r->am_number),
            'validated_method_id' => $this->intOrNull($r->dp_id),
            'corrected_recovery_id' => $this->intOrNull($r->am_corrected_recovery),
            'field_blank_id' => $this->intOrNull($r->am_field_blank),
            'iso_id' => $this->intOrNull($r->am_iso),
            'given_analyte_id' => $givenAnalyteId,
            'legacy_given_analyte_id' => $legacyGivenAnalyteId,
            'laboratory_participate_id' => $labParticipateId,
            'legacy_laboratory_participate_id' => $legacyLabParticipateId,
            'summary_performance_id' => $this->intOrNull($r->dsp_id),
            'control_charts_id' => $this->intOrNull($r->am_control_charts),
            'internal_standards_id' => $this->intOrNull($r->am_internal_standards),
            'authority_id' => $this->intOrNull($r->am_authority),
            'rating' => $this->intOrNull($r->rating),
            'remark' => $this->blankToNull($r->am_remark),
            'foa' => $this->castNumeric($r->am_foa),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * Legacy yes/no tinyint to (fk id, legacy id): 1-4 -> fk, 0 -> legacy column, NULL -> both NULL.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function splitYesNoZero(mixed $value): array
    {
        if ($value === null) {
            return [null, null];
        }
        $int = (int) $value;

        return $int === 0 ? [null, 0] : [$int, null];
    }

    private function intOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function castNumeric(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function orgSignature(?string $name, ?string $acronym, ?string $city): string
    {
        return implode('|', [
            strtolower(trim($name ?? '')),
            strtolower(trim($acronym ?? '')),
            strtolower(trim($city ?? '')),
        ]);
    }

    private function labSignature(?string $name, ?string $city, int|string|null $countryId): string
    {
        return implode('|', [
            strtolower(trim($name ?? '')),
            strtolower(trim($city ?? '')),
            (string) ($countryId ?? ''),
        ]);
    }

    /**
     * PG laboratory id for a legacy (name, city, country). A laboratory PG does
     * not have is inserted when $write is true, or counted with a negative
     * placeholder id in a dry run. Later references reuse it either way.
     */
    private function resolveLab(?string $name, ?string $city, ?string $countryCode, bool $write): ?int
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        $this->labStats['references']++;
        $countryCode = $countryCode !== null ? trim($countryCode) : null;
        $countryId = ($countryCode !== null && $countryCode !== '') ? ($this->countries()[$countryCode] ?? null) : null;
        $signature = $this->labSignature($name, $city, $countryId);
        $labs = $this->labs();

        if (isset($labs[$signature])) {
            if ($labs[$signature] > 0) {
                $this->labStats['matched']++;
            }

            return $labs[$signature] > 0 ? $labs[$signature] : null;
        }

        $this->labStats['new']++;
        if (! $write) {
            $this->labCache[$signature] = --$this->labPlaceholder;

            return null;
        }

        $now = now()->toDateTimeString();
        $id = (int) $this->ctx->pg->table('list_data_source_laboratories')->insertGetId([
            'name' => trim($name),
            'city' => ($city !== null && trim($city) !== '') ? trim($city) : null,
            'country_id' => $countryId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->labCache[$signature] = $id;
        $this->newLabIds[] = $id;

        return $id;
    }

    /**
     * Laboratory signature => id; where several PG laboratories share a
     * signature the lowest id wins (they are the same laboratory).
     *
     * @return array<string, int>
     */
    private function labs(): array
    {
        if ($this->labCache !== null) {
            return $this->labCache;
        }

        $bySignature = [];
        $ambiguous = [];
        foreach ($this->ctx->pg->table('list_data_source_laboratories')->orderBy('id')->get(['id', 'name', 'city', 'country_id']) as $r) {
            $signature = $this->labSignature($r->name, $r->city, $r->country_id);
            if (isset($bySignature[$signature])) {
                $ambiguous[$signature] = true;
            } else {
                $bySignature[$signature] = (int) $r->id;
            }
        }
        $this->labsAmbiguousInPg = count($ambiguous);

        return $this->labCache = $bySignature;
    }

    /**
     * @return array<string, int>
     */
    private function countries(): array
    {
        return $this->countryIdByCode ??= $this->ctx->pg->table('list_countries')
            ->pluck('id', 'code')
            ->map(static fn ($v): int => (int) $v)
            ->all();
    }

    private function guarded(string $step, string $table, callable $work): void
    {
        try {
            $work();
        } catch (Throwable $e) {
            $this->ctx->audit?->failedStep($step, $table, $e);
            throw $e;
        }
    }
}
