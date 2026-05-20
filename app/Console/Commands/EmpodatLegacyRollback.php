<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Rolls back a specific EmpodatLegacy seeding run by deleting ONLY the rows
 * that this run inserted. Pre-existing data is never touched.
 *
 * Source of truth: empodat_legacy_import_log entries for the given run.
 * Each log row carries either an explicit `inserted_ids` array (small steps)
 * or an `id_min`/`id_max` range (bulk steps). Rollback deletes from each
 * target table accordingly, wraps everything in a single transaction, and
 * flips the run's status to 'rolled_back'.
 */
class EmpodatLegacyRollback extends Command
{
    protected $signature = 'empodat-legacy:rollback
        {run_id : Id of the run in empodat_legacy_import_runs}
        {--dry-run : Print what would be deleted without modifying any data}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Roll back a specific EmpodatLegacy seeding run (deletes only that run\'s inserts).';

    public function handle(): int
    {
        $runId = (int) $this->argument('run_id');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $run = DB::table('empodat_legacy_import_runs')->find($runId);
        if ($run === null) {
            $this->error("Run #{$runId} not found in empodat_legacy_import_runs.");

            return self::FAILURE;
        }

        if ($run->status === 'rolled_back') {
            $this->warn("Run #{$runId} is already marked as rolled_back. Nothing to do.");

            return self::SUCCESS;
        }

        $logs = DB::table('empodat_legacy_import_log')
            ->where('run_id', $runId)
            ->where('status', 'completed')
            ->orderByDesc('id')   // reverse insertion order — undo Phase 5, 4, 3, 2, 1
            ->get();

        $this->info("Run #{$runId}  since_id={$run->since_id}  started_at={$run->started_at}  status={$run->status}");
        $this->info('Step entries to roll back: '.$logs->count());
        $this->newLine();

        $totalToDelete = 0;
        foreach ($logs as $log) {
            $count = $this->countTargetsForLog($log);
            $totalToDelete += $count;
            $this->line(sprintf(
                '  table=%-32s step=%-50s would delete %d row(s)',
                $log->table_name,
                $log->step_name,
                $count,
            ));
        }
        $this->newLine();
        $this->info("Total rows that would be deleted: {$totalToDelete}");

        if ($dryRun) {
            $this->info('Dry run — no changes made.');

            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm("Proceed with rollback of run #{$runId}?", false)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($logs, $runId) {
            foreach ($logs as $log) {
                $deleted = $this->deleteTargetsForLog($log);
                DB::table('empodat_legacy_import_log')
                    ->where('id', $log->id)
                    ->update(['status' => 'rolled_back', 'finished_at' => now()]);
                $this->line("   ✓ {$log->table_name}: deleted {$deleted}");
            }

            DB::table('empodat_legacy_import_runs')->where('id', $runId)->update([
                'status' => 'rolled_back',
                'finished_at' => now(),
            ]);
        });

        $this->info("Run #{$runId} rolled back.");

        return self::SUCCESS;
    }

    private function countTargetsForLog(stdClass $log): int
    {
        if ($log->inserted_ids !== null) {
            $ids = json_decode((string) $log->inserted_ids, true) ?: [];
            if ($ids === []) {
                return 0;
            }

            return (int) DB::table($log->table_name)->whereIn('id', $ids)->count();
        }

        if ($log->id_min !== null && $log->id_max !== null) {
            return (int) DB::table($log->table_name)
                ->whereBetween('id', [$log->id_min, $log->id_max])
                ->count();
        }

        return 0;
    }

    private function deleteTargetsForLog(stdClass $log): int
    {
        if ($log->inserted_ids !== null) {
            $ids = json_decode((string) $log->inserted_ids, true) ?: [];
            if ($ids === []) {
                return 0;
            }

            return DB::table($log->table_name)->whereIn('id', $ids)->delete();
        }

        if ($log->id_min !== null && $log->id_max !== null) {
            return DB::table($log->table_name)
                ->whereBetween('id', [$log->id_min, $log->id_max])
                ->delete();
        }

        return 0;
    }
}
