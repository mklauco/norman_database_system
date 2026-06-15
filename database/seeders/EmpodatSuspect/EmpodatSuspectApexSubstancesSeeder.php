<?php

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpodatSuspectApexSubstancesSeeder extends Seeder
{
    use WithoutModelEvents;

    protected ?int $fileId = 10007;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Processing LIFE APEX substances for empodat_suspect_substances table...');

        $path = storage_path('app/public/empodat_suspect/OK_LIFE APEX_suspect screening results_ng g wet weight_333.csv');

        if (! file_exists($path)) {
            $this->command->error("CSV file not found: {$path}");

            return;
        }

        $this->command->info('Reading CSV file...');

        $handle = fopen($path, 'r');
        if (! $handle) {
            $this->command->error('Failed to open CSV file');

            return;
        }

        // Read header
        $header = fgetcsv($handle);
        if (! $header) {
            $this->command->error('Failed to read CSV header');
            fclose($handle);

            return;
        }

        // Clean header - remove BOM, trim spaces
        $header = array_map(function ($h) {
            $h = str_replace("\xEF\xBB\xBF", '', $h);

            return trim($h);
        }, $header);

        $this->command->info('CSV Header (first 5 columns): '.implode(', ', array_slice($header, 0, 5)));

        // Track unique substances
        $substances = [];
        $rowCount = 0;
        $skippedRows = 0;
        $startTime = microtime(true);

        // Start transaction
        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                // Combine header with row
                if (count($row) !== count($header)) {
                    $skippedRows++;

                    continue;
                }

                $data = array_combine($header, $row);
                if ($data === false) {
                    $skippedRows++;

                    continue;
                }

                $normanId = trim($data['NORMAN_ID'] ?? '');
                $name = trim($data['Name'] ?? '');

                // Skip if either field is empty
                if (empty($normanId) || empty($name)) {
                    $skippedRows++;

                    continue;
                }

                // Create unique key
                $key = $normanId.'|'.$name;

                // Add to unique substances array
                if (! isset($substances[$key])) {
                    $substances[$key] = [
                        'norman_id' => $normanId,
                        'name' => $name,
                        'file_id' => $this->fileId,
                    ];
                }

                $rowCount++;

                // Progress update every 100 rows
                if ($rowCount % 100 === 0) {
                    $this->command->info("Processed {$rowCount} rows, found ".count($substances).' unique substances...');
                }
            }

            fclose($handle);

            // Insert unique substances
            if (! empty($substances)) {
                $this->command->info('Inserting '.count($substances).' unique substances...');

                $batch = [];
                $batchSize = 500;
                $inserted = 0;

                foreach ($substances as $substance) {
                    $batch[] = $substance;

                    if (count($batch) >= $batchSize) {
                        DB::table('empodat_suspect_substances')->insert($batch);
                        $inserted += count($batch);
                        $batch = [];
                        $this->command->info("Inserted {$inserted} / ".count($substances).' substances...');
                    }
                }

                // Insert remaining
                if (! empty($batch)) {
                    DB::table('empodat_suspect_substances')->insert($batch);
                    $inserted += count($batch);
                }

                $this->command->info("Successfully inserted {$inserted} unique substances");
            }

            DB::commit();

            $totalTime = round(microtime(true) - $startTime, 2);
            $this->command->info("Completed in {$totalTime}s");
            $this->command->info("Total rows processed: {$rowCount}");
            $this->command->info('Unique substances: '.count($substances));

            if ($skippedRows > 0) {
                $this->command->warn("Skipped {$skippedRows} rows (empty NORMAN_ID or Name)");
            }

            $this->command->info("All substances seeded with file_id: {$this->fileId}");

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            throw $e;
        }
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectApexSubstancesSeeder
