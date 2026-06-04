<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * TerraChem RODENT pipeline — full end-to-end import in one command.
 *
 * Phases (4-step, optimised single xlsx read, scoped to file_id=10014 only):
 *   1. TerraChemRodentFileSeeder — register THIS file row in `files` (id=10014 only)
 *   2. XlsxStationsMapping        — insert 162 station-column rows for file_id=10014
 *   3. XlsxStationsMappingFill    — resolve station_id via equality on short_sample_code
 *   4. Main+Metadata              — stream xlsx → empodat_suspect_main + empodat_suspect_metadata
 *                                   + empodat_suspect_substances (all in one pass)
 *
 * All phases are idempotent — safe to re-run. ⚠️ Main+Metadata adds to existing
 * data; truncate `empodat_suspect_main`/`empodat_suspect_metadata` for file_id=10014
 * first if re-seeding from scratch.
 *
 * php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectTerraChemRodentSeeder
 */
class EmpodatSuspectTerraChemRodentSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('=== TerraChem RODENT pipeline (file_id=10014) ===');

        $this->call([
            EmpodatSuspectTerraChemRodentFileSeeder::class,
            EmpodatSuspectTerraChemRodentXlsxStationsMappingSeeder::class,
            EmpodatSuspectTerraChemRodentXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectTerraChemRodentMainSeeder::class,
        ]);

        $this->command->info('=== TerraChem RODENT pipeline complete ===');
    }
}
