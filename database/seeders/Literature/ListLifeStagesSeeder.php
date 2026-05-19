<?php

namespace Database\Seeders\Literature;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class ListLifeStagesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'list_life_stages';
        DB::table($target_table_name)->truncate();

        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/literature/list_life_stages.csv';

        if (! file_exists($path)) {
            $this->command->error("CSV file not found: {$path}");

            return;
        }

        $this->command->info('Seeding list_life_stages table...');

        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        $rowCount = 0;

        foreach ($rows as $r) {
            // Skip empty rows
            if (empty($r['life_stage'])) {
                continue;
            }

            // Clean the value
            $cleanedValue = $this->cleanValue($r['life_stage']);

            // Skip if value is null, empty, 'no data', or 'NA' after cleaning
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
// php artisan db:seed --class=Database\\Seeders\\Literature\\ListLifeStagesSeeder
