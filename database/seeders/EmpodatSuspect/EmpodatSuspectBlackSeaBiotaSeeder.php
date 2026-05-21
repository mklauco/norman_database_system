<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * BlackSea BIOTA pipeline — full end-to-end import in one command.
 *
 * Phases (4-step, optimised single xlsx read, scoped to file_id=10009 only):
 *   1. BiotaFileSeeder         — register THIS file row in `files` (id=10009 only)
 *   2. XlsxStationsMapping     — insert 11 station-column rows for file_id=10009
 *   3. XlsxStationsMappingFill — resolve station_id via equality on short_sample_code
 *   4. Main+Metadata           — stream xlsx → empodat_suspect_main + empodat_suspect_metadata
 *                                + empodat_suspect_substances (all in one pass — avoids
 *                                reading the 19MB xlsx twice)
 *
 * All phases are idempotent — safe to re-run. The pipeline only touches rows
 * relevant to this source; other empodat_suspect file rows are untouched.
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
            EmpodatSuspectBlackSeaBiotaFileSeeder::class,
            EmpodatSuspectBlackSeaBiotaXlsxStationsMappingSeeder::class,
            EmpodatSuspectBlackSeaBiotaXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectBlackSeaBiotaMainSeeder::class,
        ]);

        $this->command->info('=== BlackSea BIOTA pipeline complete ===');
    }
}
