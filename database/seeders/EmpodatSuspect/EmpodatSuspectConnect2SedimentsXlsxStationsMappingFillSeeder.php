<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

/**
 * Resolve station_id for CONNECT 2 SEDIMENTS (file_id=10003).
 *
 * All logic lives in {@see EmpodatSuspectConnect2MappingFillSeederBase}.
 */
class EmpodatSuspectConnect2SedimentsXlsxStationsMappingFillSeeder extends EmpodatSuspectConnect2MappingFillSeederBase
{
    protected function fileId(): int
    {
        return 10003;
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectConnect2SedimentsXlsxStationsMappingFillSeeder
