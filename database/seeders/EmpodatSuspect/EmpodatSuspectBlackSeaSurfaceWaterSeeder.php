<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * BlackSea Surface Water pipeline — full end-to-end import in one command.
 *
 * Phases (4-step, optimised single xlsx read, scoped to file_id=10011 only):
 *   1. SurfaceWaterFileSeeder  — register THIS file row in `files` (id=10011 only)
 *   2. XlsxStationsMapping     — insert 20 station-column rows for file_id=10011
 *   3. XlsxStationsMappingFill — resolve station_id via equality on short_sample_code
 *   4. Main+Metadata           — stream xlsx → empodat_suspect_main + empodat_suspect_metadata
 *                                + empodat_suspect_substances (all in one pass)
 *
 * Notable: this is the first BlackSea source with a water-matrix substance set,
 * so after seeding + MV refresh, empodat_suspect_matrix_water_surface (currently
 * 0 rows) will populate.
 *
 * All phases are idempotent — safe to re-run. ⚠️ Main+Metadata adds to existing
 * data; truncate `empodat_suspect_main`/`empodat_suspect_metadata` for file_id=10011
 * first if re-seeding from scratch.
 *
 * php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectBlackSeaSurfaceWaterSeeder
 */
class EmpodatSuspectBlackSeaSurfaceWaterSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('=== BlackSea Surface Water pipeline (file_id=10011) ===');

        $this->call([
            EmpodatSuspectBlackSeaSurfaceWaterFileSeeder::class,
            EmpodatSuspectBlackSeaSurfaceWaterXlsxStationsMappingSeeder::class,
            EmpodatSuspectBlackSeaSurfaceWaterXlsxStationsMappingFillSeeder::class,
            EmpodatSuspectBlackSeaSurfaceWaterMainSeeder::class,
        ]);

        $this->command->info('=== BlackSea Surface Water pipeline complete ===');
    }
}
