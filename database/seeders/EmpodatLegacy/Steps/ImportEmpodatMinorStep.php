<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

/**
 * Phase 4a — bulk insert delta into `empodat_minor` (1:1 by id with empodat_main).
 *
 * Source : legacy `dct_analysis` WHERE id > $sinceId.
 * Target : PG `empodat_minor` (~40 cols carrying minor metadata not in main).
 *
 * No FK resolution needed — id-for-id insert. The PG schema stores most legacy
 * date / aggregation / location-precision fields as character varying / text,
 * so verbatim transfer is safe.
 *
 * Idempotent: skip ids that already exist in PG empodat_minor.
 */
class ImportEmpodatMinorStep extends Step
{
    public function name(): string
    {
        return 'Import empodat_minor (dct_analysis -> empodat_minor, 1:1 by id)';
    }

    public function run(): void
    {
        // TODO: stream legacy dct_analysis delta cols (the ~40 not in main/stations),
        // INSERT into PG empodat_minor with id = dct_analysis.id.
    }
}
