<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * JDS5 SURFACE WATER pipeline — full end-to-end import in one command.
 *
 * Phases (4-step, optimised single xlsx read, scoped to file_id=10020 only):
 *   1. Jds5SwFileSeeder             — register THIS file row in `files` (id=10016 only)
 *   2. Jds5SwXlsxStationsMapping    — insert the 43 station-column rows for file_id=10020
 *   3. Jds5SwXlsxStationsMappingFill— resolve station_id via equality on sample_code
 *   4. Jds5SwMain+Metadata          — stream xlsx → empodat_suspect_main + empodat_suspect_metadata
 *                                        + empodat_suspect_substances (all in one pass)
 *
 * All phases are idempotent — safe to re-run. ⚠️ Main+Metadata adds to existing
 * data; truncate `empodat_suspect_main`/`empodat_suspect_metadata` for file_id=10020
 * first if re-seeding from scratch, and clear
 * `empodat_suspect_substances` for the same file_id.
 *
 * ⚠️ Never run alongside another *MainSeeder — they share the
 * empodat_suspect_main id sequence and interleaving corrupts the per-file id
 * ranges.
 *
 * php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectJds5SwSeeder
 */
class EmpodatSuspectJds5SwSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('=== JDS5 SURFACE WATER pipeline (file_id=10020) ===');

        $this->call([
            EmpodatSuspectJds5SwFileSeeder::class,
            EmpodatSuspectJds5SwXlsxStationsMappingSeeder::class,
            EmpodatSuspectJds5SwXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectJds5SwMainSeeder::class,
        ]);

        $this->command->info('=== JDS5 SURFACE WATER pipeline complete ===');
    }
}
