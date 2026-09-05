<?php

declare(strict_types=1);

namespace App\Livewire\EmpodatSuspect;

use App\Actions\EmpodatSuspect\QueueEmpodatSuspectCommand;
use App\Jobs\EmpodatSuspect\RunEmpodatSuspectCommandJob;
use App\Models\Backend\File;
use App\Models\EmpodatSuspect\EmpodatSuspectCommandRun;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Super-admin control panel for the four allowlisted `empodat-suspect:*`
 * materialized-view refresh commands (see config/empodat_suspect_commands.php).
 *
 * Every trigger method only ever passes an opaque, config-validated command
 * key and a handful of re-validated arguments to a queued job — never a
 * free-form command string. Authorization is re-checked on mount and on
 * every action because Livewire's update endpoint does not automatically
 * re-run the page route's middleware.
 */
class CommandCenter extends Component
{
    use WithPagination;

    public string $matrixOnly = '';

    public string $prioritisationFileId = '';

    public function mount(): void
    {
        $this->authorize('empodat-suspect.refresh');
    }

    /**
     * Trigger a single allowlisted command by its config key.
     */
    public function trigger(string $commandKey): void
    {
        $this->authorize('empodat-suspect.refresh');
        $this->resetErrorBag();

        $definition = config("empodat_suspect_commands.commands.{$commandKey}");

        if (! is_array($definition)) {
            session()->flash('empodat-suspect-commands-error', 'Unknown command.');

            return;
        }

        if ($this->hasActiveRun()) {
            session()->flash('empodat-suspect-commands-error', 'Another EMPODAT Suspect command is already queued or running. Wait for it to finish.');

            return;
        }

        $arguments = $this->resolveArguments($commandKey);

        if ($arguments === null) {
            return;
        }

        $this->queueRun($commandKey, $arguments);

        session()->flash('empodat-suspect-commands-success', "Queued: {$definition['label']}");
    }

    /**
     * Trigger all four commands in the one order that is actually correct:
     * filters, then matrix metadata, then prioritisation, then statistics.
     * Uses a job chain so each step only starts once the previous one
     * finished successfully.
     */
    public function triggerAll(): void
    {
        $this->authorize('empodat-suspect.refresh');
        $this->resetErrorBag();

        if ($this->hasActiveRun()) {
            session()->flash('empodat-suspect-commands-error', 'Another EMPODAT Suspect command is already queued or running. Wait for it to finish.');

            return;
        }

        $order = config('empodat_suspect_commands.refresh_all_order', []);
        $jobs = [];

        foreach ($order as $commandKey) {
            $arguments = $this->defaultArgumentsFor($commandKey);
            $run = $this->createRun($commandKey, $arguments);
            $jobs[] = new RunEmpodatSuspectCommandJob($run->id, $commandKey, $arguments);
        }

        Bus::chain($jobs)->dispatch();

        session()->flash('empodat-suspect-commands-success', 'Queued: Refresh all (runs in order — filters, matrix metadata, prioritisation, statistics).');
    }

    /**
     * Whether any run is currently queued or running — used both to refuse
     * a new trigger and to decide whether the view should keep polling.
     */
    public function hasActiveRun(): bool
    {
        return $this->queueAction()->hasActiveRun();
    }

    /**
     * EMPODAT Suspect source files eligible for --file on the prioritisation
     * refresh, i.e. files.id where files.database_entity_id matches the
     * 'empodat_suspect' database entity.
     *
     * @return Collection<int, File>
     */
    public function prioritisationFiles(): Collection
    {
        $entityId = $this->empodatSuspectEntityId();

        if ($entityId === null) {
            return collect();
        }

        return File::notDeleted()
            ->byDatabaseEntity($entityId)
            ->orderBy('name')
            ->get(['id', 'name', 'original_name']);
    }

    /**
     * @return array<string, mixed>|null Null means validation failed; the
     *                                   error is already attached to the form.
     */
    private function resolveArguments(string $commandKey): ?array
    {
        return match ($commandKey) {
            'refresh_matrix_metadata' => $this->resolveMatrixMetadataArguments(),
            'refresh_prioritisation' => $this->resolvePrioritisationArguments(),
            default => $this->defaultArgumentsFor($commandKey),
        };
    }

    /**
     * The arguments implied purely by the config's "always on" options
     * (e.g. --stats, --sync) with nothing client-controlled added.
     *
     * @return array<string, mixed>
     */
    private function defaultArgumentsFor(string $commandKey): array
    {
        $definition = config("empodat_suspect_commands.commands.{$commandKey}", []);
        $arguments = [];

        foreach ($definition['options'] ?? [] as $optionName => $optionDefinition) {
            if (($optionDefinition['always'] ?? false) === true) {
                $arguments[$optionName] = true;
            }
        }

        return $arguments;
    }

    private function resolveMatrixMetadataArguments(): ?array
    {
        $arguments = $this->defaultArgumentsFor('refresh_matrix_metadata');

        if ($this->matrixOnly === '') {
            return $arguments;
        }

        $allowed = config('empodat_suspect_commands.commands.refresh_matrix_metadata.options.only.allowed', []);

        if (! in_array($this->matrixOnly, $allowed, true)) {
            $this->addError('matrixOnly', 'Choose a valid matrix type.');

            return null;
        }

        $arguments['only'] = $this->matrixOnly;

        return $arguments;
    }

    private function resolvePrioritisationArguments(): ?array
    {
        $arguments = $this->defaultArgumentsFor('refresh_prioritisation');

        if ($this->prioritisationFileId === '') {
            return $arguments;
        }

        $fileId = (int) $this->prioritisationFileId;
        $entityId = $this->empodatSuspectEntityId();

        $valid = $entityId !== null && File::where('id', $fileId)
            ->where('database_entity_id', $entityId)
            ->exists();

        if (! $valid) {
            $this->addError('prioritisationFileId', 'Choose a valid file.');

            return null;
        }

        $arguments['file'] = $fileId;

        return $arguments;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function createRun(string $commandKey, array $arguments): EmpodatSuspectCommandRun
    {
        return $this->queueAction()->createRun($commandKey, $arguments, Auth::id());
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function queueRun(string $commandKey, array $arguments): void
    {
        $this->queueAction()->queue($commandKey, $arguments, Auth::id());
    }

    private function empodatSuspectEntityId(): ?int
    {
        return $this->queueAction()->empodatSuspectEntityId();
    }

    /**
     * Shared with the per-file trigger on the Uploaded DCT Files page, so both
     * entry points validate and record a run the same way.
     */
    private function queueAction(): QueueEmpodatSuspectCommand
    {
        return app(QueueEmpodatSuspectCommand::class);
    }

    public function render(): View
    {
        $history = EmpodatSuspectCommandRun::with('user')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.empodat_suspect.command-center', [
            'commands' => config('empodat_suspect_commands.commands', []),
            'history' => $history,
            'hasActiveRun' => $this->hasActiveRun(),
            'prioritisationFiles' => $this->prioritisationFiles(),
        ]);
    }
}
