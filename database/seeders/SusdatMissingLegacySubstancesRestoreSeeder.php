<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;

/**
 * Reverts SusdatMissingLegacySubstancesSeeder: removes exactly the substances and
 * source links listed in its newest record file.
 *
 * Stops, changing nothing, when any of those substances was edited since it was
 * added, has links other than the recorded ones, or is referenced by any other row
 * (data, categories, statistics, canonical_id). Rows up to the highest id before the
 * add are never changed; the run checks their id/code fingerprint. The id sequence is
 * not moved back, so removed ids are never reused. Everything runs in one transaction.
 *
 * The checks and the delete read every table that references susdat_substances; on
 * production expect this to take seconds, not milliseconds.
 *
 * Not listed in DatabaseSeeder. Run by hand only:
 * php artisan db:seed --class=SusdatMissingLegacySubstancesRestoreSeeder --force
 */
class SusdatMissingLegacySubstancesRestoreSeeder extends Seeder
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

        try {
            $result = DB::transaction(fn (): array => $this->restore($record));
        } catch (QueryException $exception) {
            throw new RuntimeException('The database refused the removal, so another row still uses these substances: '
                .$exception->getMessage().' Nothing was changed.');
        }

        $committedBy = SusdatMissingLegacySubstancesSeeder::utcNow();

        $this->report([
            'susdat missing legacy substances restore: environment='.app()->environment(),
            "transaction started {$result['started_at']} UTC, work finished {$result['finished_at']} UTC, committed by {$committedBy} UTC",
            "substances before {$result['rows_before']}, after {$result['rows_after']}; source links before {$result['links_before']}, after {$result['links_after']}",
            "id/code fingerprint of the rows up to id {$record['max_id_before']}: {$result['id_code_fingerprint']}, unchanged",
        ]);
    }

    /**
     * Runs inside the transaction.
     *
     * @param  array{started_at_utc: string, max_id_before: int, id_code_fingerprint_before: string, substances: list<array{id: int, code: string}>, source_links: list<array{source_id: int, substance_id: int}>}  $record
     * @return array<string, int|string>
     */
    private function restore(array $record): array
    {
        DB::statement("SET LOCAL lock_timeout = '".SusdatMissingLegacySubstancesSeeder::LOCK_TIMEOUT."'");
        DB::statement('LOCK TABLE susdat_substances IN SHARE ROW EXCLUSIVE MODE');
        DB::statement('LOCK TABLE susdat_source_substance IN SHARE ROW EXCLUSIVE MODE');

        $startedAt = (string) DB::scalar("SELECT to_char(transaction_timestamp() AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI:SS')");
        $ids = array_column($record['substances'], 'id');
        $rowsBefore = DB::table('susdat_substances')->count();
        $linksBefore = DB::table('susdat_source_substance')->count();

        $this->check(
            'id/code fingerprint of the rows up to the highest id before the add',
            SusdatMissingLegacySubstancesSeeder::idCodeFingerprint($record['max_id_before']),
            $record['id_code_fingerprint_before'],
        );

        $this->assertUnchanged($record);
        $this->assertNotReferenced($ids);

        $links = DB::table('susdat_source_substance')->whereIn('substance_id', $ids)
            ->orderBy('source_id')->orderBy('substance_id')
            ->get(['source_id', 'substance_id'])
            ->map(fn (object $link): string => $link->source_id.':'.$link->substance_id)
            ->all();
        $recordedLinks = array_map(fn (array $link): string => $link['source_id'].':'.$link['substance_id'], $record['source_links']);
        sort($recordedLinks);
        $this->check('Source links of these substances', implode(',', $links), implode(',', $recordedLinks));

        $linksRemoved = DB::table('susdat_source_substance')->whereIn('substance_id', $ids)->delete();
        $rowsRemoved = DB::table('susdat_substances')->whereIn('id', $ids)->delete();

        $this->check('Source links removed', $linksRemoved, count($recordedLinks));
        $this->check('Substances removed', $rowsRemoved, count($ids));

        $rowsAfter = DB::table('susdat_substances')->count();
        $linksAfter = DB::table('susdat_source_substance')->count();
        $fingerprint = SusdatMissingLegacySubstancesSeeder::idCodeFingerprint($record['max_id_before']);

        $this->check('Substances after', $rowsAfter, $rowsBefore - count($ids));
        $this->check('id/code fingerprint after', $fingerprint, $record['id_code_fingerprint_before']);

        return [
            'started_at' => $startedAt,
            'finished_at' => SusdatMissingLegacySubstancesSeeder::utcNow(),
            'rows_before' => $rowsBefore,
            'rows_after' => $rowsAfter,
            'links_before' => $linksBefore,
            'links_after' => $linksAfter,
            'id_code_fingerprint' => $fingerprint,
        ];
    }

    /**
     * Every recorded row must still exist with its code and must not have been
     * edited since the add.
     *
     * @param  array{started_at_utc: string, substances: list<array{id: int, code: string}>}  $record
     */
    private function assertUnchanged(array $record): void
    {
        $rows = DB::table('susdat_substances')
            ->whereIn('id', array_column($record['substances'], 'id'))
            ->get(['id', 'code', 'updated_at', 'status', 'deleted_at'])
            ->keyBy('id');

        $this->check('Recorded substances found', $rows->count(), count($record['substances']));

        foreach ($record['substances'] as $substance) {
            $row = $rows[$substance['id']];

            if ($row->code !== $substance['code'] || $row->status !== 'active' || $row->deleted_at !== null) {
                throw new RuntimeException("Substance id {$substance['id']} is no longer the active {$substance['code']}. Nothing was changed.");
            }

            if ((string) $row->updated_at !== $record['started_at_utc']) {
                throw new RuntimeException("Substance {$substance['code']} was edited at {$row->updated_at} UTC after the add. Nothing was changed.");
            }
        }
    }

    /**
     * Checks every column that may hold these ids: each foreign key to susdat_substances
     * and, because not every database has all foreign keys, each column named
     * substance_id. Partitions are covered by their parent table. The source links are
     * checked separately.
     *
     * @param  list<int>  $ids
     */
    private function assertNotReferenced(array $ids): void
    {
        $references = DB::select(
            "SELECT DISTINCT r.oid::regclass::text AS table_name, a.attname AS column_name
             FROM pg_class r
             JOIN pg_attribute a ON a.attrelid = r.oid AND a.attnum > 0 AND NOT a.attisdropped
             WHERE r.relnamespace = current_schema()::regnamespace
               AND r.relkind IN ('r', 'p') AND NOT r.relispartition
               AND (
                   a.attname = 'substance_id'
                   OR EXISTS (
                       SELECT 1 FROM pg_constraint c
                       WHERE c.contype = 'f' AND c.confrelid = 'susdat_substances'::regclass
                         AND c.conrelid = r.oid AND c.conkey[1] = a.attnum
                   )
               )
               AND r.oid <> 'susdat_source_substance'::regclass
             ORDER BY 1, 2"
        );

        foreach ($references as $reference) {
            $count = DB::table(DB::raw($reference->table_name))
                ->whereIn($reference->column_name, $ids)
                ->count();

            $this->check("Rows of {$reference->table_name}.{$reference->column_name} that use these substances", $count, 0);
        }
    }

    private function newestRecord(): string
    {
        $files = glob(storage_path(SusdatMissingLegacySubstancesSeeder::RECORD_DIRECTORY.'/'
            .SusdatMissingLegacySubstancesSeeder::RECORD_PREFIX.'*.json')) ?: [];
        sort($files, SORT_STRING);

        if ($files === []) {
            throw new RuntimeException('No record file found. Nothing was changed.');
        }

        return (string) end($files);
    }

    /**
     * @return array{started_at_utc: string, max_id_before: int, id_code_fingerprint_before: string, substances: list<array{id: int, code: string}>, source_links: list<array{source_id: int, substance_id: int}>}
     */
    private function readRecord(string $path): array
    {
        try {
            $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Record file is not valid JSON: {$exception->getMessage()}. Nothing was changed.");
        }

        $this->check('Record written by', (string) ($record['seeder'] ?? ''), SusdatMissingLegacySubstancesSeeder::class);
        $this->check('Record environment', (string) ($record['environment'] ?? ''), app()->environment());

        $codes = array_column($record['substances'] ?? [], 'code');
        $exportCodes = SusdatMissingLegacySubstancesSeeder::exportCodes();
        sort($codes);
        sort($exportCodes);
        $this->check('Recorded codes', implode(',', $codes), implode(',', $exportCodes));

        return [
            'started_at_utc' => (string) $record['started_at_utc'],
            'max_id_before' => (int) $record['max_id_before'],
            'id_code_fingerprint_before' => (string) $record['id_code_fingerprint_before'],
            'substances' => array_map(
                fn (array $row): array => ['id' => (int) $row['id'], 'code' => (string) $row['code']],
                $record['substances'],
            ),
            'source_links' => array_map(
                fn (array $link): array => ['source_id' => (int) $link['source_id'], 'substance_id' => (int) $link['substance_id']],
                $record['source_links'] ?? [],
            ),
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
