<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deploy guard for the EMPODAT legacy backfill (and the delta importer): a
 * deploy replaces the app container and kills a seeder that is still running.
 * Exit code 0 = no live run, 2 = a run is active. A run that has shown no
 * activity for --stale-minutes (killed process) is reported but does not block.
 *
 * Wire it into the deploy workflow the same way as
 * `empodat-suspect:check-active-runs` (the workflow is not changed by this command).
 */
class EmpodatLegacyCheckActiveRuns extends Command
{
    protected $signature = 'empodat-legacy:check-active-runs
        {--stale-minutes=60 : A running run without log activity for this long is treated as dead}';

    protected $description = 'Exit with 2 if an EMPODAT legacy import/backfill run is still active (deploy guard)';

    public function handle(): int
    {
        $staleMinutes = max(1, (int) $this->option('stale-minutes'));
        $runs = DB::table('empodat_legacy_import_runs')->where('status', 'running')->orderBy('id')->get();

        if ($runs->isEmpty()) {
            $this->line('No active EMPODAT legacy import runs.');

            return self::SUCCESS;
        }

        $active = 0;
        foreach ($runs as $run) {
            $lastLog = DB::table('empodat_legacy_import_log')->where('run_id', $run->id)->max('finished_at');
            $lastActivity = strtotime((string) ($lastLog ?? $run->started_at)) ?: 0;
            $idleMinutes = (int) floor((time() - $lastActivity) / 60);
            $stale = $idleMinutes >= $staleMinutes;

            $this->line(sprintf(
                '#%d since %s, last activity %d min ago%s: %s',
                $run->id,
                $run->started_at,
                $idleMinutes,
                $stale ? ' (stale, not blocking)' : '',
                (string) $run->notes,
            ));

            if (! $stale) {
                $active++;
            }
        }

        return $active > 0 ? 2 : self::SUCCESS;
    }
}
