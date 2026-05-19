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
     * Idempotent insert: INSERT ... ON CONFLICT (col) DO NOTHING RETURNING <col>.
     * Returns the values that were actually inserted (skipped duplicates excluded).
     *
     * Never UPDATEs an existing row. Never TRUNCATEs or DELETEs anything.
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
