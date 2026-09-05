<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * TerraChem INVERTEBRATE 2nd batch pipeline — full end-to-end import in one command.
 *
 * Phases (4-step, optimised single xlsx read, scoped to file_id=10016 only):
 *   1. Batch2FileSeeder                — register THIS file row in `files` (id=10016 only)
 *   2. Batch2XlsxStationsMapping       — repair stale rows, then insert the missing
 *                                        station-column rows for file_id=10016 (58 total)
 *   3. Batch2XlsxStationsMappingFill   — resolve station_id via equality on sample_code
 *   4. Batch2Main+Metadata             — stream xlsx → empodat_suspect_main + empodat_suspect_metadata
 *                                        + empodat_suspect_substances (all in one pass)
 *
 * All phases are idempotent — safe to re-run. ⚠️ Main+Metadata adds to existing
 * data; truncate `empodat_suspect_main`/`empodat_suspect_metadata` for file_id=10016
 * first if re-seeding from scratch, and clear
 * `empodat_suspect_substances` for the same file_id.
 *
 * ⚠️ Never run alongside another *MainSeeder — they share the
 * empodat_suspect_main id sequence and interleaving corrupts the per-file id
 * ranges.
 *
 * php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectTerraChemInvertebrateBatch2Seeder
 */
class EmpodatSuspectTerraChemInvertebrateBatch2Seeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('=== TerraChem INVERTEBRATE 2nd batch pipeline (file_id=10016) ===');

        $this->call([
            EmpodatSuspectTerraChemInvertebrateBatch2FileSeeder::class,
            EmpodatSuspectTerraChemInvertebrateBatch2XlsxStationsMappingSeeder::class,
            EmpodatSuspectTerraChemInvertebrateBatch2XlsxStationsMappingFillSeeder::class,
            EmpodatSuspectTerraChemInvertebrateBatch2MainSeeder::class,
        ]);

        $this->command->info('=== TerraChem INVERTEBRATE 2nd batch pipeline complete ===');
    }
}
