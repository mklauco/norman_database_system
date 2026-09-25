<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use App\Services\EmpodatSuspect\FixedRangeIdAllocator;
use App\Services\EmpodatSuspect\SeedRowLimiter;
use App\Services\EmpodatSuspect\SuspectRowWriter;
use Database\Seeders\EmpodatSuspect\Traits\LoadsSubstanceCaches;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Shared body of the two CONNECT 2 Main seeders (10003 SEDIMENTS, 10004 BIOTA).
 *
 * Streams the v2 spreadsheet into `empodat_suspect_main`,
 * `empodat_suspect_metadata` and `empodat_suspect_substances` in a single pass,
 * following the newer (NKUA-format) sources: rows are written through
 * {@see SuspectRowWriter}, which binds an id onto a main row and its metadata
 * row before either is inserted.
 *
 * TWO THINGS MAKE THIS DIFFERENT FROM THE OTHER SOURCES
 * -----------------------------------------------------
 * 1. IDS COME FROM A FIXED RANGE. This is a re-import of a file that already
 *    owns a contiguous block of `empodat_suspect_main.id`. Its rows are deleted
 *    and regenerated in place, so the block is re-used rather than appended at
 *    the end of the sequence — which would move this file out of id order and
 *    defeat {@see EmpodatSuspectResetAndReseedSeeder}. See
 *    {@see FixedRangeIdAllocator}: it throws the moment an allocation would
 *    pass the end of the range.
 *
 * 2. THE WHOLE IMPORT IS ONE TRANSACTION. The other sources commit per batch,
 *    which is fine when ids come from a sequence. Here an exhausted range must
 *    leave NOTHING behind — a half-written file inside a fixed block is far
 *    worse than no file at all — so every batch is written inside one outer
 *    transaction and an abort rolls the lot back.
 *
 * The row cap ({@see SeedRowLimiter}) is REFUSED here for the same reason: a
 * deliberately truncated import into a fixed id range is meaningless.
 *
 * Both concrete seeders differ only in constants.
 */
abstract class EmpodatSuspectConnect2MainSeederBase extends Seeder
{
    use LoadsSubstanceCaches;
    use WithoutModelEvents;

    protected const STATION_BLOCK_START_AFTER = 'Units';

    protected const METADATA_BOUNDARY = 'mz score';

    /**
     * Source column → `empodat_suspect_metadata` column.
     *
     * Note `Rtavg`: the CONNECT 2 v2 files spell it without a space, unlike the
     * BlackSea files' `RT avg`. The map is per-source for exactly this reason.
     *
     * These files carry no `Method` column, so `method` stays NULL.
     */
    protected const METADATA_COLUMN_MAP = [
        'mz score' => 'mz_score',
        'isotopicfit score' => 'isotopicfit_score',
        'numoffragments score' => 'numoffragments_score',
        'DDAMSMS score' => 'ddamsms_score',
        'molecularfitfragments score' => 'molecularfitfragments_score',
        'rti score' => 'rti_score',
        'spectral similarity' => 'spectral_similarity',
        'Rtavg' => 'rt_avg',
        'Fragments' => 'fragments',
        'Based_on_similarity' => 'based_on_similarity',
        'Based_on_compound' => 'based_on_compound',
        'Identification_Proofs' => 'identification_proofs',
        'NumFragments' => 'num_fragments',
    ];

    /** Metadata columns stored as double precision. */
    protected const METADATA_DOUBLE_COLUMNS = [
        'mz_score', 'isotopicfit_score', 'numoffragments_score', 'ddamsms_score',
        'molecularfitfragments_score', 'rti_score', 'spectral_similarity', 'rt_avg',
        'num_fragments',
    ];

    /**
     * Main+metadata pairs accumulated before each write. Unrelated to
     * PostgreSQL's bind-parameter cap, which {@see SuspectRowWriter} handles.
     */
    protected const BATCH_SIZE = 4000;

    abstract protected function fileId(): int;

    abstract protected function fileName(): string;

    /**
     * First id of the block this file owns and is being written back into.
     */
    abstract protected function idFrom(): int;

    /**
     * Last id of that block. The import must fit at or below it.
     */
    abstract protected function idTo(): int;

    /**
     * Station columns the file must contain — the same assertion the mapping
     * seeder makes, re-checked here because the expected row count is
     * source rows × this number.
     */
    abstract protected function expectedStationColumns(): int;

