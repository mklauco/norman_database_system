<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Fills `list_type_wastes` from the legacy `data_type_waste.csv` export (issue #22).
 *
 * Backs `empodat_matrix_water_waste.dtw_id` (267 895 rows).
 *
 * Legacy ids are preserved 1:1, so the raw ids already stored on the
 * `empodat_matrix_*` columns resolve without remapping. Rows are written
 * with `upsert` on the primary key so a re-run is a no-op and corrects
 * drift instead of failing on a duplicate key.
 */
class ListTypeWasteSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/data_type_waste.csv';
        $rows = SimpleExcelReader::create($path)->getRows();

        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'id' => (int) $row['dtw_id'],
                'name' => trim((string) $row['dtw_name']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($payload === []) {
            $this->command->warn('data_type_waste.csv is empty — nothing seeded into list_type_wastes.');

            return;
        }

        DB::table('list_type_wastes')->upsert($payload, ['id'], ['name', 'updated_at']);

        $this->command->info(sprintf('list_type_wastes: %d rows seeded.', count($payload)));
    }
}
