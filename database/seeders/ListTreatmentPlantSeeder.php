<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Fills `list_treatment_plants` from the legacy `data_treatment_plant.csv` export (issue #22).
 *
 * Backs `dtp_id` on `empodat_matrix_water_waste` and `empodat_matrix_sewage_sludge`. The table was created in 2024 but never populated.
 *
 * Legacy ids are preserved 1:1, so the raw ids already stored on the
 * `empodat_matrix_*` columns resolve without remapping. Rows are written
 * with `upsert` on the primary key so a re-run is a no-op and corrects
 * drift instead of failing on a duplicate key.
 */
class ListTreatmentPlantSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/data_treatment_plant.csv';
        $rows = SimpleExcelReader::create($path)->getRows();

        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'id' => (int) $row['dtp_id'],
                'name' => trim((string) $row['dtp_name']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($payload === []) {
            $this->command->warn('data_treatment_plant.csv is empty — nothing seeded into list_treatment_plants.');

            return;
        }

        DB::table('list_treatment_plants')->upsert($payload, ['id'], ['name', 'updated_at']);

        $this->command->info(sprintf('list_treatment_plants: %d rows seeded.', count($payload)));
    }
}
