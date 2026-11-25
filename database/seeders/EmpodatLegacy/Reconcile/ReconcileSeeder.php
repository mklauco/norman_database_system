<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use Database\Seeders\EmpodatLegacy\Backfill\ReadOnlyGuard;
use Illuminate\Database\Connection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\Telescope;
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

    /**
     * Minutes without any write before a "running" run of the same seeder is
     * treated as dead (killed process).
     */
    protected const STALE_RUN_MINUTES = 60;

    final public function run(): void
    {
        if (class_exists(Telescope::class)) {
            Telescope::stopRecording();
        }

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

        if (! $this->readyToWrite()) {
            return;
        }

        if ($this->options->closeStaleRunId !== null) {
            $closed = ReconcileAudit::closeStaleRun($this->pg, $this->options->closeStaleRunId);
            $this->say('Closed stale run #'.$this->options->closeStaleRunId.': '.$closed.' row(s) marked failed.');
        }

        if ($this->closesOwnStaleRuns()) {
            $this->closeOwnStaleRuns($name);
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

    /**
     * Called in execute mode after the write checks passed and before any
     * run is closed or started. Return false to finish normally without writing.
     */
    protected function readyToWrite(): bool
    {
        return true;
    }

    /**
     * Whether a "running" run of this same seeder that has shown no activity
     * for STALE_RUN_MINUTES is closed automatically before a new run starts.
     */
    protected function closesOwnStaleRuns(): bool
    {
        return false;
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

    /**
     * Closes this seeder's dead runs. A run is dead only when its newest log
     * row (or, without one, its start) is older than STALE_RUN_MINUTES; a
     * younger one may still be writing, so it stops the seeder instead.
     * Log rows carry the Postgres session time, started_at the application
     * time, so each is compared against its own clock.
     */
    private function closeOwnStaleRuns(string $seederName): void
    {
        $runs = $this->pg->table('empodat_legacy_import_runs')
            ->where('status', 'running')
            ->where('notes', 'like', ReconcileAudit::NOTE_PREFIX.' '.$seederName.'%')
            ->get(['id', 'started_at']);

        foreach ($runs as $run) {
            $lastLog = $this->pg->selectOne(
                'SELECT MAX(created_at) AS last_at, MAX(created_at) > LOCALTIMESTAMP - make_interval(mins => ?) AS recent FROM empodat_reconcile_log WHERE run_id = ?',
                [self::STALE_RUN_MINUTES, $run->id],
            );

            $isLive = $lastLog->last_at !== null
                ? (bool) $lastLog->recent
                : Carbon::parse($run->started_at)->greaterThan(now()->subMinutes(self::STALE_RUN_MINUTES));

            if ($isLive) {
                throw new RuntimeException('Run #'.$run->id.' of '.$seederName.' is "running" with activity within the last '.self::STALE_RUN_MINUTES.' minutes; it may still be alive, refusing to close it.');
            }

            $closed = ReconcileAudit::closeStaleRun($this->pg, (int) $run->id);
            $this->say('Closed stale run #'.$run->id.' of '.$seederName.': '.$closed.' row(s) marked failed.');
        }
    }
}
