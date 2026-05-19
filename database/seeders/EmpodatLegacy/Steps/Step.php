<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Steps;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * Base for every legacy-import step.
 *
 * Each step is one focused unit (import a table, run a UPDATE, scrub
 * orphans). The orchestrator wires them in order. A step takes the
 * delta cutoff `$sinceId` and an optional command for progress output.
 */
abstract class Step
{
    public function __construct(
        protected readonly int $sinceId,
        protected ?Command $command = null,
    ) {}

    /**
     * Short human-readable name shown in seeder output.
     */
    abstract public function name(): string;

    /**
     * Execute this step. Idempotent where reasonable.
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
}
