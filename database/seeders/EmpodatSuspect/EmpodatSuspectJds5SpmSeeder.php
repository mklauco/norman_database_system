<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * JDS5 SPM pipeline — full end-to-end import in one command.
 *
 * Phases (4-step, optimised single xlsx read, scoped to file_id=10019 only):
 *   1. Jds5SpmFileSeeder             — register THIS file row in `files` (id=10016 only)
 *   2. Jds5SpmXlsxStationsMapping    — insert the 43 station-column rows for file_id=10019
 *   3. Jds5SpmXlsxStationsMappingFill— resolve station_id via equality on sample_code
 *   4. Jds5SpmMain+Metadata          — stream xlsx → empodat_suspect_main + empodat_suspect_metadata
 *                                        + empodat_suspect_substances (all in one pass)
 *
 * All phases are idempotent — safe to re-run. ⚠️ Main+Metadata adds to existing
 * data; truncate `empodat_suspect_main`/`empodat_suspect_metadata` for file_id=10019
 * first if re-seeding from scratch, and clear
 * `empodat_suspect_substances` for the same file_id.
 *
 * ⚠️ Never run alongside another *MainSeeder — they share the
 * empodat_suspect_main id sequence and interleaving corrupts the per-file id
 * ranges.
 *
 * php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectJds5SpmSeeder
 */
class EmpodatSuspectJds5SpmSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('=== JDS5 SPM pipeline (file_id=10019) ===');

        $this->call([
            EmpodatSuspectJds5SpmFileSeeder::class,
            EmpodatSuspectJds5SpmXlsxStationsMappingSeeder::class,
            EmpodatSuspectJds5SpmXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectJds5SpmMainSeeder::class,
        ]);

        $this->command->info('=== JDS5 SPM pipeline complete ===');
    }
}
