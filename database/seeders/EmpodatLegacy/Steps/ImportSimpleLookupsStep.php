<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use Throwable;

/**
 * Phase 6a — bulk-load the legacy `data_*` lookup tables whose PG `list_*`
 * counterparts are currently EMPTY and the legacy content is the authoritative
 * source. All targets share the same (id, name, created_at, updated_at) shape;
 * ids are preserved 1:1 from legacy so that any existing FK column already
 * carrying a legacy id resolves correctly without remapping.
 *
 * GREEN-tier imports only — by design this step never overwrites or de-dupes
 * against pre-existing rows. PG rows that already match a legacy id are
 * skipped via ON CONFLICT (matches the safety contract of every other step in
 * this seeder).
 *
 * Top-ups for partially-populated PG tables (data_preparation_method,
 * data_standardised_method, etc.) and brand-new PG tables for biota taxonomy
 * (data_kingdom / data_phylum / data_class / ...) live in Phase 6b and 6c.
 */
class ImportSimpleLookupsStep extends Step
{
    private const LOG_TABLE = 'list_simple_lookups_6a';

    /**
     * One mapping per (legacy table, legacy id col, legacy name col, PG list_* target).
     * Order doesn't matter — each pair is independent.
     *
     * @var list<array{0:string,1:string,2:string,3:string}>
     */
    private const MAPPINGS = [
        ['data_grain', 'dgra_id', 'dgra_name', 'list_grain_size_distributions'],
        ['data_soil_texture', 'dsot_id', 'dsot_name', 'list_soil_textures'],
        ['data_loc', 'dloc_id', 'dloc_name', 'list_locations'],
        ['data_depth', 'de_id', 'de_name', 'list_depths'],
        ['data_effluent_influent', 'effluent_influent_id', 'effluent_influent_name', 'list_effluent_influents'],
        ['data_pressures', 'dpr_id', 'dpr_name', 'list_proxy_pressures'],
        ['data_air_filtration', 'dairf_id', 'dairf_name', 'list_air_filtration_systems'],
        ['data_species_group', 'dsgr_id', 'dsgr_name', 'list_species_groups'],
        ['data_fraction', 'df_id', 'df_name', 'list_fractions'],
    ];

    public function name(): string
    {
        return 'Import simple lookup tables (legacy data_* -> PG list_* where PG is empty)';
    }

    public function run(): void
    {
        $start = microtime(true);

        if ($this->previouslyCompleted(self::LOG_TABLE)) {
            $this->note('Already completed for this since_id; skipping (run rollback to re-run).');
            $this->logBulkStep(self::LOG_TABLE, 0, null, null, (int) ((microtime(true) - $start) * 1000));

            return;
        }

        try {
            $totalInserted = 0;
            $now = now();

            foreach (self::MAPPINGS as [$srcTable, $idCol, $nameCol, $dstTable]) {
                $rows = $this->legacy()
                    ->table($srcTable)
                    ->orderBy($idCol)
                    ->get([$idCol, $nameCol]);

                if ($rows->isEmpty()) {
                    $this->note(sprintf('   %s -> %s: legacy table empty, nothing to import', $srcTable, $dstTable));

                    continue;
                }

                $payload = $rows->map(fn ($r) => [
                    'id' => (int) $r->{$idCol},
                    'name' => trim((string) $r->{$nameCol}),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                $inserted = $this->insertOnConflictDoNothing($dstTable, $payload);
                $totalInserted += count($inserted);

                $this->note(sprintf(
                    '   %s -> %s: legacy=%d  inserted=%d  skipped(already in PG)=%d',
                    $srcTable,
                    $dstTable,
                    count($payload),
                    count($inserted),
                    count($payload) - count($inserted),
                ));
            }

            $this->note(sprintf('Total rows inserted across %d lookup tables: %d', count(self::MAPPINGS), $totalInserted));

            $this->logBulkStep(self::LOG_TABLE, $totalInserted, null, null, (int) ((microtime(true) - $start) * 1000));
        } catch (Throwable $e) {
            $this->logStepFailed(self::LOG_TABLE, $e);
            throw $e;
        }
    }
}
