<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

/**
 * Phase 1b — import `empodat_data_sources` rows for the delta.
 *
 * Source : legacy `dct_data_source` where `id_data > $sinceId` AND
 *          not already in PG `empodat_data_sources`.
 * Target : PG `empodat_data_sources` (id = legacy id_data).
 *
 * Column rename pattern: legacy d**_id / d**_other -> PG type_*_id / type_*_other,
 * `title_project` -> `project_title`, `organisation` -> `organisation_id`,
 * `literature1/2` -> `reference1/2`. 13 legacy cols have no PG home — dropped.
 *
 * Lab resolution: legacy stores laboratory_name + _city + _country as text;
 * PG uses `laboratory1_id` / `laboratory2_id` FK to `list_data_source_laboratories`.
 * Look up by (name, city, country); unmatched -> NULL (or insert new lab row,
 * pending policy decision in LEGACY_MIGRATION_PLAN.md).
 */
class ImportDataSourcesStep extends Step
{
    public function name(): string
    {
        return 'Import data sources (dct_data_source -> empodat_data_sources)';
    }

    public function run(): void
    {
        // TODO: stream rows, resolve organisation_id + laboratory1_id + laboratory2_id,
        // INSERT into PG empodat_data_sources with id = id_data.
    }
}