    /**
     * @param  bool  $useFixedIdRange  true (the DEFAULT) re-uses this file's existing id block,
     *                                 which is what a re-import of an updated spreadsheet needs;
     *                                 false draws from the BIGSERIAL sequence like every other
     *                                 source, which is what a full reload
     *                                 ({@see EmpodatSuspectResetAndReseedSeeder}) needs after the
     *                                 table has been truncated and its identity reset.
     *
     * The default is deliberately the SAFE-BY-SURPRISE one. Running this seeder on its own —
     * `php artisan db:seed --class=…MainSeeder` — passes no arguments, and with the opposite
     * default that silently appended the file at the end of the sequence, quietly undoing the
     * id ordering the re-import exists to preserve. In fixed-range mode the same standalone run
     * instead stops at {@see assertRangeIsFree()} unless the block really is empty. Only the
     * full reload passes false, and it does so explicitly.
     */
    public function run(bool $useFixedIdRange = true): void
    {
        ini_set('memory_limit', '4G');
        ini_set('max_execution_time', '7200');

        $fileId = $this->fileId();
        $path = storage_path('app/public/empodat_suspect/'.$this->fileName());

        if (! file_exists($path)) {
            throw new RuntimeException("Source file not found: {$path}");
        }

        $limiter = app(SeedRowLimiter::class);
        $writer = app(SuspectRowWriter::class);
        $allocator = null;

        if ($useFixedIdRange) {
            if ($limiter->isActive()) {
                throw new RuntimeException(
                    'ABORTED: '.$limiter->banner().'. A capped run cannot be written into a fixed id range — '
                    .'it would leave the file partially imported. Unset EMPODAT_SUSPECT_SEED_ROW_LIMIT and re-run.'
                );
            }

            $this->assertRangeIsFree($fileId);

            $allocator = new FixedRangeIdAllocator($this->idFrom(), $this->idTo());
            $writer->allocateFrom($allocator);

            $this->command->info(sprintf(
                'Importing %s into file_id=%d, re-using ids %s..%s (%s available).',
                $this->fileName(),
                $fileId,
                number_format($allocator->from()),
                number_format($allocator->to()),
                number_format($allocator->capacity()),
            ));
        } else {
            $this->command->info(sprintf(
                'Importing %s into file_id=%d, ids from the sequence. %s',
                $this->fileName(),
                $fileId,
                $limiter->banner(),
            ));
        }

        $this->command->info('Loading lookup caches...');
        $this->loadLookupCaches();
        $this->assertStationMappingReady($fileId);

        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
        }

        DB::connection()->disableQueryLog();
        DB::statement('SET session_replication_role = replica;');
        DB::statement('SET synchronous_commit = off;');

        $startTime = microtime(true);
        $sourceRows = 0;
        $insertedMain = 0;
        $insertedSubstances = 0;

        DB::beginTransaction();

        try {
            [$sourceRows, $insertedMain, $insertedSubstances] = $this->stream($path, $writer, $fileId);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->command->error('IMPORT ABORTED AND ROLLED BACK — nothing was written.');
            $this->command->error($e->getMessage());

            throw $e;
        } finally {
            DB::statement('SET session_replication_role = default;');
            DB::connection()->enableQueryLog();

            if (class_exists(\Laravel\Telescope\Telescope::class)) {
                \Laravel\Telescope\Telescope::startRecording();
            }
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        $this->command->info(sprintf(
            'Done in %ss — %s source rows, %s main+metadata rows, %s substances.',
            $elapsed,
            number_format($sourceRows),
            number_format($insertedMain),
            number_format($insertedSubstances),
        ));

        if ($allocator !== null) {
            $this->command->info(sprintf(
                'Ids used: %s..%s (%s of %s; %s left unused at the end of the range).',
                number_format($allocator->from()),
                number_format($allocator->lastAllocated() ?? $allocator->from()),
                number_format($allocator->allocated()),
                number_format($allocator->capacity()),
                number_format($allocator->remaining()),
            ));
        }

        $this->validateSubstanceIds($fileId);
        $this->reportStationCoverage($fileId);
    }

