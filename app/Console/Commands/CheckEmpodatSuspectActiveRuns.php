<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\EmpodatSuspect\QueueEmpodatSuspectCommand;
use App\Models\EmpodatSuspect\EmpodatSuspectCommandRun;
use Illuminate\Console\Command;

/**
 * Deploy guard: closes stale runs, then reports whether any EMPODAT Suspect
 * command run is still active. Exit code 0 = none, 2 = active runs.
 */
class CheckEmpodatSuspectActiveRuns extends Command
{
    protected $signature = 'empodat-suspect:check-active-runs';

    protected $description = 'Close stale EMPODAT Suspect command runs and exit with 2 if any run is still active';

    public function handle(QueueEmpodatSuspectCommand $queue): int
    {
        $queue->closeStaleRuns();

        $runs = EmpodatSuspectCommandRun::whereIn('status', EmpodatSuspectCommandRun::ACTIVE_STATUSES)
            ->orderBy('id')
            ->get();

        if ($runs->isEmpty()) {
            $this->line('No active EMPODAT Suspect command runs.');

            return self::SUCCESS;
        }

        foreach ($runs as $run) {
            $since = $run->status === EmpodatSuspectCommandRun::STATUS_RUNNING ? $run->started_at : $run->queued_at;

            $this->line("#{$run->id} {$run->command_key} {$run->status} since {$since?->toDateTimeString()}");
        }

        return 2;
    }
}
