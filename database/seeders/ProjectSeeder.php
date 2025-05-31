<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Starting Project seeding...');
        $target_table_name = 'projects';
        $now = Carbon::now();
        $startTime = microtime(true);
        
        // Path to CSV file
        $csvPath = base_path() . '/database/seeders/seeds/dct_list_project.csv';
        
        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");
            return;
        }

        // Temporarily disable foreign key checks
        Schema::disableForeignKeyConstraints();
        
        // Use SimpleExcelReader
        $reader = SimpleExcelReader::create($csvPath)
            ->useDelimiter(',')
            ->headersToSnakeCase(false);
        
        // Process the CSV file in chunks
        $chunkSize = 100;
        $reader->getRows()
            ->chunk($chunkSize)
            ->each(function ($rows, $key) use ($target_table_name, $now, $startTime) {
                $chunkStartTime = microtime(true);
                $records = [];
                
                foreach ($rows as $r) {
                    $records[] = [
                        'id' => $this->safeInt($r['list_project_id']),
                        'name' => $this->safeString($r['list_project_title']),
                        'abbreviation' => $this->generateAbbreviation($r['list_project_title']),
                        'description' => $this->safeString($r['list_project_description']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                
                // Insert records
                if (!empty($records)) {
                    try {
                        DB::table($target_table_name)->insert($records);
                        
                        $chunkEndTime = microtime(true);
                        $chunkElapsedTime = round($chunkEndTime - $chunkStartTime, 2);
                        $totalElapsedTime = round($chunkEndTime - $startTime, 2);
                        
                        $this->command->info("Processed chunk " . ($key + 1) . " with " . count($records) . " records. Chunk time: {$chunkElapsedTime}s, Total elapsed: {$totalElapsedTime}s");
                    } catch (\Exception $e) {
                        $this->command->error("Error in chunk " . ($key + 1) . ": " . $e->getMessage());
                    }
                }
            });
        
        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();
        
        $this->command->info('Project seeding completed!');
    }

    /**
     * Helper function to safely convert empty strings to null
     */
    private function safeString($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (string) $value;
    }

    /**
     * Generate abbreviation from project title
     */
    private function generateAbbreviation($title)
    {
        if (empty($title)) {
            return null;
        }

        // Remove common words and get first letters
        $commonWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = preg_split('/\s+/', strtolower(trim($title)));
        $words = array_filter($words, function($word) use ($commonWords) {
            return !in_array($word, $commonWords) && strlen($word) > 1;
        });

        // Take first letter of each significant word, max 6 characters
        $abbreviation = '';
        foreach (array_slice($words, 0, 6) as $word) {
            $abbreviation .= strtoupper(substr($word, 0, 1));
        }

        return $abbreviation ?: null;
    }
        /**
     * Helper function to safely convert empty strings to null for integer fields
     */
    private function safeInt($value, $default = null)
    {
        if ($value === '' || $value === null) {
            return $default;
        }
        return (int) $value;
    }
}

// To run this seeder:
// php artisan db:seed --class=ProjectSeeder