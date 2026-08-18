<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Repairs NULL `substance_id` values on the 8 LEGACY Suspect sources (10001–10008).
 *
 * WHY
 * ---
 * `empodat_suspect_susdat_code_mappings` (the old-code -> new-code crosswalk) was
 * EMPTY when these files were originally imported, so `resolveSubstanceId()` in
 * LoadsSubstanceCaches returned NULL for every legacy code and ~1.03M rows
 * (~2.85% of the legacy rows) were inserted with `substance_id = NULL`.
 *
 * `empodat_suspect_main` does not store NORMAN_ID, so the value cannot be
 * recovered with an UPDATE — the rows must be regenerated from the source files.
 *
 * SCOPE
 * -----
 * LEGACY ONLY (10001–10008). The newer sources (10009–10015) use current susdat
 * codes, resolve directly against `susdat_substances`, and are NOT touched.
 *
 * WHAT IT DOES, PER FILE
 * ----------------------
 *   1. Records the before counts.
 *   2. DELETEs that file's rows from `empodat_suspect_substances` + `empodat_suspect_main`.
 *      Legacy Main seeders contain no reference to `empodat_suspect_metadata`,
 *      so legacy files have no metadata rows and none are deleted.
 *   3. Re-runs ONLY that file's *MainSeeder*. The XlsxStationsMapping / Fill
 *      seeders are deliberately NOT re-run: station mappings are already correct
 *      and their `truncate()` is commented out, so re-running them would duplicate
 *      the mapping rows.
 *   4. Verifies the row count was reproduced and reports the new NULL count.
 *
 * SAFETY
 * ------
 *   - Aborts before any DELETE if the crosswalk table is empty.
 *   - Every DELETE is scoped to a single file_id. No TRUNCATE anywhere.
 *   - One file at a time; a failure stops the run with the remaining files untouched.
 *   - The source file IS the backup — rows are regenerated from it.
 *
 * EXPECTED RESULT
 * ---------------
 * 2728 of the 2729 unresolvable legacy codes are covered by the crosswalk. One code
 * has no mapping entry, so a small residue of NULL rows is expected and correct.
 *
 * USAGE
 * -----
 *   php -d memory_limit=-1 artisan db:seed --class="Database\Seeders\EmpodatSuspect\EmpodatSuspectLegacySubstanceIdRepairSeeder"
 *
 * Resume / subset — repair only selected files (comma separated):
 *   EMPODAT_REPAIR_FILES=10001,10004 php -d memory_limit=-1 artisan db:seed --class="Database\Seeders\EmpodatSuspect\EmpodatSuspectLegacySubstanceIdRepairSeeder"
 *
 * Afterwards (not done here):
 *   php artisan empodat-suspect:refresh-filters
 *   php artisan empodat-suspect:refresh-matrix-metadata --stats
 *   php artisan empodat-suspect:refresh-prioritisation --stats
 *   then rescan the 8 files in the UI to recompute main_id_from / main_id_to.
 */
