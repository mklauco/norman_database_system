<?php

declare(strict_types=1);

namespace App\Actions\EmpodatSuspect;

use App\Jobs\EmpodatSuspect\RunEmpodatSuspectCommandJob;
use App\Models\Backend\File;
use App\Models\DatabaseEntity;
use App\Models\EmpodatSuspect\EmpodatSuspectCommandRun;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Queues one allowlisted `empodat-suspect:*` command and records the run.
 *
 * Extracted from App\Livewire\EmpodatSuspect\CommandCenter so the Commands
 * page and the per-file button on the Uploaded DCT Files page reach the queue
 * through identical validation. Two copies of "is this a real EMPODAT Suspect
 * file id" is exactly how one of them eventually stops being true.
 *
 * A command string is never assembled from caller input: callers pass an
 * opaque key that must resolve in config/empodat_suspect_commands.php, and the
 * arguments are rebuilt here from that definition.
 */
class QueueEmpodatSuspectCommand
{
    /**
     * Whether any run is queued or running right now.
     *
     * Deliberately global rather than per-command: these commands rebuild
     * overlapping derived data, and two of them at once corrupts it. The
     * job carries its own Cache::lock as the real guarantee; this is the
     * check that lets the UI refuse politely instead of silently discarding
     * the second trigger.
     */
    public function hasActiveRun(): bool
    {
        $this->closeStaleRuns();

        return EmpodatSuspectCommandRun::whereIn('status', EmpodatSuspectCommandRun::ACTIVE_STATUSES)->exists();
    }

    /**
     * Marks runs that can no longer be alive as failed, so a killed worker
     * (e.g. a deploy) does not block the UI forever. Healing happens on
     * demand, whenever the active state is asked for.
     *
     * - running: older than the job timeout plus 10 minutes. The worker kills
     *   every job at the timeout, so it cannot still be running.
     * - queued: older than 10 minutes while no row of the queue table mentions
     *   this job at all. "Refresh all" creates every run up front while only
     *   the head job is in the table, so a queued row is only stale when no
     *   job exists anywhere to carry it.
     *
     * @return int Number of runs closed.
     */
    public function closeStaleRuns(): int
    {
        $runs = EmpodatSuspectCommandRun::whereIn('status', EmpodatSuspectCommandRun::ACTIVE_STATUSES)->get();

        if ($runs->isEmpty()) {
            return 0;
        }

        $runningCutoff = now()->subSeconds(RunEmpodatSuspectCommandJob::TIMEOUT_SECONDS + 600);
        $queuedCutoff = now()->subMinutes(10);

        $stale = $runs->filter(fn (EmpodatSuspectCommandRun $run): bool => $run->status === EmpodatSuspectCommandRun::STATUS_RUNNING
            && $run->started_at !== null
            && $run->started_at->lt($runningCutoff));

        $queuedCandidates = $runs->filter(fn (EmpodatSuspectCommandRun $run): bool => $run->status === EmpodatSuspectCommandRun::STATUS_QUEUED
            && $run->queued_at !== null
            && $run->queued_at->lt($queuedCutoff));

        if ($queuedCandidates->isNotEmpty() && ! $this->queueHoldsCommandJob()) {
            $stale = $stale->merge($queuedCandidates);
        }

        foreach ($stale as $run) {
            $reason = $run->status === EmpodatSuspectCommandRun::STATUS_RUNNING
                ? 'the worker is gone (running for longer than the job timeout)'
                : 'no job for it is left in the queue';

            $run->update([
                'status' => EmpodatSuspectCommandRun::STATUS_FAILED,
                'finished_at' => now(),
                'output' => trim(($run->output ?? '')."\n\nRun interrupted: the worker stopped before the run finished ({$reason})."),
            ]);

            Cache::lock(RunEmpodatSuspectCommandJob::lockKeyFor($run->command_key))->forceRelease();

            Log::warning("EmpodatSuspect command run #{$run->id} ({$run->command_key}) closed as stale: {$reason}.");
        }

        return $stale->count();
    }

    private function queueHoldsCommandJob(): bool
    {
        $table = (string) config('queue.connections.database.table', 'jobs');
        $database = config('queue.connections.database.connection');

        return DB::connection($database)->table($table)
            ->where('payload', 'like', '%RunEmpodatSuspectCommandJob%')
            ->exists();
    }

    /**
     * The allowlist definition for a command key, or null when the key is not
     * one this application will run.
     *
     * @return array<string, mixed>|null
     */
    public function definitionFor(string $commandKey): ?array
    {
        $definition = config("empodat_suspect_commands.commands.{$commandKey}");

        return is_array($definition) ? $definition : null;
    }

    /**
     * The arguments implied purely by the config's "always on" options
     * (e.g. --stats, --sync), with nothing caller-controlled added.
     *
     * @return array<string, mixed>
     */
    public function defaultArgumentsFor(string $commandKey): array
    {
        $definition = $this->definitionFor($commandKey) ?? [];
        $arguments = [];

        foreach ($definition['options'] ?? [] as $optionName => $optionDefinition) {
            if (($optionDefinition['always'] ?? false) === true) {
                $arguments[$optionName] = true;
            }
        }

        return $arguments;
    }

    /**
     * Record a run row without dispatching it. Used by the "Refresh all"
     * chain, which needs every run to exist before the chain is dispatched.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function createRun(string $commandKey, array $arguments, ?int $userId): EmpodatSuspectCommandRun
    {
        return EmpodatSuspectCommandRun::create([
            'command_key' => $commandKey,
            'arguments' => $arguments,
            'user_id' => $userId,
            'queued_at' => now(),
            'status' => EmpodatSuspectCommandRun::STATUS_QUEUED,
        ]);
    }

    /**
     * Record and dispatch a run.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function queue(string $commandKey, array $arguments, ?int $userId): EmpodatSuspectCommandRun
    {
        $run = $this->createRun($commandKey, $arguments, $userId);

        RunEmpodatSuspectCommandJob::dispatch($run->id, $commandKey, $arguments);

        return $run;
    }

    /**
     * Resolved from `database_entities.code` rather than a hardcoded integer:
     * the numeric id is environment-specific.
     */
    public function empodatSuspectEntityId(): ?int
    {
        $entityCode = config('empodat_suspect_commands.commands.refresh_prioritisation.options.file.database_entity_code');

        return DatabaseEntity::where('code', $entityCode)->value('id');
    }

    /**
     * Whether a file really belongs to EMPODAT Suspect.
     *
     * Checked server-side on every trigger. That a button was rendered for a
     * row proves nothing about the request that arrives afterwards.
     */
    public function isEmpodatSuspectFile(File $file): bool
    {
        $entityId = $this->empodatSuspectEntityId();

        return $entityId !== null && $file->database_entity_id === $entityId;
    }
}
