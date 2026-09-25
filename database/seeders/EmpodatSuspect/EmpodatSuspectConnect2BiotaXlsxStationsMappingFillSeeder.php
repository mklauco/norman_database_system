<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

/**
 * Resolve station_id for CONNECT 2 BIOTA (file_id=10004).
 *
 * All logic lives in {@see EmpodatSuspectConnect2MappingFillSeederBase}.
 */
class EmpodatSuspectConnect2BiotaXlsxStationsMappingFillSeeder extends EmpodatSuspectConnect2MappingFillSeederBase
{
    protected function fileId(): int
    {
        return 10004;
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectConnect2BiotaXlsxStationsMappingFillSeeder
