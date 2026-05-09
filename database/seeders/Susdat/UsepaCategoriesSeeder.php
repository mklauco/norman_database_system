<?php

namespace Database\Seeders\Susdat;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsepaCategoriesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/seeds/susdat/susdat_usepa_categories.csv');

        if (! file_exists($csvFile)) {
            $this->command->error("CSV file not found: {$csvFile}");

            return;
        }

        $this->command->info('Seeding susdat_usepa_categories table...');

        // Clear existing data before seeding
        DB::table('susdat_usepa_categories')->delete();

        $handle = fopen($csvFile, 'r');
        $header = fgetcsv($handle); // Skip header row

        $batch = [];
        $batchSize = 10000;
        $rowCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            // Process data mapping CSV columns to database columns
            $processedData = [
                'sus_id' => (int) $data['sus_id'],
                'substance_id' => null, // Will be populated manually with SQL query
                'category_name' => $data['categories'] ?: null, // Map 'categories' from CSV to 'category_name' in DB
            ];

            $batch[] = $processedData;
            $rowCount++;

            if (count($batch) >= $batchSize) {
                DB::table('susdat_usepa_categories')->insert($batch);
                $this->command->info("Processed {$rowCount} rows...");
                $batch = [];
            }
        }

        // Insert remaining records
        if (! empty($batch)) {
            DB::table('susdat_usepa_categories')->insert($batch);
        }

        fclose($handle);

        $this->command->info("Successfully seeded {$rowCount} records into susdat_usepa_categories table.");
    }
}
// php artisan db:seed --class=Database\\Seeders\\Susdat\\UsepaCategoriesSeeder
