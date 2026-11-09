<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Issue 1.1 of the SUSDAT review: susdat_source_substance.substance_id was loaded
 * with the legacy SUSID number instead of susdat_substances.id, so sources are
 * paired with the wrong substances.
 *
 * Replaces the rows of susdat_source_substance with the pairs of the legacy table
 * susdat_source_join, exported to EXPORT_FILE. Each SUSID is matched to
 * susdat_substances.code to get susdat_substances.id, and each source code is
 * matched to suspect_list_exchange_sources.code. The missing source S133 is added
 * first.
 *
 * susdat_substances is only read; the run checks that the relation between its id
 * and code is unchanged. Everything that writes runs in one transaction, so any
 * failure changes nothing. The current rows are written to a backup CSV first,
 * which SusdatSourceSubstanceRestoreSeeder puts back.
 *
 * Not listed in DatabaseSeeder. Run by hand only:
 * php artisan db:seed --class=SusdatSourceSubstanceRepairSeeder --force
 */
class SusdatSourceSubstanceRepairSeeder extends Seeder
{
    public const DIRECTORY = 'app/susdat_source_repair';

    public const BACKUP_PREFIX = 'susdat_source_substance_backup_';

    public const NEW_SOURCE_CODE = 'S133';

    public const CHUNK_SIZE = 1000;

    /** How long to wait for a table lock before giving up; nothing changes then. */
    public const LOCK_TIMEOUT = '10s';

    /** Pairs fingerprint of the original rows, measured on production and dev on 2026-11-09. */
    public const ORIGINAL_LINKS_FINGERPRINT = 'cff0c98e39e35f18b956e1ace6ea54db';

    private const EXPORT_FILE = 'susdat_source_join_20261109T141938Z.csv';

    private const EXPORT_SHA256 = 'e0165ddbf8957e8b8bf8a8fbff6e25f73bafdcdf3860a6a636c49a901d804d1b';

    private const EXPORT_ROWS = 344844;

    /** Measured on production on 2026-11-09 at 14:19 UTC. */
    private const EXPECTED_LINKS_BEFORE = 283075;

    private const EXPECTED_SKIPPED_ROWS = 3233;

    private const EXPECTED_LINKS_AFTER = 341611;

    private const EXPECTED_ID_CODE_FINGERPRINT = '490a5f0518d36b4611bb4cd1a3f83d75';

    private const PAIR_KEY_FACTOR = 10_000_000;

    public function run(): void
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        DB::disableQueryLog();

        $exportPath = storage_path(self::DIRECTORY.'/'.self::EXPORT_FILE);
        $this->assertExportFile($exportPath);
        $this->say('Export '.$exportPath.': SHA-256 and row count as documented.');

        $sourceIds = self::sourceIdsByCode();

        if (array_key_exists(self::NEW_SOURCE_CODE, $sourceIds)) {
            throw new RuntimeException('Source S133 already exists, so this repair has already run. Nothing was changed.');
        }

        $substanceIds = self::substanceIdsByCode();
        $planned = $this->translateExport($exportPath, $sourceIds + [self::NEW_SOURCE_CODE => 0], $substanceIds);
        $linksBefore = DB::table('susdat_source_substance')->count();

        $this->assertSame('Export rows read', $planned['rows'], self::EXPORT_ROWS);
        $this->assertSame('Export rows with an unknown source', array_sum($planned['unknown_sources']), 0);
        $this->assertSame('Export rows whose SUSID matches more than one substance', $planned['ambiguous'], 0);

        if (self::enforcesProductionCounts()) {
            $this->assertSame('Links before the repair', $linksBefore, self::EXPECTED_LINKS_BEFORE);
            $this->assertSame('Export rows without a substance', $planned['skipped'], self::EXPECTED_SKIPPED_ROWS);
            $this->assertSame('Links after the repair', $planned['paired'], self::EXPECTED_LINKS_AFTER);
        }

        $this->say(sprintf(
            'Checks passed, nothing changed yet: links now %d, links after %d, export rows without a substance %d.',
            $linksBefore,
            $planned['paired'],
            $planned['skipped'],
        ));

