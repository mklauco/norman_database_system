<?php

namespace Database\Seeders\EmpodatSuspect;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class EmpodatSuspectUbaHelcomXlsxStationsMappingSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'empodat_suspect_xlsx_stations_mapping';

        $now = Carbon::now();
        $path = storage_path('app/public/empodat_suspect/headers_OK_UBA-HELCOM_suspect screening results_ng g wet weight_1204.csv');

        if (! file_exists($path)) {
            $this->command->error("CSV file not found: {$path}");

            return;
        }

        $this->command->info('Seeding UBA-HELCOM stations to empodat_suspect_xlsx_stations_mapping table...');

        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        $rowCount = 0;

        foreach ($rows as $r) {
            // Get the first (and only) column value
            $xlsxName = reset($r);

            // Skip empty rows
            if (empty($xlsxName)) {
                continue;
            }

            // Clean the value
            $cleanedValue = $this->cleanValue($xlsxName);

            // Skip if value is null or empty after cleaning
            if ($cleanedValue === null || $cleanedValue === '') {
                continue;
            }

            // Check if this xlsx_name already exists
            $exists = DB::table($target_table_name)
                ->where('xlsx_name', $cleanedValue)
                ->exists();

            if ($exists) {
                $this->command->info("Skipping duplicate: {$cleanedValue}");

                continue;
            }

            $p[] = [
                'xlsx_name' => $cleanedValue,
                'file_id' => 10008,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $rowCount++;
        }

        if (empty($p)) {
            $this->command->warn('No new records to insert. All stations already exist.');

            return;
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

        $this->command->info("Successfully seeded {$rowCount} new records into {$target_table_name} table.");
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
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectUbaHelcomXlsxStationsMappingSeeder
