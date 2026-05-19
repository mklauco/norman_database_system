<?php

namespace Database\Seeders\Literature;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class ListHabitatTypesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'list_habitat_types';
        DB::table($target_table_name)->truncate();

        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/literature/list_habitat_types.csv';

        if (! file_exists($path)) {
            $this->command->error("CSV file not found: {$path}");

            return;
        }

        $this->command->info('Seeding list_habitat_types table...');

        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        $rowCount = 0;

        foreach ($rows as $r) {
            // Skip empty rows only
            if (empty($r['habitat_type'])) {
                continue;
            }

            // Clean the value (only trim and remove extra whitespace)
            $cleanedValue = $this->cleanValue($r['habitat_type']);

            // Skip only if value is null or empty after cleaning
            if ($cleanedValue === null || $cleanedValue === '') {
                continue;
            }

            $p[] = [
                'name' => $cleanedValue,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $rowCount++;
        }

        $chunkSize = 2000;
        $chunks = array_chunk($p, $chunkSize);
        $k = 0;
        $count = ceil(count($p) / $chunkSize);

        foreach ($chunks as $c) {
            $this->command->info('Processing chunk '.($k + 1)."/{$count}");
            DB::table($target_table_name)->insert($c);
            $k++;
        }

        $this->command->info("Successfully seeded {$rowCount} records into {$target_table_name} table.");
    }

    /**
     * Clean and trim the value, return null if empty
     *
     * @param  mixed  $value
     * @return string|null
     */
    protected function cleanValue($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Trim whitespace
        $cleaned = trim($value);

        // Return null if empty after trimming
        if ($cleaned === '') {
            return null;
        }

        return $cleaned;
    }
}
// php artisan db:seed --class=Database\\Seeders\\Literature\\ListHabitatTypesSeeder
