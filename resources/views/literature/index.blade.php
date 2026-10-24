<x-app-layout>
  <x-slot name="header">
    @include('literature.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          {{-- Action bar: Refine Search | Download CSV --}}
          <div class="flex items-start justify-between">
            {{-- Left: Refine Search --}}
            <form method="GET" action="{{ route('literature.search.filter') }}" class="inline">
              @foreach(($countrySearch ?? []) as $country)
                <input type="hidden" name="countrySearch[]" value="{{ $country }}">
              @endforeach
              @foreach(($speciesSearch ?? []) as $species)
                <input type="hidden" name="speciesSearch[]" value="{{ $species }}">
              @endforeach
              @foreach(($classSearch ?? []) as $class)
                <input type="hidden" name="classSearch[]" value="{{ $class }}">
              @endforeach
              @foreach(($tissueSearch ?? []) as $tissue)
                <input type="hidden" name="tissueSearch[]" value="{{ $tissue }}">
              @endforeach
              @foreach(($matrixSearch ?? []) as $matrix)
                <input type="hidden" name="matrixSearch[]" value="{{ $matrix }}">
              @endforeach
              @foreach(($typeOfNumericQuantitySearch ?? []) as $quantity)
                <input type="hidden" name="typeOfNumericQuantitySearch[]" value="{{ $quantity }}">
              @endforeach
              @foreach(($categoriesSearch ?? []) as $category)
                <input type="hidden" name="categoriesSearch[]" value="{{ $category }}">
              @endforeach
              @foreach(($fileSearch ?? []) as $file)
                <input type="hidden" name="fileSearch[]" value="{{ $file }}">
              @endforeach
              @foreach(($projectSearch ?? []) as $project)
                <input type="hidden" name="projectSearch[]" value="{{ $project }}">
              @endforeach
              @foreach(($substances ?? []) as $substance)
                <input type="hidden" name="substances[]" value="{{ $substance }}">
              @endforeach
              <input type="hidden" name="displayOption" value="{{ $displayOption }}">
              <input type="hidden" name="query_log_id" value="{{ $query_log_id }}">
              <button type="submit" class="btn-submit"><i class="fas fa-filter mr-1"></i>Refine Search</button>
            </form>

            {{-- Right: Download CSV --}}
            <div class="flex flex-col items-end">
              @auth
                <a href="{{ route('literature.search.download', ['query_log_id' => $query_log_id]) }}"
                  class="btn-download"><i class="fas fa-file-csv mr-1"></i>Download CSV</a>
              @else
                <button type="button" class="btn-download" disabled>
                  <i class="fas fa-file-csv mr-1"></i>Download CSV
                </button>
                <span class="text-xs text-gray-400 mt-1">Available for logged in users only</span>
              @endauth
            </div>
          </div>

          {{-- Search parameters --}}
          <div class="flex items-center">
            @if (!empty($searchParameters))
              <span>Search parameters:</span>
              <span class="ml-1 font-bold">
                @foreach ($searchParameters as $key => $value)
                  @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
                    @foreach ($value as $item)
                      {{ $item }}@if (!$loop->last), @endif
                    @endforeach
                  @else
                    {{ $value }}
                  @endif
                  @if (!$loop->last); @endif
                @endforeach
              </span>
            @else
              <span>Search parameters:</span>
              <span class="italic text-gray-400 ml-1">no parameters have been chosen</span>
            @endif
          </div>

          {{-- Record count --}}
          <div class="mb-2">
            @if ($displayOption == 1)
              {{-- Simple output --}}
              <div class="flex items-center">
                <span>Number of matched records:</span>
                <span class="ml-1 mr-1 font-bold">{{ number_format($literatureMatchedCount, 0, '.', ' ') }}</span>
                @if ($literatureObjectsCount > 0)
                  <span>
                    of {{ number_format($literatureObjectsCount, 0, '.', ' ') }}
                    @if (is_numeric($literatureMatchedCount))
                      @if (($literatureMatchedCount / $literatureObjectsCount) * 100 < 0.01)
                        (&le; 0.01%)
                      @else
                        ({{ number_format(($literatureMatchedCount / $literatureObjectsCount) * 100, 2, '.', ' ') }}%)
                      @endif
                    @endif
                  </span>
                @endif
              </div>
            @else
              {{-- Advanced output with pagination --}}
              <div class="flex items-center">
                <span>Number of matched records:</span>
                <span class="ml-1 mr-1 font-bold">{{ number_format($literatureRecords->total(), 0, '.', ' ') }}</span>
                @if ($literatureObjectsCount > 0)
                  <span>
                    of {{ number_format($literatureObjectsCount, 0, '.', ' ') }}
                    @if (is_numeric($literatureRecords->total()))
                      @if (($literatureRecords->total() / $literatureObjectsCount) * 100 < 0.01)
                        (&le; 0.01%)
                      @else
                        ({{ number_format(($literatureRecords->total() / $literatureObjectsCount) * 100, 2, '.', ' ') }}%)
                      @endif
                    @endif
                  </span>
                @endif
              </div>
            @endif
          </div>

          
          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
                <th>ID</th>
                <th>Norman SUS ID</th>
                <th>Chemical Name</th>
                <th>Reported<br>Concentration</th>
                <th>Concentration<br>(ng/g ww)</th>
                <th>Class</th>
                <th>Species</th>
                <th>Tissue</th>
                <th>Sampling Start</th>
                <th>Country</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($literatureRecords as $record)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                  <td class="p-1 text-center">
                    <div class="flex items-center justify-center space-x-2">
                      <a href="{{ route('literature.search.show', $record->id) }}"
                         target="_blank"
                         class="text-teal-600 hover:text-teal-800 transition-colors"
                         title="View full record details">
                        <i class="fas fa-search text-sm"></i>
                      </a>
                      <a href="{{ route('literature.search.show', $record->id) }}" class="font-mono text-teal-800 hover:text-teal-600 hover:underline">
                        {!! number_format($record->id, 0, '', '&nbsp;') !!}
                      </a>
                    </div>
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->substance && $record->substance->code)
                      <a href="{{ route('substances.show', $record->substance->id) }}" class="font-mono text-teal-800 hover:text-teal-600 hover:underline">
                        NS{{ $record->substance->code }}
                      </a>
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->substance && $record->substance->name)
                      <div class="max-w-xs truncate" title="{{ $record->substance->name }}">
                        {{ $record->substance->name }}
                      </div>
                    @elseif ($record->chemical_name)
                      <div class="max-w-xs truncate text-gray-600 italic" title="Original name: {{ $record->chemical_name }}">
                        original name: {{ $record->chemical_name }}
                      </div>
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->reported_concentration !== null && $record->concentrationUnit)
                      {{ $record->reported_concentration }} {{ $record->concentrationUnit->abbreviation ?? $record->concentrationUnit->name ?? '' }}
                    @elseif ($record->reported_concentration !== null)
                      {{ $record->reported_concentration }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->ww_conc_ng !== null)
                      {{ number_format($record->ww_conc_ng, 4) }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->species && $record->species->class)
                      {{ $record->species->class }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->species)
                      @php
                        $speciesDisplay = $record->species->name ?? '';
                        if ($record->species->name_latin) {
                          $speciesDisplay .= ' (' . $record->species->name_latin . ')';
                        }
                      @endphp
                      <div class="max-w-xs truncate" title="{{ $speciesDisplay }}">
                        {{ $speciesDisplay }}
                      </div>
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->tissue && $record->tissue->name)
                      {{ $record->tissue->name }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @php
                      $startDate = [];
                      if ($record->start_of_sampling_year) $startDate[] = $record->start_of_sampling_year;
                      if ($record->start_of_sampling_month) $startDate[] = $record->start_of_sampling_month;
                      if ($record->start_of_sampling_day) $startDate[] = $record->start_of_sampling_day;
                      echo !empty($startDate) ? implode('-', $startDate) : '<span class="text-gray-400">N/A</span>';
                    @endphp
                  </td>
                  <td class="p-1 text-center">
                    @if ($record->country && $record->country->name)
                      {{ $record->country->name }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="10" class="p-4 text-center text-gray-500">
                    No records found. Please adjust your search criteria.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>

          @if ($displayOption == 1)
            {{-- use simple output --}}
            <div class="flex justify-center space-x-4 mt-4">
              @if ($literatureRecords->onFirstPage())
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded-sm cursor-not-allowed">
                  Previous
                </span>
              @else
                <a href="{{ $literatureRecords->previousPageUrl() }}"
                  class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded-sm hover:bg-stone-600">
                  Previous
                </a>
              @endif

              @if ($literatureRecords->hasMorePages())
                <a href="{{ $literatureRecords->nextPageUrl() }}"
                  class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded-sm hover:bg-stone-600">
                  Next
                </a>
              @else
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded-sm cursor-not-allowed">
                  Next
                </span>
              @endif
            </div>
          @else
            {{-- use advanced output --}}
            {{ $literatureRecords->links('pagination::tailwind') }}
          @endif

        </div>
      </div>
    </div>
  </div>

</x-app-layout>

