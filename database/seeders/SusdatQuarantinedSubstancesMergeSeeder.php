<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Issue 2.2 of the SUSDAT review: 60 substances that legacy NDS1 merged into other
 * substances and then moved to its quarantine (susdat.susdat_quarantine) are still
 * active here, because the first load (2025-05-30) took them before NDS1 retired them.
 *
 * Marks 52 of them as merged into the substance NDS1 recorded for them in
 * susdat.susdat_duplicity, with the same columns the duplicates tool sets
 * (DuplicateController::executeMerge): status, canonical_id, merged_at, merged_by and
 * merge_reason. MERGES was taken from legacy and checked against it on 2026-11-10.
 * Where NDS1 merged twice, canonical_id is the final code, as susdat_duplicity gives it,
 * because a canonical substance must be active.
 *
 * Not changed:
 * - 01090289: already merged here into 00088837, the substance NDS1 recorded.
 * - The 7 that NDS1 retired without recording a target: no merge without an NDS1
 *   record (owner, 2026-11-10). They stay active.
 * - Data of other modules: a merge moves nothing, as in the duplicates tool.
 * - audits: rows are written with the query builder, as in the other SUSDAT seeders.
 *
 * Only these 52 rows of susdat_substances change. The run checks that the id/code
 * fingerprint and the merge columns of every other row are the same afterwards.
 * Everything runs in one transaction, so any failure changes nothing. A record of the
 * previous values is written to RECORD_DIRECTORY;
 * SusdatQuarantinedSubstancesMergeRestoreSeeder puts them back.
 *
 * Not listed in DatabaseSeeder. Run by hand only:
 * php artisan db:seed --class=SusdatQuarantinedSubstancesMergeSeeder --force
 */
class SusdatQuarantinedSubstancesMergeSeeder extends Seeder
{
    public const RECORD_DIRECTORY = 'app/susdat_quarantined_merges';

    public const RECORD_PREFIX = 'susdat_quarantined_merged_';

    /** Measured on production on 2026-11-10 at 14:30 UTC. */
    private const EXPECTED_ROWS_BEFORE = 126534;

    private const EXPECTED_MAX_ID_BEFORE = 126534;

    private const EXPECTED_ACTIVE_BEFORE = 126533;

    private const EXPECTED_MERGED_BEFORE = 1;

    private const EXPECTED_ID_CODE_FINGERPRINT = 'd9e7bfcc550c57046d93fb53e0d3efc6';

