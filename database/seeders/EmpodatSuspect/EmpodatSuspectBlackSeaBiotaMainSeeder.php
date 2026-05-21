<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Database\Seeders\EmpodatSuspect\Traits\LoadsSubstanceCaches;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Stream BlackSea BIOTA xlsx → populate both `empodat_suspect_main` and
 * the new `empodat_suspect_metadata` side table in a single pass.
 *
 * Per source row:
 *   - extract Block A/B/C fields (substance, IP, Units, …)
 *   - extract Block E fields (13 HRMS metadata columns)
 *   - find station columns (after `Units`, before `mz score`)
 *   - for each non-NA station value: spawn one `empodat_suspect_main` row,
 *     capture the returned id, spawn matching `empodat_suspect_metadata` row
 *     with the same id + is_numeric_concentration (so both rows live in the
 *     same partition).
 *
 * Metadata rows are 1:1 with main rows by id (no DB-level FK — enforced here).
 * The same metadata values repeat across the N main rows spawned from one
 * source row — by design (per plan doc §3a).
 *
 * Performance: true streaming (no getRows()->toArray()), batched inserts of
 * 500 rows. PostgreSQL RETURNING preserves insert order across partitions.
 *
 * See: Empodat-Suspect-new-source-onboarding.md §3a (column-by-column log)
 */
class EmpodatSuspectBlackSeaBiotaMainSeeder extends Seeder
{
    use LoadsSubstanceCaches;
    use WithoutModelEvents;

    protected const FILE_ID = 10009;

    protected const FILE_NAME = 'DCT_BIOTA_BlackSea2025_SS_NKUA_15042026_v1.xlsx';

    /** Structural marker — station columns begin after this header. */
    protected const STATION_BLOCK_START_AFTER = 'Units';

    /** Boundary marker — HRMS metadata block begins at this column. */
    protected const METADATA_BOUNDARY = 'mz score';

    /** Block E (HRMS metadata) column names in source-file order. */
    protected const METADATA_COLUMNS = [
        'mz score',
        'isotopicfit score',
        'numoffragments score',
        'DDAMSMS score',
        'molecularfitfragments score',
        'rti score',
        'spectral similarity',
        'RT avg',
        'Fragments',
        'Based_on_similarity',
        'Based_on_compound',
        'Identification_Proofs',
        'NumFragments',
    ];

    /** Source-column → empodat_suspect_metadata column name map (for Block E). */
    protected const METADATA_COLUMN_MAP = [
        'mz score' => 'mz_score',
        'isotopicfit score' => 'isotopicfit_score',
        'numoffragments score' => 'numoffragments_score',
        'DDAMSMS score' => 'ddamsms_score',
        'molecularfitfragments score' => 'molecularfitfragments_score',
        'rti score' => 'rti_score',
        'spectral similarity' => 'spectral_similarity',
        'RT avg' => 'rt_avg',
        'Fragments' => 'fragments',
        'Based_on_similarity' => 'based_on_similarity',
        'Based_on_compound' => 'based_on_compound',
        'Identification_Proofs' => 'identification_proofs',
        'NumFragments' => 'num_fragments',
    ];

    /** Columns stored as double precision in empodat_suspect_metadata. */
    protected const METADATA_DOUBLE_COLUMNS = [
        'mz_score', 'isotopicfit_score', 'numoffragments_score', 'ddamsms_score',
        'molecularfitfragments_score', 'rti_score', 'spectral_similarity', 'rt_avg',
        'num_fragments',
    ];

    /** Number of main+metadata rows per insert batch. */
    protected const BATCH_SIZE = 500;

    public function run(): void
    {
        ini_set('memory_limit', '4G');
        ini_set('max_execution_time', '7200');

        $path = storage_path('app/public/empodat_suspect/'.self::FILE_NAME);
        if (! file_exists($path)) {
            $this->command->error("Source file not found: {$path}");

            return;
        }

        $this->command->info('Loading lookup caches...');
        $this->loadLookupCaches();

        // Disable Telescope + query log during bulk insert
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
        }
        DB::connection()->disableQueryLog();
        DB::statement('SET session_replication_role = replica;');

        $this->command->info('Streaming BlackSea BIOTA → empodat_suspect_main + empodat_suspect_metadata (file_id='
            .self::FILE_ID.')...');

        $reader = SimpleExcelReader::create($path);

        $header = null;
        $stationCols = [];
        $mainBatch = [];
        $metadataBatch = [];
        $rowCount = 0;
        $insertedMain = 0;
        $skippedSourceRows = 0;
        $startTime = microtime(true);
        $lastReport = $startTime;

