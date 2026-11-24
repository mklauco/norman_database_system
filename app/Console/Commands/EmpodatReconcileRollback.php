<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use stdClass;

/**
 * Undoes one reconcile run from its empodat_reconcile_log rows, newest first,
 * each log row in its own transaction.
 */
class EmpodatReconcileRollback extends Command
{
    private const IDENTIFIER = '/^[a-z_][a-z0-9_]*$/';

    private const NULL_CHUNK = 50000;

    protected $signature = 'empodat-reconcile:rollback
        {run_id : Id of the run in empodat_legacy_import_runs}
        {--dry-run : Print what would be done without modifying any data}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Roll back a RECONCILE run (undo its inserts, fills and deletes).';

    public function handle(): int
    {
        $runId = (int) $this->argument('run_id');
        $dryRun = (bool) $this->option('dry-run');

        $run = DB::table('empodat_legacy_import_runs')->find($runId);
        if ($run === null) {
            $this->error("Run #{$runId} not found.");

            return self::FAILURE;
        }
        if (! str_starts_with((string) $run->notes, 'RECONCILE')) {
            $this->error("Run #{$runId} is not a RECONCILE run; use empodat-legacy:rollback.");

            return self::FAILURE;
        }
        if ($run->status === 'running') {
            $this->error("Run #{$runId} is still running.");

            return self::FAILURE;
        }
        if ($run->status === 'rolled_back') {
            $this->error("Run #{$runId} is already rolled back.");

            return self::FAILURE;
        }

        $logs = DB::table('empodat_reconcile_log')->where('run_id', $runId)->orderByDesc('id')->get();
        $this->info("Run #{$runId}  status={$run->status}  log rows: ".$logs->count());

        $totals = ['insert' => 0, 'fill' => 0, 'delete' => 0];
        foreach ($logs as $log) {
            $this->assertIdentifiers($log);
            $this->line(sprintf('  #%d %-8s %-34s %-40s rows=%d', $log->id, $log->action, $log->table_name, $log->step, $log->row_count).($dryRun ? '  ('.$this->describe($log).')' : ''));
            $totals[$log->action] = ($totals[$log->action] ?? 0) + (int) $log->row_count;
        }
        $this->info(sprintf('Totals: %d inserted rows to delete, %d filled rows to restore, %d deleted rows to re-insert.', $totals['insert'], $totals['fill'], $totals['delete']));

        if ($dryRun) {
            $this->info('Dry run, no changes made.');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm("Proceed with rollback of run #{$runId}?", false)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $done = ['insert' => 0, 'fill' => 0, 'delete' => 0];
        foreach ($logs as $log) {
            $affected = 0;
            DB::transaction(function () use ($log, &$affected): void {
                $affected = $this->undo($log);
                DB::table('empodat_reconcile_log')->where('id', $log->id)->delete();
            });
            $done[$log->action] += $affected;
            $this->line("   done #{$log->id} {$log->action} {$log->table_name}: {$affected}");
        }

        DB::table('empodat_legacy_import_runs')->where('id', $runId)->update([
            'status' => 'rolled_back',
            'finished_at' => now(),
        ]);

        $this->info(sprintf('Run #%d rolled back: %d deleted, %d restored, %d re-inserted.', $runId, $done['insert'], $done['fill'], $done['delete']));

        return self::SUCCESS;
    }

    private function describe(stdClass $log): string
    {
        return match ($log->action) {
            'insert' => 'delete these ids',
            'fill' => $log->before_rows !== null ? 'restore old values' : 'set '.implode(', ', $this->columns($log)).' to NULL',
            'delete' => 'insert before_rows back',
            default => 'unknown action',
        };
    }

    private function undo(stdClass $log): int
    {
        return match ($log->action) {
            'insert' => $this->undoInsert($log),
            'fill' => $log->before_rows !== null ? $this->restoreFill($log) : $this->nullFill($log),
            'delete' => $this->reinsert($log),
            default => throw new RuntimeException("Unknown action \"{$log->action}\" in log row #{$log->id}."),
        };
    }

    private function undoInsert(stdClass $log): int
    {
        $deleted = 0;
        foreach ($this->idBatches($log) as $batch) {
            $deleted += $this->scoped($log, $batch)->delete();
        }

        return $deleted;
    }

    private function restoreFill(stdClass $log): int
    {
        $columns = $this->columns($log);
        $restored = 0;
        foreach ((array) json_decode((string) $log->before_rows, true, 512, JSON_THROW_ON_ERROR) as $row) {
            $values = array_intersect_key((array) $row, array_flip($columns));
            $restored += DB::table($log->table_name)->where('id', $row['id'])->update($values);
        }

        return $restored;
    }

    private function nullFill(stdClass $log): int
    {
        $nulls = array_fill_keys($this->columns($log), null);
        $updated = 0;
        foreach ($this->idBatches($log) as $batch) {
            $updated += $this->scoped($log, $batch)->update($nulls);
        }

        return $updated;
    }

    private function reinsert(stdClass $log): int
    {
        $rows = array_map(
            static fn (mixed $row): array => (array) $row,
            (array) json_decode((string) $log->before_rows, true, 512, JSON_THROW_ON_ERROR),
        );
        if ($rows === []) {
            return 0;
        }

        $inserted = 0;
        $perStatement = max(1, intdiv(60000, count($rows[0])));
        foreach (array_chunk($rows, $perStatement) as $chunk) {
            $inserted += DB::table($log->table_name)->insertOrIgnore($chunk);
        }

        if ($inserted !== count($rows)) {
            throw new RuntimeException(sprintf(
                'Log row #%d: only %d of %d rows could be re-inserted into %s (some ids exist again); this log row was not undone and is kept.',
                $log->id,
                $inserted,
                count($rows),
                $log->table_name,
            ));
        }

        return $inserted;
    }

    /**
     * @param  array{0: int, 1: int}  $batch  inclusive [min, max] range
     */
    private function scoped(stdClass $log, array $batch): Builder
    {
        return DB::table($log->table_name)->whereBetween('id', [$batch[0], $batch[1]]);
    }

    /**
     * The logged id ranges (id_min/id_max, or the [from, to] pairs in `ids`),
     * each split into sub-ranges of at most NULL_CHUNK ids.
     *
     * @return list<array{0: int, 1: int}>
     */
    private function idBatches(stdClass $log): array
    {
        $ranges = $log->ids !== null
            ? array_map(
                static fn (mixed $pair): array => [(int) $pair[0], (int) $pair[1]],
                (array) json_decode((string) $log->ids, true, 512, JSON_THROW_ON_ERROR),
            )
            : [[(int) $log->id_min, (int) $log->id_max]];

        $batches = [];
        foreach ($ranges as [$min, $max]) {
            for ($from = $min; $from <= $max; $from += self::NULL_CHUNK) {
                $batches[] = [$from, min($from + self::NULL_CHUNK - 1, $max)];
            }
        }

        return $batches;
    }

    /**
     * @return list<string>
     */
    private function columns(stdClass $log): array
    {
        return $log->columns === null ? [] : array_map('strval', (array) json_decode((string) $log->columns, true, 512, JSON_THROW_ON_ERROR));
    }

    private function assertIdentifiers(stdClass $log): void
    {
        foreach ([$log->table_name, ...$this->columns($log)] as $name) {
            if (preg_match(self::IDENTIFIER, $name) !== 1) {
                throw new RuntimeException("Unsafe identifier \"{$name}\" in log row #{$log->id}.");
            }
        }
        if ($log->action === 'fill' && $this->columns($log) === []) {
            throw new RuntimeException("Fill log row #{$log->id} has no columns.");
        }
    }
}
