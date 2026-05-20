<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use Throwable;

/**
 * Phase 1b — import `empodat_data_sources` rows for the delta.
 *
 * Source : legacy `dct_data_source` rows referenced by `dct_analysis` delta.
 * Target : PG `empodat_data_sources` (id = legacy id_data, same id space).
 *
 * Column mapping (verified against PG schema + existing rows):
 *   id_data              -> id
 *   dts_id               -> type_data_source_id
 *   dts_other            -> type_data_source_other
 *   dtm_id               -> type_monitoring_id
 *   dtm_other            -> type_monitoring_other
 *   dda_id               -> data_accessibility_id
 *   dda_other            -> data_accessibility_other
 *   title_project        -> project_title
 *   organisation (int)   -> organisation_id (resolved via sig-match, see below)
 *   laboratory_name+city+country     -> laboratory1_id (lookup with NULL fallback)
 *   laboratory_name_2+city_2+country_2 -> laboratory2_id (lookup with NULL fallback)
 *   author               -> author
 *   email                -> email
 *   literature1          -> reference1
 *   literature2          -> reference2
 *   data_source_link     -> data_source_url        (new col, see migration)
 *   bibliographic_source -> bibliographic_source   (new col)
 *   identifier           -> identifier_url         (new col)
 *   orcid_no             -> orcid_no               (new col)
 *   data_provider        -> data_provider          (new col)
 *   data_source_remark   -> data_source_remark     (new col)
 *
 * Dropped (no PG home): ds_id, ds_other, contact_person_family_name,
 * contact_person_first_name (covered by `author`), reference, website,
 * laboratory_id.
 *
 * Organisation resolution (legacy `data_organisation` id != PG id):
 *   1. Look up by `legacy_data_organisation_id` (new col) — covers re-runs.
 *   2. Else sig-match (name, acronym, city). 1 PG match -> use existing.
 *   3. Else (0 or N>1 matches) -> INSERT new row, set legacy_data_organisation_id.
 *
 * Laboratory resolution:
 *   Lookup by (lower(name), lower(city), country_id). Multi-match or no match -> NULL.
 *
 * Idempotent: ON CONFLICT (id) DO NOTHING on empodat_data_sources; unique index
 * on list_data_source_organisations.legacy_data_organisation_id prevents
 * duplicate org inserts on re-runs.
 */
class ImportDataSourcesStep extends Step
{
    private const TABLE = 'empodat_data_sources';

    private const ORG_TABLE = 'list_data_source_organisations';

    public function name(): string
    {
        return 'Import data sources (dct_data_source -> empodat_data_sources)';
    }

