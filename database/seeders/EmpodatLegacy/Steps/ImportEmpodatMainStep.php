<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

/**
 * Phase 3a — bulk insert delta into `empodat_main` (file_id left NULL).
 *
 * Source : legacy `dct_analysis` WHERE id > $sinceId (skips the 1-row overlap).
 * Target : PG `empodat_main`.
 *
 * Column mapping:
 *   id                  = dct_analysis.id (preserve)
 *   substance_id        = (SELECT id FROM susdat_substances WHERE code = LPAD(sus_id,8,'0'))
 *   station_id          = from ImportStationsStep's signature map
 *   matrix_id           = dct_analysis.matrix
 *   sampling_date_year  = dct_analysis.sampling_date_y
 *   concentration_indicator_id = dct_analysis.dic_id
 *   concentration_value = dct_analysis.concentration_value
 *   method_id           = dct_analysis.id_method
 *   data_source_id      = dct_analysis.id_data
 *   country_id          = (SELECT id FROM list_countries WHERE code = country)
 *   file_id             = NULL (populated in Phase 3b for performance, per v1 doc)
 *
 * Batching: stream from MariaDB cursor, INSERT to PG in chunks (~5–10k rows).
 * Orphan ids (substance/method/data) silently land as NULL via LEFT JOIN
 * lookup — the same v1 convention.
 */
class ImportEmpodatMainStep extends Step
{
    public function name(): string
    {
        return 'Import empodat_main (dct_analysis -> empodat_main, file_id NULL)';
    }

    public function run(): void
    {
        // TODO: cursor over legacy delta, resolve FKs in-stream, INSERT into PG empodat_main.
    }
}
