<?php

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class EmpodatSuspectHelcomBiotaSubstancesSeeder extends Seeder
{
    use WithoutModelEvents;

    protected ?int $fileId = 10006;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Processing HELCOM PreEMPT BIOTA substances for empodat_suspect_substances table...');

        // Increase PHP memory limit for large Excel files
        ini_set('memory_limit', '2G');
        ini_set('max_execution_time', '7200');

        $path = storage_path('app/public/empodat_suspect/OK_HELCOM PreEMPT_suspect screening results_ng g wet weight_1980 - BIOTA.xlsx');

        if (! file_exists($path)) {
            $this->command->error("Excel file not found: {$path}");

            return;
        }

        $this->command->info('Reading Excel file (streaming mode)...');

        try {
            // Read Excel file in streaming mode
            $reader = SimpleExcelReader::create($path);

            // Track unique substances
            $substances = [];
            $rowCount = 0;
            $skippedRows = 0;
            $startTime = microtime(true);

            // Start transaction
            DB::beginTransaction();

            try {
                // Stream rows instead of loading all into memory
                foreach ($reader->getRows() as $row) {
                    $normanId = trim($row['NORMAN_ID'] ?? '');
                    $name = trim($row['Name'] ?? '');

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

                        // Force garbage collection periodically
                        if ($rowCount % 1000 === 0) {
                            gc_collect_cycles();
                        }
                    }
                }

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
                throw $e;
            }

        } catch (\Exception $e) {
            $this->command->error('Failed to read Excel file: '.$e->getMessage());
            throw $e;
        }
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectHelcomBiotaSubstancesSeeder
