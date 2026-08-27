<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use App\Services\EmpodatSuspect\SeedRowLimiter;
use App\Services\EmpodatSuspect\SuspectRowWriter;
use Database\Seeders\EmpodatSuspect\Traits\LoadsSubstanceCaches;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Stream TerraChem INVERTEBRATE xlsx → populate `empodat_suspect_main`,
 * `empodat_suspect_metadata`, and `empodat_suspect_substances` in a single pass.
 *
 * Per source row:
 *   - extract Block A/B/C fields (substance, IP, Units, …)
 *   - extract Block E fields (HRMS metadata columns)
 *   - accumulate unique (NORMAN_ID, Name) for end-of-run substances bulk insert
 *   - find station columns (after `Units`, before `mz score`, minus `Blank ...` filler)
 *   - for each non-NA station value: spawn one `empodat_suspect_main` row
 *     paired with a matching `empodat_suspect_metadata` row; both rows share
 *     an id allocated up front by {@see SuspectRowWriter} instead of an id
 *     captured from an INSERT ... RETURNING.
 *
 * Single xlsx read (no separate substances pass) — see plan doc §3a. An
 * optional per-file row cap ({@see SeedRowLimiter}) can bound a run for
 * local smoke-testing.
 *
 * TerraChem deviations from BlackSea SEDIMENT:
 *   - `Blank ...` filler columns between `Units` and the samples are skipped.
 *   - HRMS column is spelled `numberoffragments score` (with "ber"), not
 *     `numoffragments score` — reflected in METADATA_COLUMN_MAP below.
 *
 * See: Empodat-Suspect-3-new-source.md §11 (TerraChem)
 */
class EmpodatSuspectTerraChemInvertebrateMainSeeder extends Seeder
{
    use LoadsSubstanceCaches;
    use WithoutModelEvents;

    protected const FILE_ID = 10012;

    protected const FILE_NAME = 'TerraChem invertebrate samples 2026-06-03 upload ready.xlsx';

    protected const STATION_BLOCK_START_AFTER = 'Units';

    protected const METADATA_BOUNDARY = 'mz score';

    /** Source-column → empodat_suspect_metadata column name map (for Block E). */
    protected const METADATA_COLUMN_MAP = [
        'mz score' => 'mz_score',
        'isotopicfit score' => 'isotopicfit_score',
        'numberoffragments score' => 'numoffragments_score',
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

    /**
     * Number of main+metadata rows accumulated before each write. Unrelated
     * to PostgreSQL's bind-parameter limit — {@see SuspectRowWriter} chunks
     * each INSERT internally against that limit regardless of this value.
     */
    protected const BATCH_SIZE = 4000;

    public function run(): void
    {
        ini_set('memory_limit', '16G');
        ini_set('max_execution_time', '7200');

        $path = storage_path('app/public/empodat_suspect/'.self::FILE_NAME);
        if (! file_exists($path)) {
            $this->command->error("Source file not found: {$path}");

            return;
        }

        $writer = app(SuspectRowWriter::class);
        $limiter = app(SeedRowLimiter::class);

        $this->command->info('Loading lookup caches...');
        $this->loadLookupCaches();

        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
        }
        DB::connection()->disableQueryLog();
        DB::statement('SET session_replication_role = replica;');
        DB::statement('SET synchronous_commit = off;');

        $this->command->info($limiter->banner());
        $this->command->info('Streaming TerraChem INVERTEBRATE → empodat_suspect_main + empodat_suspect_metadata + empodat_suspect_substances (file_id='
            .self::FILE_ID.')...');

        $existingSubstances = DB::table('empodat_suspect_substances')
            ->where('file_id', self::FILE_ID)
            ->pluck('norman_id')
            ->map(fn (?string $v): string => (string) $v)
            ->flip()
            ->all();

        $reader = SimpleExcelReader::create($path);

        $header = null;
        $stationCols = [];
        $mainBatch = [];
        $metadataBatch = [];
        $substancesByKey = [];
        $rowCount = 0;
        $insertedMain = 0;
        $insertedSubstances = 0;
        $skippedSourceRows = 0;
        $capped = false;
        $startTime = microtime(true);

        try {
            foreach ($reader->getRows() as $sourceRow) {
                if ($header === null) {
                    $header = $this->cleanHeader(array_keys($sourceRow));
                    $stationCols = $this->extractStationColumns($header);
                    $this->command->info('  '.count($stationCols).' station columns identified for this source row.');
                }

                $rowCount++;

                try {
                    [$mainRows, $metadataPayload] = $this->buildRows($sourceRow, $stationCols, $substancesByKey, $existingSubstances);
                } catch (\Throwable $e) {
                    $skippedSourceRows++;
                    if ($skippedSourceRows <= 10) {
                        $this->command->error("  row {$rowCount}: ".$e->getMessage());
                    }

                    continue;
                }

                foreach ($mainRows as $mainRow) {
                    $mainBatch[] = $mainRow;
                    $metadataBatch[] = $metadataPayload;
                }

                if (count($mainBatch) >= self::BATCH_SIZE) {
                    $insertedMain += $writer->write($mainBatch, $metadataBatch, self::FILE_ID);
                    $mainBatch = [];
                    $metadataBatch = [];
                }

                // Source-row boundary: every main row this source row can spawn
                // has already been appended above, so stopping here never
                // truncates a row's station list. See SeedRowLimiter's docblock.
                if ($limiter->reached($insertedMain + count($mainBatch))) {
                    $capped = true;
                    break;
                }

                if ($rowCount % 200 === 0) {
                    $rate = round($rowCount / (microtime(true) - $startTime), 1);
                    $this->command->info("  processed {$rowCount} source rows, {$insertedMain} main rows inserted ({$rate} src rows/s)");
                    gc_collect_cycles();
                }
            }

            if (! empty($mainBatch)) {
                $insertedMain += $writer->write($mainBatch, $metadataBatch, self::FILE_ID);
            }

            $insertedSubstances = $this->insertSubstances($substancesByKey);
        } finally {
            DB::statement('SET session_replication_role = default;');
            DB::connection()->enableQueryLog();
            if (class_exists(\Laravel\Telescope\Telescope::class)) {
                \Laravel\Telescope\Telescope::startRecording();
            }
        }

        $totalTime = round(microtime(true) - $startTime, 2);
        $this->command->info("Done in {$totalTime}s — processed {$rowCount} source rows, inserted {$insertedMain} main+metadata rows, {$insertedSubstances} substances.");
        if ($skippedSourceRows > 0) {
            $this->command->warn("Skipped {$skippedSourceRows} source rows due to errors.");
        }
        if ($capped) {
            $this->command->warn("Row cap reached ({$limiter->limit()} rows/file) — import stopped early and is PARTIAL.");
        }

        $this->validateSubstanceIds(self::FILE_ID);
    }

    /**
     * @param  array<string, mixed>  $sourceRow
     * @param  array<int, string>  $stationCols
     * @param  array<string, array<string, mixed>>  $substancesByKey
     * @param  array<string, int>  $existingSubstances
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, mixed>}
     */
    protected function buildRows(array $sourceRow, array $stationCols, array &$substancesByKey, array $existingSubstances): array
    {
        $normanId = $this->cleanString($sourceRow['NORMAN_ID'] ?? null);
        if ($normanId === null) {
            return [[], []];
        }

        $name = $this->cleanString($sourceRow['Name'] ?? null);
        if ($name !== null && ! isset($existingSubstances[$normanId])) {
            $key = $normanId.'|'.$name;
            $substancesByKey[$key] ??= [
                'norman_id' => $normanId,
                'name' => $name,
                'file_id' => self::FILE_ID,
            ];
        }

        $code = preg_replace('/^NS/i', '', $normanId) ?? '';
        $substanceId = $this->resolveSubstanceId($code);

        $ip = $this->cleanString($sourceRow['IP'] ?? null);
        $ipMax = $this->cleanDouble($sourceRow['IP_max'] ?? null);
        $basedOnHrms = $this->cleanBoolean($sourceRow['BasedonHRMSLibrary'] ?? null);
        $units = $this->cleanString($sourceRow['Units'] ?? null);
        $method = $this->cleanString($sourceRow['Method'] ?? null);

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
     * @param  array<string, array<string, mixed>>  $substancesByKey
     */
    protected function insertSubstances(array $substancesByKey): int
    {
        if (empty($substancesByKey)) {
            return 0;
        }

        $rows = array_values($substancesByKey);
        $inserted = 0;
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('empodat_suspect_substances')->insert($chunk);
            $inserted += count($chunk);
        }

        return $inserted;
    }

    /**
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
            if (stripos($name, 'Blank') === 0) {
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
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectTerraChemInvertebrateMainSeeder
