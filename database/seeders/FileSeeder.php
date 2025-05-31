<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;

class FileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Starting File seeding from old system data...');
        $target_table_name = 'files';
        $now = Carbon::now();
        $startTime = microtime(true);
        
        // Path to old system CSV file
        $csvPath = base_path() . '/database/seeders/seeds/dct_list.csv';
        
        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");
            return;
        }

        // Temporarily disable foreign key checks
        Schema::disableForeignKeyConstraints();
        
        // Use lower memory usage options for SimpleExcelReader
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
                        'id' => $this->safeInt($r['list_id']),
                        'name' => $this->safeString($r['list_title']),
                        'original_name' => $this->safeString($r['list_file']) ?: $this->safeString($r['list_name']),
                        'description' => $this->safeString($r['list_description']),
                        'file_path' => 'uploads/migrated/' . $this->safeString($r['list_file']),
                        'file_size' => 0,
                        'mime_type' => $this->getMimeType($r['list_file']),
                        'template_id' => null,
                        'database_entity_id' => 2,
                        'processing_notes' => $this->safeString($r['list_note']),
                        'uploaded_by' => null,
                        'uploaded_at' => $this->parseDate($r['list_date']) ?? $now,
                        'is_deleted' => $this->safeInt($r['list_deleted'], 0),
                        'project_id' => $this->safeInt($r['list_project_id']),
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
        
        $this->command->info('File seeding completed!');
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

    /**
     * Helper function to safely convert empty strings to default string
     */
    private function safeString($value, $default = null)
    {
        if ($value === null || $value === '') {
            return $default;
        }
        return (string) $value;
    }

    /**
     * Helper function to parse date strings
     */
    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }
        
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * Get mime type based on file extension
     */
    private function getMimeType($fileName)
    {
        if (empty($fileName)) {
            return 'application/octet-stream';
        }

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}

// To run this seeder:
// php artisan db:seed --class=FileSeeder