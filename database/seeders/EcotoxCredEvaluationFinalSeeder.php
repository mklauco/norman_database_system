<?php

namespace Database\Seeders;

use App\Models\Ecotox\EcotoxCredEvaluationFinal;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class EcotoxCredEvaluationFinalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting EcotoxCredEvaluationFinal seeding...');

        $path = base_path('database/seeders/seeds/ecotox_tables/cred_evaluation_final.csv');

        if (! file_exists($path)) {
            $this->command->error("CSV file not found at: {$path}");

            return;
        }

        // Temporarily disable foreign key checks
        Schema::disableForeignKeyConstraints();

        try {
            // Use SimpleExcelReader for efficient CSV processing
            $reader = SimpleExcelReader::create($path)
                ->useDelimiter(',')
                ->headersToSnakeCase(false);

            // Get headers for debugging
            $firstRow = $reader->getRows()->first();
            if ($firstRow) {
                $headers = array_keys($firstRow);
                $this->command->info('CSV headers: '.implode(', ', $headers));
            }

            // Process in chunks to conserve memory
            $chunkSize = 100;
            $totalProcessed = 0;

            $reader->getRows()
                ->chunk($chunkSize)
                ->each(function ($rows, $key) use (&$totalProcessed) {
                    $records = [];

                    foreach ($rows as $row) {
                        // Helper function to safely convert empty strings to null
                        $safeValue = function ($value) {
                            return ($value === '' || $value === null) ? null : $value;
                        };

                        // Helper function for safe integer conversion
                        $safeInt = function ($value) {
                            if ($value === '' || $value === null) {
                                return null;
                            }

                            return (int) $value;
                        };

                        // Helper function for safe float conversion
                        $safeFloat = function ($value) {
                            if ($value === '' || $value === null) {
                                return null;
                            }

                            return (float) $value;
                        };

                        // Helper function for date parsing
                        $safeDate = function ($value) {
                            if ($value === '' || $value === null) {
                                return null;
                            }
                            try {
                                return Carbon::parse($value);
                            } catch (\Exception $e) {
                                return null;
                            }
                        };

                        $records[] = [
                            'ecotox_id' => $safeValue($row['ecotox_id']),
                            'user_id' => $safeInt($row['user_id']),
                            'cred_final_score' => $safeFloat($row['cred_final_score']),
                            'cred_final_score_total' => $safeFloat($row['cred_final_score_total']),
                            'cred_final_close' => $safeInt($row['cred_final_close']),
                            'cred_final_evaluation' => $safeInt($row['cred_final_evaluation']),
                            'cred_final_comment' => $safeValue($row['cred_final_comment']),
                            'cred_final_date' => $safeDate($row['cred_final_date']),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    // Insert chunk
                    if (! empty($records)) {
                        EcotoxCredEvaluationFinal::insert($records);
                        $totalProcessed += count($records);
                        $this->command->info("Processed chunk {$key}: ".count($records).' records');
                    }
                });

            $this->command->info("EcotoxCredEvaluationFinal seeder completed successfully. Total records processed: {$totalProcessed}");

        } catch (\Exception $e) {
            $this->command->error('Error during seeding: '.$e->getMessage());
            $this->command->error('Stack trace: '.$e->getTraceAsString());
        } finally {
            // Re-enable foreign key checks
            Schema::enableForeignKeyConstraints();
        }
    }
}

// php artisan db:seed --class=EcotoxCredEvaluationFinalSeeder
