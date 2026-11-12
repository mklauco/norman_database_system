<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Console\Commands\SusdatLegacySyncPlan;
use App\Services\Susdat\LegacySyncPlanBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use JsonException;
use Laravel\Telescope\Telescope;
use RuntimeException;

/**
 * Issue of the SUSDAT review: about 5,900 substances of susdat_substances show stale or
 * missing values, because legacy NDS1 (susdat.susdat) was edited after the one-time copy.
 *
 * Applies the newest plan that `php artisan susdat:legacy-sync-plan` wrote for this
 * environment to database/seeders/seeds/susdat/legacy_sync/: every planned cell gets the
 * final legacy value. Only the 19 sync columns (15 page columns and 4 metadata columns) and
 * updated_at of the planned rows change; id and code never do, and no other table is
 * touched. Cells somebody changed in NDS2.0 are not in the plan (held_back).
 *
 * Before writing, every planned cell must still hold the plan's `from` value. Afterwards
 * the run checks that every planned cell holds `to`, every other sync column of those rows
 * is untouched, the id/code fingerprint and the fixed columns of the planned rows are
 * unchanged, and the transaction wrote no row outside the plan. Everything runs in one transaction under a short table
 * lock, so any failure changes nothing. A record of the previous values is written to
 * RECORD_DIRECTORY; SusdatLegacyValuesSyncRestoreSeeder puts them back.
 *
 * Not listed in DatabaseSeeder. Run by hand only:
 * php artisan db:seed --class=SusdatLegacyValuesSyncSeeder --force
 */
class SusdatLegacyValuesSyncSeeder extends Seeder
{
    public const RECORD_DIRECTORY = 'app/susdat_legacy_sync';

    public const RECORD_PREFIX = 'susdat_legacy_sync_applied_';

    public const RESTORED_PREFIX = 'susdat_legacy_sync_restored_';

    public const ID_TABLE = 'susdat_legacy_sync_ids';

    public const CHUNK = 1000;

    private const PLAN_KIND = LegacySyncPlanBuilder::PLAN_KIND;

    private const JSON_COLUMNS = ['metadata_synonyms', 'metadata_cas', 'metadata_ms_ready', 'metadata_general'];

    /** The columns that must never change for a planned row. */
    private const FIXED_COLUMNS = ['id', 'code', 'status', 'canonical_id', 'merged_at', 'merged_by', 'merge_reason', 'added_by', 'created_at', 'deleted_at', 'relevant_to_norman'];

    /** numeric(20,8) rounds to eight decimals, so its tolerance is above half a unit of the last one. */
    private const MASS_TOLERANCES = ['mass_iso' => 1e-9, 'average_mass' => 1e-8];

    public function run(): void
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        DB::disableQueryLog();

        $environment = SusdatMissingLegacySubstancesSeeder::enforcesProductionCounts() ? 'production' : 'local';
        $planPath = self::newestPlan($environment);
        $planSha256 = (string) hash_file('sha256', $planPath);
        $this->say("Plan {$planPath}: SHA-256 {$planSha256}.");

        $plan = self::readPlan($planPath, $environment);
        $this->assertNotApplied($planSha256);
        $this->assertProductionState($plan['expected']);

        File::ensureDirectoryExists(storage_path(self::RECORD_DIRECTORY));

        $this->say("Checks passed, nothing changed yet: {$plan['counts']['total_cells']} cells in {$plan['counts']['rows']} rows to write.");

        $result = self::withoutTelescope(fn (): array => DB::transaction(fn (): array => $this->apply($plan, basename($planPath), $planSha256)));
        $committedBy = SusdatMissingLegacySubstancesSeeder::utcNow();

        $perColumn = [];

        foreach ($result['per_column'] as $column => $count) {
            $perColumn[] = "{$column} {$count}";
        }

