<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Fills `list_air_locations` from the legacy `data_location.csv` export (issue #22).
 *
 * Backs `empodat_matrix_air.dloca_id`. Distinct from `list_locations` (legacy `data_loc`), whose ids clash with these.
 *
 * Legacy ids are preserved 1:1, so the raw ids already stored on the
 * `empodat_matrix_*` columns resolve without remapping. Rows are written
 * with `upsert` on the primary key so a re-run is a no-op and corrects
 * drift instead of failing on a duplicate key.
 */
class ListAirLocationSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/data_location.csv';
        $rows = SimpleExcelReader::create($path)->getRows();

        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'id' => (int) $row['dloca_id'],
                'name' => trim((string) $row['dloca_name']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($payload === []) {
            $this->command->warn('data_location.csv is empty — nothing seeded into list_air_locations.');

            return;
        }

        DB::table('list_air_locations')->upsert($payload, ['id'], ['name', 'updated_at']);

        $this->command->info(sprintf('list_air_locations: %d rows seeded.', count($payload)));
    }
}
