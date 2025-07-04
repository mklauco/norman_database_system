<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelReader;

class BioassayMonitorXSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    function getCsvFiles($directory)
    {
        // Check if directory exists
        if (!is_dir($directory)) {
            return [];
        }

        // Get all files in the directory
        $allFiles = scandir($directory);

        // Filter to only include CSV files
        $csvFiles = array_filter($allFiles, function($file) use ($directory) {
            $fullPath = rtrim($directory, '/') . '/' . $file;
            // Skip . and .. directories
            if ($file === '.' || $file === '..') {
                return false;
            }
            // Check if it's a file and has .csv extension
            return is_file($fullPath) && pathinfo($file, PATHINFO_EXTENSION) === 'csv';
        });

        // Skip the files in this array
        $skip_files = [
        ];

        // Filter out the files to skip
        $csvFiles = array_filter($csvFiles, function($file) use ($skip_files) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            return !in_array($filename, $skip_files);
        });

        // Return filenames without the .csv extension
        return array_map(function($file) {
            return pathinfo($file, PATHINFO_FILENAME);
        }, array_values($csvFiles));
    }

    public function run(): void
    {
        $this->command->info('Starting bioassay Monitor X seeding...');
        $folder = base_path() . '/database/seeders/seeds/bioassay_tables/bioassay_monitor_x';
        $tables = $this->getCsvFiles($folder);
        $files_with_text_ids = [
            'monitor_x_yes_no',
            'monitor_x_yes_no_na_nr',
            'monitor_x_yes_no_na_nr_other',
        ];
        foreach ($tables as $table) {
            $target_table_name = $table;
            DB::table($target_table_name)->delete();
            $now = Carbon::now();

            $path = $folder . '/' . $target_table_name . '.csv';

            // Check if file exists
            if (!file_exists($path)) {
                $this->command->error("File not found: $path");
                continue;
            }

            // Temporarily disable foreign key checks
            Schema::disableForeignKeyConstraints();

            try {
                // Use lower memory usage options for SimpleExcelReader
                $reader = SimpleExcelReader::create($path)
                    ->useDelimiter(',')
                    ->headersToSnakeCase(false);

                // Use lazy collection to process the CSV file in chunks without loading it all
                $chunkSize = 50; // Increased from 100
                $reader->getRows()
                    ->chunk($chunkSize)
                    ->each(function ($rows, $key) use ($target_table_name, $now, $files_with_text_ids) {
                        $records = [];
                        if (in_array($target_table_name, $files_with_text_ids)) {
                            $k = 1;
                            foreach ($rows as $r) {
                                $ordering = isset($r['ordering']) && $r['ordering'] !== '' ? (int)$r['ordering'] : null;
                                $records[] = [
                                    'id' => $k++,
                                    'name' => $r['id'] ?? '',
                                    'ordering' => $ordering ,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }
                        } else {
                            foreach ($rows as $r) {
                                $ordering = isset($r['ordering']) && $r['ordering'] !== '' ? (int)$r['ordering'] : null;
                                $records[] = [
                                    'id' => $r['id'] ?? null,
                                    'name' => $r['name'] ?? '',
                                    'ordering' => $ordering ,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }
                        }

                        // Only attempt to insert if there are records
                        if (!empty($records)) {
                            try {
                                DB::table($target_table_name)->insert($records);
                                $this->command->info("Processed chunk " . ($key + 1) . " with " . count($records) . " records for table: $target_table_name");
                            } catch (\Exception $e) {
                                $this->command->error("Error inserting into $target_table_name: " . $e->getMessage());
                            }
                        }
                    });
            } catch (\Exception $e) {
                $this->command->error("Error processing file $path: " . $e->getMessage());
            } finally {
                // Re-enable foreign key checks
                Schema::enableForeignKeyConstraints();
            }
        }



        $this->command->info('Bioassay field study seeding completed!');
    }

}

// php artisan db:seed --class="BioassayMonitorXSeeder"
