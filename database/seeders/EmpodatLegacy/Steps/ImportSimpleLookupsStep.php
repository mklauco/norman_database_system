<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use Throwable;

/**
 * Phase 6 — bulk-load legacy `data_*` lookup tables into PG `list_*` targets.
 *
 * All targets share the canonical `(id, name, created_at, updated_at)` shape;
 * ids are preserved 1:1 from legacy so any existing FK column already
 * carrying a legacy id resolves without remapping.
 *
 * Inserts go through `insertOnConflictDoNothing()` — never overwrites PG rows
 * that already exist (matches the safety contract of every other step in this
 * seeder). The three sub-phases are bundled into a single step because they
 * use identical mechanics; the LOG_TABLE token below was bumped from
 * `_6a` to `_6abc` so the previouslyCompleted guard re-runs cleanly to pick
 * up Phase 6b (top-ups) and Phase 6c (new tables) on top of an already-done
 * Phase 6a.
 *
 *   - 6a (GREEN, DONE under previous LOG_TABLE token): empty PG, authoritative
 *     legacy content. On re-run all 9 are no-ops via ON CONFLICT.
 *   - 6b (YELLOW): PG list_* already partially populated. Inserts only the
 *     legacy ids that don't yet exist in PG.
 *   - 6c (NEW): legacy lookups with no v2 counterpart — tables created by
 *     migration `2026_05_20_180134_create_legacy_lookup_list_tables.php`.
 *
 * Intentionally NOT included: `data_order`, `data_family`, `data_habitat_type`
 * — no legacy dumps available in `/Users/mk/Documents/NORMAN/alfac/`. The
 * corresponding `empodat_matrix_biota.dord_id` / `dfam_id` / `dht_id` columns
 * stay as raw smallint integers without an enforced FK.
 */
class ImportSimpleLookupsStep extends Step
{
    private const LOG_TABLE = 'list_lookups_6abc';

    /**
     * One mapping per (legacy table, legacy id col, legacy name col, PG list_* target).
     * Order doesn't matter — each pair is independent.
     *
     * @var list<array{0:string,1:string,2:string,3:string}>
     */
    private const MAPPINGS = [
        // Phase 6a — empty PG targets (DONE under previous run; re-run is a no-op).
        ['data_grain', 'dgra_id', 'dgra_name', 'list_grain_size_distributions'],
        ['data_soil_texture', 'dsot_id', 'dsot_name', 'list_soil_textures'],
        ['data_loc', 'dloc_id', 'dloc_name', 'list_locations'],
        ['data_depth', 'de_id', 'de_name', 'list_depths'],
        ['data_effluent_influent', 'effluent_influent_id', 'effluent_influent_name', 'list_effluent_influents'],
        ['data_pressures', 'dpr_id', 'dpr_name', 'list_proxy_pressures'],
        ['data_air_filtration', 'dairf_id', 'dairf_name', 'list_air_filtration_systems'],
        ['data_species_group', 'dsgr_id', 'dsgr_name', 'list_species_groups'],
        ['data_fraction', 'df_id', 'df_name', 'list_fractions'],

        // Phase 6b — top-up PG list_* tables already partially populated.
        ['data_preparation_method', 'dpm_id', 'dpm_name', 'list_sample_preparation_methods'],
        ['data_standardised_method', 'dsm_id', 'dsm_name', 'list_standardised_methods'],
        ['data_sampling_method', 'dsa_id', 'dsa_name', 'list_sampling_methods'],
        ['data_sampling_technique', 'dst_id', 'dst_name', 'list_sampling_techniques'],
        ['data_tissue_element', 'dtiel_id', 'dtiel_name', 'list_tissues'],
        ['data_summary_performance', 'dsp_id', 'dsp_name', 'list_summary_performances'],
        ['data_coverage_factor', 'dcf_id', 'dcf_name', 'list_coverage_factors'],
        ['data_precision_coordinates', 'dpc_id', 'dpc_name', 'list_coordinate_precisions'],
        ['data_ind_concentration', 'dic_id', 'dic_name', 'list_concentration_indicators'],

        // Phase 6c — new PG tables created by migration 2026_05_20_180134_*.
        ['data_kingdom', 'dki_id', 'dki_name', 'list_kingdoms'],
        ['data_phylum', 'dph_id', 'dph_name', 'list_phyla'],
        ['data_class', 'dcla_id', 'dcla_name', 'list_classes'],
        ['data_individual_or_pooled', 'diop_id', 'diop_name', 'list_individual_or_pooled'],
        ['data_category', 'dcat_id', 'dcat_name', 'list_categories'],
        ['data_biota_species', 'dspc_id', 'dspc_name', 'list_biota_species'],
        ['data_measurement', 'dmeas_id', 'dmeas_name', 'list_measurements'],
        ['data_concentration_data', 'dcod_id', 'dcod_name', 'list_concentration_data'],
        ['data_prevalent_land_use', 'dplu_id', 'dplu_name', 'list_prevalent_land_uses'],
        ['data_particle_size', 'dps_id', 'dps_name', 'list_particle_sizes'],
        ['data_conc_normal_particle_size', 'dcnps_id', 'dcnps_name', 'list_conc_normal_particle_sizes'],
    ];

    public function name(): string
    {
        return 'Import lookup tables (legacy data_* -> PG list_*; 6a empty fills + 6b top-ups + 6c new tables)';
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