class EmpodatSuspectLegacySubstanceIdRepairSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Legacy file_id => its Main seeder, ordered smallest source first so a
     * problem surfaces on a 191k-row file rather than on the 19M-row one.
     *
     * @var array<int, class-string>
     */
    private const LEGACY_MAIN_SEEDERS = [
        10001 => EmpodatSuspectSedimentMainSeeder::class,
        10004 => EmpodatSuspectConnect2BiotaMainSeeder::class,
        10008 => EmpodatSuspectUbaHelcomMainSeeder::class,
        10003 => EmpodatSuspectConnect2SedimentsMainSeeder::class,
        10005 => EmpodatSuspectHelcomSedimentsMainSeeder::class,
        10002 => EmpodatSuspectBiotaMainSeeder::class,
        10006 => EmpodatSuspectHelcomBiotaMainSeeder::class,
        10007 => EmpodatSuspectApexMainSeeder::class,
    ];

    /**
     * Verified pre-repair row counts per legacy file. Used to detect a file that was
     * only PARTIALLY imported by an interrupted run — such a file has a low NULL
     * ratio but a short row count, and must be repaired rather than skipped.
     *
     * @var array<int, int>
     */
    private const EXPECTED_ROWS = [
        10001 => 191476,
        10002 => 4786900,
        10003 => 1436070,
        10004 => 765904,
        10005 => 2871625,
        10006 => 6126120,
        10007 => 18956322,
        10008 => 1148856,
    ];

    /**
     * A repaired legacy file sits at ~0.001% NULL (only the single code that has no
     * crosswalk entry); an unrepaired one sits at ~2.85%. Anything under this ratio,
     * at the full expected row count, has already been repaired.
     */
    private const REPAIRED_NULL_RATIO = 0.001;

    public function run(): void
    {
        $this->disableDebugTooling();
        $this->assertCrosswalkPopulated();

        $targets = $this->resolveTargets();

        $this->command->info('Repairing '.count($targets).' file(s): '.implode(', ', array_keys($targets)));
        $this->command->newLine();

        $summary = [];

        foreach ($targets as $fileId => $seederClass) {
            $summary[] = $this->repairFile($fileId, $seederClass);
        }

        $this->reportSummary($summary);
    }

    /**
     * Refuse to delete anything while the crosswalk is empty — re-importing then
     * would reproduce exactly the same NULLs this seeder exists to fix.
     */
    private function assertCrosswalkPopulated(): void
    {
        $mappings = DB::table('empodat_suspect_susdat_code_mappings')->count();

        if ($mappings === 0) {
            throw new RuntimeException(
                'ABORTED: empodat_suspect_susdat_code_mappings is EMPTY. Nothing was deleted. '
                .'Ensure storage/app/public/empodat_suspect/duplicity_final.csv exists (it is gitignored, '
                .'so a deploy never delivers it — rsync it to the server), then run: '
                .'php artisan db:seed --class="Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectSusdatCodeMappingsSeeder"'
            );
        }

        $this->command->info("Crosswalk check OK — {$mappings} code mappings loaded.");
    }

    /**
     * @return array<int, class-string>
     */
    private function resolveTargets(): array
    {
        $requested = trim((string) (getenv('EMPODAT_REPAIR_FILES') ?: ''));

        if ($requested === '') {
            return self::LEGACY_MAIN_SEEDERS;
        }

        $ids = array_map('intval', array_filter(array_map('trim', explode(',', $requested)), 'strlen'));
        $unknown = array_diff($ids, array_keys(self::LEGACY_MAIN_SEEDERS));

        if ($unknown !== []) {
            throw new RuntimeException(
                'Unknown legacy file_id(s): '.implode(', ', $unknown)
                .'. Valid values: '.implode(', ', array_keys(self::LEGACY_MAIN_SEEDERS))
            );
        }

        $targets = [];

        foreach (self::LEGACY_MAIN_SEEDERS as $fileId => $seederClass) {
            if (in_array($fileId, $ids, true)) {
                $targets[$fileId] = $seederClass;
            }
        }

        return $targets;
    }

    /**
     * @param  class-string  $seederClass
     * @return array{file_id: int, before: int, after: int, nulls_before: int, nulls_after: int, seconds: float, skipped: bool}
     */
    private function repairFile(int $fileId, string $seederClass): array
    {
        $this->command->info(str_repeat('=', 72));
        $this->command->info("FILE {$fileId} — ".class_basename($seederClass));
        $this->command->info(str_repeat('=', 72));

        $started = microtime(true);
        $before = $this->statsFor($fileId);

        $this->command->info("  Before: {$before['total']} rows, {$before['nulls']} with NULL substance_id");

        if ($this->isAlreadyRepaired($fileId, $before)) {
            $this->command->info("  SKIPPED — file_id={$fileId} is already repaired.");
            $this->command->newLine();

            return [
                'file_id' => $fileId,
                'before' => $before['total'],
                'after' => $before['total'],
                'nulls_before' => $before['nulls'],
                'nulls_after' => $before['nulls'],
                'seconds' => 0.0,
                'skipped' => true,
            ];
        }

        if ($before['total'] === 0) {
            $this->command->warn("  No existing rows for file_id={$fileId} — importing fresh.");
        }

        $this->deleteFileRows($fileId);
        $this->call($seederClass);

        $after = $this->statsFor($fileId);
        $seconds = round(microtime(true) - $started, 1);

        $this->command->info("  After:  {$after['total']} rows, {$after['nulls']} with NULL substance_id ({$seconds}s)");

        if ($before['total'] > 0 && $after['total'] !== $before['total']) {
            $this->command->error(
                "  ROW COUNT MISMATCH for file_id={$fileId}: expected {$before['total']}, got {$after['total']}. "
                .'Investigate before continuing — the source file may differ from the one originally imported.'
            );
        }

        $this->command->newLine();

        return [
            'file_id' => $fileId,
            'before' => $before['total'],
            'after' => $after['total'],
            'nulls_before' => $before['nulls'],
            'nulls_after' => $after['nulls'],
            'seconds' => $seconds,
            'skipped' => false,
        ];
    }

    /**
     * A file is treated as already repaired only when BOTH hold: the row count matches
     * the verified baseline (so a run interrupted mid-import is never mistaken for a
     * finished one), and the NULL ratio has collapsed to the post-repair level.
     *
     * Set EMPODAT_REPAIR_FORCE=1 to repair regardless.
     *
     * @param  array{total: int, nulls: int}  $before
     */
    private function isAlreadyRepaired(int $fileId, array $before): bool
    {
        if ((string) (getenv('EMPODAT_REPAIR_FORCE') ?: '') !== '') {
            return false;
        }

        return $this->looksRepaired($fileId, $before);
    }

    /**
     * State-only view of the same test, ignoring EMPODAT_REPAIR_FORCE.
     *
     * @param  array{total: int, nulls: int}  $stats
     */
    private function looksRepaired(int $fileId, array $stats): bool
    {
        if ($stats['total'] === 0 || $stats['total'] !== (self::EXPECTED_ROWS[$fileId] ?? -1)) {
            return false;
        }

        return $stats['nulls'] / $stats['total'] < self::REPAIRED_NULL_RATIO;
    }

    /**
     * Scoped delete. Substances are removed too because the legacy Main seeders
     * bulk-insert their collected substances without deduping against existing rows.
     */
    private function deleteFileRows(int $fileId): void
    {
        DB::transaction(function () use ($fileId): void {
            $substances = DB::table('empodat_suspect_substances')->where('file_id', $fileId)->delete();
            $main = DB::table('empodat_suspect_main')->where('file_id', $fileId)->delete();

            $this->command->info("  Deleted {$main} main rows, {$substances} substance rows.");
        });
    }

    /**
     * @return array{total: int, nulls: int}
     */
    private function statsFor(int $fileId): array
    {
        $row = DB::table('empodat_suspect_main')
            ->selectRaw('count(*) AS total, count(*) FILTER (WHERE substance_id IS NULL) AS nulls')
            ->where('file_id', $fileId)
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'nulls' => (int) ($row->nulls ?? 0),
        ];
    }

    /**
     * @param  array<int, array{file_id: int, before: int, after: int, nulls_before: int, nulls_after: int, seconds: float, skipped: bool}>  $summary
     */
    private function reportSummary(array $summary): void
    {
        $this->command->info('=== REPAIR SUMMARY ===');

        $this->command->table(
            ['file_id', 'status', 'rows before', 'rows after', 'NULL before', 'NULL after', 'seconds'],
            array_map(
                static fn (array $r): array => [
                    $r['file_id'],
                    $r['skipped'] ? 'skipped' : 'repaired',
                    number_format($r['before'], 0, '.', ' '),
                    number_format($r['after'], 0, '.', ' '),
                    number_format($r['nulls_before'], 0, '.', ' '),
                    number_format($r['nulls_after'], 0, '.', ' '),
                    $r['seconds'],
                ],
                $summary
            )
        );

        $fixed = array_sum(array_column($summary, 'nulls_before')) - array_sum(array_column($summary, 'nulls_after'));
        $this->command->info('Rows repaired: '.number_format($fixed, 0, '.', ' '));

        $this->command->newLine();
        $this->command->newLine();
        $this->reportFinalState();

        $this->command->newLine();
        $this->command->warn('NEXT (run manually, in this order):');
        $this->command->warn('  php artisan empodat-suspect:refresh-filters');
        $this->command->warn('  php artisan empodat-suspect:refresh-matrix-metadata --stats');
        $this->command->warn('  php artisan empodat-suspect:refresh-prioritisation --stats');
        $this->command->warn('  then rescan the repaired files in the UI (main_id_from / main_id_to / number_of_records).');
    }

    /**
     * Whole-table state after the repair, grouped the same way the problem was
     * originally diagnosed: legacy sources vs. the newer ones that were never affected.
     * Full scan of `empodat_suspect_main` — expect this to take a while.
     */
    private function reportFinalState(): void
    {
        $this->command->info('=== FINAL STATE (whole empodat_suspect_main) ===');

        $rows = DB::table('empodat_suspect_main')
            ->selectRaw("
                CASE
                    WHEN file_id BETWEEN 10001 AND 10008 THEN '10001-10008 (legacy)'
                    WHEN file_id BETWEEN 10009 AND 10015 THEN '10009-10015 (BlackSea/TerraChem)'
                    ELSE 'other / unassigned'
                END AS grp,
                count(*) AS total,
                count(*) FILTER (WHERE substance_id IS NULL) AS nulls
            ")
            ->groupBy('grp')
            ->orderBy('grp')
            ->get();

        $this->command->table(
            ['file_id', 'rows', 'NULL substance_id', '%'],
            $rows->map(static function (object $r): array {
                $total = (int) $r->total;
                $nulls = (int) $r->nulls;

                return [
                    $r->grp,
                    number_format($total, 0, '.', ' '),
                    number_format($nulls, 0, '.', ' '),
                    $total > 0 ? number_format($nulls / $total * 100, 3, '.', ' ').' %' : '-',
                ];
            })->all()
        );
    }

    private function disableDebugTooling(): void
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        DB::disableQueryLog();
    }
}