        $this->report([
            'susdat legacy values sync: environment='.app()->environment().' exact_counts='.(SusdatMissingLegacySubstancesSeeder::enforcesProductionCounts() ? 'yes' : 'no'),
            "transaction started {$result['started_at']} UTC, work finished {$result['finished_at']} UTC, committed by {$committedBy} UTC",
            "rows changed {$result['rows']}, cells changed {$result['cells']}: ".implode(', ', $perColumn),
            "id/code fingerprint {$result['id_code_fingerprint']} before and after",
            "fixed columns of the planned rows {$result['fixed_fingerprint']} before and after",
            "rows written by the transaction {$result['rows_written']}, all of them planned rows",
            "record {$result['record_path']}: SHA-256 {$result['record_sha256']}",
            "held back in the plan, not applied: {$plan['counts']['held_back']}",
        ]);
    }

    /**
     * Runs the work with Telescope's recording paused, as the bulk EMPODAT seeders do:
     * recording every query made the table lock last about four times longer on dev.
     *
     * @template T
     *
     * @param  callable(): T  $work
     * @return T
     */
    public static function withoutTelescope(callable $work): mixed
    {
        $wasRecording = class_exists(Telescope::class) && Telescope::isRecording();

        if ($wasRecording) {
            Telescope::stopRecording();
        }

        try {
            return $work();
        } finally {
            if ($wasRecording) {
                Telescope::startRecording();
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public static function columnTypes(): array
    {
        $types = [];

        foreach (LegacySyncPlanBuilder::syncColumns() as $column) {
            $types[$column] = match (true) {
                $column === 'mass_iso' => 'double precision',
                $column === 'average_mass' => 'numeric',
                in_array($column, self::JSON_COLUMNS, true) => 'json',
                default => 'text',
            };
        }

        return $types;
    }

    /**
     * Strict for text and json, numeric within the column's tolerance for masses.
     */
    public static function sameValue(string $column, ?string $actual, ?string $expected): bool
    {
        if ($actual === null || $expected === null) {
            return $actual === $expected;
        }

        if (isset(self::MASS_TOLERANCES[$column])) {
            return is_numeric($actual) && is_numeric($expected)
                && abs((float) $actual - (float) $expected) <= self::MASS_TOLERANCES[$column];
        }

        return $actual === $expected;
    }

    /**
     * @param  list<int>  $ids
     */
    public static function createIdTable(array $ids): void
    {
        DB::statement('CREATE TEMP TABLE '.self::ID_TABLE.' (id bigint PRIMARY KEY) ON COMMIT DROP');

        foreach (array_chunk($ids, self::CHUNK) as $chunk) {
            DB::insert(
                'INSERT INTO '.self::ID_TABLE.' (id) VALUES '.implode(', ', array_fill(0, count($chunk), '(?)')),
                $chunk,
            );
        }
    }

    /**
     * The rows listed in the id table: code, the 19 sync columns and updated_at, all as text.
     *
     * @return array<int, array<string, string|null>>
     */
    public static function readRows(): array
    {
        $columns = implode(', ', array_map(fn (string $column): string => "s.{$column}::text AS {$column}", LegacySyncPlanBuilder::syncColumns()));
        $rows = [];

        foreach (DB::select(
            "SELECT s.id::text AS id, s.code::text AS code, {$columns}, s.updated_at::text AS updated_at
             FROM susdat_substances s JOIN ".self::ID_TABLE.' t ON t.id = s.id ORDER BY s.id'
        ) as $row) {
            $row = (array) $row;
            $rows[(int) $row['id']] = $row;
        }

        return $rows;
    }

    /**
     * How many rows of susdat_substances this transaction wrote, and how many of those are
     * not in the id table. Nobody else can write while the table is locked, so the two
     * numbers prove that no other row changed. Unlike a fingerprint of every row (1.7 s on
     * production) it reads no row data, which keeps the lock short.
     *
     * @return array{written: int, outside: int}
     */
    public static function rowsWrittenByThisTransaction(): array
    {
        $counts = DB::selectOne(
            'SELECT count(*) AS written,
                    count(*) FILTER (WHERE NOT EXISTS (SELECT 1 FROM '.self::ID_TABLE.' t WHERE t.id = s.id)) AS outside
             FROM susdat_substances s WHERE s.xmin = pg_current_xact_id()::xid'
        );

        return ['written' => (int) $counts->written, 'outside' => (int) $counts->outside];
    }

    /**
     * @param  list<int>  $ids
     */
    public static function assertOnlyListedRowsWritten(array $ids): int
    {
        $written = self::rowsWrittenByThisTransaction();

        self::check('Rows written outside the list', $written['outside'], 0);
        self::check('Rows written by this transaction', $written['written'], count($ids));

        return $written['written'];
    }

    public static function fixedColumnsFingerprint(): string
    {
        $columns = implode(', ', array_map(fn (string $column): string => "s.{$column}", self::FIXED_COLUMNS));

        return (string) DB::scalar(
            "SELECT md5(coalesce(string_agg(md5(ROW({$columns})::text), '' ORDER BY s.id), ''))
             FROM susdat_substances s JOIN ".self::ID_TABLE.' t ON t.id = s.id'
        );
    }

    /**
     * Sets one column and updated_at for the given rows, in chunks.
     *
     * @param  array<int, string|null>  $values  id => new value (text, null for NULL)
     * @param  array<int, string|null>  $updatedAt  id => new updated_at (text, null for NULL)
     */
    public static function bulkUpdate(string $column, array $values, array $updatedAt): void
    {
        $type = self::columnTypes()[$column] ?? throw new RuntimeException("Column {$column} is not a sync column. Nothing was changed.");

        foreach (array_chunk($values, self::CHUNK, true) as $chunk) {
            $bindings = [];

            foreach ($chunk as $id => $value) {
                array_push($bindings, $id, $value, $updatedAt[$id]);
            }

            $updated = DB::update(
                "UPDATE susdat_substances AS s SET {$column} = v.val::{$type}, updated_at = v.ts::timestamp
                 FROM (VALUES ".implode(', ', array_fill(0, count($chunk), '(?::bigint, ?::text, ?::text)')).') AS v(id, val, ts)
                 WHERE s.id = v.id',
                $bindings,
            );

            self::check("Rows updated in {$column}", $updated, count($chunk));
        }
    }

    /**
     * Locks the table, as the other SUSDAT seeders do, and returns the transaction start in UTC.
     */
    public static function lockTable(): string
    {
        DB::statement("SET LOCAL lock_timeout = '".SusdatMissingLegacySubstancesSeeder::LOCK_TIMEOUT."'");
        DB::statement('LOCK TABLE susdat_substances IN SHARE ROW EXCLUSIVE MODE');

        return (string) DB::scalar("SELECT to_char(transaction_timestamp() AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI:SS')");
    }

    /**
     * Creates the file (never overwrites) and returns its SHA-256.
     *
     * @param  array<string, mixed>  $content
     */
    public static function writeJsonFile(string $path, array $content): string
    {
        $handle = @fopen($path, 'x');

        if ($handle === false) {
            throw new RuntimeException("Cannot create the file {$path}. Nothing was changed.");
        }

        $text = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";

        if (fwrite($handle, $text) !== strlen($text)) {
            fclose($handle);

            throw new RuntimeException("Cannot write the file {$path}. Nothing was changed.");
        }

        if (! fclose($handle)) {
            throw new RuntimeException("Cannot close the file {$path}. Nothing was changed.");
        }

        return (string) hash_file('sha256', $path);
    }

    /**
     * Names of the applied records that a restore file says were undone.
     *
     * @return list<string>
     */
    public static function restoredRecordNames(): array
    {
        $names = [];

        foreach (glob(storage_path(self::RECORD_DIRECTORY.'/'.self::RESTORED_PREFIX.'*.json')) ?: [] as $file) {
            $restored = json_decode((string) file_get_contents($file), true);

            if (is_array($restored) && isset($restored['record_file'])) {
                $names[] = (string) $restored['record_file'];
            }
        }

        return $names;
    }

    public static function timestampForFile(string $startedAt): string
    {
        return str_replace(['-', ' ', ':'], ['', 'T', ''], $startedAt).'Z';
    }

    public static function check(string $description, int|string $actual, int|string $expected): void
    {
        if ($actual !== $expected) {
            throw new RuntimeException("{$description}: expected {$expected}, found {$actual}. Nothing was changed.");
        }
    }

    public static function newestPlan(string $environment): string
    {
        $files = glob(database_path(SusdatLegacySyncPlan::PLAN_DIRECTORY.'/'.SusdatLegacySyncPlan::PLAN_PREFIX.$environment.'_*.json')) ?: [];
        sort($files, SORT_STRING);

        if ($files === []) {
            throw new RuntimeException("No {$environment} plan found in ".database_path(SusdatLegacySyncPlan::PLAN_DIRECTORY).'. Nothing was changed.');
        }

        return (string) end($files);
    }

    /**
     * Reads and validates a plan.
     *
     * @return array{counts: array<string, mixed>, expected: array<string, mixed>, cells_sha256: string, cells: list<array{id: int, code: string, column: string, from: ?string, to: ?string}>}
     */
    public static function readPlan(string $path, string $environment): array
    {
        try {
            $plan = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Plan file is not valid JSON: {$exception->getMessage()}. Nothing was changed.");
        }

        self::check('Plan kind', (string) ($plan['kind'] ?? ''), self::PLAN_KIND);
        self::check('Plan format', (int) ($plan['format'] ?? 0), LegacySyncPlanBuilder::PLAN_FORMAT);
        self::check('Plan environment', (string) ($plan['environment'] ?? ''), $environment);

        $cells = $plan['cells'] ?? null;

        if (! is_array($cells) || $cells === []) {
            throw new RuntimeException('The plan has no cells. Nothing was changed.');
        }

        self::check('Plan cells SHA-256', hash('sha256', json_encode($cells, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)), (string) ($plan['cells_sha256'] ?? ''));

        $allowed = LegacySyncPlanBuilder::syncColumns();
        $seen = [];

        foreach ($cells as $cell) {
            $column = $cell['column'] ?? null;

            if (! is_int($cell['id'] ?? null) || ! is_string($cell['code'] ?? null) || ! is_string($column) || ! in_array($column, $allowed, true)) {
                throw new RuntimeException('The plan has a cell with an unknown id, code or column. Nothing was changed.');
            }

            $key = $cell['id'].'|'.$column;

            if (isset($seen[$key])) {
                throw new RuntimeException("The plan has the cell {$cell['code']} {$column} twice. Nothing was changed.");
            }

            $seen[$key] = true;

            if (! array_key_exists('from', $cell) || ! array_key_exists('to', $cell) || ($cell['from'] !== null && ! is_string($cell['from'])) || ($cell['to'] !== null && ! is_string($cell['to']))) {
                throw new RuntimeException("The plan cell {$cell['code']} {$column} has a from or to that is neither text nor null. Nothing was changed.");
            }

            self::assertValueFits($cell['code'], $column, $cell['to']);
        }

        foreach (['counts', 'expected'] as $part) {
            if (! is_array($plan[$part] ?? null)) {
                throw new RuntimeException("The plan has no {$part}. Nothing was changed.");
            }
        }

        $plan['counts']['total_cells'] = count($cells);
        $plan['counts']['rows'] = count(array_unique(array_column($cells, 'id')));
        $plan['counts']['held_back'] = (int) ($plan['counts']['held_back'] ?? 0);

        return $plan;
    }

    private static function assertValueFits(string $code, string $column, ?string $to): void
    {
        if ($to === null) {
            return;
        }

        if (in_array($column, self::JSON_COLUMNS, true)) {
            try {
                json_decode($to, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new RuntimeException("The plan value of {$code} {$column} is not valid JSON. Nothing was changed.");
            }
        }

        if (isset(self::MASS_TOLERANCES[$column]) && ! is_numeric($to)) {
            throw new RuntimeException("The plan value of {$code} {$column} is not numeric. Nothing was changed.");
        }
    }

    /**
     * Runs inside the transaction.
     *
     * @param  array{expected: array<string, mixed>, cells_sha256: string, cells: list<array{id: int, code: string, column: string, from: ?string, to: ?string}>}  $plan
     * @return array<string, mixed>
     */
    private function apply(array $plan, string $planFile, string $planSha256): array
    {
        $startedAt = self::lockTable();

        $this->assertProductionState($plan['expected']);

        $byId = [];
        $byColumn = [];

        foreach ($plan['cells'] as $cell) {
            $byId[$cell['id']][$cell['column']] = $cell;
            $byColumn[$cell['column']][$cell['id']] = $cell;
        }

        ksort($byId);
        $ids = array_keys($byId);

        self::createIdTable($ids);

        $before = self::readRows();
        self::check('Planned rows found', count($before), count($ids));
        $this->assertMatchesPlan($byId, $before);

        $rowsBefore = (int) DB::table('susdat_substances')->count();
        $idCodeBefore = SusdatMissingLegacySubstancesSeeder::idCodeFingerprint();
        $fixedBefore = self::fixedColumnsFingerprint();
        self::check('Rows written by this transaction before the updates', self::rowsWrittenByThisTransaction()['written'], 0);

        $perColumn = [];

        foreach ($byColumn as $column => $cells) {
            self::bulkUpdate(
                $column,
                array_map(fn (array $cell): ?string => $cell['to'], $cells),
                array_map(fn (array $cell): string => $startedAt, $cells),
            );
            $perColumn[$column] = count($cells);
        }

        ksort($perColumn);

        $after = self::readRows();
        $this->assertApplied($byId, $before, $after, $startedAt);

        self::check('Substances after', (int) DB::table('susdat_substances')->count(), $rowsBefore);
        self::check('id/code fingerprint after', SusdatMissingLegacySubstancesSeeder::idCodeFingerprint(), $idCodeBefore);
        self::check('Fixed columns of the planned rows after', self::fixedColumnsFingerprint(), $fixedBefore);
        $rowsWritten = self::assertOnlyListedRowsWritten($ids);

        $record = $this->writeRecord($startedAt, $planFile, $planSha256, $plan['cells_sha256'], $byId, $before);

        return [
            'started_at' => $startedAt,
            'finished_at' => SusdatMissingLegacySubstancesSeeder::utcNow(),
            'rows' => count($ids),
            'cells' => count($plan['cells']),
            'per_column' => $perColumn,
            'id_code_fingerprint' => $idCodeBefore,
            'fixed_fingerprint' => $fixedBefore,
            'rows_written' => $rowsWritten,
            'record_path' => $record['path'],
            'record_sha256' => $record['sha256'],
        ];
    }

    /**
     * Every planned cell must still hold the value the plan was made from.
     *
     * @param  array<int, array<string, array{id: int, code: string, column: string, from: ?string, to: ?string}>>  $byId
     * @param  array<int, array<string, string|null>>  $rows
     */
    private function assertMatchesPlan(array $byId, array $rows): void
    {
        $mismatches = [];
        $total = 0;

        foreach ($byId as $id => $cells) {
            foreach ($cells as $column => $cell) {
                if (trim((string) $rows[$id]['code'], "\r\n \t") !== $cell['code']) {
                    throw new RuntimeException("Row {$id} has another code than the plan's {$cell['code']}. Nothing was changed.");
                }

                if (! self::sameValue($column, $rows[$id][$column], $cell['from'])) {
                    $total++;

                    if (count($mismatches) < 20) {
                        $mismatches[] = "{$cell['code']} {$column}: plan from ".$this->shorten($cell['from']).', found '.$this->shorten($rows[$id][$column]);
                    }
                }
            }
        }

        if ($total > 0) {
            throw new RuntimeException("{$total} planned cells no longer hold the plan's `from` value, for example: ".implode('; ', $mismatches).'. Nothing was changed.');
        }
    }

    /**
     * @param  array<int, array<string, array{id: int, code: string, column: string, from: ?string, to: ?string}>>  $byId
     * @param  array<int, array<string, string|null>>  $before
     * @param  array<int, array<string, string|null>>  $after
     */
    private function assertApplied(array $byId, array $before, array $after, string $startedAt): void
    {
        self::check('Planned rows read back', count($after), count($byId));

        foreach ($byId as $id => $cells) {
            $code = $cells[array_key_first($cells)]['code'];

            if ($after[$id]['updated_at'] !== $startedAt) {
                throw new RuntimeException("updated_at of {$code} is {$after[$id]['updated_at']}, not {$startedAt}. Nothing was changed.");
            }

            foreach (LegacySyncPlanBuilder::syncColumns() as $column) {
                $same = isset($cells[$column])
                    ? self::sameValue($column, $after[$id][$column], $cells[$column]['to'])
                    : $after[$id][$column] === $before[$id][$column];

                if (! $same) {
                    throw new RuntimeException("Stored {$column} of {$code} differs from the plan. Nothing was changed.");
                }
            }
        }
    }

    /**
     * @param  array<int, array<string, array{id: int, code: string, column: string, from: ?string, to: ?string}>>  $byId
     * @param  array<int, array<string, string|null>>  $before
     * @return array{path: string, sha256: string}
     */
    private function writeRecord(string $startedAt, string $planFile, string $planSha256, string $cellsSha256, array $byId, array $before): array
    {
        $path = storage_path(self::RECORD_DIRECTORY.'/'.self::RECORD_PREFIX.self::timestampForFile($startedAt).'.json');

        $rows = [];

        foreach ($byId as $id => $cells) {
            $rows[] = [
                'id' => $id,
                'code' => $before[$id]['code'],
                'updated_at_before' => $before[$id]['updated_at'],
                'cells' => array_map(fn (array $cell): array => [
                    'column' => $cell['column'],
                    'from' => $before[$id][$cell['column']],
                    'to' => $cell['to'],
                ], array_values($cells)),
            ];
        }

        $sha256 = self::writeJsonFile($path, [
            'seeder' => self::class,
            'environment' => app()->environment(),
            'started_at_utc' => $startedAt,
            'plan_file' => $planFile,
            'plan_sha256' => $planSha256,
            'cells_sha256' => $cellsSha256,
            'rows' => $rows,
        ]);

        return ['path' => $path, 'sha256' => $sha256];
    }

    /**
     * A plan that was applied and not restored since must not be applied again.
     */
    private function assertNotApplied(string $planSha256): void
    {
        $restored = self::restoredRecordNames();

        foreach (glob(storage_path(self::RECORD_DIRECTORY.'/'.self::RECORD_PREFIX.'*.json')) ?: [] as $file) {
            $record = json_decode((string) file_get_contents($file), true);

            if (is_array($record) && ($record['plan_sha256'] ?? null) === $planSha256 && ! in_array(basename($file), $restored, true)) {
                throw new RuntimeException('This plan was already applied, see '.basename($file).'. Nothing was changed.');
            }
        }
    }

    /**
     * Exact production counts are checked before and inside the transaction; a local or
     * test database is not checked.
     *
     * @param  array<string, mixed>  $expected
     */
    private function assertProductionState(array $expected): void
    {
        if (! SusdatMissingLegacySubstancesSeeder::enforcesProductionCounts()) {
            return;
        }

        $counts = SusdatQuarantinedSubstancesMergeSeeder::statusCounts();

        self::check('Substances before', $counts['rows'], (int) ($expected['rows'] ?? -1));
        self::check('Highest id before', (int) DB::table('susdat_substances')->max('id'), (int) ($expected['max_id'] ?? -1));
        self::check('Active before', $counts['active'], (int) ($expected['active'] ?? -1));
        self::check('Merged before', $counts['merged'], (int) ($expected['merged'] ?? -1));
        self::check('id/code fingerprint before', SusdatMissingLegacySubstancesSeeder::idCodeFingerprint(), (string) ($expected['id_code_fingerprint'] ?? ''));
    }

    private function shorten(?string $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return mb_strlen($value) > 80 ? mb_substr($value, 0, 80).'...' : $value;
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