    /**
     * code here, its production id, its old code in NDS1, date NDS1 renumbered it,
     * code NDS1 merged it into, final code (susdat_duplicity), production id of the final code.
     */
    private const MERGES = [
        ['01073508', 120919, '00073508', '2025-02-24', '00108473', '00108473', 96950],
        ['01077626', 120920, '00077626', '2024-10-22', '00005788', '00005788', 5453],
        ['01077645', 120921, '00077645', '2024-11-06', '00018778', '00018778', 17988],
        ['01086212', 120922, '00086212', '2025-04-09', '00090123', '00090123', 85533],
        ['01086440', 120923, '00086440', '2025-04-10', '00086138', '00086138', 81903],
        ['01087282', 120924, '00087282', '2025-03-25', '00049800', '00049800', 47674],
        ['01087962', 120928, '00087962', '2025-03-24', '00089001', '00089001', 84501],
        ['01088078', 120929, '00088078', '2025-03-24', '00088079', '00088079', 83677],
        ['01088314', 120931, '00088314', '2025-04-01', '00086318', '00086318', 82066],
        ['01088700', 120933, '00088700', '2025-02-28', '00092560', '00092560', 87840],
        ['01088740', 120934, '00088740', '2025-02-28', '00092677', '00092677', 87956],
        ['01088800', 120935, '00088800', '2025-03-04', '00088379', '00088379', 83948],
        ['01088913', 120936, '00088913', '2025-03-25', '00087282', '00049800', 47674],
        ['01088988', 120937, '00088988', '2025-03-25', '00087338', '00087338', 82999],
        ['01089200', 120938, '00089200', '2025-03-24', '00088164', '00088164', 83755],
        ['01089438', 120939, '00089438', '2025-04-01', '00086857', '00086857', 82560],
        ['01089445', 120940, '00089445', '2025-03-03', '00088472', '00088472', 84027],
        ['01089596', 120941, '00089596', '2025-02-27', '00092266', '00092266', 87557],
        ['01089634', 120942, '00089634', '2025-02-27', '00092508', '00092508', 87789],
        ['01089823', 120943, '00089823', '2025-03-27', '00086932', '00086932', 82626],
        ['01090341', 120946, '00090341', '2025-03-26', '00087061', '00087061', 82748],
        ['01090402', 120947, '00090402', '2025-03-18', '00088192', '00088192', 83780],
        ['01090405', 120948, '00090405', '2025-02-25', '00092212', '00092212', 87508],
        ['01090454', 120950, '00090454', '2025-02-26', '00089989', '00089989', 85413],
        ['01090760', 120951, '00090760', '2025-02-26', '00089764', '00089764', 85206],
        ['01090955', 120952, '00090955', '2025-02-27', '00089346', '00089346', 84813],
        ['01091029', 120953, '00091029', '2025-02-27', '00089327', '00089327', 84794],
        ['01091152', 120954, '00091152', '2025-02-26', '00089889', '00089889', 85320],
        ['01091368', 120955, '00091368', '2025-03-18', '00088413', '00088413', 83979],
        ['01091595', 120956, '00091595', '2025-03-03', '00088607', '00088607', 84148],
        ['01092321', 120957, '00092321', '2025-03-24', '00087939', '00087939', 83547],
        ['01092455', 120958, '00092455', '2025-02-25', '00092011', '00092011', 87315],
        ['01092466', 120959, '00092466', '2025-02-24', '00092467', '00092467', 87749],
        ['01092587', 120960, '00092587', '2025-02-24', '00092588', '00092588', 87867],
        ['01092859', 120961, '00092859', '2025-03-24', '00088065', '00088065', 83666],
        ['01092895', 120962, '00092895', '2025-02-24', '00092870', '00092870', 88139],
        ['01093426', 120963, '00093426', '2025-02-24', '00093170', '00093170', 88425],
        ['01093476', 120964, '00093476', '2025-02-24', '00093467', '00093467', 88712],
        ['01093549', 120965, '00093549', '2025-02-26', '00089802', '00089802', 85240],
        ['01096946', 120966, '00096946', '2025-02-28', '00088652', '00088652', 84191],
        ['01098474', 120967, '00098474', '2025-03-25', '00008324', '00008324', 7848],
        ['01098475', 120968, '00098475', '2025-02-24', '00098474', '00008324', 7848],
        ['01111893', 120969, '00111893', '2025-02-24', '00110824', '00110824', 99006],
        ['01112610', 120970, '00112610', '2025-02-24', '00093461', '00093461', 88706],
        ['01114764', 120971, '00114764', '2024-11-06', '00077682', '00077682', 74486],
        ['01115845', 120972, '00115845', '2024-11-05', '00000309', '00000309', 290],
        ['01122017', 120973, '00122017', '2025-01-13', '00006697', '00006697', 6310],
        ['01124748', 120974, '00124748', '2025-02-24', '00094476', '00094476', 89665],
        ['01124782', 120975, '00124782', '2024-10-24', '00103765', '00103765', 96266],
        ['01126677', 120976, '00126677', '2025-01-13', '00002790', '00002790', 2672],
        ['01134770', 120977, '00134770', '2025-02-18', '00115934', '00115934', 103632],
        ['01502166', 120978, '00502166', '2024-10-24', '00100125', '00100125', 93914],
    ];

    private const EXPECTED_MERGES = 52;

    /** The merge columns, compared before and after for every row that must not change. */
    private const MERGE_COLUMNS = ['id', 'code', 'status', 'canonical_id', 'merged_at', 'merged_by', 'merge_reason', 'deleted_at', 'updated_at'];

    public function run(): void
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        DB::disableQueryLog();

        $merges = self::merges();
        $this->assertState($merges);

        File::ensureDirectoryExists(storage_path(self::RECORD_DIRECTORY));

        $this->say('Checks passed, nothing changed yet: '.count($merges).' substances to mark as merged.');

        $result = DB::transaction(fn (): array => $this->merge($merges));
        $committedBy = SusdatMissingLegacySubstancesSeeder::utcNow();