        $result = DB::transaction(fn (): array => $this->repair($exportPath, $sourceIds, $substanceIds, $planned['paired']));
        $committedBy = self::utcNow();

        $this->report([
            'susdat_source_substance repair: environment='.app()->environment().' exact_counts='.(self::enforcesProductionCounts() ? 'yes' : 'no'),
            "transaction started {$result['started_at']} UTC, work finished {$result['finished_at']} UTC, committed by {$committedBy} UTC",
            "links before {$result['links_before']}, links after {$result['links_after']}, export rows without a substance {$planned['skipped']}, S133 id {$result['new_source_id']}",
            "id/code fingerprint {$result['id_code_fingerprint']} before and after",
            "links fingerprint before {$result['links_fingerprint_before']}, after {$result['links_fingerprint_after']}",
            "backup {$result['backup_path']}: {$result['backup_rows']} rows, SHA-256 {$result['backup_sha256']}",
            'peak memory '.round(memory_get_peak_usage(true) / 1048576).' MB',
        ]);
    }

    /**
     * The fingerprint of every id and code in susdat_substances.
     */
    public static function idCodeFingerprint(): string
    {
        return (string) DB::scalar(
            "SELECT md5(coalesce(string_agg(id::text || ':' || code, ',' ORDER BY id), '')) FROM susdat_substances"
        );
    }

    /**
     * The fingerprint of every pair in susdat_source_substance, comparable with fingerprintOf().
     */
    public static function linksFingerprint(): string
    {
        return (string) DB::scalar(
            "SELECT md5(coalesce(string_agg(lpad(source_id::text, 10, '0') || ':' || lpad(substance_id::text, 10, '0'), ',' "
            ."ORDER BY source_id, substance_id), '')) FROM susdat_source_substance"
        );
    }

    /**
     * Same value as linksFingerprint() for the same pairs, computed without
     * holding the whole text in memory.
     *
     * @param  list<int>  $keys  values from pairKey()
     */
    public static function fingerprintOf(array $keys): string
    {
        sort($keys, SORT_NUMERIC);
        $context = hash_init('md5');

        foreach ($keys as $index => $key) {
            hash_update($context, ($index === 0 ? '' : ',').sprintf(
                '%010d:%010d',
                intdiv($key, self::PAIR_KEY_FACTOR),
                $key % self::PAIR_KEY_FACTOR,
            ));
        }

        return hash_final($context);
    }

    /**
     * One integer per pair, ordered like (source_id, substance_id).
     */
    public static function pairKey(int $sourceId, int $substanceId): int
    {
        if ($substanceId >= self::PAIR_KEY_FACTOR) {
            throw new RuntimeException("substance_id {$substanceId} is too large for the pair key. Nothing was changed.");
        }

        return $sourceId * self::PAIR_KEY_FACTOR + $substanceId;
    }

    public static function utcNow(): string
    {
        return (string) DB::scalar("SELECT to_char(clock_timestamp() AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI:SS')");
    }

    /**
     * Exact production counts are checked everywhere except on a local or test
     * database, whose substances may differ from production.
     */
    public static function enforcesProductionCounts(): bool
    {
        return ! app()->environment('local', 'testing');
    }

    /**
     * Runs inside the transaction.
     *
     * @param  array<string, int>  $sourceIds
     * @param  array<string, int|null>  $substanceIds
     * @return array<string, int|string>
     */
    private function repair(string $exportPath, array $sourceIds, array $substanceIds, int $plannedLinks): array
    {
        DB::statement("SET LOCAL lock_timeout = '".self::LOCK_TIMEOUT."'");
        DB::statement('LOCK TABLE susdat_source_substance IN SHARE ROW EXCLUSIVE MODE');

        $startedAt = (string) DB::scalar("SELECT to_char(transaction_timestamp() AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI:SS')");
        $idCodeFingerprint = self::idCodeFingerprint();
        $linksBefore = DB::table('susdat_source_substance')->count();
        $linksFingerprintBefore = self::linksFingerprint();

        $this->assertSame('Links fingerprint before the repair', $linksFingerprintBefore, self::ORIGINAL_LINKS_FINGERPRINT);

        if (self::enforcesProductionCounts()) {
            $this->assertSame('id/code fingerprint before the repair', $idCodeFingerprint, self::EXPECTED_ID_CODE_FINGERPRINT);
        }

        $backup = $this->writeBackup($startedAt);
        $this->assertSame('Rows in the backup', $backup['rows'], $linksBefore);
        $this->assertSame('Backup fingerprint', $backup['fingerprint'], $linksFingerprintBefore);

        $newSourceId = $this->addNewSource($startedAt);

        DB::table('susdat_source_substance')->delete();

        $keys = [];
        $chunk = [];
        $insertChunk = function () use (&$chunk): void {
            DB::table('susdat_source_substance')->insert($chunk);
            $chunk = [];
        };

        $this->translateExport(
            $exportPath,
            $sourceIds + [self::NEW_SOURCE_CODE => $newSourceId],
            $substanceIds,
            function (int $sourceId, int $substanceId) use (&$keys, &$chunk, $insertChunk, $startedAt): void {
                $keys[] = self::pairKey($sourceId, $substanceId);
                $chunk[] = [
                    'source_id' => $sourceId,
                    'substance_id' => $substanceId,
                    'created_at' => $startedAt,
                    'updated_at' => $startedAt,
                ];

                if (count($chunk) === self::CHUNK_SIZE) {
                    $insertChunk();
                }
            },
        );

        if ($chunk !== []) {
            $insertChunk();
        }

        $linksAfter = DB::table('susdat_source_substance')->count();
        $linksFingerprintAfter = self::linksFingerprint();

        $this->assertSame('Links after the repair', $linksAfter, $plannedLinks);
        $this->assertSame('Links fingerprint after the repair', $linksFingerprintAfter, self::fingerprintOf($keys));
        $this->assertSame('id/code fingerprint', self::idCodeFingerprint(), $idCodeFingerprint);

        return [
            'started_at' => $startedAt,
            'finished_at' => self::utcNow(),
            'links_before' => $linksBefore,
            'links_after' => $linksAfter,
            'new_source_id' => $newSourceId,
            'id_code_fingerprint' => $idCodeFingerprint,
            'links_fingerprint_before' => $linksFingerprintBefore,
            'links_fingerprint_after' => $linksFingerprintAfter,
            'backup_path' => $backup['path'],
            'backup_rows' => $backup['rows'],
            'backup_sha256' => $backup['sha256'],
        ];
    }

    /**
     * Reads the export line by line. Calls $onPair for every pair that has exactly
     * one substance and a known source.
     *
     * @param  array<string, int>  $sourceIds
     * @param  array<string, int|null>  $substanceIds
     * @return array{rows: int, paired: int, skipped: int, ambiguous: int, unknown_sources: array<string, int>}
     */
    private function translateExport(string $path, array $sourceIds, array $substanceIds, ?callable $onPair = null): array
    {
        $counts = ['rows' => 0, 'paired' => 0, 'skipped' => 0, 'ambiguous' => 0, 'unknown_sources' => []];

        foreach (File::lines($path)->skip(1) as $line) {
            if ($line === '') {
                continue;
            }

            [$susId, $sourceCode] = explode(',', $line);
            $counts['rows']++;

            if (! array_key_exists($sourceCode, $sourceIds)) {
                $counts['unknown_sources'][$sourceCode] = ($counts['unknown_sources'][$sourceCode] ?? 0) + 1;

                continue;
            }

            if (! array_key_exists($susId, $substanceIds)) {
                $counts['skipped']++;

                continue;
            }

            $substanceId = $substanceIds[$susId];

            if ($substanceId === null) {
                $counts['ambiguous']++;

                continue;
            }

            $counts['paired']++;

            if ($onPair !== null) {
                $onPair($sourceIds[$sourceCode], $substanceId);
            }
        }

        return $counts;
    }

    /**
     * Writes the current rows of susdat_source_substance to a new file; never
     * overwrites one.
     *
     * @return array{path: string, rows: int, sha256: string, fingerprint: string}
     */
    private function writeBackup(string $startedAt): array
    {
        $path = storage_path(self::DIRECTORY.'/'.self::BACKUP_PREFIX
            .str_replace(['-', ' ', ':'], ['', 'T', ''], $startedAt).'Z.csv');
        $handle = @fopen($path, 'x');

        if ($handle === false) {
            throw new RuntimeException("Cannot create the backup file {$path}. Nothing was changed.");
        }

        fwrite($handle, "source_id,substance_id,created_at,updated_at\n");
        $rows = 0;
        $keys = [];

        $links = DB::table('susdat_source_substance')
            ->select(['source_id', 'substance_id', 'created_at', 'updated_at'])
            ->orderBy('source_id')
            ->orderBy('substance_id')
            ->cursor();

        foreach ($links as $link) {
            fwrite($handle, "{$link->source_id},{$link->substance_id},{$link->created_at},{$link->updated_at}\n");
            $keys[] = self::pairKey((int) $link->source_id, (int) $link->substance_id);
            $rows++;
        }

        fclose($handle);

        return [
            'path' => $path,
            'rows' => $rows,
            'sha256' => (string) hash_file('sha256', $path),
            'fingerprint' => self::fingerprintOf($keys),
        ];
    }

    /**
     * The id sequence is moved to MAX(id) first, because source rows were seeded
     * with explicit ids, which can leave it behind.
     */
    private function addNewSource(string $startedAt): int
    {
        DB::statement(
            "SELECT setval(pg_get_serial_sequence('suspect_list_exchange_sources', 'id'), (SELECT MAX(id) FROM suspect_list_exchange_sources))"
        );

        return (int) DB::table('suspect_list_exchange_sources')->insertGetId([
            'code' => self::NEW_SOURCE_CODE,
            'name' => 'MPLEACH',
            'description' => 'Chemicals Leaching from Microplastics',
            'order' => 133,
            'show' => 1,
            'created_at' => $startedAt,
            'updated_at' => $startedAt,
        ]);
    }

    private function assertExportFile(string $path): void
    {
        if (! is_file($path)) {
            throw new RuntimeException("Export file not found: {$path}. Nothing was changed.");
        }

        if (hash_file('sha256', $path) !== self::EXPORT_SHA256) {
            throw new RuntimeException("Export file has an unexpected SHA-256: {$path}. Nothing was changed.");
        }
    }

    private function assertSame(string $description, int|string $actual, int|string $expected): void
    {
        if ($actual !== $expected) {
            throw new RuntimeException("{$description}: expected {$expected}, found {$actual}. Nothing was changed.");
        }
    }

    /**
     * @return array<string, int>
     */
    private static function sourceIdsByCode(): array
    {
        $ids = [];

        foreach (DB::table('suspect_list_exchange_sources')->select(['id', 'code'])->orderBy('id')->get() as $source) {
            $code = (string) $source->code;

            if (array_key_exists($code, $ids)) {
                throw new RuntimeException("Source code {$code} exists more than once. Nothing was changed.");
            }

            $ids[$code] = (int) $source->id;
        }

        return $ids;
    }

    /**
     * Codes are trimmed because some end in a carriage return (issue 3.1). A code
     * held by more than one substance maps to null.
     *
     * @return array<string, int|null>
     */
    private static function substanceIdsByCode(): array
    {
        $ids = [];

        foreach (DB::table('susdat_substances')->select(['id', 'code'])->orderBy('id')->cursor() as $substance) {
            $code = trim((string) $substance->code, "\r\n ");
            $ids[$code] = array_key_exists($code, $ids) ? null : (int) $substance->id;
        }

        return $ids;
    }

    private function say(string $line): void
    {
        $this->command?->info($line);
    }

    /**
     * @param  list<string>  $lines
     */
    private function report(array $lines): void
    {
        foreach ($lines as $line) {
            $this->say($line);
            Log::info($line);
        }
    }
}