    /**
     * Single streaming pass.
     *
     * @return array{0: int, 1: int, 2: int} source rows, main rows, substances
     */
    private function stream(string $path, SuspectRowWriter $writer, int $fileId): array
    {
        $reader = SimpleExcelReader::create($path);

        $stationCols = [];
        $mainBatch = [];
        $metadataBatch = [];
        $substancesByKey = [];
        $sourceRows = 0;
        $insertedMain = 0;

        foreach ($reader->getRows() as $sourceRow) {
            if ($stationCols === []) {
                $stationCols = $this->extractStationColumns(array_keys($sourceRow));

                if (count($stationCols) !== $this->expectedStationColumns()) {
                    throw new RuntimeException(sprintf(
                        'ABORTED: expected %d station column(s), found %d: %s.',
                        $this->expectedStationColumns(),
                        count($stationCols),
                        implode(', ', $stationCols),
                    ));
                }

                $this->assertStationColumnsMapped($stationCols);
                $this->command->info('  '.count($stationCols).' station columns, all mapped.');
            }

            $sourceRows++;

            [$mainRows, $metadataPayload] = $this->buildRows($sourceRow, $stationCols, $substancesByKey, $fileId);

            foreach ($mainRows as $mainRow) {
                $mainBatch[] = $mainRow;
                $metadataBatch[] = $metadataPayload;
            }

            if (count($mainBatch) >= self::BATCH_SIZE) {
                $insertedMain += $writer->write($mainBatch, $metadataBatch, $fileId);
                $mainBatch = [];
                $metadataBatch = [];
            }

            if ($sourceRows % 5000 === 0) {
                $this->command->info('  '.number_format($sourceRows).' source rows, '
                    .number_format($insertedMain).' main rows written');
                gc_collect_cycles();
            }
        }

        if ($mainBatch !== []) {
            $insertedMain += $writer->write($mainBatch, $metadataBatch, $fileId);
        }

        return [$sourceRows, $insertedMain, $this->insertSubstances($substancesByKey)];
    }

