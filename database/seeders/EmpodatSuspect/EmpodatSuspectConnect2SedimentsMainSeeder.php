<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

/**
 * CONNECT 2 SEDIMENTS (file_id=10003) → empodat_suspect_main +
 * empodat_suspect_metadata + empodat_suspect_substances.
 *
 * All logic lives in {@see EmpodatSuspectConnect2MainSeederBase}. The id
 * range below is the block this file already occupies and is written back
 * into; the import aborts rather than passing 6414446.
 *
 * Expected result from the v2 spreadsheet: 95 738 source rows x 14 station
 * columns = 1 340 332 main rows.
 */
class EmpodatSuspectConnect2SedimentsMainSeeder extends EmpodatSuspectConnect2MainSeederBase
{
    protected function fileId(): int
    {
        return 10003;
    }

    protected function fileName(): string
    {
        return 'OK_CONNECT 2_suspect screening results_ng g dry weight_1192 - SEDIMENTS v2.xlsx';
    }

    protected function idFrom(): int
    {
        return 4978377;
    }

    protected function idTo(): int
    {
        return 6414446;
    }

    protected function expectedStationColumns(): int
    {
        return 14;
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectConnect2SedimentsMainSeeder
