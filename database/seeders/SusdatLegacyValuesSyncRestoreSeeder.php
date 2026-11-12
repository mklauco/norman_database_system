<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Susdat\LegacySyncPlanBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;

/**
 * Reverts SusdatLegacyValuesSyncSeeder: gives every cell listed in its newest record file
 * (one that no restore file names yet) the value it had before the sync, and every row its
 * previous updated_at.
 *
 * Stops, changing nothing, when any recorded cell no longer holds the value the sync wrote
 * or any row's updated_at is no longer the sync's start, because then somebody changed it
 * after the sync. Only the recorded cells and updated_at change; the run checks that the
 * id/code fingerprint and the fixed columns of the recorded rows are the same afterwards
 * and that the transaction wrote no row outside the record. Everything runs in one transaction under a short table lock.
 *
 * Not listed in DatabaseSeeder. Run by hand only:
 * php artisan db:seed --class=SusdatLegacyValuesSyncRestoreSeeder --force
 */
class SusdatLegacyValuesSyncRestoreSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        DB::disableQueryLog();

        $recordPath = $this->newestRecord();
        $recordSha256 = (string) hash_file('sha256', $recordPath);
        $record = $this->readRecord($recordPath);
        $this->say("Record {$recordPath}: ".count($record['rows']).' rows, SHA-256 '.$recordSha256.'.');

        $result = SusdatLegacyValuesSyncSeeder::withoutTelescope(fn (): array => DB::transaction(fn (): array => $this->restore($record, basename($recordPath), $recordSha256)));
        $committedBy = SusdatMissingLegacySubstancesSeeder::utcNow();

        $perColumn = [];

        foreach ($result['per_column'] as $column => $count) {
            $perColumn[] = "{$column} {$count}";
        }

        $this->report([
            'susdat legacy values sync restore: environment='.app()->environment(),
            "transaction started {$result['started_at']} UTC, work finished {$result['finished_at']} UTC, committed by {$committedBy} UTC",
            "rows restored {$result['rows']}, cells restored {$result['cells']}: ".implode(', ', $perColumn),
            "id/code fingerprint {$result['id_code_fingerprint']} before and after",
            "fixed columns of the recorded rows {$result['fixed_fingerprint']} before and after",
            "rows written by the transaction {$result['rows_written']}, all of them recorded rows",
            "restore file {$result['restore_path']}: SHA-256 {$result['restore_sha256']}",
        ]);
    }

    /**
     * Runs inside the transaction.
     *
     * @param  array{started_at_utc: string, rows: list<array{id: int, code: string, updated_at_before: ?string, cells: list<array{column: string, from: ?string, to: ?string}>}>}  $record
     * @return array<string, mixed>
     */
    private function restore(array $record, string $recordFile, string $recordSha256): array
    {
        $startedAt = SusdatLegacyValuesSyncSeeder::lockTable();

        $ids = array_column($record['rows'], 'id');
        SusdatLegacyValuesSyncSeeder::createIdTable($ids);

        $before = SusdatLegacyValuesSyncSeeder::readRows();
        SusdatLegacyValuesSyncSeeder::check('Recorded rows found', count($before), count($ids));
        $this->assertUnchangedSinceSync($record, $before);

        $rowsBefore = (int) DB::table('susdat_substances')->count();
        $idCodeBefore = SusdatMissingLegacySubstancesSeeder::idCodeFingerprint();
        $fixedBefore = SusdatLegacyValuesSyncSeeder::fixedColumnsFingerprint();
        SusdatLegacyValuesSyncSeeder::check('Rows written by this transaction before the updates', SusdatLegacyValuesSyncSeeder::rowsWrittenByThisTransaction()['written'], 0);

        $values = [];
        $updatedAt = [];
        $cellCount = 0;

        foreach ($record['rows'] as $row) {
            foreach ($row['cells'] as $cell) {
                $values[$cell['column']][$row['id']] = $cell['from'];
                $updatedAt[$cell['column']][$row['id']] = $row['updated_at_before'];
                $cellCount++;
            }
        }

        ksort($values);
        $perColumn = [];

        foreach ($values as $column => $columnValues) {
            SusdatLegacyValuesSyncSeeder::bulkUpdate($column, $columnValues, $updatedAt[$column]);
            $perColumn[$column] = count($columnValues);
        }

        $after = SusdatLegacyValuesSyncSeeder::readRows();
        $this->assertRestored($record, $before, $after);

        SusdatLegacyValuesSyncSeeder::check('Substances after', (int) DB::table('susdat_substances')->count(), $rowsBefore);
        SusdatLegacyValuesSyncSeeder::check('id/code fingerprint after', SusdatMissingLegacySubstancesSeeder::idCodeFingerprint(), $idCodeBefore);
        SusdatLegacyValuesSyncSeeder::check('Fixed columns of the recorded rows after', SusdatLegacyValuesSyncSeeder::fixedColumnsFingerprint(), $fixedBefore);
        $rowsWritten = SusdatLegacyValuesSyncSeeder::assertOnlyListedRowsWritten($ids);

        $path = storage_path(SusdatLegacyValuesSyncSeeder::RECORD_DIRECTORY.'/'.SusdatLegacyValuesSyncSeeder::RESTORED_PREFIX
            .SusdatLegacyValuesSyncSeeder::timestampForFile($startedAt).'.json');
        $sha256 = SusdatLegacyValuesSyncSeeder::writeJsonFile($path, [
            'seeder' => self::class,
            'environment' => app()->environment(),
            'record_file' => $recordFile,
            'record_sha256' => $recordSha256,
            'restored_at_utc' => $startedAt,
            'rows' => count($ids),
            'cells' => $cellCount,
            'cells_per_column' => $perColumn,
        ]);

        return [
            'started_at' => $startedAt,
            'finished_at' => SusdatMissingLegacySubstancesSeeder::utcNow(),
            'rows' => count($ids),
            'cells' => $cellCount,
            'per_column' => $perColumn,
            'id_code_fingerprint' => $idCodeBefore,
            'fixed_fingerprint' => $fixedBefore,
            'rows_written' => $rowsWritten,
            'restore_path' => $path,
            'restore_sha256' => $sha256,
        ];
    }

    /**
     * Every recorded cell must still hold what the sync wrote, and every row must still
     * carry the sync's updated_at.
     *
     * @param  array{started_at_utc: string, rows: list<array{id: int, code: string, cells: list<array{column: string, to: ?string}>}>}  $record
     * @param  array<int, array<string, string|null>>  $rows
     */
    private function assertUnchangedSinceSync(array $record, array $rows): void
    {
        foreach ($record['rows'] as $row) {
            $stored = $rows[$row['id']];

            $changed = $stored['code'] !== $row['code'] || $stored['updated_at'] !== $record['started_at_utc'];

            foreach ($row['cells'] as $cell) {
                $changed = $changed || ! SusdatLegacyValuesSyncSeeder::sameValue($cell['column'], $stored[$cell['column']], $cell['to']);
            }

            if ($changed) {
                $code = trim($row['code'], "\r\n \t");

                throw new RuntimeException("Substance {$code} was changed after the sync. Nothing was changed.");
            }
        }
    }

    /**
     * @param  array{rows: list<array{id: int, code: string, updated_at_before: ?string, cells: list<array{column: string, from: ?string}>}>}  $record
     * @param  array<int, array<string, string|null>>  $before
     * @param  array<int, array<string, string|null>>  $after
     */
    private function assertRestored(array $record, array $before, array $after): void
    {
        SusdatLegacyValuesSyncSeeder::check('Recorded rows read back', count($after), count($record['rows']));

        foreach ($record['rows'] as $row) {
            $code = trim($row['code'], "\r\n \t");
            $cells = array_column($row['cells'], null, 'column');

            if ($after[$row['id']]['updated_at'] !== $row['updated_at_before']) {
                throw new RuntimeException("Restored updated_at of {$code} differs from the record. Nothing was changed.");
            }

            foreach (LegacySyncPlanBuilder::syncColumns() as $column) {
                $same = isset($cells[$column])
                    ? SusdatLegacyValuesSyncSeeder::sameValue($column, $after[$row['id']][$column], $cells[$column]['from'])
                    : $after[$row['id']][$column] === $before[$row['id']][$column];

                if (! $same) {
                    throw new RuntimeException("Restored {$column} of {$code} differs from the record. Nothing was changed.");
                }
            }
        }
    }

    private function newestRecord(): string
    {
        $files = glob(storage_path(SusdatLegacyValuesSyncSeeder::RECORD_DIRECTORY.'/'.SusdatLegacyValuesSyncSeeder::RECORD_PREFIX.'*.json')) ?: [];
        sort($files, SORT_STRING);
        $restored = SusdatLegacyValuesSyncSeeder::restoredRecordNames();

        foreach (array_reverse($files) as $file) {
            if (! in_array(basename($file), $restored, true)) {
                return $file;
            }
        }

        throw new RuntimeException('No record file without a restore file found. Nothing was changed.');
    }

    /**
     * @return array{started_at_utc: string, rows: list<array{id: int, code: string, updated_at_before: ?string, cells: list<array{column: string, from: ?string, to: ?string}>}>}
     */
    private function readRecord(string $path): array
    {
        try {
            $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Record file is not valid JSON: {$exception->getMessage()}. Nothing was changed.");
        }

        SusdatLegacyValuesSyncSeeder::check('Record written by', (string) ($record['seeder'] ?? ''), SusdatLegacyValuesSyncSeeder::class);
        SusdatLegacyValuesSyncSeeder::check('Record environment', (string) ($record['environment'] ?? ''), app()->environment());

        $startedAt = $record['started_at_utc'] ?? null;
        $rows = $record['rows'] ?? null;

        if (! is_string($startedAt) || preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $startedAt) !== 1 || ! is_array($rows) || $rows === []) {
            throw new RuntimeException('Record file has no valid start time or rows. Nothing was changed.');
        }

        $allowed = LegacySyncPlanBuilder::syncColumns();
        $ids = [];

        foreach ($rows as $row) {
            if (! is_int($row['id'] ?? null) || ! is_string($row['code'] ?? null) || ! array_key_exists('updated_at_before', $row)
                || ($row['updated_at_before'] !== null && ! is_string($row['updated_at_before'])) || ! is_array($row['cells'] ?? null) || $row['cells'] === []) {
                throw new RuntimeException('Record file has a row without a valid id, code, updated_at_before or cells. Nothing was changed.');
            }

            if (isset($ids[$row['id']])) {
                throw new RuntimeException("Record file lists row {$row['id']} twice. Nothing was changed.");
            }

            $ids[$row['id']] = true;
            $columns = [];

            foreach ($row['cells'] as $cell) {
                $column = $cell['column'] ?? null;

                if (! is_string($column) || ! in_array($column, $allowed, true) || isset($columns[$column])
                    || ! array_key_exists('from', $cell) || ! array_key_exists('to', $cell)
                    || ($cell['from'] !== null && ! is_string($cell['from'])) || ($cell['to'] !== null && ! is_string($cell['to']))) {
                    throw new RuntimeException("Record file has an invalid or repeated cell for row {$row['id']}. Nothing was changed.");
                }

                $columns[$column] = true;
            }
        }

        return ['started_at_utc' => $startedAt, 'rows' => $rows];
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
