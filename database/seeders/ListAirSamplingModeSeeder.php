<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Fills `list_air_sampling_modes` from the legacy `data_smo.csv` export (issue #22).
 *
 * Backs `empodat_matrix_air.dsmo_id`. Distinct from `list_sampling_methods`, which agrees on ids 1-3 by coincidence and diverges at id 4.
 *
 * Legacy ids are preserved 1:1, so the raw ids already stored on the
 * `empodat_matrix_*` columns resolve without remapping. Rows are written
 * with `upsert` on the primary key so a re-run is a no-op and corrects
 * drift instead of failing on a duplicate key.
 */
class ListAirSamplingModeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/data_smo.csv';
        $rows = SimpleExcelReader::create($path)->getRows();

        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'id' => (int) $row['dsmo_id'],
                'name' => trim((string) $row['dsmo_name']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($payload === []) {
            $this->command->warn('data_smo.csv is empty — nothing seeded into list_air_sampling_modes.');

            return;
        }

        DB::table('list_air_sampling_modes')->upsert($payload, ['id'], ['name', 'updated_at']);

        $this->command->info(sprintf('list_air_sampling_modes: %d rows seeded.', count($payload)));
    }
}
