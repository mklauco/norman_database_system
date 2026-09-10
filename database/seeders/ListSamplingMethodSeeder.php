<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Fills `list_sampling_methods` from the legacy `data_sampling_method` export.
 *
 * This seeder used to read `data_smo.csv` — the AIR sampling modes, a
 * different codelist. Air had no table of its own at the time, so its four
 * values were parked here. `ImportSimpleLookupsStep` later added the real
 * sampling methods from `data_sampling_method`, but it inserts with
 * "skip if the row exists", so ids 1-3 kept the air text:
 *
 *   id 1  "High volume sampler (HVS)"  should be  "Grab sample"
 *   id 2  "Low volume sampler (LVS)"   should be  "Grab sample - filtered"
 *   id 3  "Other"                      should be  "LVSPE Mariani Box"
 *
 * Air now has `list_air_sampling_modes` (added with issue #22) and
 * `ListAirSamplingModeSeeder` fills it from `data_smo.csv`, so those three
 * rows are stale leftovers. Pointing this seeder at the correct export and
 * writing with `upsert` corrects them in place.
 *
 * `upsert` on the primary key also makes a re-run a no-op rather than a
 * duplicate-key failure, which is what the previous `insert()` did on any
 * database that had already been seeded.
 */
class ListSamplingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/data_sampling_method.csv';
        $rows = SimpleExcelReader::create($path)->getRows();

        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'id' => (int) $row['dsa_id'],
                'name' => trim((string) $row['dsa_name']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($payload === []) {
            $this->command->warn('data_sampling_method.csv is empty — nothing seeded.');

            return;
        }

        DB::table('list_sampling_methods')->upsert($payload, ['id'], ['name', 'updated_at']);

        $this->command->info(sprintf('list_sampling_methods: %d rows seeded.', count($payload)));
    }
}
