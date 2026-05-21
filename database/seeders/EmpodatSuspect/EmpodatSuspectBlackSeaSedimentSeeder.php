<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * BlackSea SEDIMENT pipeline — full end-to-end import in one command.
 *
 * Phases (4-step, optimised single xlsx read, scoped to file_id=10010 only):
 *   1. SedimentFileSeeder      — register THIS file row in `files` (id=10010 only)
 *   2. XlsxStationsMapping     — insert 10 station-column rows for file_id=10010
 *   3. XlsxStationsMappingFill — resolve station_id via equality on short_sample_code
 *   4. Main+Metadata           — stream xlsx → empodat_suspect_main + empodat_suspect_metadata
 *                                + empodat_suspect_substances (all in one pass)
 *
 * All phases are idempotent — safe to re-run. ⚠️ Main+Metadata adds to existing
 * data; truncate `empodat_suspect_main`/`empodat_suspect_metadata` for file_id=10010
 * first if re-seeding from scratch.
 *
 * php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectBlackSeaSedimentSeeder
 */
class EmpodatSuspectBlackSeaSedimentSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('=== BlackSea SEDIMENT pipeline (file_id=10010) ===');

        $this->call([
            EmpodatSuspectBlackSeaSedimentFileSeeder::class,
            EmpodatSuspectBlackSeaSedimentXlsxStationsMappingSeeder::class,
            EmpodatSuspectBlackSeaSedimentXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectBlackSeaSedimentMainSeeder::class,
        ]);

        $this->command->info('=== BlackSea SEDIMENT pipeline complete ===');
    }
}
