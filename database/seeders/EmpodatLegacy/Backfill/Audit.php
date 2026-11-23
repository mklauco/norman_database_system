<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use Illuminate\Database\ConnectionInterface;
use Throwable;

/**
 * Audit trail compatible with `php artisan empodat-legacy:rollback {run_id}`.
 *
 * Every chunk writes ONE log row inside the same transaction as its data, so a
 * log row exists exactly when its rows do. A chunk whose inserted ids are
 * contiguous is logged as id_min/id_max (exact, tiny); any other chunk logs the
 * exact id list in inserted_ids. The rollback command therefore never deletes a
 * row this run did not insert. Rollback deletes log rows newest first, which
 * undoes matrix/minor before main before stations before lookups.
 */
final class Audit
{
    public const NOTE_PREFIX = 'BACKFILL';

    private function __construct(
        private readonly ConnectionInterface $pg,
        public readonly int $runId,
    ) {}

    public static function start(ConnectionInterface $pg, string $notes): self
    {
        $runId = (int) $pg->table('empodat_legacy_import_runs')->insertGetId([
            'since_id' => 0,
            'status' => 'running',
            'started_at' => now(),
            'notes' => substr(self::NOTE_PREFIX.' '.$notes, 0, 1000),
        ]);

        return new self($pg, $runId);
    }

    /**
     * Must be called inside the transaction that inserted $ids.
     *
     * @param  list<int>  $ids  ids that were actually inserted (RETURNING)
     */
    public function chunk(string $step, string $table, array $ids, int $durationMs): void
    {
        if ($ids === []) {
            return;
        }

        sort($ids);
        $min = $ids[0];
        $max = $ids[array_key_last($ids)];
        $contiguous = count($ids) === ($max - $min + 1);

        $this->pg->table('empodat_legacy_import_log')->insert([
            'run_id' => $this->runId,
            'step_name' => substr($step, 0, 100),
            'table_name' => $table,
            'inserted_ids' => $contiguous ? null : json_encode($ids),
            'id_min' => $contiguous ? $min : null,
            'id_max' => $contiguous ? $max : null,
            'inserted_count' => count($ids),
            'duration_ms' => $durationMs,
            'status' => 'completed',
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }

    public function failedStep(string $step, string $table, Throwable $error): void
    {
        $this->pg->table('empodat_legacy_import_log')->insert([
            'run_id' => $this->runId,
            'step_name' => substr($step, 0, 100),
            'table_name' => $table,
            'inserted_count' => 0,
            'status' => 'failed',
            'error' => substr($error->getMessage(), 0, 2000),
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->pg->table('empodat_legacy_import_runs')->where('id', $this->runId)->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);
    }

    public function fail(Throwable $error): void
    {
        $this->pg->table('empodat_legacy_import_runs')->where('id', $this->runId)->update([
            'status' => 'failed',
            'finished_at' => now(),
            'notes' => substr(self::NOTE_PREFIX.' FAILED: '.$error->getMessage(), 0, 1000),
        ]);
    }

    /**
     * Marks a dead BACKFILL run (killed process) as failed so a new run may start.
     * Only touches audit rows of runs this seeder started.
     */
    public static function closeStaleRun(ConnectionInterface $pg, int $runId): int
    {
        return $pg->table('empodat_legacy_import_runs')
            ->where('id', $runId)
            ->where('status', 'running')
            ->where('notes', 'like', self::NOTE_PREFIX.'%')
            ->update(['status' => 'failed', 'finished_at' => now()]);
    }
}
