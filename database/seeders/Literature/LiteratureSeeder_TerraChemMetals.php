<?php

declare(strict_types=1);

namespace Database\Seeders\Literature;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LiteratureSeeder_TerraChemMetals extends Seeder
{
    use WithoutModelEvents;

    // Lookup caches (lowercase key => id)
    protected array $speciesCache = [];

    protected array $tissuesCache = [];

    protected array $sexCache = [];

    protected array $lifeStagesCache = [];

    protected array $concentrationUnitsCache = [];

    protected array $commonNamesCache = [];

    protected array $matricesCache = [];

    protected array $substanceCache = [];

    protected array $countriesCache = [];

    protected array $typeOfNumericQuantitiesCache = [];

    // Test mode - set to null for full processing
    protected ?int $limitRows = null;

    protected int $fileId = 9003;

    // Skipped rows log
    protected array $skippedRows = [];

    protected string $skippedRowsLogPath;

    // New substances log
    protected array $newSubstances = [];

    protected string $newSubstancesLogPath;

    /**
     * Per-sheet column index maps (0-based).
     * Columns [0]-[8] are common across all sheets.
     * From [9] onward, positions differ per sheet.
     *
     * @var array<string, array<string, int|null>>
     */
    protected const SHEET_COLUMNS = [
        'Sheet-1' => [
            // Common columns [0]-[8]
            'chemical_name' => 1,
            'cas' => 2,
            'individual_id' => 3,
            'reported_concentration' => 4,
            'sd' => 5,
            'type_of_numeric_quantity' => 6,
            'range' => 7,
            'concentration_units' => 8,
            // Sheet-specific from [9]
            'sample_matrix' => 9,
            'tissue' => 10,
            'remark_matrix' => 11,
            'sample_preparation' => 12,
            'analytical_method' => 13,
            'storage_temp_c' => 14,
            'lod' => 15,
            'loq' => 16,
            'species_common' => 17,
            'suggested_name' => 18,
            'latin_name' => 19,
            'kingdom' => 20,
            'class_phyl' => 21,
            'order' => 22,
            'life_stage' => 23,
            'age' => 24,
            'sex' => 25,
            'x_of_subsamples' => 26,
            'x_of_replicates' => 27,
            'sampling_date' => 28,
            'biota_weight' => 29,
            'biota_size' => 30,
            'fat_content' => 31,
            'protein_content' => 32,
            'water_content' => 33,
            'health_status' => 34,
            'trophic_level_diet' => 40,
            'delta_13c' => 41,
            'd15n' => 42,
            'remark_isotope' => 43,
            'cause_of_death' => 46,
            'country' => 47,
            'region_city' => 48,
            'lat_lon' => 49,
            'habitat_type' => 50,
            'temperature' => 54,
            'remark_2' => 55,
            'reported_distance_to_industry' => 59,
            'title_author_year_doi' => 60,
        ],
        'Sheet-2' => [
            // Common columns [0]-[8]
            'chemical_name' => 1,
            'cas' => 2,
            'individual_id' => 3,
            'reported_concentration' => 4,
            'sd' => 5,
            'type_of_numeric_quantity' => 6,
            'range' => 7,
            'concentration_units' => 8,
            // Sheet-specific from [9] - tissue and matrix are swapped
            'tissue' => 9,
            'sample_matrix' => 10,
            'remark_matrix' => 11,
            'sample_preparation' => 12,
            'analytical_method' => 13,
            'storage_temp_c' => 14,
            'lod' => 15,
            'loq' => 16,
            'species_common' => 17,
            'suggested_name' => 18,
            'latin_name' => 19,
            'kingdom' => 20,
            'class_phyl' => 21,
            'order' => 22,
            'life_stage' => 23,
            'age' => 24,
            'sex' => 25,
            'x_of_subsamples' => 26,
            'x_of_replicates' => 27,
            'sampling_date' => 28,
            'biota_weight' => 29,
            'biota_size' => 30,
            'fat_content' => null, // Not present in Sheet-2
            'protein_content' => 32, // Combined "Protein content/Fat content"
            'water_content' => 33,
            'health_status' => 34,
            'trophic_level_diet' => 40,
            'delta_13c' => 41,
            'd15n' => 42,
            'remark_isotope' => 43,
            'cause_of_death' => 46,
            'country' => 47,
            'region_city' => 48,
            'lat_lon' => 49,
            'habitat_type' => 50,
            'temperature' => 54,
            'remark_2' => 55,
            'reported_distance_to_industry' => 59,
            'title_author_year_doi' => 60,
        ],
        'Sheet-3' => [
            // Common columns [0]-[8]
            'chemical_name' => 1,
            'cas' => 2,
            'individual_id' => 3,
            'reported_concentration' => 4,
            'sd' => 5,
            'type_of_numeric_quantity' => 6,
            'range' => 7,
            'concentration_units' => 8,
            // Sheet-specific from [9] - Birds, 59 columns, no age, combined lod/loq
            'sample_matrix' => 9,
            'tissue' => 10,
            'remark_matrix' => 11,
            'sample_preparation' => 12,
            'analytical_method' => 13,
            'storage_temp_c' => 14,
            'lod' => 15, // Combined lod/loq
            'loq' => null, // No separate loq
            'species_common' => 16,
            'suggested_name' => 17,
            'latin_name' => 18,
            'kingdom' => 19,
            'class_phyl' => 20,
            'order' => 21,
            'life_stage' => 22,
            'age' => null, // No age column in Sheet-3
            'sex' => 23,
            'x_of_subsamples' => 24,
            'x_of_replicates' => 25,
            'sampling_date' => 26,
            'biota_weight' => 27,
            'biota_size' => 28,
            'fat_content' => null,
            'protein_content' => null,
            'water_content' => null,
            'health_status' => 30,
            'trophic_level_diet' => 36,
            'delta_13c' => 37,
            'd15n' => 38,
            'remark_isotope' => 39,
            'cause_of_death' => 42,
            'country' => 43,
            'region_city' => 44,
            'lat_lon' => 45,
            'habitat_type' => 46,
            'temperature' => 50,
            'remark_2' => 51,
            'reported_distance_to_industry' => 55,
            'title_author_year_doi' => 56,
        ],
    ];

