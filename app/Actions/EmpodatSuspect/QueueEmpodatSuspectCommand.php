<?php

declare(strict_types=1);

namespace App\Actions\EmpodatSuspect;

use App\Jobs\EmpodatSuspect\RunEmpodatSuspectCommandJob;
use App\Models\Backend\File;
use App\Models\DatabaseEntity;
use App\Models\EmpodatSuspect\EmpodatSuspectCommandRun;

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
        return EmpodatSuspectCommandRun::whereIn('status', EmpodatSuspectCommandRun::ACTIVE_STATUSES)->exists();
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
