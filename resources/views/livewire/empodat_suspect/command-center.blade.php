<div @if($hasActiveRun) wire:poll.3s="$refresh" @endif>

  @if (session('empodat-suspect-commands-success'))
    <div class="mb-4 bg-lime-50 border border-lime-300 text-lime-800 px-4 py-3 rounded-lg text-sm">
      {{ session('empodat-suspect-commands-success') }}
    </div>
  @endif

  @if (session('empodat-suspect-commands-error'))
    <div class="mb-4 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm">
      {{ session('empodat-suspect-commands-error') }}
    </div>
  @endif

  @if ($hasActiveRun)
    <div class="mb-4 bg-amber-50 border border-amber-300 text-amber-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
      <i class="fas fa-circle-notch fa-spin"></i>
      A command is currently queued or running. All triggers are disabled until it finishes — this page refreshes itself every few seconds.
    </div>
  @endif

  <!-- Primary action: Refresh all, in the only correct order -->
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
    <div class="p-4 bg-gradient-to-r from-lime-600 to-green-700">
      <h3 class="text-base font-semibold text-white">
        <i class="fas fa-layer-group mr-2"></i>
        Refresh All
      </h3>
      <p class="text-xs text-lime-100 mt-1">Runs every step below, strictly in this order: Station Filters &rarr; Matrix Metadata &rarr; Prioritisation &rarr; Statistics.</p>
    </div>
    <div class="p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <p class="text-sm text-gray-600 max-w-2xl">
        This is the action you want in almost every case. Station Filters must run first — it rebuilds the helper
        table that every other view reads from — so the individual buttons below are marked "advanced" and should
        only be used when you specifically need to re-run one step.
      </p>
      <button
        type="button"
        wire:click="triggerAll"
        wire:loading.attr="disabled"
        wire:target="triggerAll"
        @if($hasActiveRun) disabled @endif
        class="btn-create shrink-0 text-sm px-6 py-3 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span wire:loading.remove wire:target="triggerAll">
          <i class="fas fa-play mr-2"></i>Refresh All
        </span>
        <span wire:loading wire:target="triggerAll">
          <i class="fas fa-circle-notch fa-spin mr-2"></i>Queuing&hellip;
        </span>
      </button>
    </div>
  </div>

  <!-- Advanced: individual commands -->
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
    <div class="p-4 bg-gray-800 border-b border-gray-700">
      <h3 class="text-base font-semibold text-white">
        <i class="fas fa-sliders-h mr-2 text-gray-300"></i>
        Advanced: Run an Individual Step
      </h3>
      <p class="text-xs text-gray-300 mt-1">Only use these if you know why "Refresh All" isn't what you want. Running a step out of order can silently leave views stale.</p>
    </div>
    <div class="divide-y divide-gray-200">
      @foreach($commands as $key => $definition)
        <div class="p-6 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4" wire:key="command-row-{{ $key }}">
          <div class="max-w-xl">
            <h4 class="text-sm font-semibold text-gray-800">{{ $definition['label'] }}</h4>
            <p class="text-xs text-gray-500 mt-1">{{ $definition['description'] }}</p>
            <p class="text-xs text-gray-400 mt-1">
              <i class="fas fa-clock mr-1"></i>Typically {{ $definition['estimated_duration'] }}
              <span class="font-mono ml-2">{{ $definition['signature'] }}</span>
            </p>
          </div>

          <div class="flex flex-col sm:flex-row items-start sm:items-end gap-3 shrink-0">
            @if($key === 'refresh_matrix_metadata')
              <div>
                <label for="matrixOnly" class="block text-xs font-medium text-gray-600 mb-1">Matrix type (optional)</label>
                <select id="matrixOnly" wire:model="matrixOnly" class="form-select text-sm py-1.5">
                  <option value="">All matrix types</option>
                  @foreach($definition['options']['only']['allowed'] as $matrixType)
                    <option value="{{ $matrixType }}">{{ ucwords(str_replace('_', ' ', $matrixType)) }}</option>
                  @endforeach
                </select>
                @error('matrixOnly') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
              </div>
            @endif

            @if($key === 'refresh_prioritisation')
              <div>
                <label for="prioritisationFileId" class="block text-xs font-medium text-gray-600 mb-1">Single source file (optional)</label>
                <select id="prioritisationFileId" wire:model="prioritisationFileId" class="form-select text-sm py-1.5">
                  <option value="">All source files</option>
                  @foreach($prioritisationFiles as $file)
                    <option value="{{ $file->id }}">{{ $file->name ?? $file->original_name }} (#{{ $file->id }})</option>
                  @endforeach
                </select>
                @error('prioritisationFileId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
              </div>
            @endif

            <button
              type="button"
              wire:click="trigger('{{ $key }}')"
              wire:loading.attr="disabled"
              wire:target="trigger('{{ $key }}'), matrixOnly, prioritisationFileId"
              @if($hasActiveRun) disabled @endif
              class="btn-submit text-xs px-4 py-2 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
            >
              <span wire:loading.remove wire:target="trigger('{{ $key }}')">
                <i class="fas fa-play mr-1"></i>Run
              </span>
              <span wire:loading wire:target="trigger('{{ $key }}')">
                <i class="fas fa-circle-notch fa-spin mr-1"></i>Queuing&hellip;
              </span>
            </button>
          </div>
        </div>
      @endforeach
    </div>
  </div>

  <!-- Run history -->
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-4 bg-slate-800 border-b border-slate-700">
      <h3 class="text-base font-semibold text-white">
        <i class="fas fa-history mr-2 text-slate-300"></i>
        Run History
      </h3>
    </div>

    @if($history->count() > 0)
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Command</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arguments</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Triggered By</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Started</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            @foreach($history as $run)
              <tr wire:key="run-{{ $run->id }}">
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-500">{{ $run->id }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $run->label() }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-500">
                  @if(!empty($run->arguments))
                    {{ collect($run->arguments)->map(fn($value, $option) => $value === true ? "--{$option}" : "--{$option}={$value}")->implode(' ') }}
                  @else
                    &mdash;
                  @endif
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                  {{ $run->user?->full_name ?? 'System' }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  @switch($run->status)
                    @case(\App\Models\EmpodatSuspect\EmpodatSuspectCommandRun::STATUS_SUCCESS)
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Success</span>
                      @break
                    @case(\App\Models\EmpodatSuspect\EmpodatSuspectCommandRun::STATUS_FAILED)
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                      @break
                    @case(\App\Models\EmpodatSuspect\EmpodatSuspectCommandRun::STATUS_RUNNING)
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-lime-100 text-lime-800">
                        <i class="fas fa-circle-notch fa-spin mr-1"></i>Running
                      </span>
                      @break
                    @case(\App\Models\EmpodatSuspect\EmpodatSuspectCommandRun::STATUS_QUEUED)
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800">Queued</span>
                      @break
                    @case(\App\Models\EmpodatSuspect\EmpodatSuspectCommandRun::STATUS_REFUSED)
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-zinc-200 text-zinc-800" title="{{ $run->output }}">Refused</span>
                      @break
                    @default
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($run->status) }}</span>
                  @endswitch
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                  {{ $run->started_at?->timezone('Europe/Prague')->format('Y-m-d H:i') ?? '—' }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                  {{ $run->duration_ms !== null ? number_format($run->duration_ms / 1000, 1, '.', ' ').' s' : '—' }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="p-4">
        {{ $history->links() }}
      </div>
    @else
      <div class="text-center py-8">
        <p class="text-gray-500 text-sm">No commands have been run yet.</p>
      </div>
    @endif
  </div>

</div>
