<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

/**
 * Phase 1c — import `empodat_analytical_methods` rows for the delta.
 *
 * Source : legacy `dct_analytical_method` where `id_method > $sinceId`
 *          AND not already in PG `empodat_analytical_methods`.
 * Target : PG `empodat_analytical_methods` (id = legacy id_method).
 *
 * Column rename pattern: legacy am_* -> PG without prefix (am_lod -> lod,
 * am_loq -> loq, am_remark -> remark, ...); d**_id -> longer PG names
 * (dcf_id -> coverage_factor_id, dpm_id -> sample_preparation_method_id, ...).
 * Type drift: am_uncertainty_loq varchar -> PG numeric (safe-cast, else NULL);
 * am_foa varchar(10) -> PG double precision (safe-cast, else NULL).
 *
 * Orphan handling: legacy `dct_analysis` rows referencing an id_method that
 * does NOT exist in `dct_analytical_method` are accepted; the corresponding
 * empodat_main rows get method_id = NULL in ScrubOrphansStep.
 */
class ImportAnalyticalMethodsStep extends Step
{
    public function name(): string
    {
        return 'Import analytical methods (dct_analytical_method -> empodat_analytical_methods)';
    }

    public function run(): void
    {
        // TODO: stream rows from legacy, transform columns, INSERT into PG
        // empodat_analytical_methods with id = id_method.
    }
}
