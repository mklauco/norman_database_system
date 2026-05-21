<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * BlackSea BIOTA pipeline — full end-to-end import in one command.
 *
 * Phases:
 *   1. FileSeeder              — register file rows (10001–10011) in `files`
 *   2. XlsxStationsMapping     — insert 11 station-column rows for file_id=10009
 *   3. XlsxStationsMappingFill — resolve station_id via equality on short_sample_code
 *   4. Main+Metadata           — stream xlsx → empodat_suspect_main + empodat_suspect_metadata
 *                                AND populate empodat_suspect_substances in the same single pass
 *                                (avoids reading the 19MB xlsx twice; saves ~130s vs. running
 *                                EmpodatSuspectBlackSeaBiotaSubstancesSeeder separately)
 *
 * All phases are idempotent — safe to re-run.
 *
 * php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectBlackSeaBiotaSeeder
 */
class EmpodatSuspectBlackSeaBiotaSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('=== BlackSea BIOTA pipeline (file_id=10009) ===');

        $this->call([
            EmpodatSuspectFileSeeder::class,
            EmpodatSuspectBlackSeaBiotaXlsxStationsMappingSeeder::class,
            EmpodatSuspectBlackSeaBiotaXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectBlackSeaBiotaMainSeeder::class,
        ]);

        $this->command->info('=== BlackSea BIOTA pipeline complete ===');
    }
}
