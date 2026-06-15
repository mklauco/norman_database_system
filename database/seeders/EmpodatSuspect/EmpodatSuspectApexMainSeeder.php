<?php

namespace Database\Seeders\EmpodatSuspect;

use Database\Seeders\EmpodatSuspect\Traits\LoadsSubstanceCaches;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpodatSuspectApexMainSeeder extends Seeder
{
    use LoadsSubstanceCaches;
    use WithoutModelEvents;

    // Test mode - set to null for full processing
    protected ?int $limitRows = null;

    // File tracking - set this to the file_id from the 'files' table
    protected ?int $fileId = 10007;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'empodat_suspect_main';

        $this->command->info('Processing LIFE APEX data for empodat_suspect_main table...');
        $this->command->warn('Note: This seeder adds to existing data. To start fresh, truncate tables manually.');

        $this->command->info('Loading lookup tables into cache...');
        $this->loadLookupCaches();

        // Disable Telescope during seeding to prevent memory issues
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
            $this->command->info('Telescope recording stopped for memory optimization');
        }

        // Disable query logging for performance
        DB::connection()->disableQueryLog();

        // Disable foreign key checks temporarily for faster inserts (PostgreSQL)
        DB::statement('SET session_replication_role = replica;');
        DB::statement('SET synchronous_commit = off;');

        $path = storage_path('app/public/empodat_suspect/OK_LIFE APEX_suspect screening results_ng g wet weight_333.csv');

        if (! file_exists($path)) {
            $this->command->error("CSV file not found: {$path}");

            return;
        }

        if ($this->limitRows) {
            $this->command->warn("TEST MODE: Processing only first {$this->limitRows} rows");
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
            // Remove UTF-8 BOM if present
            $h = str_replace("\xEF\xBB\xBF", '', $h);

            return trim($h);
        }, $header);

        $this->command->info('CSV Header (first 10 columns): '.implode(', ', array_slice($header, 0, 10)));
        $this->command->info('Total columns in header: '.count($header));

        // Identify station columns (columns after "Units")
        $stationColumns = $this->identifyStationColumns($header);
        $this->command->info('Identified '.count($stationColumns).' station columns');

        $batch = [];
        $substances = [];
        $batchSize = 5000;
        $rowCount = 0;
        $recordCount = 0;
        $skippedRows = 0;
        $progressInterval = 100;
        $startTime = microtime(true);
        $lastProgressTime = $startTime;

        // Increase PHP memory limit and execution time for large imports
        ini_set('memory_limit', '16G'); // Increased from 2GB to 16GB
        ini_set('max_execution_time', '7200'); // 2 hours

        // Start transaction for better performance
        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                // Test mode row limit
                if ($this->limitRows && $rowCount >= $this->limitRows) {
                    break;
                }

                // Combine header with row
                if (count($row) !== count($header)) {
                    if ($skippedRows < 10) {
                        $this->command->warn('Row '.($rowCount + $skippedRows + 1).' column count mismatch: expected '.count($header).', got '.count($row));
                    }
                    $skippedRows++;

                    continue;
                }

                $data = array_combine($header, $row);
                if ($data === false) {
                    if ($skippedRows < 10) {
                        $this->command->error('Failed to combine header and row at line '.($rowCount + $skippedRows + 1));
                    }
                    $skippedRows++;

                    continue;
                }

                $substanceNormanId = trim((string) ($data['NORMAN_ID'] ?? ''));
                $substanceName = trim((string) ($data['Name'] ?? ''));
                if ($substanceNormanId !== '' && $substanceName !== '') {
                    $substances[$substanceNormanId.'|'.$substanceName] ??= [
                        'norman_id' => $substanceNormanId,
                        'name' => $substanceName,
                        'file_id' => $this->fileId,
                    ];
                }

                try {
                    $processedRecords = $this->processRow($data, $stationColumns);
                    if ($processedRecords) {
                        foreach ($processedRecords as $record) {
                            $batch[] = $record;
                            $recordCount++;
                        }
                        $rowCount++;
                    }
                } catch (\Exception $e) {
                    // Only show first 10 errors to avoid spam
                    if ($skippedRows < 10) {
                        $this->command->error('Error processing row '.($rowCount + $skippedRows + 1).': '.$e->getMessage());
                    }
                    $skippedRows++;

                    continue;
                }

                // Insert batch when it reaches the batch size
                if (count($batch) >= $batchSize) {
                    DB::table($target_table_name)->insert($batch);
                    unset($batch);
                    $batch = [];

                    // Force garbage collection periodically
                    if ($rowCount % 1000 === 0) {
                        gc_collect_cycles();
                    }

                    // Report progress less frequently with timing
                    if ($rowCount % $progressInterval === 0) {
                        $currentTime = microtime(true);
                        $batchDuration = round($currentTime - $lastProgressTime, 2);
                        $totalDuration = round($currentTime - $startTime, 2);
                        $this->command->info("Processed {$rowCount} compounds ({$recordCount} records)... (batch: {$batchDuration}s, total: {$totalDuration}s)");
                        $lastProgressTime = $currentTime;
                    }
                }
            }

            // Insert remaining records
            if (! empty($batch)) {
                DB::table($target_table_name)->insert($batch);
                unset($batch);
                $batch = [];
            }

            if (! empty($substances)) {
                foreach (array_chunk(array_values($substances), 5000) as $substanceChunk) {
                    DB::table('empodat_suspect_substances')->insert($substanceChunk);
                }
                $this->command->info('Inserted '.count($substances).' unique substances (collected in main pass)');
            }

            DB::commit();

            // Clear memory
            gc_collect_cycles();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        $totalTime = round(microtime(true) - $startTime, 2);
        $avgPerRow = $rowCount > 0 ? round($totalTime / $rowCount * 1000, 2) : 0;
        $rowsPerSecond = $rowCount > 0 ? round($rowCount / $totalTime, 2) : 0;

        $this->command->info("Successfully seeded {$recordCount} records from {$rowCount} compounds into {$target_table_name} table in {$totalTime}s");
        $this->command->info("Performance: {$avgPerRow}ms/row ({$rowsPerSecond} rows/second)");
        if ($skippedRows > 0) {
            $this->command->warn("Skipped {$skippedRows} rows due to errors.");
        }

        // Re-enable foreign key checks (PostgreSQL)
        DB::statement('SET session_replication_role = default;');

        // Re-enable query logging
        DB::connection()->enableQueryLog();

        // Re-enable Telescope
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::startRecording();
            $this->command->info('Telescope recording re-enabled');
        }

        $this->command->info("All records seeded with file_id: {$this->fileId}");

        $this->validateSubstanceIds($this->fileId);
    }

    /**
     * Identify which columns contain station data
     * Returns array of ['column_name' => 'mapping_id']
     */
    protected function identifyStationColumns(array $header): array
    {
        $stationColumns = [];
        $startCollecting = false;

        foreach ($header as $columnName) {
            // Start collecting after "Units" column
            if ($columnName === 'Units') {
                $startCollecting = true;

                continue;
            }

            if ($startCollecting) {
                // Look up this column name in the station mapping cache
                if (isset($this->stationMappingCache[$columnName])) {
                    $mappingData = $this->stationMappingCache[$columnName];
                    $stationColumns[$columnName] = $mappingData;
                } else {
                    // Column not found in mapping - skip it
                    $this->command->warn("Station column not found in mapping: {$columnName}");
                }
            }
        }

        return $stationColumns;
    }

    /**
     * Process a single row from CSV
     * Returns array of records (one per non-NA station value)
     */
    protected function processRow(array $data, array $stationColumns): ?array
    {
        $normanId = $data['NORMAN_ID'] ?? null;
        $ip = $this->cleanString($data['IP'] ?? null);
        $ipMax = $this->cleanDouble($data['IP_max'] ?? null);
        $basedOnHRMSLibrary = $this->cleanBoolean($data['BasedonHRMSLibrary'] ?? null);
        $units = $this->cleanString($data['Units'] ?? null);

        if (empty($normanId)) {
            return null;
        }

        // Strip "NS" prefix from NORMAN_ID to get the code
        // Example: "NS00000001" -> "00000001"
        $code = preg_replace('/^NS/', '', $normanId);

        // Look up substance_id using the code (handles both old and new codes)
        $substanceId = $this->resolveSubstanceId($code);

        $records = [];

        // Process each station column
        foreach ($stationColumns as $columnName => $mappingData) {
            $concentrationValue = $data[$columnName] ?? null;

            // Skip only truly empty values (null or empty string)
            if ($concentrationValue === null || $concentrationValue === '') {
                continue;
            }

            // Determine if concentration is numeric
            $concentration = $this->cleanDouble($concentrationValue);
            $isNumeric = ($concentration !== null);

            // Create record for ALL values (don't skip non-numeric)
            $records[] = [
                'file_id' => $this->fileId,
                'substance_id' => $substanceId,
                'xlsx_station_mapping_id' => $mappingData['mapping_id'],
                'station_id' => $mappingData['station_id'],
                'concentration' => $concentration,
                'is_numeric_concentration' => $isNumeric,
                'ip' => $ip,
                'ip_max' => $ipMax,
                'based_on_hrms_library' => $basedOnHRMSLibrary,
                'units' => $units,
            ];
        }

        return $records;
    }

    // Data cleaning methods
    protected function cleanString(?string $value): ?string
    {
        if ($value === null || $value === '' || $value === 'NA') {
            return null;
        }
        $cleaned = trim($value);

        return $cleaned === '' || $cleaned === 'NA' ? null : $cleaned;
    }

    protected function cleanDouble(?string $value): ?float
    {
        if ($value === null || $value === '' || $value === 'NA') {
            return null;
        }
        $cleaned = trim($value);
        if ($cleaned === '' || $cleaned === 'NA') {
            return null;
        }

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    protected function cleanBoolean(?string $value): ?bool
    {
        if ($value === null || $value === '' || $value === 'NA') {
            return null;
        }
        $cleaned = strtoupper(trim($value));
        if ($cleaned === 'TRUE' || $cleaned === '1' || $cleaned === 'YES') {
            return true;
        }
        if ($cleaned === 'FALSE' || $cleaned === '0' || $cleaned === 'NO') {
            return false;
        }

        return null;
    }

    protected function isNullOrNA($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $cleaned = trim($value);

        return $cleaned === '' || $cleaned === 'NA';
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectApexMainSeeder
