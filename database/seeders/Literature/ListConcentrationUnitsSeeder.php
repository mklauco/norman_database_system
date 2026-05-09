<?php

namespace Database\Seeders\Literature;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class ListConcentrationUnitsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'list_concentration_units';
        DB::table($target_table_name)->truncate();

        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/literature/list_concentration_units.csv';

        if (! file_exists($path)) {
            $this->command->error("CSV file not found: {$path}");

            return;
        }

        $this->command->info('Seeding list_concentration_units table...');

        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        $rowCount = 0;

        foreach ($rows as $r) {
            // Skip empty rows
            if (empty($r['concentration_units'])) {
                continue;
            }

            // Clean the value (only trim and remove extra whitespace)
            $cleanedValue = $this->cleanValue($r['concentration_units']);

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

        // Fix encoding issues - convert Latin-1 to UTF-8 if needed
        if (! mb_check_encoding($cleaned, 'UTF-8')) {
            $cleaned = mb_convert_encoding($cleaned, 'UTF-8', 'ISO-8859-1');
        }

        // Replace common problematic characters
        // Replace micro symbol (µ) variations with proper Greek mu (μ) or 'u'
        $cleaned = str_replace(['µ', chr(0xB5), '�'], 'μ', $cleaned);

        // Ensure the result is valid UTF-8
        $cleaned = mb_convert_encoding($cleaned, 'UTF-8', 'UTF-8');

        return $cleaned;
    }
}
// php artisan db:seed --class=Database\\Seeders\\Literature\\ListConcentrationUnitsSeeder