        $this->report([
            'susdat quarantined substances merge: environment='.app()->environment().' exact_counts='.(SusdatMissingLegacySubstancesSeeder::enforcesProductionCounts() ? 'yes' : 'no'),
            "transaction started {$result['started_at']} UTC, work finished {$result['finished_at']} UTC, committed by {$committedBy} UTC",
            "substances {$result['rows']}; active before {$result['active_before']}, after {$result['active_after']}; merged before {$result['merged_before']}, after {$result['merged_after']}",
            "id/code fingerprint {$result['id_code_fingerprint']} before and after",
            "merge columns of the other rows {$result['other_rows_fingerprint']} before and after",
            "record {$result['record_path']}: SHA-256 {$result['record_sha256']}",
        ]);
    }

    /**
     * The 52 merges, checked for consistency.
     *
     * @return list<array{code: string, id: int, old_code: string, renumbered_on: string, merged_into: string, target_code: string, target_id: int}>
     */
    public static function merges(): array
    {
        $merges = array_map(fn (array $row): array => [
            'code' => $row[0],
            'id' => $row[1],
            'old_code' => $row[2],
            'renumbered_on' => $row[3],
            'merged_into' => $row[4],
            'target_code' => $row[5],
            'target_id' => $row[6],
        ], self::MERGES);

        $codes = array_column($merges, 'code');

        self::check('Merges listed', count($merges), self::EXPECTED_MERGES);
        self::check('Distinct codes listed', count(array_unique($codes)), self::EXPECTED_MERGES);
        self::check('Distinct ids listed', count(array_unique(array_column($merges, 'id'))), self::EXPECTED_MERGES);

        foreach ($merges as $merge) {
            if (preg_match('/^00\d{6}$/', $merge['old_code']) !== 1 || $merge['code'] !== '01'.substr($merge['old_code'], 2)) {
                throw new RuntimeException("Code {$merge['code']} is not the renumbered form of {$merge['old_code']}. Nothing was changed.");
            }

            if (in_array($merge['target_code'], $codes, true)) {
                throw new RuntimeException("Target {$merge['target_code']} is itself one of the codes to merge. Nothing was changed.");
            }
        }

        return $merges;
    }

    /**
     * @param  array{code: string, old_code: string, renumbered_on: string, merged_into: string, target_code: string}  $merge
     */
    public static function mergeReason(array $merge): string
    {
        $into = $merge['merged_into'] === $merge['target_code']
            ? "merged into {$merge['target_code']}"
            : "merged into {$merge['merged_into']}, which NDS1 merged into {$merge['target_code']}";

        return "Merged in NDS1 (legacy): old code {$merge['old_code']} was renumbered to {$merge['code']} on {$merge['renumbered_on']}"
            ." and {$into} (susdat.susdat_duplicity); NDS1 keeps it in susdat_quarantine."
            .' Copied from NDS1 by SusdatQuarantinedSubstancesMergeSeeder.';
    }

    /**
     * The fingerprint of the merge columns of every row except the given ids.
     *
     * @param  list<int>  $excludedIds
     */
    public static function otherRowsFingerprint(array $excludedIds): string
    {
        return (string) DB::scalar(
            'SELECT md5(coalesce(string_agg(md5(ROW('.implode(', ', self::MERGE_COLUMNS).")::text), '' ORDER BY id), ''))
             FROM susdat_substances WHERE NOT (id = ANY(?::bigint[]))",
            ['{'.implode(',', $excludedIds).'}']
        );
    }

    /**
     * @return array{rows: int, active: int, merged: int}
     */
    public static function statusCounts(): array
    {
        $counts = DB::selectOne(
            "SELECT count(*) AS total, count(*) FILTER (WHERE status = 'active') AS active,
                    count(*) FILTER (WHERE status = 'merged') AS merged
             FROM susdat_substances"
        );

        return ['rows' => (int) $counts->total, 'active' => (int) $counts->active, 'merged' => (int) $counts->merged];
    }

    /**
     * Runs inside the transaction.
     *
     * @param  list<array{code: string, id: int, old_code: string, renumbered_on: string, merged_into: string, target_code: string, target_id: int}>  $merges
     * @return array<string, int|string>
     */
    private function merge(array $merges): array
    {
        DB::statement("SET LOCAL lock_timeout = '".SusdatMissingLegacySubstancesSeeder::LOCK_TIMEOUT."'");
        DB::statement('LOCK TABLE susdat_substances IN SHARE ROW EXCLUSIVE MODE');

        $startedAt = (string) DB::scalar("SELECT to_char(transaction_timestamp() AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI:SS')");

        $state = $this->assertState($merges);
        $ids = array_map(fn (array $found): int => $found['id'], $state);
        $countsBefore = self::statusCounts();
        $idCodeBefore = SusdatMissingLegacySubstancesSeeder::idCodeFingerprint();
        $othersBefore = self::otherRowsFingerprint(array_values($ids));

        foreach ($merges as $merge) {
            $updated = DB::table('susdat_substances')
                ->where('id', $ids[$merge['code']])
                ->where('status', 'active')
                ->whereNull('canonical_id')
                ->whereNull('deleted_at')
                ->update([
                    'status' => 'merged',
                    'canonical_id' => $state[$merge['code']]['target_id'],
                    'merged_at' => $startedAt,
                    'merged_by' => null,
                    'merge_reason' => self::mergeReason($merge),
                    'updated_at' => $startedAt,
                ]);

            self::check("Rows updated for {$merge['code']}", $updated, 1);
        }

        $countsAfter = self::statusCounts();

        self::check('Substances after', $countsAfter['rows'], $countsBefore['rows']);
        self::check('Active after', $countsAfter['active'], $countsBefore['active'] - count($merges));
        self::check('Merged after', $countsAfter['merged'], $countsBefore['merged'] + count($merges));
        self::check('id/code fingerprint after', SusdatMissingLegacySubstancesSeeder::idCodeFingerprint(), $idCodeBefore);
        self::check('Merge columns of the other rows after', self::otherRowsFingerprint(array_values($ids)), $othersBefore);
        $this->assertStored($merges, $state, $startedAt);

        $record = $this->writeRecord($startedAt, $idCodeBefore, $othersBefore, $merges, $state);

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
            'record_path' => $record['path'],
            'record_sha256' => $record['sha256'],
        ];
    }

    /**
     * Every substance to merge and every target must exist exactly once and be a plain
     * active row, and no substance may already point to one of those to merge.
     * Codes are compared trimmed, because some end in a carriage return (issue 3.1).
     *
     * @param  list<array{code: string, id: int, target_code: string, target_id: int}>  $merges
     * @return array<string, array{id: int, target_id: int, before: array<string, int|string|null>}>
     */
    private function assertState(array $merges): array
    {
        $this->assertProductionState();

        $codes = array_values(array_unique(array_merge(array_column($merges, 'code'), array_column($merges, 'target_code'))));
        $rows = DB::table('susdat_substances')
            ->whereIn(DB::raw("trim(both E'\\r\\n ' from code)"), $codes)
            ->get(['id', 'code', 'status', 'canonical_id', 'merged_at', 'merged_by', 'merge_reason', 'deleted_at', 'updated_at'])
            ->groupBy(fn (object $row): string => trim((string) $row->code, "\r\n "));

        $state = [];

        foreach ($merges as $merge) {
            $substance = $this->onePlainActiveRow($rows->get($merge['code']), $merge['code']);
            $target = $this->onePlainActiveRow($rows->get($merge['target_code']), $merge['target_code']);

            foreach (['merged_at', 'merged_by', 'merge_reason'] as $column) {
                if ($substance->{$column} !== null) {
                    throw new RuntimeException("{$merge['code']} already has {$column}. Nothing was changed.");
                }
            }

            if (SusdatMissingLegacySubstancesSeeder::enforcesProductionCounts()) {
                self::check("Production id of {$merge['code']}", (int) $substance->id, $merge['id']);
                self::check("Production id of {$merge['target_code']}", (int) $target->id, $merge['target_id']);
            }

            $state[$merge['code']] = [
                'id' => (int) $substance->id,
                'target_id' => (int) $target->id,
                'before' => [
                    'status' => $substance->status,
                    'canonical_id' => $substance->canonical_id,
                    'merged_at' => $substance->merged_at,
                    'merged_by' => $substance->merged_by,
                    'merge_reason' => $substance->merge_reason,
                    'updated_at' => $substance->updated_at,
                ],
            ];
        }

        $ids = array_column($state, 'id');

        foreach ($state as $code => $found) {
            if (in_array($found['target_id'], $ids, true)) {
                throw new RuntimeException("The target of {$code} is itself one of the substances to merge. Nothing was changed.");
            }
        }

        self::check(
            'Substances whose canonical_id points to one of those to merge',
            DB::table('susdat_substances')->whereIn('canonical_id', $ids)->count(),
            0,
        );

        return $state;
    }

    /**
     * @param  Collection<int, object>|null  $rows
     */
    private function onePlainActiveRow(?Collection $rows, string $code): object
    {
        self::check("Rows with code {$code}", $rows === null ? 0 : $rows->count(), 1);
        $row = $rows->first();

        if ($row->code !== $code) {
            throw new RuntimeException("Code {$code} is stored with extra characters. Nothing was changed.");
        }

        if ($row->status !== 'active' || $row->canonical_id !== null || $row->deleted_at !== null) {
            throw new RuntimeException("{$code} is not a plain active substance (status {$row->status}). Nothing was changed.");
        }

        return $row;
    }

    private function assertProductionState(): void
    {
        if (! SusdatMissingLegacySubstancesSeeder::enforcesProductionCounts()) {
            return;
        }

        $counts = self::statusCounts();

        self::check('Substances before', $counts['rows'], self::EXPECTED_ROWS_BEFORE);
        self::check('Highest id before', (int) DB::table('susdat_substances')->max('id'), self::EXPECTED_MAX_ID_BEFORE);
        self::check('Active before', $counts['active'], self::EXPECTED_ACTIVE_BEFORE);
        self::check('Merged before', $counts['merged'], self::EXPECTED_MERGED_BEFORE);
        self::check('id/code fingerprint before', SusdatMissingLegacySubstancesSeeder::idCodeFingerprint(), self::EXPECTED_ID_CODE_FINGERPRINT);
    }

    /**
     * Reads the 52 rows back and compares them with the plan.
     *
     * @param  list<array{code: string, old_code: string, renumbered_on: string, merged_into: string, target_code: string}>  $merges
     * @param  array<string, array{id: int, target_id: int}>  $state
     */
    private function assertStored(array $merges, array $state, string $startedAt): void
    {
        $stored = DB::table('susdat_substances')
            ->whereIn('id', array_column($state, 'id'))
            ->get(['id', 'code', 'status', 'canonical_id', 'merged_at', 'merged_by', 'merge_reason', 'deleted_at', 'updated_at'])
            ->keyBy('id');

        self::check('Merged rows read back', $stored->count(), count($merges));

        foreach ($merges as $merge) {
            $row = $stored[$state[$merge['code']]['id']];
            $same = $row->code === $merge['code']
                && $row->status === 'merged'
                && (int) $row->canonical_id === $state[$merge['code']]['target_id']
                && (string) $row->merged_at === $startedAt
                && $row->merged_by === null
                && $row->merge_reason === self::mergeReason($merge)
                && $row->deleted_at === null
                && (string) $row->updated_at === $startedAt;

            if (! $same) {
                throw new RuntimeException("Stored row of {$merge['code']} differs from the plan. Nothing was changed.");
            }
        }
    }

    /**
     * @param  list<array{code: string, target_code: string}>  $merges
     * @param  array<string, array{id: int, target_id: int, before: array<string, int|string|null>}>  $state
     * @return array{path: string, sha256: string}
     */
    private function writeRecord(string $startedAt, string $idCodeFingerprint, string $othersFingerprint, array $merges, array $state): array
    {
        $path = storage_path(self::RECORD_DIRECTORY.'/'.self::RECORD_PREFIX
            .str_replace(['-', ' ', ':'], ['', 'T', ''], $startedAt).'Z.json');
        $handle = @fopen($path, 'x');

        if ($handle === false) {
            throw new RuntimeException("Cannot create the record file {$path}. Nothing was changed.");
        }

        $record = [
            'seeder' => self::class,
            'environment' => app()->environment(),
            'started_at_utc' => $startedAt,
            'id_code_fingerprint' => $idCodeFingerprint,
            'other_rows_fingerprint_before' => $othersFingerprint,
            'substances' => array_map(fn (array $merge): array => [
                'id' => $state[$merge['code']]['id'],
                'code' => $merge['code'],
                'canonical_id' => $state[$merge['code']]['target_id'],
                'target_code' => $merge['target_code'],
                'merge_reason' => self::mergeReason($merge),
                'before' => $state[$merge['code']]['before'],
            ], $merges),
        ];

        fwrite($handle, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
        fclose($handle);

        return ['path' => $path, 'sha256' => (string) hash_file('sha256', $path)];
    }

    private static function check(string $description, int|string $actual, int|string $expected): void
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
