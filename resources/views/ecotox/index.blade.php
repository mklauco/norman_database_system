<x-app-layout>
  <x-slot name="header">
    @include('ecotox.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <!-- Initialize Alpine component with clean x-data -->
        <div class="p-6 text-gray-900" x-data="ecotoxModal()">

          <a
            href="{{ route('ecotox.data.search.filter', [
                'substances' => $request->input('substances'),
                'query_log_id' => $query_log_id,
            ]) }}">
            <button type="submit" class="btn-submit">Refine Search</button>
          </a>

          <div class="text-gray-600 flex border-l-2 border-white">
            @if (isset($request->displayOption) && $request->displayOption == 1)
              {{-- Simple output --}}
              @livewire('backend.query-counter', [
                  'queryId' => $query_log_id ?? null,
                  'resultsCount' => $resultsObjectsCount,
                  'count_again' => request()->has('page') ? false : true,
              ])
            @else
              {{-- Advanced output with better styling --}}
              <div class="flex flex-wrap items-center bg-gray-50 p-3 rounded-lg shadow-sm border border-gray-200">
                <div class="flex items-center mr-4">
                  <span class="text-gray-700">Number of matched records:</span>
                  <span class="font-bold text-lg ml-2 text-sky-700">
                    {{ number_format($resultsObjects->total(), 0, '.', ' ') }}
                  </span>
                </div>

                <div class="flex items-center">
                  <span class="text-gray-700">of</span>
                  <span class="font-medium ml-2 text-gray-800">
                    {{ number_format($resultsObjectsCount, 0, '.', ' ') }}
                  </span>

                  @if (is_numeric($resultsObjects->total()) && $resultsObjectsCount > 0)
                    <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                      @if (($resultsObjects->total() / $resultsObjectsCount) * 100 < 0.01)
                        &le; 0.01% of total
                      @else
                        {{ number_format(($resultsObjects->total() / $resultsObjectsCount) * 100, 2, '.', ' ') }}% of
                        total
                      @endif
                    </span>
                  @endif
                </div>
              </div>
            @endif

            @auth
              {{-- <div class="py-2 px-2"><a href="{{ route('ecotox.search.download', ['query_log_id' => $query_log_id]) }}" class="btn-download">Download</a></div> --}}
            @else
              <div class="py-2 px-2 text-gray-400">Downloads are available for registered users only</div>
            @endauth
          </div>

          <div class="text-gray-600 flex border-l-2 border-white">
            Search parameters:&nbsp;<span class="font-semibold">
              @foreach ($searchParameters as $key => $value)
                @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
                  @foreach ($value as $item)
                    {{ $item }}@if (!$loop->last)
                      ,
                    @endif
                  @endforeach
                @else
                  {{ $value }}
                  @endif @if (!$loop->last)
                    ;
                  @endif
                @endforeach
            </span>
          </div>

          {{-- Tabs for different matrix-habitat/acute-chronic combinations --}}
          <div class="mt-4">
            <div class="border-b border-gray-200">
              <nav class="-mb-px flex space-x-8">
                <button
                  class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-sky-500 text-sky-600"
                  data-tab="all" data-filter-matrix="" data-filter-acute="">
                  All Results
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-sky-100 text-sky-800 rounded-full">
                    {{ $resultsObjects->total() }}
                  </span>
                </button>

                <button
                  class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                  data-tab="freshwater-acute" data-filter-matrix="freshwater" data-filter-acute="acute">
                  Freshwater - Acute
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $resultsObjects->where('matrix_habitat', 'freshwater')->where('acute_or_chronic', 'acute')->count() }}
                  </span>
                </button>

                <button
                  class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                  data-tab="freshwater-chronic" data-filter-matrix="freshwater" data-filter-acute="chronic">
                  Freshwater - Chronic
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $resultsObjects->where('matrix_habitat', 'freshwater')->where('acute_or_chronic', 'chronic')->count() }}
                  </span>
                </button>

                <button
                  class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                  data-tab="marine-acute" data-filter-matrix="marine water" data-filter-acute="acute">
                  Marine Water - Acute
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $resultsObjects->where('matrix_habitat', 'marine water')->where('acute_or_chronic', 'acute')->count() }}
                  </span>
                </button>

                <button
                  class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                  data-tab="marine-chronic" data-filter-matrix="marine water" data-filter-acute="chronic">
                  Marine Water - Chronic
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $resultsObjects->where('matrix_habitat', 'marine water')->where('acute_or_chronic', 'chronic')->count() }}
                  </span>
                </button>
              </nav>
            </div>
          </div>

          <!-- Results Table -->
          {{-- Single Unified Table --}}
          @php
            // Sortable column headers (#41). Clicking a header toggles the
            // direction; every other query parameter is preserved.
            $sortLink = function (string $column) use ($sortColumn, $sortDirection) {
                return request()->fullUrlWithQuery([
                    'sort' => $column,
                    'direction' => ($sortColumn === $column && $sortDirection === 'asc') ? 'desc' : 'asc',
                ]);
            };
          @endphp
          <div class="mt-4">
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th>
                    <a href="{{ $sortLink('ecotox_id') }}" class="inline-flex items-center gap-1 hover:underline">
                      Biotest ID
                      @if ($sortColumn === 'ecotox_id')
                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                      @else
                        <i class="fas fa-sort text-gray-400"></i>
                      @endif
                    </a>
                  </th>
                  <th>Taxonomic group</th>
                  <th>
                    <a href="{{ $sortLink('scientific_name') }}" class="inline-flex items-center gap-1 hover:underline">
                      Scientific name
                      @if ($sortColumn === 'scientific_name')
                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                      @else
                        <i class="fas fa-sort text-gray-400"></i>
                      @endif
                    </a>
                  </th>
                  <th>Endpoint</th>
                  <th>Duration</th>
                  <th>Effect measurement</th>
                  <th>Test type</th>
                  <th>Standard test</th>
                  <th>Effect based on</th>
                  <th>pH</th>
                  <th>Exposure regime</th>
                  <th>Purity [%]</th>
                  <th></th>
                  <th>
                    <a href="{{ $sortLink('concentration_value') }}" class="inline-flex items-center gap-1 hover:underline">
                      Effect value [µg/L]
                      @if ($sortColumn === 'concentration_value')
                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                      @else
                        <i class="fas fa-sort text-gray-400"></i>
                      @endif
                    </a>
                  </th>
                  <th>Measured or nominal</th>
                  <th>Reference</th>
                  @if (auth()->check() &&
                          (auth()->user()->hasRole('super_admin') ||
                              auth()->user()->hasRole('admin') ||
                              auth()->user()->hasRole('ecotox')))
                    <th>Reliability score</th>
                    <th>Use of study</th>
                    <th>Editor</th>
                  @endif
                </tr>
              </thead>
              <tbody id="ecotox-table-body">
                @foreach ($resultsObjects as $e)
                  <tr class="ecotox-row @if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif"
                    data-matrix="{{ $e->matrix_habitat }}" data-acute="{{ $e->acute_or_chronic }}">
                    <td class="p-1 text-center">
                      @if (auth()->check() &&
                              (auth()->user()->hasRole('super_admin') ||
                                  auth()->user()->hasRole('admin') ||
                                  auth()->user()->hasRole('ecotox')))
                        <div class="flex space-x-2">
                          <a href="#" class="link-lime-text" title="Click to view details"
                            x-on:click.prevent="openModal('{{ $e->ecotox_id }}')">
                            {{-- <i class="fas fa-search"></i> --}}
                            {{ $e->ecotox_id ?? 'N/A' }}
                          </a>
                          <a href="{{ route('ecotox.data.form', $e->ecotox_id) }}" 
                             class="link-lime-text text-xs" 
                             title="View full form">
                            <i class="fas fa-external-link-alt"></i>
                          </a>
                        </div>
                      @else
                        {{ $e->ecotox_id ?? 'N/A' }}
                      @endif
                    </td>
                    <td class="p-1 text-center">{{ $e->taxonomic_group ?? 'N/A' }}</td>
                    <td class="p-1">
                      <div class="italic">{{ $e->scientific_name ?? 'N/A' }}</div>
                      @if ($e->common_name)
                        <div class="text-xs text-gray-500">{{ $e->common_name }}</div>
                      @endif
                    </td>
                    <td class="p-1 text-center">{{ $e->endpoint ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->duration ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->effect_measurement ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->test_type ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->standard_test ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->effect ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->ph ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->exposure_regime ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->purity ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $e->concentration_qualifier ?? '' }}</td>
                    <td class="p-1 text-center">
                      @if ($e->concentration_value)
                        <div> {{ number_format($e->concentration_value, 4) }}
                        </div>
                        @if ($e->unit_concentration)
                          <div class="text-xs text-gray-500">{{ $e->unit_concentration }}</div>
                        @endif
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    <td class="p-1 text-center">{{ $e->measured_or_nominal ?? 'N/A' }}</td>
                    <td class="p-1 text-center">
                      @if ($e->bibliographic_source)
                        <div>{{ $e->bibliographic_source }}</div>
                        @if ($e->year_publication)
                          <div class="text-xs text-gray-500">{{ $e->year_publication }}</div>
                        @endif
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    @if (auth()->check() &&
                            (auth()->user()->hasRole('super_admin') ||
                                auth()->user()->hasRole('admin') ||
                                auth()->user()->hasRole('ecotox')))
                      <td class="p-1 text-center">{{ $e->reliability_study ?? 'N/A' }}</td>
                      <td class="p-1 text-center">{{ $e->use_study ?? 'N/A' }}</td>
                      <td class="p-1 text-center">
                        @if ($e->editorUser)
                          {{ $e->editorUser->name ?? 'N/A' }}
                        @else
                          N/A
                        @endif
                      </td>
                    @endif
                  </tr>
                @endforeach
              </tbody>
            </table>

            {{-- No results message (hidden by default) --}}
            <div id="no-results-message" class="hidden text-center py-8 text-gray-500">
              No results found for the selected filter.
            </div>
          </div>

          @if (isset($request->displayOption) && $request->displayOption == 1)
            {{-- use simple output --}}
            <div class="flex justify-center space-x-4 mt-4">
              @if ($resultsObjects->onFirstPage())
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
                  Previous
                </span>
              @else
                <a href="{{ $resultsObjects->previousPageUrl() }}"
                  class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
                  Previous
                </a>
              @endif

              @if ($resultsObjects->hasMorePages())
                <a href="{{ $resultsObjects->nextPageUrl() }}"
                  class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
                  Next
                </a>
              @else
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
                  Next
                </span>
              @endif
            </div>
          @else
            {{-- use advanced output --}}
            {{ $resultsObjects->links('pagination::tailwind') }}
          @endif

          <!-- Include the modal component -->
          <x-ecotox-modal :auth-check="auth()->check()" :is-super-admin="auth()->check() && auth()->user()->hasRole('super_admin')" />

        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tableRows = document.querySelectorAll('.ecotox-row');
        const noResultsMessage = document.getElementById('no-results-message');
        const tableBody = document.getElementById('ecotox-table-body');

        // Function to filter table rows based on selected tab
        function filterTable(matrixFilter, acuteFilter) {
          let visibleCount = 0;

          tableRows.forEach(row => {
            const rowMatrix = row.dataset.matrix;
            const rowAcute = row.dataset.acute;

            // Show row if it matches the filters (or if "All" is selected)
            if (matrixFilter === '' && acuteFilter === '') {
              // Show all rows
              row.style.display = '';
              visibleCount++;
            } else if (rowMatrix === matrixFilter && rowAcute === acuteFilter) {
              row.style.display = '';
              visibleCount++;
            } else {
              row.style.display = 'none';
            }
          });

          // Show/hide no results message
          if (visibleCount === 0) {
            noResultsMessage.classList.remove('hidden');
            tableBody.parentElement.style.display = 'none';
          } else {
            noResultsMessage.classList.add('hidden');
            tableBody.parentElement.style.display = '';
          }
        }

        // Function to activate tab
        function activateTab(button) {
          const tabId = button.dataset.tab;
          const matrixFilter = button.dataset.filterMatrix;
          const acuteFilter = button.dataset.filterAcute;

          // Update button styles
          tabButtons.forEach(btn => {
            btn.classList.remove('border-sky-500', 'text-sky-600');
            btn.classList.add('border-transparent', 'text-gray-500');
          });

          button.classList.remove('border-transparent', 'text-gray-500');
          button.classList.add('border-sky-500', 'text-sky-600');

          // Filter table rows
          filterTable(matrixFilter, acuteFilter);

          // Save active tab to localStorage
          localStorage.setItem('activeEcotoxTab', tabId);
        }

        // Add click event to tab buttons
        tabButtons.forEach(button => {
          button.addEventListener('click', function() {
            activateTab(this);
          });
        });

        // Check for saved tab in localStorage and activate it
        const savedTab = localStorage.getItem('activeEcotoxTab');
        if (savedTab) {
          const savedButton = document.querySelector(`[data-tab="${savedTab}"]`);
          if (savedButton) {
            activateTab(savedButton);
          }
        }
      });
    </script>
  @endpush
</x-app-layout>
