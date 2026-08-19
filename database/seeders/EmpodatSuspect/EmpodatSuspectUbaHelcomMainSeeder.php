<?php

namespace Database\Seeders\EmpodatSuspect;

use App\Services\EmpodatSuspect\SeedRowLimiter;
use Database\Seeders\EmpodatSuspect\Traits\CapturesUnresolvedSubstanceRows;
use Database\Seeders\EmpodatSuspect\Traits\LoadsSubstanceCaches;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class EmpodatSuspectUbaHelcomMainSeeder extends Seeder
{
    use CapturesUnresolvedSubstanceRows;
    use LoadsSubstanceCaches;
    use WithoutModelEvents;

    // Test mode - set to null for full processing
    protected ?int $limitRows = null;

    // File tracking - set this to the file_id from the 'files' table
    protected ?int $fileId = 10008;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Increase PHP memory limit and execution time for large imports - MUST be set early
        ini_set('memory_limit', '16G');
        ini_set('max_execution_time', '7200'); // 2 hours
        $this->command->info('Memory limit set to 16GB, execution time to 2 hours');

        $limiter = app(SeedRowLimiter::class);
        $this->command->info($limiter->banner());

        $target_table_name = 'empodat_suspect_main';

        $this->command->info('Processing UBA-HELCOM data for empodat_suspect_main table...');
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

        $path = storage_path('app/public/empodat_suspect/OK_UBA-HELCOM_suspect screening results_ng g wet weight_1204.xlsx');

        if (! file_exists($path)) {
            $this->command->error("Excel file not found: {$path}");

            return;
        }

        if ($this->limitRows) {
            $this->command->warn("TEST MODE: Processing only first {$this->limitRows} rows");
        }

        $this->command->info('Reading Excel file...');

        // Read Excel file using SimpleExcelReader and convert to array
        $reader = SimpleExcelReader::create($path);
        $this->command->info('Loading Excel data into memory...');
        $rowsArray = $reader->getRows()->toArray();

        if (empty($rowsArray)) {
            $this->command->error('Excel file contains no data');

            return;
        }

        $this->command->info('Loaded '.count($rowsArray).' rows from Excel file');

        // Get header from first row
        $header = array_keys($rowsArray[0]);

        // Clean header - remove BOM, trim spaces
        $header = array_map(function ($h) {
            // Remove UTF-8 BOM if present
            $h = str_replace("\xEF\xBB\xBF", '', $h);

            return trim($h);
        }, $header);

        $this->command->info('Excel Header (first 10 columns): '.implode(', ', array_slice($header, 0, 10)));
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
        $capped = false;
        $progressInterval = 10; // Report every 10 rows for better visibility
        $startTime = microtime(true);
        $lastProgressTime = $startTime;

        // Start transaction for better performance
        DB::beginTransaction();

        try {
            // Process all rows
            foreach ($rowsArray as $row) {
                // Test mode row limit
                if ($this->limitRows && $rowCount >= $this->limitRows) {
                    break;
                }

                $substanceNormanId = trim((string) ($row['NORMAN_ID'] ?? ''));
                $substanceName = trim((string) ($row['Name'] ?? ''));
                if ($substanceNormanId !== '' && $substanceName !== '') {
                    $substances[$substanceNormanId.'|'.$substanceName] ??= [
                        'norman_id' => $substanceNormanId,
                        'name' => $substanceName,
                        'file_id' => $this->fileId,
                    ];
                }

                try {
                    $processedRecords = $this->processRow($row, $stationColumns);
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

                // Row cap: check only here, at the source-row boundary — never inside the
                // station-column loop above — so a capped run still samples every station
                // for the rows it does keep. $recordCount already counts rows flushed so
                // far plus the pending batch.
                if ($limiter->reached($recordCount)) {
                    $capped = true;

                    break;
                }

                // Report progress
                if ($rowCount % $progressInterval === 0) {
                    $currentTime = microtime(true);
                    $batchDuration = round($currentTime - $lastProgressTime, 2);
                    $totalDuration = round($currentTime - $startTime, 2);
                    $this->command->info("Processed {$rowCount} compounds ({$recordCount} records)... (batch: {$batchDuration}s, total: {$totalDuration}s)");
                    $lastProgressTime = $currentTime;
                }

                // Insert batch when it reaches the batch size
                if (count($batch) >= $batchSize) {
                    $this->insertMainBatch($target_table_name, $batch);
                    unset($batch);
                    $batch = [];

                    // Force garbage collection periodically
                    if ($rowCount % 1000 === 0) {
                        gc_collect_cycles();
                    }
                }
            }

            // Insert remaining records
            if (! empty($batch)) {
                $this->insertMainBatch($target_table_name, $batch);
                unset($batch);
                $batch = [];
            }

            if (! empty($substances)) {
                foreach (array_chunk(array_values($substances), 5000) as $substanceChunk) {
                    DB::table('empodat_suspect_substances')->insert($substanceChunk);
                }
                $this->command->info('Inserted '.count($substances).' unique substances (collected in main pass)');
            }

            $this->persistUnresolvedRowIds($this->fileId);

            DB::commit();

            // Clear memory
            gc_collect_cycles();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        } finally {
            DB::statement('SET session_replication_role = default;');
            DB::connection()->enableQueryLog();
            if (class_exists(\Laravel\Telescope\Telescope::class)) {
                \Laravel\Telescope\Telescope::startRecording();
            }
        }

        $totalTime = round(microtime(true) - $startTime, 2);
        $avgPerRow = $rowCount > 0 ? round($totalTime / $rowCount * 1000, 2) : 0;
        $rowsPerSecond = $rowCount > 0 ? round($rowCount / $totalTime, 2) : 0;

        $this->command->info("Successfully seeded {$recordCount} records from {$rowCount} compounds into {$target_table_name} table in {$totalTime}s");
        $this->command->info("Performance: {$avgPerRow}ms/row ({$rowsPerSecond} rows/second)");
        if ($skippedRows > 0) {
            $this->command->warn("Skipped {$skippedRows} rows due to errors.");
        }
        if ($capped) {
            $this->command->warn("Row cap reached ({$limiter->banner()}) — stopped early after {$recordCount} rows for this file.");
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
     * Process a single row from Excel
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

        if ($substanceId === null && $records !== []) {
            foreach ($records as $index => $record) {
                $records[$index][self::UNRESOLVED_TAG] = $normanId;
            }
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

    protected function cleanDouble($value): ?float
    {
        if ($value === null || $value === '' || $value === 'NA') {
            return null;
        }

        // Convert to string if not already
        $strValue = (string) $value;
        $cleaned = trim($strValue);

        if ($cleaned === '' || $cleaned === 'NA') {
            return null;
        }

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    protected function cleanBoolean($value): ?bool
    {
        if ($value === null || $value === '' || $value === 'NA') {
            return null;
        }

        // Convert to string for comparison
        $strValue = (string) $value;
        $cleaned = strtoupper(trim($strValue));

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

        // Convert to string for comparison
        $strValue = (string) $value;
        $cleaned = trim($strValue);

        return $cleaned === '' || $cleaned === 'NA';
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectUbaHelcomMainSeeder
