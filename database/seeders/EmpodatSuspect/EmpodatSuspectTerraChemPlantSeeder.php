<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * TerraChem PLANT pipeline — full end-to-end import in one command.
 *
 * Phases (4-step, optimised single xlsx read, scoped to file_id=10013 only):
 *   1. TerraChemPlantFileSeeder — register THIS file row in `files` (id=10013 only)
 *   2. XlsxStationsMapping       — insert 71 station-column rows for file_id=10013
 *   3. XlsxStationsMappingFill   — resolve station_id via equality on short_sample_code
 *   4. Main+Metadata             — stream xlsx → empodat_suspect_main + empodat_suspect_metadata
 *                                  + empodat_suspect_substances (all in one pass)
 *
 * All phases are idempotent — safe to re-run. ⚠️ Main+Metadata adds to existing
 * data; truncate `empodat_suspect_main`/`empodat_suspect_metadata` for file_id=10013
 * first if re-seeding from scratch.
 *
 * php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectTerraChemPlantSeeder
 */
class EmpodatSuspectTerraChemPlantSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('=== TerraChem PLANT pipeline (file_id=10013) ===');

        $this->call([
            EmpodatSuspectTerraChemPlantFileSeeder::class,
            EmpodatSuspectTerraChemPlantXlsxStationsMappingSeeder::class,
            EmpodatSuspectTerraChemPlantXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectTerraChemPlantMainSeeder::class,
        ]);

        $this->command->info('=== TerraChem PLANT pipeline complete ===');
    }
}
