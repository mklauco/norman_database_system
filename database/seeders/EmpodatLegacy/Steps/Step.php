<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Base for every legacy-import step.
 *
 * Each step is one focused unit (import a table, run a UPDATE, scrub
 * orphans). The orchestrator wires them in order. A step takes the delta
 * cutoff `$sinceId` and the active `$runId` from the audit log.
 *
 * Safety rules baked into the helpers below:
 *   - All inserts go through insertOnConflictDoNothing() — never overwrites,
 *     never deletes, never updates existing rows.
 *   - Every step writes one row to empodat_legacy_import_log so the run can
 *     be rolled back row-for-row via `php artisan empodat-legacy:rollback`.
 */
abstract class Step
{
    public function __construct(
        protected readonly int $sinceId,
        protected readonly int $runId,
        protected ?Command $command = null,
    ) {}

    /**
     * Short human-readable name shown in seeder output and the audit log.
     */
    abstract public function name(): string;

    /**
     * Execute this step. Must be idempotent (re-running is a no-op).
     *
     * Implementations should call logSmallStep() or logBulkStep() with the
     * id list / range that was actually inserted, OR logStepFailed() on
     * error. Wrapping each step's work in pg()->transaction() is recommended.
     */
    abstract public function run(): void;

    /**
     * Connection to the legacy MariaDB. Configure `legacy_empodat`
     * in config/database.php — see LEGACY_MIGRATION_PLAN.md.
     */
    protected function legacy(): ConnectionInterface
    {
        return DB::connection('legacy_empodat');
    }

    /**
     * Connection to the PG target (default app connection).
     */
    protected function pg(): ConnectionInterface
    {
        return DB::connection();
    }

    protected function note(string $message): void
    {
        $this->command?->line('   '.$message);
    }

    /**
     * True if this step's table_name was previously inserted into for the same
     * since_id by a run that has not been rolled back. Use this in non-idempotent
     * steps (e.g. ImportStationsStep, which uses auto-id and would duplicate) to
     * skip on re-run.
     */
    protected function previouslyCompleted(string $tableName): bool
    {
        return $this->pg()
            ->table('empodat_legacy_import_log')
            ->join('empodat_legacy_import_runs', 'empodat_legacy_import_runs.id', '=', 'empodat_legacy_import_log.run_id')
            ->where('empodat_legacy_import_log.table_name', $tableName)
            ->where('empodat_legacy_import_log.status', 'completed')
            ->where('empodat_legacy_import_runs.since_id', $this->sinceId)
            ->where('empodat_legacy_import_runs.status', '<>', 'rolled_back')
            ->where('empodat_legacy_import_log.inserted_count', '>', 0)
            ->exists();
    }

    /**
     * Idempotent insert: INSERT ... ON CONFLICT (col) DO NOTHING RETURNING <col>.
     * Returns the values that were actually inserted (skipped duplicates excluded).
     *
     * Never UPDATEs an existing row. Never TRUNCATEs or DELETEs anything.
     *
     * Auto-chunks to stay under PG's 65 535 bound-parameter cap (floor(65000 / cols)
     * rows per statement, safety margin 535).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  string  $conflictColumn  PK or unique column to check for conflict (default: 'id')
     * @return array<int, int> ids that were actually inserted
     */
    protected function insertOnConflictDoNothing(
        string $table,
        array $rows,
        string $conflictColumn = 'id',
    ): array {
        if (empty($rows)) {
            return [];
        }

        $columns = array_keys($rows[0]);
        $columnCount = count($columns);
        $chunkSize = max(1, intdiv(65000, $columnCount));

        $allInserted = [];

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $allInserted = array_merge(
                $allInserted,
                $this->insertChunk($table, $columns, $chunk, $conflictColumn),
            );
        }

        return $allInserted;
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, int>
     */
    private function insertChunk(string $table, array $columns, array $rows, string $conflictColumn): array
    {
        $columnList = implode(', ', array_map(fn ($c) => '"'.$c.'"', $columns));
        $valuesSql = [];
        $bindings = [];

        foreach ($rows as $row) {
            $placeholders = [];
            foreach ($columns as $col) {
                $placeholders[] = '?';
                $bindings[] = $row[$col] ?? null;
            }
            $valuesSql[] = '('.implode(', ', $placeholders).')';
        }

        $sql = sprintf(
            'INSERT INTO "%s" (%s) VALUES %s ON CONFLICT ("%s") DO NOTHING RETURNING "%s"',
            $table,
            $columnList,
            implode(', ', $valuesSql),
            $conflictColumn,
            $conflictColumn,
        );

        $result = $this->pg()->select($sql, $bindings);

        return array_map(static fn ($r) => (int) $r->{$conflictColumn}, $result);
    }

    /**
     * Record a small step's results in the audit log (exact id list).
     *
     * @param  array<int, int>  $insertedIds
     */
    protected function logSmallStep(
        string $tableName,
        array $insertedIds,
        ?int $durationMs = null,
    ): void {
        $this->pg()->table('empodat_legacy_import_log')->insert([
            'run_id' => $this->runId,
            'step_name' => $this->name(),
            'table_name' => $tableName,
            'inserted_ids' => json_encode(array_values($insertedIds)),
            'inserted_count' => count($insertedIds),
            'duration_ms' => $durationMs,
            'status' => 'completed',
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }

    /**
     * Record a bulk step's results in the audit log (id range).
     */
    protected function logBulkStep(
        string $tableName,
        int $insertedCount,
        ?int $idMin,
        ?int $idMax,
        ?int $durationMs = null,
    ): void {
        $this->pg()->table('empodat_legacy_import_log')->insert([
            'run_id' => $this->runId,
            'step_name' => $this->name(),
            'table_name' => $tableName,
            'id_min' => $idMin,
            'id_max' => $idMax,
            'inserted_count' => $insertedCount,
            'duration_ms' => $durationMs,
            'status' => 'completed',
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }

    protected function logStepFailed(string $tableName, Throwable $error): void
    {
        $this->pg()->table('empodat_legacy_import_log')->insert([
            'run_id' => $this->runId,
            'step_name' => $this->name(),
            'table_name' => $tableName,
            'inserted_count' => 0,
            'status' => 'failed',
            'error' => $error->getMessage(),
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }
}
