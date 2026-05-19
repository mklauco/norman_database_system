<?php

namespace Database\Seeders\Susdat;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsepaSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/seeds/susdat/susdat_usepa.csv');

        if (! file_exists($csvFile)) {
            $this->command->error("CSV file not found: {$csvFile}");

            return;
        }

        $this->command->info('Seeding susdat_usepa table...');

        // Clear existing data before seeding
        DB::table('susdat_usepa')->delete();

        $handle = fopen($csvFile, 'r');
        $header = fgetcsv($handle); // Skip header row

        $batch = [];
        $batchSize = 1000;
        $rowCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            // Convert empty strings to null and handle numeric fields
            $processedData = [
                'sus_id' => (int) $data['sus_id'],
                'substance_id' => null, // Will be populated manually with SQL query
                'dtsxid' => $data['DTXSID'] ?: null, // Map DTXSID from CSV to dtsxid in DB
                'usepa_formula' => $data['usepa_formula'] ?: null,
                'usepa_wikipedia' => $data['usepa_wikipedia'] ?: null,
                'usepa_wikipedia_url' => $data['usepa_wikipedia_url'] ?: null,
                'usepa_Log_Kow_experimental' => $data['usepa_Log_Kow_experimental'] !== '' ? (float) $data['usepa_Log_Kow_experimental'] : null,
                'usepa_Log_Kow_predicted' => $data['usepa_Log_Kow_predicted'] !== '' ? (float) $data['usepa_Log_Kow_predicted'] : null,
                'usepa_solubility_experimental' => $data['usepa_solubility_experimental'] !== '' ? (float) $data['usepa_solubility_experimental'] : null,
                'usepa_solubility_predicted' => $data['usepa_solubility_predicted'] !== '' ? (float) $data['usepa_solubility_predicted'] : null,
                'usepa_Koc_min_experimental' => $data['usepa_Koc_min_experimental'] !== '' ? (float) $data['usepa_Koc_min_experimental'] : null,
                'usepa_Koc_max_experimental' => $data['usepa_Koc_max_experimental'] !== '' ? (float) $data['usepa_Koc_max_experimental'] : null,
                'usepa_Koc_min_predicted' => $data['usepa_Koc_min_predicted'] !== '' ? (float) $data['usepa_Koc_min_predicted'] : null,
                'usepa_Koc_max_predicted' => $data['usepa_Koc_max_predicted'] !== '' ? (float) $data['usepa_Koc_max_predicted'] : null,
                'usepa_Life_experimental' => $data['usepa_Life_experimental'] !== '' ? (float) $data['usepa_Life_experimental'] : null,
                'usepa_Life_predicted' => $data['usepa_Life_predicted'] !== '' ? (float) $data['usepa_Life_predicted'] : null,
                'usepa_BCF_experimental' => $data['usepa_BCF_experimental'] !== '' ? (float) $data['usepa_BCF_experimental'] : null,
                'usepa_BCF_predicted' => $data['usepa_BCF_predicted'] !== '' ? (float) $data['usepa_BCF_predicted'] : null,
            ];

            $batch[] = $processedData;
            $rowCount++;

            if (count($batch) >= $batchSize) {
                DB::table('susdat_usepa')->insert($batch);
                $this->command->info("Processed {$rowCount} rows...");
                $batch = [];
            }
        }

        // Insert remaining records
        if (! empty($batch)) {
            DB::table('susdat_usepa')->insert($batch);
        }

        fclose($handle);

        $this->command->info("Successfully seeded {$rowCount} records into susdat_usepa table.");
    }
}
// php artisan db:seed --class=Database\\Seeders\\Susdat\\UsepaSeeder
