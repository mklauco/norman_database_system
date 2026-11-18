<div>
    <div class="mb-4">
        <div class="flex items-center justify-between gap-2 mb-2">
            <h3 class="text-sm font-medium text-gray-700">
                {{ $multiple ? 'Selected substances (several allowed)' : 'Selected substance (one only)' }}
            </h3>
            @if ($selected->count() > 0)
                <button type="button" wire:click="clearSelection" class="text-xs text-lime-800 underline hover:text-red-600">
                    Clear selection
                </button>
            @endif
        </div>

        @if ($selected->count() > 0)
            <div class="flex flex-wrap gap-2">
                @foreach ($selected as $substance)
                    <div wire:key="sel-{{ $substance->id }}" class="flex items-center px-4 py-2 rounded-lg bg-lime-50 border border-lime-200 shadow-xs group hover:bg-lime-100 transition duration-150 ease-in-out">
                        <input type="hidden" name="substances[]" value="{{ $substance->id }}">

                        <div class="flex flex-col">
                            <span class="font-medium text-lime-800">{{ $substance->name }}</span>
                            <span class="text-xs text-lime-700">
                                CAS: {{ $substance->cas_number ?? 'N/A' }} |
                                NS<span class="font-mono">{{ $substance->code }}</span>
                                @if ($hasRecords)
                                    <span class="ml-1 inline-block rounded-full bg-lime-200 px-2 text-lime-900">{{ number_format((int) $substance->records, 0, '.', ' ') }} records</span>
                                @endif
                            </span>
                        </div>

                        <button type="button" wire:click="removeSubstance({{ $substance->id }})" class="ml-3 text-lime-800 hover:text-red-600 focus:outline-hidden group-hover:text-red-600 transition duration-150 ease-in-out" title="Remove">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center text-gray-500">
                {{ $multiple ? 'No substances selected.' : 'No substance selected.' }}
            </div>
        @endif
    </div>

    <input type="text" wire:model.live.debounce.300ms="search" x-on:keydown.enter.prevent autocomplete="off"
        class="w-full px-4 py-2 border border-gray-300 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-lime-500 focus:border-transparent transition duration-200 ease-in-out"
        placeholder="Search for substances...">

    <div class="mt-4">
        <span class="text-gray-700">Search by:</span>
        <div class="mt-2 flex flex-wrap gap-x-6 gap-y-2">
            @foreach (['cas_number' => 'CAS', 'name' => 'Name', 'stdinchikey' => 'StdInChIKey', 'code' => 'NORMAN SusDat ID'] as $type => $label)
                <label class="inline-flex items-center">
                    <input wire:model.live="searchType" x-on:keydown.enter.prevent type="radio" value="{{ $type }}" class="text-lime-600 focus:ring-lime-500">
                    <span class="ml-2">{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="mt-3 border border-gray-200 rounded-md bg-white">
        @if (! $searchable)
            <div class="p-4 text-center text-gray-500">Type at least 3 characters to search</div>
        @elseif ($results->isEmpty())
            <div class="p-4 text-center text-gray-500">No substances found matching "{{ $term }}"</div>
        @else
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-3 py-2 bg-lime-50 border-b border-lime-200 text-sm text-lime-900">
                <span>
                    Showing {{ number_format($results->count(), 0, '.', ' ') }} of {{ number_format($total, 0, '.', ' ') }} matches
                    · click a row to {{ $multiple ? 'add or remove it' : 'select it' }}
                </span>
                @if ($total > $inlineLimit)
                    <button type="button" wire:click="openDialog" class="rounded-md border border-lime-600 bg-lime-600 px-3 py-1 text-white hover:bg-lime-700">
                        Show all {{ number_format($total, 0, '.', ' ') }} matches
                    </button>
                @endif
            </div>

            <div class="max-h-80 overflow-y-auto">
                @foreach ($results as $result)
                    @php
                        $isSelected = in_array((int) $result->id, $selectedIds, true);
                    @endphp
                    <div wire:key="res-{{ $result->id }}" wire:click="pick({{ $result->id }})"
                        class="flex items-start gap-3 p-2 cursor-pointer border-b border-gray-100 last:border-b-0 {{ $isSelected ? 'bg-lime-100' : 'bg-white hover:bg-lime-50' }}">
                        <span class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-{{ $multiple ? 'sm' : 'full' }} border {{ $isSelected ? 'border-lime-600 bg-lime-600 text-white' : 'border-gray-300' }}">
                            @if ($isSelected)
                                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd" /></svg>
                            @endif
                        </span>
                        <div class="grow min-w-0">
                            <div class="font-medium">{{ $result->name }}</div>
                            <div class="text-sm text-gray-500 break-words">
                                CAS: {{ $result->cas_number ?? 'N/A' }}
                                | InChIKey: {{ $result->stdinchikey ?? 'N/A' }}
                                | NORMAN ID: NS<span class="font-mono">{{ $result->code }}</span>
                            </div>
                        </div>
                        @if ($hasRecords)
                            <span class="shrink-0 rounded-full bg-lime-200 px-2 text-sm text-lime-900">{{ number_format((int) $result->records, 0, '.', ' ') }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($dialogOpen)
        <div x-data x-on:keydown.escape.window="$wire.closeDialog()" class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-6" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-gray-900/60" wire:click="closeDialog"></div>

            <div class="relative flex max-h-full w-full max-w-5xl flex-col rounded-lg bg-white shadow-xl">
                <div class="flex items-start justify-between gap-3 border-b border-lime-200 bg-lime-50 px-4 py-3 rounded-t-lg">
                    <div>
                        <h2 class="text-lg font-semibold text-lime-900">
                            All {{ number_format($total, 0, '.', ' ') }} matches for "{{ $term }}"
                        </h2>
                        <p class="text-sm text-lime-800">
                            {{ number_format(count($tempIds), 0, '.', ' ') }} selected
                            · {{ $multiple ? 'tick several substances' : 'choose one substance' }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeDialog" class="text-lime-800 hover:text-red-600" title="Cancel">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.3 4.3a1 1 0 011.4 0L10 8.6l4.3-4.3a1 1 0 111.4 1.4L11.4 10l4.3 4.3a1 1 0 01-1.4 1.4L10 11.4l-4.3 4.3a1 1 0 01-1.4-1.4L8.6 10 4.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <div class="min-h-0 grow overflow-auto">
                    <table class="min-w-full text-sm">
                        <thead class="sticky top-0 bg-lime-100 text-left text-lime-900">
                            <tr>
                                <th class="w-10 px-3 py-2"></th>
                                @php
                                    $columns = ['name' => 'Name', 'cas_number' => 'CAS', 'code' => 'NS code'];
                                    if ($hasRecords) {
                                        $columns['records'] = 'Records';
                                    }
                                @endphp
                                @foreach ($columns as $key => $label)
                                    <th class="px-3 py-2 whitespace-nowrap">
                                        <button type="button" wire:click="sortBy('{{ $key }}')" class="font-semibold hover:underline">
                                            {{ $label }}
                                            @if ($dialogSort === $key)
                                                {{ $dialogDir === 'asc' ? '▲' : '▼' }}
                                            @endif
                                        </button>
                                    </th>
                                @endforeach
                                <th class="hidden px-3 py-2 md:table-cell">InChIKey</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dialogRows as $row)
                                @php
                                    $checked = in_array((int) $row->id, $tempIds, true);
                                @endphp
                                <tr wire:key="dlg-{{ $row->id }}" wire:click="toggleTemp({{ $row->id }})"
                                    class="cursor-pointer border-b border-gray-100 {{ $checked ? 'bg-lime-100' : 'hover:bg-lime-50' }}">
                                    <td class="px-3 py-2">
                                        <input type="{{ $multiple ? 'checkbox' : 'radio' }}" @checked($checked) tabindex="-1"
                                            class="pointer-events-none text-lime-600 focus:ring-lime-500 {{ $multiple ? 'rounded-sm' : '' }}">
                                    </td>
                                    <td class="px-3 py-2 font-medium">{{ $row->name }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $row->cas_number ?? 'N/A' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">NS<span class="font-mono">{{ $row->code }}</span></td>
                                    @if ($hasRecords)
                                        <td class="px-3 py-2 whitespace-nowrap">{{ number_format((int) $row->records, 0, '.', ' ') }}</td>
                                    @endif
                                    <td class="hidden px-3 py-2 text-gray-500 md:table-cell">{{ $row->stdinchikey ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-lime-200 bg-lime-50 px-4 py-3 rounded-b-lg sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2 text-sm text-lime-900">
                        <button type="button" wire:click="previousPage" @disabled($dialogPage <= 1) class="rounded-md border border-lime-600 px-3 py-1 hover:bg-lime-100 disabled:opacity-40">Previous</button>
                        <span>Page {{ number_format($dialogPage, 0, '.', ' ') }} of {{ number_format($lastPage, 0, '.', ' ') }}</span>
                        <button type="button" wire:click="nextPage" @disabled($dialogPage >= $lastPage) class="rounded-md border border-lime-600 px-3 py-1 hover:bg-lime-100 disabled:opacity-40">Next</button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="closeDialog" class="rounded-md border border-gray-300 bg-white px-4 py-1.5 text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="button" wire:click="applyTemp" class="rounded-md border border-lime-600 bg-lime-600 px-4 py-1.5 text-white hover:bg-lime-700">Use selection</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
