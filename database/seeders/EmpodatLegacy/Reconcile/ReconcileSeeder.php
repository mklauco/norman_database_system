<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use Database\Seeders\EmpodatLegacy\Backfill\ReadOnlyGuard;
use Illuminate\Database\Connection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Base of the small, auditable reconcile seeders. A subclass builds its work
 * list in plan() (read-only) and writes it in apply(), logging every change
 * through the audit inside the transaction that made it.
 */
abstract class ReconcileSeeder extends Seeder
{
    protected ReconcileOptions $options;

    protected Connection $legacy;

    protected Connection $pg;

    /** @return int number of rows the seeder would change */
    abstract protected function plan(): int;

    abstract protected function apply(ReconcileAudit $audit): void;

    /** Short description stored in the run notes. */
    abstract protected function notes(): string;

    final public function run(): void
    {
        $this->options = ReconcileOptions::fromEnvironment();

        ini_set('memory_limit', '2G');
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::disableQueryLog();
        DB::connection('legacy_empodat')->disableQueryLog();

        $this->legacy = DB::connection('legacy_empodat');
        $this->pg = DB::connection();

        ReadOnlyGuard::lock($this->legacy);
        if (! $this->options->isExecute()) {
            ReadOnlyGuard::lock($this->pg);
        }

        $database = (string) $this->pg->selectOne('SELECT current_database() AS db')->db;
        $name = class_basename($this);
        $this->say($name.'  mode='.$this->options->mode.'  database='.$database);
        if ($this->options->idFrom !== null || $this->options->idTo !== null) {
            $this->say('id window: '.$this->options->windowLabel());
        }

        $count = $this->options->isExecute() && ! $this->countsBeforeWriting() ? null : $this->plan();

        if (! $this->options->isExecute()) {
            $this->say('Planned rows: '.$count.'. Nothing written (dry-run).');
            $this->say('To write: RECONCILE_MODE=execute RECONCILE_EXECUTE=true RECONCILE_EXPECT_PG_DATABASE='.$database.' RECONCILE_EXPECT_COUNT='.$count);

            return;
        }

        if ($count === 0) {
            $this->say('Planned rows: 0, nothing to do.');

            return;
        }

        $violations = $this->options->writeViolations($database, $count);
        if ($violations !== []) {
            throw new RuntimeException('Refusing to write: '.implode('; ', $violations));
        }

        if ($this->options->closeStaleRunId !== null) {
            $closed = ReconcileAudit::closeStaleRun($this->pg, $this->options->closeStaleRunId);
            $this->say('Closed stale run #'.$this->options->closeStaleRunId.': '.$closed.' row(s) marked failed.');
        }

        $audit = ReconcileAudit::start($this->pg, $name, $this->notes());

        try {
            $this->apply($audit);
            $audit->complete();
        } catch (Throwable $e) {
            $audit->fail($e);

            throw $e;
        }

        $this->say('Run #'.$audit->runId.' completed. Undo with: php artisan empodat-reconcile:rollback '.$audit->runId);
    }

    /**
     * Whether an execute run calls plan() before apply(). A seeder whose
     * apply() re-reads everything itself can skip the extra pass.
     */
    protected function countsBeforeWriting(): bool
    {
        return true;
    }

    protected function say(string $line): void
    {
        $this->command?->line($line);
    }

    protected function pauseLegacy(): void
    {
        if ($this->options->legacyPauseMs > 0) {
            usleep($this->options->legacyPauseMs * 1000);
        }
    }
}