        try {
            foreach ($reader->getRows() as $sourceRow) {
                if ($header === null) {
                    $header = $this->cleanHeader(array_keys($sourceRow));
                    $stationCols = $this->extractStationColumns($header);
                    $this->command->info('  '.count($stationCols).' station columns identified for this source row.');
                }

                $rowCount++;

                try {
                    [$mainRows, $metadataPayload] = $this->buildRows($sourceRow, $stationCols);
                } catch (\Throwable $e) {
                    $skippedSourceRows++;
                    if ($skippedSourceRows <= 10) {
                        $this->command->error("  row {$rowCount}: ".$e->getMessage());
                    }

                    continue;
                }

                foreach ($mainRows as $i => $mainRow) {
                    $mainBatch[] = $mainRow;
                    $metadataBatch[] = $metadataPayload + ['is_numeric_concentration' => $mainRow['is_numeric_concentration']];
                }

                if (count($mainBatch) >= self::BATCH_SIZE) {
                    $this->flushBatch($mainBatch, $metadataBatch);
                    $insertedMain += count($mainBatch);
                    $mainBatch = [];
                    $metadataBatch = [];
                }

                if ($rowCount % 200 === 0) {
                    $now = microtime(true);
                    $rate = round($rowCount / ($now - $startTime), 1);
                    $this->command->info("  processed {$rowCount} source rows, {$insertedMain} main rows inserted ({$rate} src rows/s)");
                    $lastReport = $now;
                    gc_collect_cycles();
                }
            }

            // Flush remainder
            if (! empty($mainBatch)) {
                $this->flushBatch($mainBatch, $metadataBatch);
                $insertedMain += count($mainBatch);
            }
        } finally {
            DB::statement('SET session_replication_role = default;');
            DB::connection()->enableQueryLog();
            if (class_exists(\Laravel\Telescope\Telescope::class)) {
                \Laravel\Telescope\Telescope::startRecording();
            }
        }

        $totalTime = round(microtime(true) - $startTime, 2);
        $this->command->info("Done in {$totalTime}s — processed {$rowCount} source rows, inserted {$insertedMain} main+metadata rows.");
        if ($skippedSourceRows > 0) {
            $this->command->warn("Skipped {$skippedSourceRows} source rows due to errors.");
        }

