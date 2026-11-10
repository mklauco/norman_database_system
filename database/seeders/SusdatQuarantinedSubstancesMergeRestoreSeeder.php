<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;

/**
 * Reverts SusdatQuarantinedSubstancesMergeSeeder: gives the 52 substances listed in its
 * newest record file the values they had before the merge (status, canonical_id,
 * merged_at, merged_by, merge_reason and updated_at).
 *
 * Stops, changing nothing, when any of them was changed after the merge, or when another
 * substance points to one of them through canonical_id. Only these 52 rows change; the
 * run checks that the id/code fingerprint and the merge columns of every other row are
 * the same afterwards. Everything runs in one transaction.
 *
 * Not listed in DatabaseSeeder. Run by hand only:
 * php artisan db:seed --class=SusdatQuarantinedSubstancesMergeRestoreSeeder --force
 */
class SusdatQuarantinedSubstancesMergeRestoreSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        DB::disableQueryLog();

        $recordPath = $this->newestRecord();
        $record = $this->readRecord($recordPath);
        $this->say("Record {$recordPath}: ".count($record['substances']).' substances, SHA-256 '.hash_file('sha256', $recordPath).'.');

        $result = DB::transaction(fn (): array => $this->restore($record));
        $committedBy = SusdatMissingLegacySubstancesSeeder::utcNow();

        $this->report([
            'susdat quarantined substances merge restore: environment='.app()->environment(),
            "transaction started {$result['started_at']} UTC, work finished {$result['finished_at']} UTC, committed by {$committedBy} UTC",
            "substances {$result['rows']}; active before {$result['active_before']}, after {$result['active_after']}; merged before {$result['merged_before']}, after {$result['merged_after']}",
            "id/code fingerprint {$result['id_code_fingerprint']} before and after",
            "merge columns of the other rows {$result['other_rows_fingerprint']} before and after",
        ]);
    }

    /**
     * Runs inside the transaction.
     *
     * @param  array{started_at_utc: string, substances: list<array{id: int, code: string, canonical_id: int, merge_reason: string, before: array<string, int|string|null>}>}  $record
     * @return array<string, int|string>
     */
    private function restore(array $record): array
    {
        DB::statement("SET LOCAL lock_timeout = '".SusdatMissingLegacySubstancesSeeder::LOCK_TIMEOUT."'");
        DB::statement('LOCK TABLE susdat_substances IN SHARE ROW EXCLUSIVE MODE');

        $startedAt = (string) DB::scalar("SELECT to_char(transaction_timestamp() AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI:SS')");
        $ids = array_column($record['substances'], 'id');

        $this->assertUnchanged($record);
        $this->check(
            'Substances whose canonical_id points to one of these',
            DB::table('susdat_substances')->whereIn('canonical_id', $ids)->count(),
            0,
        );

        $countsBefore = SusdatQuarantinedSubstancesMergeSeeder::statusCounts();
        $idCodeBefore = SusdatMissingLegacySubstancesSeeder::idCodeFingerprint();
        $othersBefore = SusdatQuarantinedSubstancesMergeSeeder::otherRowsFingerprint($ids);

        foreach ($record['substances'] as $substance) {
            $updated = DB::table('susdat_substances')
                ->where('id', $substance['id'])
                ->where('status', 'merged')
                ->where('canonical_id', $substance['canonical_id'])
                ->update($substance['before']);

            $this->check("Rows restored for {$substance['code']}", $updated, 1);
        }

        $countsAfter = SusdatQuarantinedSubstancesMergeSeeder::statusCounts();
        $count = count($record['substances']);

        $this->check('Substances after', $countsAfter['rows'], $countsBefore['rows']);
        $this->check('Active after', $countsAfter['active'], $countsBefore['active'] + $count);
        $this->check('Merged after', $countsAfter['merged'], $countsBefore['merged'] - $count);
        $this->check('id/code fingerprint after', SusdatMissingLegacySubstancesSeeder::idCodeFingerprint(), $idCodeBefore);
        $this->check('Merge columns of the other rows after', SusdatQuarantinedSubstancesMergeSeeder::otherRowsFingerprint($ids), $othersBefore);
        $this->assertRestored($record);

        return [
            'started_at' => $startedAt,
            'finished_at' => SusdatMissingLegacySubstancesSeeder::utcNow(),
            'rows' => $countsAfter['rows'],
            'active_before' => $countsBefore['active'],
            'active_after' => $countsAfter['active'],
            'merged_before' => $countsBefore['merged'],
            'merged_after' => $countsAfter['merged'],
            'id_code_fingerprint' => $idCodeBefore,
            'other_rows_fingerprint' => $othersBefore,
        ];
    }

    /**
     * Every recorded row must still be exactly as the merge left it.
     *
     * @param  array{started_at_utc: string, substances: list<array{id: int, code: string, canonical_id: int, merge_reason: string}>}  $record
     */
    private function assertUnchanged(array $record): void
    {
        $rows = DB::table('susdat_substances')
            ->whereIn('id', array_column($record['substances'], 'id'))
            ->get(['id', 'code', 'status', 'canonical_id', 'merged_at', 'merged_by', 'merge_reason', 'deleted_at', 'updated_at'])
            ->keyBy('id');

        $this->check('Recorded substances found', $rows->count(), count($record['substances']));

        foreach ($record['substances'] as $substance) {
            $row = $rows[$substance['id']];
            $asMerged = $row->code === $substance['code']
                && $row->status === 'merged'
                && (int) $row->canonical_id === $substance['canonical_id']
                && (string) $row->merged_at === $record['started_at_utc']
                && $row->merged_by === null
                && $row->merge_reason === $substance['merge_reason']
                && $row->deleted_at === null
                && (string) $row->updated_at === $record['started_at_utc'];

            if (! $asMerged) {
                throw new RuntimeException("Substance {$substance['code']} was changed after the merge. Nothing was changed.");
            }
        }
    }

    /**
     * @param  array{substances: list<array{id: int, code: string, before: array<string, int|string|null>}>}  $record
     */
    private function assertRestored(array $record): void
    {
        $rows = DB::table('susdat_substances')
            ->whereIn('id', array_column($record['substances'], 'id'))
            ->get(['id', 'status', 'canonical_id', 'merged_at', 'merged_by', 'merge_reason', 'updated_at'])
            ->keyBy('id');

        foreach ($record['substances'] as $substance) {
            $row = (array) $rows[$substance['id']];

            foreach ($substance['before'] as $column => $value) {
                $same = $value === null ? $row[$column] === null : (string) $row[$column] === (string) $value;

                if (! $same) {
                    throw new RuntimeException("Restored {$column} of {$substance['code']} differs from the record. Nothing was changed.");
                }
            }
        }
    }

    private function newestRecord(): string
    {
        $files = glob(storage_path(SusdatQuarantinedSubstancesMergeSeeder::RECORD_DIRECTORY.'/'
            .SusdatQuarantinedSubstancesMergeSeeder::RECORD_PREFIX.'*.json')) ?: [];
        sort($files, SORT_STRING);

        if ($files === []) {
            throw new RuntimeException('No record file found. Nothing was changed.');
        }

        return (string) end($files);
    }

    /**
     * @return array{started_at_utc: string, substances: list<array{id: int, code: string, canonical_id: int, merge_reason: string, before: array<string, int|string|null>}>}
     */
    private function readRecord(string $path): array
    {
        try {
            $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Record file is not valid JSON: {$exception->getMessage()}. Nothing was changed.");
        }

        $this->check('Record written by', (string) ($record['seeder'] ?? ''), SusdatQuarantinedSubstancesMergeSeeder::class);
        $this->check('Record environment', (string) ($record['environment'] ?? ''), app()->environment());

        $codes = array_column($record['substances'] ?? [], 'code');
        $expectedCodes = array_column(SusdatQuarantinedSubstancesMergeSeeder::merges(), 'code');
        sort($codes);
        sort($expectedCodes);
        $this->check('Recorded codes', implode(',', $codes), implode(',', $expectedCodes));

        $columns = ['status', 'canonical_id', 'merged_at', 'merged_by', 'merge_reason', 'updated_at'];

        foreach ($record['substances'] as $substance) {
            $before = $substance['before'] ?? [];
            $this->check("Recorded previous columns of {$substance['code']}", implode(',', array_keys($before)), implode(',', $columns));
        }

        return [
            'started_at_utc' => (string) $record['started_at_utc'],
            'substances' => array_map(fn (array $row): array => [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'canonical_id' => (int) $row['canonical_id'],
                'merge_reason' => (string) $row['merge_reason'],
                'before' => $row['before'],
            ], $record['substances']),
        ];
    }

    private function check(string $description, int|string $actual, int|string $expected): void
    {
        if ($actual !== $expected) {
            throw new RuntimeException("{$description}: expected {$expected}, found {$actual}. Nothing was changed.");
        }
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
