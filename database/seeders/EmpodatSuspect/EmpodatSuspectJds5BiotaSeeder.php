<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * JDS5 BIOTA pipeline — full end-to-end import in one command.
 *
 * Phases (4-step, optimised single xlsx read, scoped to file_id=10017 only):
 *   1. Jds5BiotaFileSeeder             — register THIS file row in `files` (id=10016 only)
 *   2. Jds5BiotaXlsxStationsMapping    — insert the 43 station-column rows for file_id=10017
 *   3. Jds5BiotaXlsxStationsMappingFill— resolve station_id via equality on short_sample_code
 *   4. Jds5BiotaMain+Metadata          — stream xlsx → empodat_suspect_main + empodat_suspect_metadata
 *                                        + empodat_suspect_substances (all in one pass)
 *
 * All phases are idempotent — safe to re-run. ⚠️ Main+Metadata adds to existing
 * data; truncate `empodat_suspect_main`/`empodat_suspect_metadata` for file_id=10017
 * first if re-seeding from scratch, and clear
 * `empodat_suspect_substances` for the same file_id.
 *
 * ⚠️ Never run alongside another *MainSeeder — they share the
 * empodat_suspect_main id sequence and interleaving corrupts the per-file id
 * ranges.
 *
 * php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectJds5BiotaSeeder
 */
class EmpodatSuspectJds5BiotaSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('=== JDS5 BIOTA pipeline (file_id=10017) ===');

        $this->call([
            EmpodatSuspectJds5BiotaFileSeeder::class,
            EmpodatSuspectJds5BiotaXlsxStationsMappingSeeder::class,
            EmpodatSuspectJds5BiotaXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectJds5BiotaMainSeeder::class,
        ]);

        $this->command->info('=== JDS5 BIOTA pipeline complete ===');
    }
}