    /**
     * CSV files to process, in order.
     *
     * @var array<string, string>
     */
    protected const CSV_FILES = [
        'Sheet-1' => 'DCT_BIOTA_TerraChem_Naturalis_LiteratureData_metals_08082024_v2-Sheet-1.csv',
        'Sheet-2' => 'DCT_BIOTA_TerraChem_Naturalis_LiteratureData_metals_08082024_v2-Sheet-2.csv',
        'Sheet-3' => 'DCT_BIOTA_TerraChem_Naturalis_LiteratureData_metals_08082024_v2-Sheet-3.csv',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ini_set('memory_limit', '4G');
        ini_set('max_execution_time', '3600');
        $this->command->info('Memory limit set to 4GB, execution time to 1 hour');

        $targetTable = 'literature_temp_main';

        $this->skippedRowsLogPath = base_path('database/seeders/seeds/literature/terrachem_metals_skipped_rows.log');
        $this->newSubstancesLogPath = base_path('database/seeders/seeds/literature/terrachem_metals_new_substances.log');

        // Delete only records for this file_id (idempotent re-runs)
        $deleted = DB::table($targetTable)->where('file_id', $this->fileId)->delete();
        $this->command->info("Deleted {$deleted} existing records for file_id = {$this->fileId}");

        // Reset PostgreSQL sequence to avoid primary key conflicts
        $maxId = DB::table($targetTable)->max('id') ?? 0;
        DB::statement("SELECT setval(pg_get_serial_sequence('{$targetTable}', 'id'), ".max($maxId, 1).')');
        $this->command->info("Reset sequence to {$maxId}");

        // Reset sequences on tables where we auto-create records
        foreach (['susdat_substances', 'list_species'] as $table) {
            $tableMax = DB::table($table)->max('id') ?? 0;
            if ($tableMax > 0) {
                DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), {$tableMax})");
            }
        }
        $this->command->info('Reset sequences for susdat_substances and list_species');

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

        if ($this->limitRows) {
            $this->command->warn("TEST MODE: Processing only first {$this->limitRows} rows per sheet");
        }

        $totalRowCount = 0;
        $totalSkippedCount = 0;
        $startTime = microtime(true);

        foreach (self::CSV_FILES as $sheetKey => $filename) {
            $path = base_path("database/seeders/seeds/literature/{$filename}");

            if (! file_exists($path)) {
                $this->command->error("CSV file not found: {$path}");

                continue;
            }

            $this->command->info("Processing {$sheetKey}: {$filename}");

            [$rowCount, $skippedCount] = $this->processSheet($path, $sheetKey, $targetTable);
            $totalRowCount += $rowCount;
            $totalSkippedCount += $skippedCount;

            $this->command->info("{$sheetKey} done: {$rowCount} rows inserted, {$skippedCount} skipped");
            gc_collect_cycles();
        }

        // Write logs
        $this->writeSkippedRowsLog();
        $this->writeNewSubstancesLog();

        $totalTime = round(microtime(true) - $startTime, 2);
        $rowsPerSecond = $totalRowCount > 0 ? round($totalRowCount / $totalTime, 2) : 0;

        $this->command->info("Successfully seeded {$totalRowCount} records into {$targetTable} in {$totalTime}s");
        $this->command->info("Performance: {$rowsPerSecond} rows/second");

        if ($totalSkippedCount > 0) {
            $this->command->warn("Skipped {$totalSkippedCount} rows due to validation errors.");
            $this->command->warn("See log file: {$this->skippedRowsLogPath}");
        }

        if (! empty($this->newSubstances)) {
            $this->command->warn('Auto-created '.count($this->newSubstances).' new substances.');
            $this->command->warn("See log file: {$this->newSubstancesLogPath}");
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
    }

    /**
     * Process a single CSV sheet.
     *
     * @return array{int, int} [rowCount, skippedCount]
     */
    protected function processSheet(string $path, string $sheetKey, string $targetTable): array
    {
        $columnMap = self::SHEET_COLUMNS[$sheetKey];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            $this->command->error("Could not open file: {$path}");

            return [0, 0];
        }

        // Line 1: headers (skip, used for logging only)
        fgetcsv($handle);
        // Line 2: legacy row (skip)
        fgetcsv($handle);

        $batch = [];
        $batchSize = 500;
        $rowCount = 0;
        $skippedCount = 0;
        $lineNumber = 2; // We've read 2 lines already
        $progressInterval = 500;
        $lastProgressTime = microtime(true);
        $now = Carbon::now();

        DB::beginTransaction();

        try {
            while (($csvRow = fgetcsv($handle)) !== false) {
                $lineNumber++;

                // Test mode row limit
                if ($this->limitRows && $rowCount >= $this->limitRows) {
                    break;
                }

                try {
                    // Validate row
                    $validationResult = $this->validateRow($csvRow, $lineNumber, $sheetKey, $columnMap);
                    if ($validationResult !== true) {
                        $this->logSkippedRow($lineNumber, $sheetKey, $validationResult, $csvRow);
                        $skippedCount++;

                        continue;
                    }

                    // Process row
                    $record = $this->processRow($csvRow, $lineNumber, $sheetKey, $columnMap, $now);

                    if ($record) {
                        $batch[] = $record;
                        $rowCount++;

                        if (count($batch) >= $batchSize) {
                            DB::table($targetTable)->insert($batch);
                            unset($batch);
                            $batch = [];
                        }
                    }

                    // Report progress
                    if ($rowCount % $progressInterval === 0 && $rowCount > 0) {
                        $currentTime = microtime(true);
                        $batchDuration = round($currentTime - $lastProgressTime, 2);
                        $this->command->info("  {$sheetKey}: {$rowCount} rows processed (last batch: {$batchDuration}s)");
                        $lastProgressTime = $currentTime;

                        gc_collect_cycles();
                    }
                } catch (\Exception $e) {
                    $this->logSkippedRow($lineNumber, $sheetKey, 'Exception: '.$e->getMessage(), $csvRow);
                    $skippedCount++;

                    continue;
                }
            }

            // Insert remaining records
            if (! empty($batch)) {
                DB::table($targetTable)->insert($batch);
                unset($batch);
                $batch = [];
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);

            throw $e;
        }

        fclose($handle);

        return [$rowCount, $skippedCount];
    }

    /**
     * Load all lookup tables into memory for faster processing.
     */
    protected function loadLookupCaches(): void
    {
        // Load species by name_latin with lowercase keys
        $species = DB::table('list_species')
            ->whereNotNull('name_latin')
            ->select('id', 'name_latin')
            ->get();
        foreach ($species as $s) {
            $this->speciesCache[$this->normalizeForLookup($s->name_latin)] = $s->id;
        }
        $this->command->info('Loaded '.count($this->speciesCache).' species');

        // Load tissues by name with lowercase keys
        $tissues = DB::table('list_tissues')
            ->select('id', 'name')
            ->get();
        foreach ($tissues as $t) {
            $this->tissuesCache[$this->normalizeForLookup($t->name)] = $t->id;
        }
        $this->command->info('Loaded '.count($this->tissuesCache).' tissues');

        // Load sex by name with lowercase keys
        $sexes = DB::table('list_biota_sexs')
            ->select('id', 'name')
            ->get();
        foreach ($sexes as $s) {
            $this->sexCache[$this->normalizeForLookup($s->name)] = $s->id;
        }
        $this->command->info('Loaded '.count($this->sexCache).' sex types');

        // Load life stages by name with lowercase keys
        $lifeStages = DB::table('list_life_stages')
            ->select('id', 'name')
            ->get();
        foreach ($lifeStages as $ls) {
            $this->lifeStagesCache[$this->normalizeForLookup($ls->name)] = $ls->id;
        }
        $this->command->info('Loaded '.count($this->lifeStagesCache).' life stages');

        // Load concentration units (use unit-specific normalization)
        $concentrationUnits = DB::table('list_concentration_units')
            ->select('id', 'name')
            ->get();
        foreach ($concentrationUnits as $cu) {
            $this->concentrationUnitsCache[$this->normalizeUnitForLookup($cu->name)] = $cu->id;
        }
        $this->command->info('Loaded '.count($this->concentrationUnitsCache).' concentration units');

        // Load common names by name with lowercase keys
        $commonNames = DB::table('list_common_names')
            ->select('id', 'name')
            ->get();
        foreach ($commonNames as $cn) {
            $this->commonNamesCache[$this->normalizeForLookup($cn->name)] = $cn->id;
        }
        $this->command->info('Loaded '.count($this->commonNamesCache).' common names');

        // Load matrices by name with lowercase keys
        $matrices = DB::table('list_matrices')
            ->select('id', 'name')
            ->get();
        foreach ($matrices as $m) {
            $this->matricesCache[$this->normalizeForLookup($m->name)] = $m->id;
        }
        $this->command->info('Loaded '.count($this->matricesCache).' matrices');

        // Load susdat_substances mapping: code => id
        $substances = DB::table('susdat_substances')
            ->whereNotNull('code')
            ->select('id', 'code')
            ->get();
        foreach ($substances as $s) {
            $this->substanceCache[$s->code] = $s->id;
        }
        $this->command->info('Loaded '.count($this->substanceCache).' substances');

        // Load countries by name with lowercase keys
        $countries = DB::table('list_countries')
            ->select('id', 'name')
            ->get();
        foreach ($countries as $c) {
            $this->countriesCache[$this->normalizeForLookup($c->name)] = $c->id;
        }
        $this->command->info('Loaded '.count($this->countriesCache).' countries');

        // Load type of numeric quantities by name with lowercase keys
        $typeOfNumericQuantities = DB::table('list_type_of_numeric_quantities')
            ->select('id', 'name')
            ->get();
        foreach ($typeOfNumericQuantities as $tonq) {
            $this->typeOfNumericQuantitiesCache[$this->normalizeForLookup($tonq->name)] = $tonq->id;
        }
        $this->command->info('Loaded '.count($this->typeOfNumericQuantitiesCache).' type of numeric quantities');
    }

    /**
     * Validate a CSV row before processing.
     *
     * @return true|string True if valid, error message string if invalid
     */
    protected function validateRow(array $csvRow, int $lineNumber, string $sheetKey, array $columnMap): bool|string
    {
        // Check for completely empty rows
        $hasData = false;
        foreach ($csvRow as $value) {
            if (! $this->isSkipValue($value)) {
                $hasData = true;

                break;
            }
        }

        if (! $hasData) {
            return 'Empty row';
        }

        // Individual_ID must contain a Norman ID (starting with NS) or be empty
        $normanId = $csvRow[$columnMap['individual_id']] ?? null;
        if (! $this->isSkipValue($normanId)) {
            if (! is_string($normanId)) {
                return 'Invalid Norman ID type: expected string, got '.gettype($normanId).' ('.$normanId.')';
            }
            if (! str_starts_with(trim($normanId), 'NS')) {
                return 'Invalid Norman ID format: '.$normanId.' (must start with NS)';
            }
        }

        return true;
    }

    /**
     * Process a single CSV row into a database record.
     *
     * @param  array<string, int|null>  $columnMap
     */
    protected function processRow(array $csvRow, int $lineNumber, string $sheetKey, array $columnMap, Carbon $now): ?array
    {
        // Get value helper - returns null for out-of-range or null-mapped columns
        $col = function (string $key) use ($csvRow, $columnMap): ?string {
            $index = $columnMap[$key] ?? null;
            if ($index === null) {
                return null;
            }

            return $csvRow[$index] ?? null;
        };

        // Concentration unit lookup
        $concentrationUnit = $col('concentration_units');
        $unitLookup = $this->lookupConcentrationUnit($concentrationUnit);
        $isWetWeight = $this->isWetWeightUnit($concentrationUnit);

        // Concentration value handling
        $concentrationValue = $col('reported_concentration');
        $reportedConcentration = null;
        $concentrationLevel = null;
        $wwConcNg = null;

        if (! $this->isSkipValue($concentrationValue)) {
            $strValue = trim((string) $concentrationValue);
            if (is_numeric($strValue)) {
                $reportedConcentration = $strValue;
                if ($isWetWeight) {
                    $wwConcNg = (float) $strValue;
                }
            } else {
                $concentrationLevel = $strValue;
            }
        }

        // Parse latitude/longitude from combined field
        $latLon = $col('lat_lon');
        $latitude = null;
        $longitude = null;
        if (! $this->isSkipValue($latLon)) {
            [$latitude, $longitude] = $this->parseLatLon($latLon);
        }

        // Parse sampling date
        $samplingDate = $col('sampling_date');
        $samplingYear = null;
        $samplingMonth = null;
        if (! $this->isSkipValue($samplingDate)) {
            [$samplingYear, $samplingMonth] = $this->parseSamplingDate($samplingDate);
        }

        // Build notes from remark columns
        $notesParts = [];
        if (! $this->isSkipValue($col('remark_matrix'))) {
            $notesParts[] = 'Matrix: '.$this->cleanString($col('remark_matrix'));
        }
        if (! $this->isSkipValue($col('remark_2'))) {
            $notesParts[] = 'Env: '.$this->cleanString($col('remark_2'));
        }
        if (! $this->isSkipValue($col('remark_isotope'))) {
            $notesParts[] = 'Isotope: '.$this->cleanString($col('remark_isotope'));
        }
        if (! $this->isSkipValue($col('cause_of_death'))) {
            $notesParts[] = 'Cause of death: '.$this->cleanString($col('cause_of_death'));
        }
        if (! $this->isSkipValue($col('sample_preparation'))) {
            $notesParts[] = 'Sample prep: '.$this->cleanString($col('sample_preparation'));
        }
        if (! $this->isSkipValue($col('biota_weight'))) {
            $notesParts[] = 'Weight: '.$this->cleanString($col('biota_weight'));
        }
        if (! $this->isSkipValue($col('biota_size'))) {
            $notesParts[] = 'Size: '.$this->cleanString($col('biota_size'));
        }
        if (! $this->isSkipValue($col('temperature'))) {
            $notesParts[] = 'Temp: '.$this->cleanString($col('temperature'));
        }
        $comment = ! empty($notesParts) ? implode(' | ', $notesParts) : null;

        // Parse trophic level and diet from combined field
        $trophicDiet = $col('trophic_level_diet');
        $trophicLevel = null;
        $diet = null;
        if (! $this->isSkipValue($trophicDiet)) {
            $trophicLevel = $this->cleanString($trophicDiet);
            $diet = $this->cleanString($trophicDiet);
        }

        // Parse age
        $age = $col('age');
        $ageInDays = null;
        if (! $this->isSkipValue($age)) {
            $ageInDays = $this->cleanString($age);
        }

        // Parse water content
        $waterContent = null;
        $waterContentRaw = $col('water_content');
        if (! $this->isSkipValue($waterContentRaw)) {
            $cleaned = trim((string) $waterContentRaw);
            if (is_numeric($cleaned)) {
                $waterContent = (float) $cleaned;
            }
        }

        return [
            'file_id' => $this->fileId,
            'rowid' => $lineNumber,

            // Substance
            'substance_id' => $this->lookupOrCreateSubstance($col('individual_id'), $col('chemical_name')),
            'chemical_name' => $this->cleanString($col('chemical_name')),

            // Species
            'species_id' => $this->lookupSpecies(
                $col('latin_name'),
                $col('kingdom'),
                $col('class_phyl')
            ),
            'common_name_id' => $this->lookupCommonName($col('suggested_name')),

            // Bibliographic source
            'title' => $this->cleanString($col('title_author_year_doi')),

            // Biota information
            'sex_id' => $this->lookupSex($col('sex')),
            'diet_as_described_in_paper' => $diet,
            'trophic_level_as_described_in_paper' => $trophicLevel,
            'life_stage_id' => $this->lookupLifeStage($col('life_stage')),
            'age_in_days' => $ageInDays,

            // Location
            'country_id' => $this->lookupCountry($col('country')),
            'region_city' => $this->cleanString($col('region_city')),

            // Health and habitat
            'health_status' => $this->cleanString($col('health_status')),
            'reported_distance_to_industry' => $this->cleanString($col('reported_distance_to_industry')),

            // Tissue and measurement
            'tissue_id' => $this->lookupTissue($col('tissue')),
            'matrix_id' => $this->lookupMatrix($col('sample_matrix')),
            'analytical_method' => $this->cleanString($col('analytical_method')),
            'storage_temp_c' => $this->cleanString($col('storage_temp_c')),

            // Detection limits
            'lod' => $this->cleanString($col('lod')),
            'loq' => $this->cleanString($col('loq')),

            // Sample information
            'x_of_subsamples' => $this->cleanString($col('x_of_subsamples')),
            'x_of_replicates' => $this->cleanInt($col('x_of_replicates')),
            'sd' => $this->cleanString($col('sd')),
            'type_of_numeric_quantity_id' => $this->lookupTypeOfNumericQuantity($col('type_of_numeric_quantity')),

            // Range
            'range_min' => $this->extractRangeMin($col('range')),
            'range_max' => $this->extractRangeMax($col('range')),

            // Concentration
            'concentration_units_id' => $unitLookup['id'],
            'concentration_unit_raw' => $unitLookup['raw'],
            'reported_concentration' => $reportedConcentration,
            'concentrationlevel' => $concentrationLevel,
            'ww_conc_ng' => $wwConcNg,

            // Sampling dates
            'start_of_sampling_year' => $samplingYear,
            'start_of_sampling_month' => $samplingMonth,

            // Coordinates
            'latitude_1' => $latitude,
            'longitude_1' => $longitude,

            // Phylogenetic data
            'kingdom' => $this->cleanString($col('kingdom')),
            'class_phyl' => $this->cleanString($col('class_phyl')),
            'order' => $this->cleanString($col('order')),

            // Habitat
            'habitat_class' => $this->cleanString($col('habitat_type')),

            // Water content
            'water_content' => $waterContent,

            // Comments
            'comment' => $comment,

            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    // ==================== LOOKUP METHODS ====================

    protected function normalizeForLookup(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }

    protected function normalizeUnitForLookup(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return strtolower(str_replace(' ', '', trim($value)));
    }

    protected function isWetWeightUnit(?string $name): bool
    {
        if ($name === null) {
            return false;
        }
        $normalized = $this->normalizeUnitForLookup($name);

        return $normalized === 'ng/gww' || str_contains($normalized, 'ww');
    }

    /**
     * Lookup substance by Norman ID, auto-create if missing.
     */
    protected function lookupOrCreateSubstance(?string $normanId, ?string $chemicalName = null): ?int
    {
        if ($this->isSkipValue($normanId)) {
            return null;
        }

        // Strip "NS" prefix to get the code
        $code = preg_replace('/^NS/', '', trim((string) $normanId));

        if (isset($this->substanceCache[$code])) {
            return $this->substanceCache[$code];
        }

        // Auto-create missing substance with only the code
        $newId = DB::table('susdat_substances')->insertGetId([
            'code' => $code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Cache for future lookups
        $this->substanceCache[$code] = $newId;

        // Log the new substance
        $this->newSubstances[] = [
            'norman_id' => trim((string) $normanId),
            'code' => $code,
            'chemical_name' => $chemicalName,
            'db_id' => $newId,
        ];

        return $newId;
    }

    protected function lookupSpecies(?string $latinName, ?string $kingdom = null, ?string $classPhy = null): ?int
    {
        if ($this->isSkipValue($latinName)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($latinName);

        if (isset($this->speciesCache[$normalized])) {
            return $this->speciesCache[$normalized];
        }

        // Auto-create missing species
        $cleanLatinName = $this->cleanString($latinName);
        $cleanKingdom = $this->cleanString($kingdom);
        $cleanClass = $this->cleanString($classPhy);

        $newId = DB::table('list_species')->insertGetId([
            'name_latin' => $cleanLatinName,
            'name' => $cleanLatinName,
            'kingdom' => $cleanKingdom,
            'class' => $cleanClass,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->speciesCache[$normalized] = $newId;

        return $newId;
    }

    protected function lookupCommonName(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        return $this->commonNamesCache[$normalized] ?? null;
    }

    protected function lookupCountry(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        return $this->countriesCache[$normalized] ?? null;
    }

    protected function lookupTissue(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        if (isset($this->tissuesCache[$normalized])) {
            return $this->tissuesCache[$normalized];
        }

        // Mapping for common variations
        $mapping = [
            'egg' => 'eggs',
            'eggs' => 'eggs',
            'liver' => 'liver',
            'muscle' => 'muscle',
            'whole body' => 'whole body',
            'soft body' => 'soft body',
            'blood' => 'blood',
            'feather' => 'feather',
            'feathers' => 'feather',
            'brain' => 'brain',
            'fat' => 'fat',
            'kidney' => 'kidney',
            'bone' => 'bone',
            'hair' => 'hair',
            'fur' => 'fur',
        ];

        $mapped = $mapping[$normalized] ?? null;
        if ($mapped && isset($this->tissuesCache[$mapped])) {
            return $this->tissuesCache[$mapped];
        }

        return $this->tissuesCache['other'] ?? null;
    }

    protected function lookupSex(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        if (isset($this->sexCache[$normalized])) {
            return $this->sexCache[$normalized];
        }

        $mapping = [
            'm' => 'male',
            'f' => 'female',
            'male' => 'male',
            'female' => 'female',
            'mixed' => 'mixed',
            'unknown' => 'nr',
            'na' => 'nr',
            'n/a' => 'nr',
        ];

        $mapped = $mapping[$normalized] ?? null;
        if ($mapped && isset($this->sexCache[$mapped])) {
            return $this->sexCache[$mapped];
        }

        return $this->sexCache['nr'] ?? null;
    }

    protected function lookupLifeStage(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        if (isset($this->lifeStagesCache[$normalized])) {
            return $this->lifeStagesCache[$normalized];
        }

        $mapping = [
            'adult' => 'adult',
            'adults' => 'adult',
            'juvenile' => 'juvenile',
            'juveniles' => 'juvenile',
            'na' => 'na',
            'n/a' => 'na',
        ];

        $mapped = $mapping[$normalized] ?? null;
        if ($mapped && isset($this->lifeStagesCache[$mapped])) {
            return $this->lifeStagesCache[$mapped];
        }

        return $this->lifeStagesCache['no data'] ?? null;
    }

    /**
     * @return array{id: ?int, raw: ?string}
     */
    protected function lookupConcentrationUnit(?string $name): array
    {
        if ($this->isSkipValue($name)) {
            return ['id' => null, 'raw' => null];
        }
        $normalized = $this->normalizeUnitForLookup($name);

        if (isset($this->concentrationUnitsCache[$normalized])) {
            return ['id' => $this->concentrationUnitsCache[$normalized], 'raw' => null];
        }

        // No match: assign "other" ID and preserve raw value
        return [
            'id' => $this->concentrationUnitsCache['other'] ?? null,
            'raw' => trim($name),
        ];
    }

    protected function lookupMatrix(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        if (isset($this->matricesCache[$normalized])) {
            return $this->matricesCache[$normalized];
        }

        $mapping = [
            'biota-terrestrial' => 'biota - terrestrial',
            'biota terrestrial' => 'biota - terrestrial',
        ];

        $mapped = $mapping[$normalized] ?? null;
        if ($mapped && isset($this->matricesCache[$mapped])) {
            return $this->matricesCache[$mapped];
        }

        foreach ($this->matricesCache as $key => $id) {
            if (str_contains($normalized, 'terrestrial') && str_contains($key, 'terrestrial')) {
                return $id;
            }
        }

        return $this->matricesCache['biota - other'] ?? null;
    }

    protected function lookupTypeOfNumericQuantity(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        if (isset($this->typeOfNumericQuantitiesCache[$normalized])) {
            return $this->typeOfNumericQuantitiesCache[$normalized];
        }

        $mapping = [
            'mean' => 'mean',
            'average' => 'average',
            'arithmetic mean' => 'arithmetic mean',
            'median' => 'median',
            'geometric mean' => 'geometric mean',
            'number' => 'single value',
            'single value' => 'single value',
            'no data' => 'nr',
            'na' => 'nr',
        ];

        $standardized = $mapping[$normalized] ?? 'other';

        return $this->typeOfNumericQuantitiesCache[$standardized] ?? null;
    }

    // ==================== HELPER METHODS ====================

    protected function isSkipValue($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $strValue = strtolower(trim((string) $value));

        return $strValue === '' || $strValue === 'na' || $strValue === 'n/a' || $strValue === '-';
    }

    protected function cleanString($value): ?string
    {
        if ($this->isSkipValue($value)) {
            return null;
        }

        return trim((string) $value);
    }

    protected function cleanInt($value): ?int
    {
        if ($this->isSkipValue($value)) {
            return null;
        }
        $cleaned = trim((string) $value);

        return is_numeric($cleaned) ? (int) $cleaned : null;
    }

    /**
     * Extract minimum value from range string like "0.5-1.2" or "0.5–1.2".
     */
    protected function extractRangeMin(?string $value): ?string
    {
        if ($this->isSkipValue($value)) {
            return null;
        }
        $strValue = trim((string) $value);
        if (preg_match('/^([\d.]+)\s*[-–]\s*[\d.]+$/', $strValue, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract maximum value from range string like "0.5-1.2" or "0.5–1.2".
     */
    protected function extractRangeMax(?string $value): ?string
    {
        if ($this->isSkipValue($value)) {
            return null;
        }
        $strValue = trim((string) $value);
        if (preg_match('/^[\d.]+\s*[-–]\s*([\d.]+)$/', $strValue, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Parse latitude/longitude from combined field.
     * Handles formats like "33.3/44.4" or "33.3, 44.4".
     *
     * @return array{?string, ?string} [latitude, longitude]
     */
    protected function parseLatLon(?string $value): array
    {
        if ($this->isSkipValue($value)) {
            return [null, null];
        }

        $strValue = trim((string) $value);

        // Try "lat/lon" format
        if (preg_match('/^([+-]?[\d.]+)\s*[\/,]\s*([+-]?[\d.]+)$/', $strValue, $matches)) {
            return [$matches[1], $matches[2]];
        }

        // Return as latitude only if single value
        return [$strValue, null];
    }

    /**
     * Parse sampling date string into year and month components.
     * Handles formats like "between November 2021 and March 2022", "from 1994 to 1995", "2020", etc.
     *
     * @return array{?string, ?string} [year, month]
     */
    protected function parseSamplingDate(?string $value): array
    {
        if ($this->isSkipValue($value)) {
            return [null, null];
        }

        $strValue = trim((string) $value);

        // Try to extract a 4-digit year
        $year = null;
        $month = null;

        if (preg_match('/(\d{4})/', $strValue, $matches)) {
            $year = $matches[1];
        }

        // Try to extract month names
        $months = ['january', 'february', 'march', 'april', 'may', 'june',
            'july', 'august', 'september', 'october', 'november', 'december'];
        $lowerValue = strtolower($strValue);
        foreach ($months as $m) {
            if (str_contains($lowerValue, $m)) {
                $month = ucfirst($m);

                break;
            }
        }

        // If no structured data found, store entire string as year field
        if ($year === null && $month === null) {
            return [$strValue, null];
        }

        return [$year, $month ?? $strValue];
    }

    protected function logSkippedRow(int $lineNumber, string $sheetKey, string $reason, array $csvRow): void
    {
        $this->skippedRows[] = [
            'sheet' => $sheetKey,
            'line' => $lineNumber,
            'reason' => $reason,
            'data_sample' => array_slice($csvRow, 0, 10),
        ];

        if (count($this->skippedRows) <= 10) {
            $this->command->warn("{$sheetKey} line {$lineNumber}: {$reason}");
        }
    }

    protected function writeSkippedRowsLog(): void
    {
        if (empty($this->skippedRows)) {
            return;
        }

        $content = "TerraChem Metals Seeder - Skipped Rows Log\n";
        $content .= 'Generated: '.Carbon::now()->toDateTimeString()."\n";
        $content .= str_repeat('=', 80)."\n\n";

        foreach ($this->skippedRows as $entry) {
            $content .= "{$entry['sheet']} line {$entry['line']}: {$entry['reason']}\n";
            if (! empty($entry['data_sample'])) {
                $content .= '  Sample data: '.json_encode($entry['data_sample'])."\n";
            }
            $content .= "\n";
        }

        file_put_contents($this->skippedRowsLogPath, $content);
    }

    protected function writeNewSubstancesLog(): void
    {
        if (empty($this->newSubstances)) {
            return;
        }

        $content = "TerraChem Metals Seeder - Auto-Created Substances Log\n";
        $content .= 'Generated: '.Carbon::now()->toDateTimeString()."\n";
        $content .= 'Total: '.count($this->newSubstances)." new substances\n";
        $content .= str_repeat('=', 80)."\n\n";

        foreach ($this->newSubstances as $entry) {
            $content .= "Norman ID: {$entry['norman_id']} | Code: {$entry['code']} | Chemical: {$entry['chemical_name']} | DB ID: {$entry['db_id']}\n";
        }

        file_put_contents($this->newSubstancesLogPath, $content);
    }
}
// php artisan db:seed --class=Database\\Seeders\\Literature\\LiteratureSeeder_TerraChemMetals
