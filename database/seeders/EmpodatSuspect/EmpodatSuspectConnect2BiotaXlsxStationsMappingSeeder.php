<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

/**
 * Station-mapping rows for CONNECT 2 BIOTA (file_id=10004).
 *
 * All logic lives in {@see EmpodatSuspectConnect2MappingSeederBase} — this
 * class is only the per-file constants, so SEDIMENTS and BIOTA cannot drift
 * apart.
 */
class EmpodatSuspectConnect2BiotaXlsxStationsMappingSeeder extends EmpodatSuspectConnect2MappingSeederBase
{
    protected function fileId(): int
    {
        return 10004;
    }

    protected function fileName(): string
    {
        return 'OK_CONNECT 2_suspect screening results_ng g wet weight_1192 - BIOTA v2.xlsx';
    }

    protected function expectedStationColumns(): int
    {
        return 8;
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectConnect2BiotaXlsxStationsMappingSeeder
