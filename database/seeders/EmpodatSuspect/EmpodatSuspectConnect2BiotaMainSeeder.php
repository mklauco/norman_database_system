<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

/**
 * CONNECT 2 BIOTA (file_id=10004) → empodat_suspect_main +
 * empodat_suspect_metadata + empodat_suspect_substances.
 *
 * All logic lives in {@see EmpodatSuspectConnect2MainSeederBase}. The id
 * range below is the block this file already occupies and is written back
 * into; the import aborts rather than passing 7180350.
 *
 * Expected result from the v2 spreadsheet: 95 738 source rows x 8 station
 * columns = 765 904 main rows.
 */
class EmpodatSuspectConnect2BiotaMainSeeder extends EmpodatSuspectConnect2MainSeederBase
{
    protected function fileId(): int
    {
        return 10004;
    }

    protected function fileName(): string
    {
        return 'OK_CONNECT 2_suspect screening results_ng g wet weight_1192 - BIOTA v2.xlsx';
    }

    protected function idFrom(): int
    {
        return 6414447;
    }

    protected function idTo(): int
    {
        return 7180350;
    }

    protected function expectedStationColumns(): int
    {
        return 8;
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectConnect2BiotaMainSeeder
