<?php

declare(strict_types=1);

namespace Database\Seeders\Literature;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class LiteratureSeeder_TerraChem extends Seeder
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

    protected array $substanceCache = []; // norman_code (without NS) => substance_id

    // Chemical columns mapping: column_index => norman_id (e.g., 58 => 'NS00009638')
    protected array $chemicalColumnsMap = [];

    // Test mode - set to null for full processing
    protected ?int $limitRows = null;

    protected int $fileId = 9001;

    // Excel structure constants
    protected int $headerRow = 5;

    protected int $chemicalNormanIdRow = 6;

    protected int $dataStartRow = 7;

    protected int $lastMetadataColumn = 36; // Column AJ

    protected int $firstChemicalColumn = 58; // Column BF

    protected int $lastChemicalColumn = 289; // Column KC

    // Skipped rows log
    protected array $skippedRows = [];

    protected string $skippedRowsLogPath;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Increase PHP memory limit and execution time for large imports - MUST be set early
        ini_set('memory_limit', '16G');
        ini_set('max_execution_time', '7200'); // 2 hours
        $this->command->info('Memory limit set to 16GB, execution time to 2 hours');

        $target_table_name = 'literature_temp_main';

        $this->skippedRowsLogPath = base_path('database/seeders/seeds/literature/terrachem_skipped_rows.log');

        // Delete only records for this file_id (preserves data from other seeders)
        $deleted = DB::table($target_table_name)->where('file_id', $this->fileId)->delete();
        $this->command->info("Deleted {$deleted} existing records for file_id = {$this->fileId}");

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

        $path = base_path('database/seeders/seeds/literature/DCT_BIOTA_LITERATURE_TerraChem_NILU Heimstad_2013_2023_11122025_v3.xlsx');

        if (! file_exists($path)) {
            $this->command->error("Excel file not found: {$path}");

            return;
        }

        if ($this->limitRows) {
            $this->command->warn("TEST MODE: Processing only first {$this->limitRows} rows");
        }

        $this->command->info('Loading Excel file (this may take a moment)...');

        // Load the spreadsheet
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        // Load chemical column mappings from row 6
        $this->loadChemicalColumnsMap($sheet);

        $this->command->info("Sheet has {$highestRow} rows");
        $this->command->info("Metadata columns: A-AJ (1-{$this->lastMetadataColumn})");
        $this->command->info("Chemical columns: BF-KC ({$this->firstChemicalColumn}-{$this->lastChemicalColumn})");
        $this->command->info('Chemical columns with valid Norman IDs: '.count($this->chemicalColumnsMap));

        $batch = [];
        $batchSize = 500;
        $rowCount = 0;
        $recordCount = 0;
        $skippedRowCount = 0;
        $progressInterval = 50;
        $startTime = microtime(true);
        $lastProgressTime = $startTime;
        $now = Carbon::now();

        // Start transaction for better performance
        DB::beginTransaction();

        try {
            $this->command->info("Processing rows {$this->dataStartRow} to {$highestRow}...");

            for ($rowIndex = $this->dataStartRow; $rowIndex <= $highestRow; $rowIndex++) {
                // Test mode row limit
                if ($this->limitRows && $rowCount >= $this->limitRows) {
                    break;
                }

                try {
                    // Read row data (metadata + chemical columns)
                    $rowData = $this->readRow($sheet, $rowIndex);

                    // Sanity check before processing
                    $validationResult = $this->validateRow($rowData, $rowIndex);
                    if ($validationResult !== true) {
                        $this->logSkippedRow($rowIndex, $validationResult, $rowData);
                        $skippedRowCount++;

                        continue;
                    }

                    // Process row - returns multiple records (one per chemical)
                    $records = $this->processRow($rowData, $rowIndex, $now);

                    foreach ($records as $record) {
                        $batch[] = $record;
                        $recordCount++;

                        // Insert batch when it reaches the batch size
                        if (count($batch) >= $batchSize) {
                            DB::table($target_table_name)->insert($batch);
                            unset($batch);
                            $batch = [];
                        }
                    }

                    $rowCount++;

                    // Report progress
                    if ($rowCount % $progressInterval === 0) {
                        $currentTime = microtime(true);
                        $batchDuration = round($currentTime - $lastProgressTime, 2);
                        $totalDuration = round($currentTime - $startTime, 2);
                        $this->command->info("Processed {$rowCount} rows ({$recordCount} records)... (batch: {$batchDuration}s, total: {$totalDuration}s)");
                        $lastProgressTime = $currentTime;

                        // Force garbage collection periodically
                        gc_collect_cycles();
                    }
                } catch (\Exception $e) {
                    $this->logSkippedRow($rowIndex, 'Exception: '.$e->getMessage(), []);
                    $skippedRowCount++;

                    continue;
                }
            }

            // Insert remaining records
            if (! empty($batch)) {
                DB::table($target_table_name)->insert($batch);
                unset($batch);
                $batch = [];
            }

            DB::commit();

            // Clear memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            gc_collect_cycles();
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }

        // Write skipped rows log
        $this->writeSkippedRowsLog();

        $totalTime = round(microtime(true) - $startTime, 2);
        $avgPerRow = $rowCount > 0 ? round($totalTime / $rowCount * 1000, 2) : 0;
        $rowsPerSecond = $rowCount > 0 ? round($rowCount / $totalTime, 2) : 0;

        $this->command->info("Successfully seeded {$recordCount} records from {$rowCount} Excel rows into {$target_table_name} in {$totalTime}s");
        $this->command->info("Performance: {$avgPerRow}ms/row ({$rowsPerSecond} rows/second)");

        if ($skippedRowCount > 0) {
            $this->command->warn("Skipped {$skippedRowCount} rows due to validation errors.");
            $this->command->warn("See log file: {$this->skippedRowsLogPath}");
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
     * Load chemical column mappings from row 6 (Norman IDs)
     */
    protected function loadChemicalColumnsMap($sheet): void
    {
        for ($col = $this->firstChemicalColumn; $col <= $this->lastChemicalColumn; $col++) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $value = $sheet->getCell($letter.$this->chemicalNormanIdRow)->getValue();

            // Convert RichText objects to plain strings
            if ($value instanceof RichText) {
                $value = $value->getPlainText();
            }

            // Skip empty or invalid values
            if ($value === null || $value === '' || $value === '#N/A') {
                continue;
            }

            $normanId = trim((string) $value);
            if (! empty($normanId)) {
                $this->chemicalColumnsMap[$col] = $normanId;
            }
        }

        $this->command->info('Loaded '.count($this->chemicalColumnsMap).' chemical column mappings from row 6');
    }

    /**
     * Load all lookup tables into memory for faster processing
     * Using lowercase keys for case-insensitive matching
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

        // Load concentration units by name (use unit-specific normalization)
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
        // Norman IDs are like "NS00009638", code in DB is "00009638"
        $substances = DB::table('susdat_substances')
            ->whereNotNull('code')
            ->select('id', 'code')
            ->get();
        foreach ($substances as $s) {
            $this->substanceCache[$s->code] = $s->id;
        }
        $this->command->info('Loaded '.count($this->substanceCache).' substances');
    }

    /**
     * Read a single row from the Excel sheet (metadata + chemical columns)
     */
    protected function readRow($sheet, int $rowIndex): array
    {
        $data = [
            'metadata' => [],
            'chemicals' => [],
        ];

        // Read metadata columns (A-AJ)
        for ($col = 1; $col <= $this->lastMetadataColumn; $col++) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $value = $sheet->getCell($letter.$rowIndex)->getValue();

            // Convert RichText objects to plain strings
            if ($value instanceof RichText) {
                $value = $value->getPlainText();
            }

            $data['metadata'][$letter] = $value;
        }

        // Read chemical columns (BF-KC) - only those with valid Norman IDs
        foreach ($this->chemicalColumnsMap as $col => $normanId) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $value = $sheet->getCell($letter.$rowIndex)->getValue();

            // Convert RichText objects to plain strings
            if ($value instanceof RichText) {
                $value = $value->getPlainText();
            }

            $data['chemicals'][$col] = [
                'norman_id' => $normanId,
                'value' => $value,
            ];
        }

        return $data;
    }

    /**
     * Validate a row before processing
     *
     * @return true|string True if valid, error message string if invalid
     */
    protected function validateRow(array $rowData, int $rowIndex): bool|string
    {
        // Check for completely empty metadata
        $hasData = false;
        foreach ($rowData['metadata'] as $value) {
            if (! $this->isSkipValue($value)) {
                $hasData = true;

                break;
            }
        }

        if (! $hasData) {
            return 'Empty row (no metadata)';
        }

        return true;
    }

    /**
     * Process a single row from Excel - returns multiple records (one per chemical)
     *
     * @return array Array of records to insert
     */
    protected function processRow(array $rowData, int $rowIndex, Carbon $now): array
    {
        $records = [];
        $metadata = $rowData['metadata'];

        // Build comment from Data provider (AB) and remark (AG)
        $commentParts = [];
        if (! $this->isSkipValue($metadata['AB'] ?? null)) {
            $commentParts[] = 'Data provider: '.$this->cleanString($metadata['AB']);
        }
        if (! $this->isSkipValue($metadata['AG'] ?? null)) {
            $commentParts[] = $this->cleanString($metadata['AG']);
        }
        $comment = ! empty($commentParts) ? implode(' | ', $commentParts) : null;

        // Check if concentration unit is wet weight (for ww_conc_ng population)
        $concentrationUnit = $metadata['E'] ?? null;
        $isWetWeight = $this->isWetWeightUnit($concentrationUnit);

        // Build base record with metadata (shared across all chemicals)
        $baseRecord = [
            // File reference
            'file_id' => $this->fileId,

            // Row identifier (use Excel row as rowid)
            'rowid' => $rowIndex,

            // Species information (Col I: latin_name, with kingdom K and class L for auto-creation)
            'species_id' => $this->lookupSpecies(
                $metadata['I'] ?? null,
                $metadata['K'] ?? null,  // kingdom
                $metadata['L'] ?? null   // class_phyl
            ),
            'common_name_id' => $this->lookupCommonName($metadata['R'] ?? null),

            // Bibliographic source (Col AE: Reference -> title)
            'title' => $this->cleanString($metadata['AE'] ?? null),
            'first_author' => $this->cleanString($metadata['A'] ?? null),

            // Biota information
            'sex_id' => $this->lookupSex($metadata['P'] ?? null),
            'diet_as_described_in_paper' => $this->cleanString($metadata['X'] ?? null),
            'trophic_level_as_described_in_paper' => $this->cleanString($metadata['AA'] ?? null),
            'life_stage_id' => $this->lookupLifeStage($metadata['C'] ?? null),
            'age_in_days' => $this->cleanString($metadata['Q'] ?? null),

            // Location information
            'region_city' => $this->cleanString($metadata['S'] ?? null),

            // Health and habitat
            'health_status' => $this->cleanString($metadata['AF'] ?? null),
            'reported_distance_to_industry' => $this->cleanString($metadata['Y'] ?? null),

            // Tissue and measurement
            'tissue_id' => $this->lookupTissue($metadata['G'] ?? null),
            'matrix_id' => $this->lookupMatrix($metadata['M'] ?? null),
            'analytical_method' => $this->cleanString($metadata['AD'] ?? null),

            // Concentration
            'concentration_units_id' => $this->lookupConcentrationUnit($metadata['E'] ?? null),

            // Sampling dates
            'start_of_sampling_month' => $this->cleanString($metadata['V'] ?? null),
            'start_of_sampling_year' => $this->cleanString($metadata['O'] ?? null),

            // Coordinates
            'latitude_1' => $this->cleanString($metadata['AI'] ?? null),
            'longitude_1' => $this->cleanString($metadata['AJ'] ?? null),

            // Phylogenetic data
            'kingdom' => $this->cleanString($metadata['K'] ?? null),
            'class_phyl' => $this->cleanString($metadata['L'] ?? null),

            // IDs
            'individual_id' => $this->extractNumericId($metadata['N'] ?? null),

            // Comments
            'comment' => $comment,

            'created_at' => $now,
            'updated_at' => $now,
        ];

        // Iterate through chemical columns and create records
        foreach ($rowData['chemicals'] as $col => $chemData) {
            $value = $chemData['value'];
            $normanId = $chemData['norman_id'];

            // Skip empty, NA, N/A, or dash values
            if ($this->isSkipValue($value)) {
                continue;
            }

            // Create record for this chemical
            $record = $baseRecord;

            // Look up substance_id from Norman ID
            $record['substance_id'] = $this->lookupSubstance($normanId);
            $record['chemical_name'] = $normanId;

            // Determine if value is numeric or text
            $strValue = trim((string) $value);
            if (is_numeric($strValue)) {
                $record['reported_concentration'] = $strValue;
                $record['concentrationlevel'] = null;

                // If unit is ng/gww (wet weight), also populate ww_conc_ng
                $record['ww_conc_ng'] = $isWetWeight ? (float) $strValue : null;
            } else {
                $record['reported_concentration'] = null;
                $record['concentrationlevel'] = $strValue;
                $record['ww_conc_ng'] = null;
            }

            $records[] = $record;
        }

        return $records;
    }

    // ==================== LOOKUP METHODS ====================

    /**
     * Normalize string for lookup: trim, lowercase, strip extra whitespace
     */
    protected function normalizeForLookup(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }

    /**
     * Look up substance_id from Norman ID (e.g., "NS00009638" -> code "00009638" -> id)
     */
    protected function lookupSubstance(?string $normanId): ?int
    {
        if ($normanId === null || $normanId === '') {
            return null;
        }

        // Strip "NS" prefix to get the code
        $code = preg_replace('/^NS/', '', $normanId);

        return $this->substanceCache[$code] ?? null;
    }

    protected function lookupSpecies(?string $latinName, ?string $kingdom = null, ?string $classPhy = null): ?int
    {
        if ($this->isSkipValue($latinName)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($latinName);

        // Return from cache if found
        if (isset($this->speciesCache[$normalized])) {
            return $this->speciesCache[$normalized];
        }

        // Auto-create missing species
        $cleanLatinName = $this->cleanString($latinName);
        $cleanKingdom = $this->cleanString($kingdom);
        $cleanClass = $this->cleanString($classPhy);

        $newId = DB::table('list_species')->insertGetId([
            'name_latin' => $cleanLatinName,
            'name' => $cleanLatinName, // Use latin name as display name
            'kingdom' => $cleanKingdom,
            'class' => $cleanClass,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add to cache for future lookups
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

    protected function lookupTissue(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        // Direct match
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
        ];

        $mapped = $mapping[$normalized] ?? null;
        if ($mapped && isset($this->tissuesCache[$mapped])) {
            return $this->tissuesCache[$mapped];
        }

        // Default to "Other" if not found
        return $this->tissuesCache['other'] ?? null;
    }

    protected function lookupSex(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        // Direct match
        if (isset($this->sexCache[$normalized])) {
            return $this->sexCache[$normalized];
        }

        // Mapping for common variations
        $mapping = [
            'm' => 'male',
            'f' => 'female',
            'male' => 'male',
            'female' => 'female',
            'mixed' => 'mixed',
            'unknown' => 'nr',
            'unknown (egg)' => 'nr',
            'na' => 'nr',
            'n/a' => 'nr',
            'no data' => 'nr',
        ];

        $mapped = $mapping[$normalized] ?? null;
        if ($mapped && isset($this->sexCache[$mapped])) {
            return $this->sexCache[$mapped];
        }

        // Default to NR if not found
        return $this->sexCache['nr'] ?? null;
    }

    protected function lookupLifeStage(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        // Direct match
        if (isset($this->lifeStagesCache[$normalized])) {
            return $this->lifeStagesCache[$normalized];
        }

        // Mapping for common variations
        $mapping = [
            'egg' => 'freshly laid',
            'eggs' => 'freshly laid',
            'adult' => 'adult',
            'adults' => 'adult',
            'juvenile' => 'juvenile',
            'juveniles' => 'juvenile',
            'chick' => 'chick',
            'chicks' => 'chicks',
            'nestling' => 'nestling',
            'nestlings' => 'nestlings',
            'larva' => 'larvae',
            'larvae' => 'larvae',
            'imago' => 'imago',
            'subadult' => 'subadult',
            'immature' => 'immature',
            'na' => 'na',
            'n/a' => 'na',
            'unknown' => 'unknown',
        ];

        $mapped = $mapping[$normalized] ?? null;
        if ($mapped && isset($this->lifeStagesCache[$mapped])) {
            return $this->lifeStagesCache[$mapped];
        }

        // Try partial match for complex descriptions
        foreach ($this->lifeStagesCache as $key => $id) {
            if (str_contains($normalized, $key) || str_contains($key, $normalized)) {
                return $id;
            }
        }

        // Default to "no data" if not found
        return $this->lifeStagesCache['no data'] ?? null;
    }

    protected function lookupConcentrationUnit(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }

        // Normalize: lowercase, strip spaces and strange characters, keep essential chars
        $normalized = $this->normalizeUnitForLookup($name);

        // Direct match
        if (isset($this->concentrationUnitsCache[$normalized])) {
            return $this->concentrationUnitsCache[$normalized];
        }

        // Default to "other" if not found
        return $this->concentrationUnitsCache['other'] ?? null;
    }

    /**
     * Normalize unit string for lookup: lowercase, strip spaces
     */
    protected function normalizeUnitForLookup(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        // Lowercase and remove spaces
        $normalized = strtolower(trim($value));
        $normalized = str_replace(' ', '', $normalized);

        return $normalized;
    }

    /**
     * Check if concentration unit is wet weight (ng/gww)
     */
    protected function isWetWeightUnit(?string $name): bool
    {
        if ($name === null) {
            return false;
        }
        $normalized = $this->normalizeUnitForLookup($name);

        return $normalized === 'ng/gww';
    }

    protected function lookupMatrix(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        // Direct match
        if (isset($this->matricesCache[$normalized])) {
            return $this->matricesCache[$normalized];
        }

        // Mapping for common variations
        $mapping = [
            'biota-terrestrial' => 'biota - terrestrial',
            'biota terrestrial' => 'biota - terrestrial',
            'biota-marine' => 'biota - sea',
            'biota-freshwater' => 'biota - river water',
            'biota-coastal' => 'biota - coastal water',
        ];

        $mapped = $mapping[$normalized] ?? null;
        if ($mapped && isset($this->matricesCache[$mapped])) {
            return $this->matricesCache[$mapped];
        }

        // Try partial match
        foreach ($this->matricesCache as $key => $id) {
            if (str_contains($normalized, 'terrestrial') && str_contains($key, 'terrestrial')) {
                return $id;
            }
            if (str_contains($normalized, 'marine') && str_contains($key, 'sea')) {
                return $id;
            }
        }

        // Default to "Biota - Other" if not found
        return $this->matricesCache['biota - other'] ?? null;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if value should be skipped (empty, NA, N/A, dash)
     */
    protected function isSkipValue($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $strValue = strtolower(trim((string) $value));

        return $strValue === '' || $strValue === 'na' || $strValue === 'n/a' || $strValue === '-';
    }

    /**
     * Legacy method for backward compatibility
     */
    protected function isEmptyValue($value): bool
    {
        return $this->isSkipValue($value);
    }

    /**
     * Clean string value
     */
    protected function cleanString($value): ?string
    {
        if ($this->isSkipValue($value)) {
            return null;
        }

        return trim((string) $value);
    }

    /**
     * Extract numeric ID from string like "273_1"
     */
    protected function extractNumericId($value): ?int
    {
        if ($this->isSkipValue($value)) {
            return null;
        }
        $strValue = (string) $value;

        // Try to extract first number
        if (preg_match('/^(\d+)/', $strValue, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Log a skipped row for later review
     */
    protected function logSkippedRow(int $rowIndex, string $reason, array $rowData): void
    {
        $this->skippedRows[] = [
            'row' => $rowIndex,
            'reason' => $reason,
            'data_sample' => array_slice($rowData['metadata'] ?? $rowData, 0, 10),
        ];

        // Only show first 10 errors to avoid spam
        if (count($this->skippedRows) <= 10) {
            $this->command->warn("Row {$rowIndex}: {$reason}");
        }
    }

    /**
     * Write skipped rows log to file
     */
    protected function writeSkippedRowsLog(): void
    {
        if (empty($this->skippedRows)) {
            return;
        }

        $content = "TerraChem Seeder - Skipped Rows Log\n";
        $content .= 'Generated: '.Carbon::now()->toDateTimeString()."\n";
        $content .= str_repeat('=', 80)."\n\n";

        foreach ($this->skippedRows as $entry) {
            $content .= "Row {$entry['row']}: {$entry['reason']}\n";
            if (! empty($entry['data_sample'])) {
                $content .= '  Sample data: '.json_encode($entry['data_sample'])."\n";
            }
            $content .= "\n";
        }

        file_put_contents($this->skippedRowsLogPath, $content);
    }
}
// php artisan db:seed --class=Database\\Seeders\\Literature\\LiteratureSeeder_TerraChem