        $this->validateSubstanceIds(self::FILE_ID);
    }

    /**
     * Build the main + metadata rows for a single source row.
     *
     * Returns: [array<int, array> $mainRows, array $metadataPayloadCommonFields].
     * Each main row's `is_numeric_concentration` is then attached to the corresponding
     * metadata row in run() before insertion (so both share the same partition).
     *
     * @param  array<string, mixed>  $sourceRow
     * @param  array<int, string>  $stationCols
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, mixed>}
     */
    protected function buildRows(array $sourceRow, array $stationCols): array
    {
        $normanId = $this->cleanString($sourceRow['NORMAN_ID'] ?? null);
        if ($normanId === null) {
            return [[], []];
        }

        // Strip NS prefix, normalize, resolve to susdat_substances.id
        $code = preg_replace('/^NS/i', '', $normanId) ?? '';
        $substanceId = $this->resolveSubstanceId($code);

        $ip = $this->cleanString($sourceRow['IP'] ?? null);
        $ipMax = $this->cleanDouble($sourceRow['IP_max'] ?? null);
        $basedOnHrms = $this->cleanBoolean($sourceRow['BasedonHRMSLibrary'] ?? null);
        $units = $this->cleanString($sourceRow['Units'] ?? null);
        $method = $this->cleanString($sourceRow['Method'] ?? null);

        // Block E (metadata) values — same across all spawned main rows for this source row
        $metadataPayload = ['method' => $method];
        foreach (self::METADATA_COLUMN_MAP as $sourceCol => $destCol) {
            $raw = $sourceRow[$sourceCol] ?? null;
            if (in_array($destCol, self::METADATA_DOUBLE_COLUMNS, true)) {
                $metadataPayload[$destCol] = $this->cleanDouble($raw);
            } else {
                $metadataPayload[$destCol] = $this->cleanString($raw);
            }
        }

        $mainRows = [];
        foreach ($stationCols as $colName) {
            $raw = $sourceRow[$colName] ?? null;
            if ($raw === null || $raw === '') {
                continue;
            }

            $mapping = $this->stationMappingCache[$colName] ?? null;
            if ($mapping === null) {
                // Column not registered in mapping table — skip silently.
                // (Mapping seeder should have inserted all station columns; if not,
                // a non-empty cell here is genuinely lost. Logged in §3.6b QA.)
                continue;
            }

            $concentration = $this->cleanDouble($raw);
            $isNumeric = $concentration !== null;

            $mainRows[] = [
                'file_id' => self::FILE_ID,
                'is_numeric_concentration' => $isNumeric,
                'substance_id' => $substanceId,
                'xlsx_station_mapping_id' => $mapping['mapping_id'],
                'station_id' => $mapping['station_id'],
                'concentration' => $concentration,
                'ip' => $ip,
                'ip_max' => $ipMax,
                'based_on_hrms_library' => $basedOnHrms,
                'units' => $units,
            ];
        }

        return [$mainRows, $metadataPayload];
    }

    /**
     * INSERT a batch of main rows with RETURNING id, then INSERT matching
     * metadata rows using the captured ids. Both inserts run in a single
     * transaction so partial failure leaves no orphan rows.
     *
     * @param  array<int, array<string, mixed>>  $mainBatch
     * @param  array<int, array<string, mixed>>  $metadataBatch
     */
    protected function flushBatch(array $mainBatch, array $metadataBatch): void
    {
        if (count($mainBatch) !== count($metadataBatch)) {
            throw new \LogicException('main/metadata batch length mismatch: '
                .count($mainBatch).' vs '.count($metadataBatch));
        }

        DB::transaction(function () use ($mainBatch, $metadataBatch): void {
            $mainCols = array_keys($mainBatch[0]);
            $placeholders = '('.implode(', ', array_fill(0, count($mainCols), '?')).')';
            $valuesSql = implode(', ', array_fill(0, count($mainBatch), $placeholders));
            $colsSql = implode(', ', $mainCols);

            $bindings = [];
            foreach ($mainBatch as $row) {
                foreach ($mainCols as $col) {
                    $bindings[] = $row[$col];
                }
            }

            $sql = "INSERT INTO empodat_suspect_main ({$colsSql}) VALUES {$valuesSql} "
                .'RETURNING id, is_numeric_concentration';
            $returned = DB::select($sql, $bindings);

            if (count($returned) !== count($mainBatch)) {
                throw new \RuntimeException('RETURNING count mismatch: '
                    .count($returned).' vs expected '.count($mainBatch));
            }

            $metadataInsert = [];
            foreach ($returned as $i => $r) {
                $metadataInsert[] = $metadataBatch[$i] + [
                    'id' => $r->id,
                    'is_numeric_concentration' => $r->is_numeric_concentration,
                ];
            }
            DB::table('empodat_suspect_metadata')->insert($metadataInsert);
        });
    }

    /**
     * Trim each header; strip UTF-8 BOM from the first column if present.
     *
     * @param  array<int, string>  $header
     * @return array<int, string>
     */
    protected function cleanHeader(array $header): array
    {
        return array_map(
            fn (string $h): string => trim(str_replace("\xEF\xBB\xBF", '', $h)),
            $header
        );
    }

    /**
     * Extract station column names: everything after `Units`, before `mz score`.
     *
     * @param  array<int, string>  $header
     * @return array<int, string>
     */
    protected function extractStationColumns(array $header): array
    {
        $startIndex = array_search(self::STATION_BLOCK_START_AFTER, $header, true);
        if ($startIndex === false) {
            return [];
        }

        $stationCols = [];
        $headerCount = count($header);
        for ($i = $startIndex + 1; $i < $headerCount; $i++) {
            $name = $header[$i] ?? '';
            if ($name === self::METADATA_BOUNDARY) {
                break;
            }
            if ($name === '' || $name === null) {
                continue;
            }
            $stationCols[] = $name;
        }

        return $stationCols;
    }

    protected function cleanString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $cleaned = trim((string) $value);

        return ($cleaned === '' || $cleaned === 'NA') ? null : $cleaned;
    }

    protected function cleanDouble(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $cleaned = trim((string) $value);
        if ($cleaned === '' || $cleaned === 'NA') {
            return null;
        }

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    protected function cleanBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        $cleaned = strtoupper(trim((string) $value));
        if ($cleaned === '' || $cleaned === 'NA') {
            return null;
        }
        if (in_array($cleaned, ['TRUE', '1', 'YES'], true)) {
            return true;
        }
        if (in_array($cleaned, ['FALSE', '0', 'NO'], true)) {
            return false;
        }

        return null;
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectBlackSeaBiotaMainSeeder
