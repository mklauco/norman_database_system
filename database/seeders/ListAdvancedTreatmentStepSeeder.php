<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Fills `list_advanced_treatment_steps` from the legacy `data_tertiary_treatment.csv` export (issue #22).
 *
 * Backs `dtt_id` on `empodat_matrix_water_waste` and `empodat_matrix_sewage_sludge`. The table was created in 2024 but never populated.
 *
 * Legacy ids are preserved 1:1, so the raw ids already stored on the
 * `empodat_matrix_*` columns resolve without remapping. Rows are written
 * with `upsert` on the primary key so a re-run is a no-op and corrects
 * drift instead of failing on a duplicate key.
 */
class ListAdvancedTreatmentStepSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/data_tertiary_treatment.csv';
        $rows = SimpleExcelReader::create($path)->getRows();

        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'id' => (int) $row['dtt_id'],
                'name' => trim((string) $row['dtt_name']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($payload === []) {
            $this->command->warn('data_tertiary_treatment.csv is empty — nothing seeded into list_advanced_treatment_steps.');

            return;
        }

        DB::table('list_advanced_treatment_steps')->upsert($payload, ['id'], ['name', 'updated_at']);

        $this->command->info(sprintf('list_advanced_treatment_steps: %d rows seeded.', count($payload)));
    }
}
