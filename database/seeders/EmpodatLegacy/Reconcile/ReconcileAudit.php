<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use Illuminate\Database\ConnectionInterface;
use RuntimeException;
use Throwable;

/**
 * Audit trail for reconcile runs, undone by `php artisan empodat-reconcile:rollback {run_id}`.
 * The log methods must be called inside the transaction that made the change.
 */
final class ReconcileAudit
{
    public const NOTE_PREFIX = 'RECONCILE';

    private function __construct(
        private readonly ConnectionInterface $pg,
        public readonly int $runId,
    ) {}

    public static function start(ConnectionInterface $pg, string $seederName, string $notes): self
    {
        $running = $pg->table('empodat_legacy_import_runs')->where('status', 'running')->first(['id']);
        if ($running !== null) {
            throw new RuntimeException('Run #'.$running->id.' is still "running" in empodat_legacy_import_runs; refusing to start.');
        }

        $runId = (int) $pg->table('empodat_legacy_import_runs')->insertGetId([
            'since_id' => 0,
            'status' => 'running',
            'started_at' => now(),
            'notes' => substr(self::NOTE_PREFIX.' '.$seederName.' '.$notes, 0, 1000),
        ]);

        return new self($pg, $runId);
    }

    /**
     * @param  list<int>  $ids
     */
    public function logInsert(string $step, string $table, array $ids, int $durationMs): void
    {
        $this->write($step, $table, 'insert', $ids, null, null, $durationMs);
    }

    /**
     * @param  list<string>  $columns
     * @param  list<int>  $ids
     * @param  list<array<string, mixed>>|null  $beforeRows  old values (id plus the filled columns), only for small tables
     */
    public function logFill(string $step, string $table, array $columns, array $ids, ?array $beforeRows, int $durationMs): void
    {
        $this->write($step, $table, 'fill', $ids, $columns, $beforeRows, $durationMs);
    }

    /**
     * @param  list<array<string, mixed>>  $beforeRows  full deleted rows, each with its `id`
     */
    public function logDelete(string $step, string $table, array $beforeRows, int $durationMs): void
    {
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $beforeRows);
        $this->write($step, $table, 'delete', $ids, null, $beforeRows, $durationMs);
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
     * @param  list<int>  $ids
     * @param  list<string>|null  $columns
     * @param  list<array<string, mixed>>|null  $beforeRows
     */
    private function write(string $step, string $table, string $action, array $ids, ?array $columns, ?array $beforeRows, int $durationMs): void
    {
        if ($ids === []) {
            return;
        }

        $ranges = self::ranges($ids);
        $single = count($ranges) === 1;

        $this->pg->table('empodat_reconcile_log')->insert([
            'run_id' => $this->runId,
            'step' => substr($step, 0, 100),
            'table_name' => $table,
            'action' => $action,
            'columns' => $columns === null ? null : json_encode(array_values($columns), JSON_THROW_ON_ERROR),
            'id_min' => $single ? $ranges[0][0] : null,
            'id_max' => $single ? $ranges[0][1] : null,
            'ids' => $single ? null : json_encode($ranges, JSON_THROW_ON_ERROR),
            'before_rows' => $beforeRows === null ? null : json_encode($beforeRows, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE),
            'row_count' => count($ids),
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Collapses ids into runs of consecutive ids, so a bulk fill of millions of
     * rows logs a few ranges instead of every id.
     *
     * @param  list<int>  $ids
     * @return list<array{0: int, 1: int}>
     */
    public static function ranges(array $ids): array
    {
        $ids = array_values(array_unique($ids));
        sort($ids);

        $ranges = [];
        foreach ($ids as $id) {
            $last = array_key_last($ranges);
            if ($last !== null && $ranges[$last][1] === $id - 1) {
                $ranges[$last][1] = $id;
            } else {
                $ranges[] = [$id, $id];
            }
        }

        return $ranges;
    }
}
