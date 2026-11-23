<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Backfill;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

/**
 * Shared state of one invocation: both connections, options, the report and,
 * in execute mode only, the audit handle. `writes()` is the single switch every
 * step consults before touching PostgreSQL.
 */
final class BackfillContext
{
    public ?Audit $audit = null;

    /**
     * PostgreSQL read-only settings captured BEFORE the session was locked, so
     * preflight can still tell whether an execute run would be able to write.
     */
    public ?object $pgSessionBeforeLock = null;

    private float $lastProgressAt = 0.0;

    public function __construct(
        public readonly BackfillOptions $options,
        public readonly ConnectionInterface $legacy,
        public readonly ConnectionInterface $pg,
        public readonly PlanReport $report,
        public readonly ?Command $command = null,
    ) {}

    public function writes(): bool
    {
        return $this->audit !== null;
    }

    /**
     * Print and record a line.
     */
    public function emit(string $line): void
    {
        $this->command?->line($line);
        $this->report->add($line);
    }

    public function heading(string $title): void
    {
        $this->command?->newLine();
        $this->command?->info('== '.$title);
        $this->report->heading($title);
    }

    /**
     * Print only (progress noise that does not belong in the report).
     */
    public function progress(string $line): void
    {
        $now = microtime(true);
        if ($now - $this->lastProgressAt < 5.0) {
            return;
        }
        $this->lastProgressAt = $now;
        $this->command?->line('   '.$line);
    }

    public function pauseLegacy(): void
    {
        if ($this->options->legacyPauseMs > 0) {
            usleep($this->options->legacyPauseMs * 1000);
        }
    }

    public function quoteLegacy(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    public function quotePg(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
}
