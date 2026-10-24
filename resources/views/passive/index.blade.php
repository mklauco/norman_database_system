<x-app-layout>
  <x-slot name="header">
    @include('passive.header')
  </x-slot>


  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          {{-- main div --}}

          {{-- Action bar: Refine Search | Download CSV --}}
          <div class="flex items-start justify-between">
            {{-- Left: Refine Search --}}
            <a href="{{ route('passive.search.filter', [
                'countrySearch' => $request->input('countrySearch'),
                'matrixSearch' => $request->input('matrixSearch'),
                'year_to' => $request->input('year_to'),
                'year_from' => $request->input('year_from'),
                'substances' => $request->input('substances'),
                'query_log_id' => $query_log_id,
            ]) }}" class="btn-submit"><i class="fas fa-filter mr-1"></i>Refine Search</a>

            {{-- Right: Download CSV --}}
            <div class="flex flex-col items-end">
              @auth
                <a href="{{ route('passive.search.download', ['query_log_id' => $query_log_id]) }}"
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
            @if (isset($request->displayOption) && $request->displayOption == 1)
              {{-- Simple output - use Livewire query counter --}}
              @livewire('backend.query-counter', [
                  'queryId' => $query_log_id ?? null,
                  'resultsCount' => $resultsObjectsCount,
                  'count_again' => request()->has('page') ? false : true,
              ])
            @else
              {{-- Advanced output with pagination --}}
              <div class="flex items-center">
                <span>Number of matched records:</span>
                <span class="ml-1 mr-1 font-bold">{{ number_format($resultsObjects->total(), 0, '.', ' ') }}</span>
                @if ($resultsObjectsCount > 0)
                  <span>
                    of {{ number_format($resultsObjectsCount, 0, '.', ' ') }}
                    @if (is_numeric($resultsObjects->total()))
                      @if (($resultsObjects->total() / $resultsObjectsCount) * 100 < 0.01)
                        (&le; 0.01%)
                      @else
                        ({{ number_format(($resultsObjects->total() / $resultsObjectsCount) * 100, 2, '.', ' ') }}%)
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
                <th>Substance</th>
                {{-- <th>Country</th> --}}
                <th>Matrix</th>
                <th>Organisation</th>
                <th>Date of sampling</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($resultsObjects as $e)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                  <td class="p-1 text-center">
                    <div class="flex items-center justify-center space-x-2">
                      <a href="{{ route('passive.search.show', $e->id) }}"
                         target="_blank"
                         class="text-teal-600 hover:text-teal-800 transition-colors"
                         title="View full record details">
                        <i class="fas fa-search"></i>
                      </a>
                      <a href="{{ route('passive.search.show', $e->id) }}" target="_blank" class="font-mono text-teal-800 hover:text-teal-600 hover:underline">
                        {!! number_format($e->id, 0, '', '&nbsp;') !!}
                      </a>
                    </div>
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->sus_id)
                      {{ $e->substance->name ?? 'N/A' }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  {{-- <td class="p-1 text-center">
                    @if ($e->country_id)
                      {{ $e->country->name ?? $e->country_id }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td> --}}
                  <td class="p-1 text-center">
                    @if ($e->matrix_id)
                      {{ $e->matrix->name }}
                    @elseif($e->matrix_other)
                      {{ $e->matrix_other }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->org_id)
                      {{ $e->organisation->org_name ?? 'Org ID: ' . $e->org_id }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->date_sampling_start_year)
                      {{ $e->date_sampling_start_year }}-{{ sprintf('%02d', $e->date_sampling_start_month) }}-{{ sprintf('%02d', $e->date_sampling_start_day) }}
                    @else
                      <span class="text-gray-400">N/A</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>

          @if (isset($request->displayOption) && $request->displayOption == 1)
            {{-- use simple output --}}
            <div class="flex justify-center space-x-4 mt-4">
              @if ($resultsObjects->onFirstPage())
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded-sm cursor-not-allowed">
                  Previous
                </span>
              @else
                <a href="{{ $resultsObjects->previousPageUrl() }}"
                  class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded-sm hover:bg-stone-600">
                  Previous
                </a>
              @endif

              @if ($resultsObjects->hasMorePages())
                <a href="{{ $resultsObjects->nextPageUrl() }}"
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
            {{ $resultsObjects->links('pagination::tailwind') }}
          @endif



          <!-- The Modal (hidden by default) -->




          {{-- end of main div --}}
        </div>
      </div>
    </div>
  </div>

</x-app-layout>