    /**
     * @param  array<string, mixed>  $sourceRow
     * @param  list<string>  $stationCols
     * @param  array<string, array<string, mixed>>  $substancesByKey
     * @return array{0: list<array<string, mixed>>, 1: array<string, mixed>}
     */
    protected function buildRows(array $sourceRow, array $stationCols, array &$substancesByKey, int $fileId): array
    {
        $normanId = $this->cleanString($sourceRow['NORMAN_ID'] ?? null);

        if ($normanId === null) {
            return [[], []];
        }

        $name = $this->cleanString($sourceRow['Name'] ?? null);

        if ($name !== null) {
            $substancesByKey[$normanId.'|'.$name] ??= [
                'norman_id' => $normanId,
                'name' => $name,
                'file_id' => $fileId,
            ];
        }

        $substanceId = $this->resolveSubstanceId(preg_replace('/^NS/i', '', $normanId) ?? '');

        $ip = $this->cleanString($sourceRow['IP'] ?? null);
        $ipMax = $this->cleanDouble($sourceRow['IP_max'] ?? null);
        $basedOnHrms = $this->cleanBoolean($sourceRow['BasedonHRMSLibrary'] ?? null);
        $units = $this->cleanString($sourceRow['Units'] ?? null);

        $metadataPayload = ['method' => $this->cleanString($sourceRow['Method'] ?? null)];

        foreach (self::METADATA_COLUMN_MAP as $sourceCol => $destCol) {
            $raw = $sourceRow[$sourceCol] ?? null;

            $metadataPayload[$destCol] = in_array($destCol, self::METADATA_DOUBLE_COLUMNS, true)
                ? $this->cleanDouble($raw)
                : $this->cleanString($raw);
        }

        $mainRows = [];

        foreach ($stationCols as $colName) {
            $raw = $sourceRow[$colName] ?? null;

            if ($raw === null || $raw === '') {
                continue;
            }

            $mapping = $this->stationMappingCache[$colName];
            $concentration = $this->cleanDouble($raw);

            $mainRows[] = [
                'file_id' => $fileId,
                'is_numeric_concentration' => $concentration !== null,
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
     * The id block must be empty before it is written into — otherwise the
     * insert collides with rows that were never deleted.
     */
    private function assertRangeIsFree(int $fileId): void
    {
        $occupied = DB::table('empodat_suspect_main')
            ->whereBetween('id', [$this->idFrom(), $this->idTo()])
            ->selectRaw('count(*) AS rows, min(file_id) AS min_file, max(file_id) AS max_file')
            ->first();

        $rows = (int) ($occupied->rows ?? 0);

        if ($rows === 0) {
            return;
        }

        throw new RuntimeException(sprintf(
            'ABORTED: %s row(s) still occupy ids %s..%s (file_id %s..%s). '
            .'Clear file_id=%d before importing into its range.',
            number_format($rows),
            number_format($this->idFrom()),
            number_format($this->idTo()),
            (string) $occupied->min_file,
            (string) $occupied->max_file,
            $fileId,
        ));
    }

    /**
     * The mapping must exist and be fully resolved before a single row is read.
     */
    private function assertStationMappingReady(int $fileId): void
    {
        $rows = DB::table('empodat_suspect_xlsx_stations_mapping')
            ->where('file_id', $fileId)
            ->selectRaw('count(*) AS rows, count(*) FILTER (WHERE station_id IS NULL) AS unresolved')
            ->first();

        if ((int) $rows->rows !== $this->expectedStationColumns()) {
            throw new RuntimeException(sprintf(
                'ABORTED: file_id=%d has %s mapping row(s), expected %d. Run the mapping seeder first.',
                $fileId,
                number_format((int) $rows->rows),
                $this->expectedStationColumns(),
            ));
        }

        if ((int) $rows->unresolved > 0) {
            throw new RuntimeException(sprintf(
                'ABORTED: %d mapping row(s) for file_id=%d have no station_id. Run the fill seeder first.',
                (int) $rows->unresolved,
                $fileId,
            ));
        }
    }

    /**
     * Every station column of the file must be in the mapping cache. The other
     * sources merely skip an unmapped column with a warning; here that would
     * silently drop a station's entire dataset and shrink the row count.
     *
     * @param  list<string>  $stationCols
     */
    private function assertStationColumnsMapped(array $stationCols): void
    {
        $missing = array_values(array_filter(
            $stationCols,
            fn (string $col): bool => ! isset($this->stationMappingCache[$col])
        ));

        if ($missing !== []) {
            throw new RuntimeException(
                'ABORTED: station column(s) absent from empodat_suspect_xlsx_stations_mapping: '
                .implode(', ', $missing).'.'
            );
        }
    }

    /**
     * Post-import check the other seeders lack: a NULL station is invisible in
     * station-filtered searches rather than obviously broken, so it is counted
     * and reported explicitly.
     */
    private function reportStationCoverage(int $fileId): void
    {
        $nulls = DB::table('empodat_suspect_main')
            ->where('file_id', $fileId)
            ->whereNull('station_id')
            ->count();

        if ($nulls === 0) {
            $this->command->info('Station coverage: every row has a station_id.');

            return;
        }

        $this->command->error('Station coverage: '.number_format($nulls).' row(s) have station_id = NULL.');
    }

    /**
     * @param  array<string, array<string, mixed>>  $substancesByKey
     */
    protected function insertSubstances(array $substancesByKey): int
    {
        if ($substancesByKey === []) {
            return 0;
        }

        $inserted = 0;

        foreach (array_chunk(array_values($substancesByKey), 500) as $chunk) {
            DB::table('empodat_suspect_substances')->insert($chunk);
            $inserted += count($chunk);
        }

        return $inserted;
    }

    /**
     * @param  list<string>  $header
     * @return list<string>
     */
    protected function extractStationColumns(array $header): array
    {
        $header = array_map(
            static fn (string $h): string => trim(str_replace("\xEF\xBB\xBF", '', $h)),
            $header
        );

        $startIndex = array_search(self::STATION_BLOCK_START_AFTER, $header, true);

        if ($startIndex === false) {
            throw new RuntimeException('No "'.self::STATION_BLOCK_START_AFTER.'" column in the header.');
        }

        $columns = [];
        $count = count($header);

        for ($i = $startIndex + 1; $i < $count; $i++) {
            if ($header[$i] === self::METADATA_BOUNDARY) {
                break;
            }

            if ($header[$i] === '') {
                continue;
            }

            $columns[] = $header[$i];
        }

        return $columns;
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

        if (in_array($cleaned, ['TRUE', '1', 'YES'], true)) {
            return true;
        }

        if (in_array($cleaned, ['FALSE', '0', 'NO'], true)) {
            return false;
        }

        return null;
    }
}