    public function run(): void
    {
        $start = microtime(true);

        try {
            $referencedIds = $this->legacy()
                ->table('dct_analysis')
                ->where('id', '>=', $this->sinceId)
                ->whereNotNull('id_data')
                ->distinct()
                ->pluck('id_data')
                ->map(fn ($v) => (int) $v)
                ->all();

            if ($referencedIds === []) {
                $this->note('No data sources referenced in delta.');
                $this->logSmallStep(self::TABLE, [], (int) ((microtime(true) - $start) * 1000));

                return;
            }

            $legacyRows = $this->legacy()
                ->table('dct_data_source')
                ->whereIn('id_data', $referencedIds)
                ->orderBy('id_data')
                ->get();

            $resolvedIds = $legacyRows->pluck('id_data')->map(fn ($v) => (int) $v)->all();
            $orphans = array_values(array_diff($referencedIds, $resolvedIds));
            if ($orphans !== []) {
                $this->note('Orphan id_data values (referenced but missing in dct_data_source): '
                    .implode(', ', $orphans).' — main rows will get data_source_id = NULL in Phase 5');
            }

            $alreadyInPg = $this->pg()
                ->table(self::TABLE)
                ->whereIn('id', $resolvedIds)
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $toInsert = $legacyRows->reject(fn ($r) => in_array((int) $r->id_data, $alreadyInPg, true));

            if ($toInsert->isEmpty()) {
                $this->note('All '.count($resolvedIds).' referenced data sources already in PG.');
                $this->logSmallStep(self::TABLE, [], (int) ((microtime(true) - $start) * 1000));

                return;
            }

            $legacyOrgIds = $toInsert
                ->pluck('organisation')
                ->filter(fn ($v) => $v !== null)
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            $orgMap = $this->resolveOrganisations($legacyOrgIds, $start);

            $labCache = $this->buildLabCache();

            $insertable = $toInsert->map(function ($r) use ($orgMap, $labCache) {
                $lab1 = $this->resolveLab($labCache, $r->laboratory_name, $r->laboratory_city, $r->laboratory_country);
                $lab2 = $this->resolveLab($labCache, $r->laboratory_name_2, $r->laboratory_city_2, $r->laboratory_country_2);
                $orgId = $r->organisation !== null ? ($orgMap[(int) $r->organisation] ?? null) : null;

                return [
                    'id' => (int) $r->id_data,
                    'type_data_source_id' => $r->dts_id !== null ? (int) $r->dts_id : null,
                    'type_data_source_other' => $this->blankToNull($r->dts_other),
                    'type_monitoring_id' => $r->dtm_id !== null ? (int) $r->dtm_id : null,
                    'type_monitoring_other' => $this->blankToNull($r->dtm_other),
                    'data_accessibility_id' => $r->dda_id !== null ? (int) $r->dda_id : null,
                    'data_accessibility_other' => $this->blankToNull($r->dda_other),
                    'project_title' => $this->blankToNull($r->title_project),
                    'organisation_id' => $orgId,
                    'laboratory1_id' => $lab1,
                    'laboratory2_id' => $lab2,
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
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            $insertedIds = $this->insertOnConflictDoNothing(self::TABLE, $insertable);

            $this->note(sprintf(
                'Candidates=%d  inserted=%d  skipped(already in PG)=%d',
                $toInsert->count(),
                count($insertedIds),
                $toInsert->count() - count($insertedIds),
            ));

            $this->logSmallStep(self::TABLE, $insertedIds, (int) ((microtime(true) - $start) * 1000));
        } catch (Throwable $e) {
            $this->logStepFailed(self::TABLE, $e);
            throw $e;
        }
    }

    /**
     * Resolve each legacy data_organisation.id to a PG list_data_source_organisations.id.
     * Inserts new PG rows when no single match exists; writes its own audit-log row.
     *
     * @param  array<int, int>  $legacyOrgIds
     * @return array<int, int> map legacy_id => pg_id
     */
    private function resolveOrganisations(array $legacyOrgIds, float $stepStart): array
    {
        if ($legacyOrgIds === []) {
            return [];
        }

        $alreadyMapped = $this->pg()
            ->table(self::ORG_TABLE)
            ->whereIn('legacy_data_organisation_id', $legacyOrgIds)
            ->pluck('id', 'legacy_data_organisation_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $unmappedLegacyIds = array_values(array_diff($legacyOrgIds, array_keys($alreadyMapped)));
        $map = $alreadyMapped;

        if ($unmappedLegacyIds === []) {
            return $map;
        }

        $legacyOrgs = $this->legacy()
            ->table('data_organisation')
            ->whereIn('id', $unmappedLegacyIds)
            ->get(['id', 'organisation_name_en', 'acronym', 'city', 'country']);

        $pgOrgs = $this->pg()
            ->table(self::ORG_TABLE)
            ->whereNull('legacy_data_organisation_id')
            ->get(['id', 'name', 'acronym', 'city']);

        $sigToPgIds = [];
        foreach ($pgOrgs as $po) {
            $sig = $this->orgSignature($po->name, $po->acronym, $po->city);
            $sigToPgIds[$sig][] = (int) $po->id;
        }

        $countryIdByCode = $this->pg()
            ->table('list_countries')
            ->pluck('id', 'code')
            ->map(fn ($v) => (int) $v)
            ->all();

        $insertedOrgIds = [];

        foreach ($legacyOrgs as $lo) {
            $sig = $this->orgSignature($lo->organisation_name_en, $lo->acronym, $lo->city);
            $matches = $sigToPgIds[$sig] ?? [];

            if (count($matches) === 1) {
                $pgId = $matches[0];
                $this->pg()->table(self::ORG_TABLE)
                    ->where('id', $pgId)
                    ->whereNull('legacy_data_organisation_id')
                    ->update(['legacy_data_organisation_id' => (int) $lo->id, 'updated_at' => now()]);
                $map[(int) $lo->id] = $pgId;

                continue;
            }

            $newId = (int) $this->pg()->table(self::ORG_TABLE)->insertGetId([
                'name' => $lo->organisation_name_en !== '' ? $lo->organisation_name_en : null,
                'acronym' => $lo->acronym !== '' ? $lo->acronym : null,
                'city' => $lo->city !== '' ? $lo->city : null,
                'country_id' => $lo->country !== null && $lo->country !== '' ? ($countryIdByCode[$lo->country] ?? null) : null,
                'legacy_data_organisation_id' => (int) $lo->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $map[(int) $lo->id] = $newId;
            $insertedOrgIds[] = $newId;
        }

        if ($insertedOrgIds !== []) {
            $this->note('Inserted '.count($insertedOrgIds).' new rows into list_data_source_organisations (ids: '.implode(', ', $insertedOrgIds).')');
            $this->pg()->table('empodat_legacy_import_log')->insert([
                'run_id' => $this->runId,
                'step_name' => $this->name(),
                'table_name' => self::ORG_TABLE,
                'inserted_ids' => json_encode($insertedOrgIds),
                'inserted_count' => count($insertedOrgIds),
                'duration_ms' => (int) ((microtime(true) - $stepStart) * 1000),
                'status' => 'completed',
                'started_at' => now(),
                'finished_at' => now(),
            ]);
        }

        return $map;
    }

    /**
     * @return array<string, int> signature(name|city|country_id) => lab_id
     */
    private function buildLabCache(): array
    {
        $rows = $this->pg()
            ->table('list_data_source_laboratories')
            ->get(['id', 'name', 'city', 'country_id']);

        $bySig = [];
        $ambiguous = [];

        foreach ($rows as $r) {
            $sig = $this->labSignature($r->name, $r->city, $r->country_id);
            if (isset($bySig[$sig])) {
                $ambiguous[$sig] = true;
            } else {
                $bySig[$sig] = (int) $r->id;
            }
        }

        foreach (array_keys($ambiguous) as $sig) {
            unset($bySig[$sig]);
        }

        return $bySig;
    }

    /**
     * @param  array<string, int>  $cache
     */
    private function resolveLab(array $cache, ?string $name, ?string $city, ?string $countryCode): ?int
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        static $countryIdByCode = null;
        if ($countryIdByCode === null) {
            $countryIdByCode = $this->pg()
                ->table('list_countries')
                ->pluck('id', 'code')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        $countryId = ($countryCode !== null && $countryCode !== '')
            ? ($countryIdByCode[$countryCode] ?? null)
            : null;

        $sig = $this->labSignature($name, $city, $countryId);

        return $cache[$sig] ?? null;
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

    private function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
