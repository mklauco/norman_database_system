<x-app-layout>
  <x-slot name="header">
    @include('ecotox.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <a href="{{ route('ecotox.quality.search.filter', [
              'substances' => $request->input('substances'),
              'query_log_id' => $query_log_id,
          ]) }}">
            <button type="submit" class="btn-submit">Refine Search</button>
          </a>

          <div class="text-gray-600 flex border-l-2 border-white">
            @if (isset($request->displayOption) && $request->displayOption == 1)
              {{-- Simple output --}}
              <div class="py-2 px-2">
                Number of matched records: <span class="font-bold">{{ number_format($resultsObjects->total(), 0, '.', ' ') }}</span>
              </div>
            @else
              {{-- Advanced output with better styling --}}
              <div class="flex flex-wrap items-center bg-gray-50 p-3 rounded-lg shadow-xs border border-gray-200">
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
                        {{ number_format(($resultsObjects->total() / $resultsObjectsCount) * 100, 2, '.', ' ') }}% of total
                      @endif
                    </span>
                  @endif
                </div>
              </div>
            @endif

            @auth
              {{-- Download functionality can be added here later --}}
            @else
              <div class="py-2 px-2 text-gray-400">Downloads are available for registered users only</div>
            @endauth
          </div>

          <div class="text-gray-600 flex border-l-2 border-white">
            Search parameters:&nbsp;<span class="font-semibold">
              @foreach ($searchParameters as $key => $value)
                @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
                  @foreach ($value as $item)
                    {{ $item }}@if (!$loop->last), @endif
                  @endforeach
                @else
                  {{ $value }}
                @endif @if (!$loop->last); @endif
              @endforeach
            </span>
          </div>

          {{-- Tabs for different matrix-habitat categories --}}
          @if($matrixHabitatCounts->count() > 0)
            <div class="mt-4">
              <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                  <button
                    class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-sky-500 text-sky-600"
                    data-tab="all" data-filter-matrix="">
                    All Results
                    <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-sky-100 text-sky-800 rounded-full">
                      {{ $resultsObjects->total() }}
                    </span>
                  </button>

                  @foreach($matrixHabitatCounts as $habitat)
                    <button
                      class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                      data-tab="{{ $habitat->matrix_habitat }}" data-filter-matrix="{{ $habitat->matrix_habitat }}">
                      {{ ucfirst($habitat->matrix_habitat ?? 'Unknown') }}
                      <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                        {{ $habitat->count }}
                      </span>
                    </button>
                  @endforeach
                </nav>
              </div>
            </div>
          @endif

          {{-- Matrix Habitat Summary --}}


          {{-- Results Table --}}
          @if($resultsObjects->count() > 0 || $derivationObjects->count() > 0)
            <div class="mt-6">
              <table class="table-standard">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th>Data Source</th>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Country/Region</th>
                    <th>Institution</th>
                    <th>Scientific Name</th>
                    <th>Endpoint|Duration|Effect</th>
                    <th>Derivation Method</th>
                    <th>AF</th>
                    <th>Justification</th>
                    <th>Value</th>
                    <th>Remarks</th>
                    <th>Editor</th>
                    <th>Reference ID</th>
                    <th>CRED Screening Score</th>
                  </tr>
                </thead>
                <tbody id="quality-table-body">
                  {{-- PNEC Records --}}
                  @foreach ($resultsObjects as $pnec)
                    <tr class="quality-row @if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif"
                        data-matrix="{{ $pnec->matrix_habitat }}" data-source="pnec">
                      <td class="p-1 text-center">
                        <span class="px-2 py-1 text-xs font-medium bg-slate-600 text-white rounded-full">PNEC</span>
                      </td>
                      <td class="p-1 text-center">
                        @if (Auth::check() && (Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('ecotox')))
                          <a href="{{ route('ecotox.quality.form', $pnec->norman_pnec_id) }}?substances={{ json_encode($request->substances) }}&returnUrl={{ urlencode(url()->current() . '?' . http_build_query($request->all())) }}" 
                             class="text-slate-600 hover:text-slate-800 underline font-medium">
                            {{ $pnec->norman_pnec_id ?? 'N/A' }}
                          </a>
                        @else
                          {{ $pnec->norman_pnec_id ?? 'N/A' }}
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $pnec->pnec_type ?? 'N/A' }}</td>
                      <td class="p-1 text-center">{{ $pnec->pnec_type_country ?? 'N/A' }}</td>
                      <td class="p-1 text-center">{{ $pnec->institution ?? 'N/A' }}</td>
                      <td class="p-1">
                        <div class="italic">{{ $pnec->scientific_name ?? 'N/A' }}</div>
                      </td>
                      <td class="p-1 text-center">
                        <div class="text-sm">
                          <div><strong>Endpoint:</strong> {{ $pnec->endpoint ?? 'N/A' }}</div>
                          <div><strong>Duration:</strong> {{ $pnec->duration ?? 'N/A' }}</div>
                          <div><strong>Effect:</strong> {{ $pnec->effect_measurement ?? 'N/A' }}</div>
                        </div>
                      </td>
                      <td class="p-1 text-center">{{ $pnec->derivation_method ?? 'N/A' }}</td>
                      <td class="p-1 text-center">{{ $pnec->AF ?? 'N/A' }}</td>
                      <td class="p-1 text-center">{{ $pnec->justification ?? 'N/A' }}</td>
                      <td class="p-1 text-center">
                        @if ($pnec->value)
                          <div class="font-medium">{{ number_format($pnec->value, 4) }}</div>
                          @if ($pnec->concentration_specification)
                            <div class="text-xs text-gray-500">{{ $pnec->concentration_specification }}</div>
                          @endif
                        @else
                          <span class="text-gray-400">N/A</span>
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $pnec->remarks ?? 'N/A' }}</td>
                      <td class="p-1 text-center">
                        @if ($pnec->editor)
                          {{ $pnec->editor ?? 'N/A' }}
                        @else
                          N/A
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $pnec->ecotox_id ?? 'N/A' }}</td>
                      <td class="p-1 text-center">
                        @if (isset($pnec->cred_screening_score))
                          <span class="font-medium">{{ number_format($pnec->cred_screening_score, 2) }}%</span>
                        @else
                          <span class="text-gray-400">0.00%</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                  
                  {{-- Derivation Records --}}
                  @foreach ($derivationObjects as $derivation)
                    <tr class="quality-row @if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif"
                        data-matrix="{{ $derivation->matrix_habitat ?? 'Unknown' }}" data-source="derivation">
                      <td class="p-1 text-center">
                        <span class="px-2 py-1 text-xs font-medium bg-stone-600 text-white rounded-full">Derivation</span>
                      </td>
                      <td class="p-1 text-center">{{ $derivation->norman_pnec_id ?? 'N/A' }}</td>
                      <td class="p-1 text-center">{{ $derivation->pnec_type ?? 'N/A' }}</td>
                      <td class="p-1 text-center">{{ $derivation->country_or_region ?? 'N/A' }}</td>
                      <td class="p-1 text-center">{{ $derivation->institution ?? 'N/A' }}</td>
                      <td class="p-1">
                        <div class="italic">{{ $derivation->scientific_name ?? 'N/A' }}</div>
                      </td>
                      <td class="p-1 text-center">
                        <div class="text-sm">
                          <div><strong>Endpoint:</strong> {{ $derivation->endpoint ?? 'N/A' }}</div>
                          <div><strong>Duration:</strong> {{ $derivation->duration ?? 'N/A' }}</div>
                          <div><strong>Effect:</strong> {{ $derivation->effect_measurement ?? 'N/A' }}</div>
                        </div>
                      </td>
                      <td class="p-1 text-center">{{ $derivation->derivation_method ?? 'N/A' }}</td>
                      <td class="p-1 text-center">{{ $derivation->AF ?? 'N/A' }}</td>
                      <td class="p-1 text-center">{{ $derivation->justification ?? 'N/A' }}</td>
                      <td class="p-1 text-center">
                        @if ($derivation->concentration_value)
                          <div class="font-medium">{{ $derivation->concentration_value }}</div>
                          @if ($derivation->concentration_qualifier)
                            <div class="text-xs text-gray-500">{{ $derivation->concentration_qualifier }}</div>
                          @endif
                        @else
                          <span class="text-gray-400">N/A</span>
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $derivation->remarks ?? 'N/A' }}</td>
                      <td class="p-1 text-center">
                        @if ($derivation->der_editor)
                          {{ $derivation->der_editor ?? 'N/A' }}
                        @else
                          N/A
                        @endif
                      </td>
                      <td class="p-1 text-center">{{ $derivation->ecotox_id ?? 'N/A' }}</td>
                      <td class="p-1 text-center">
                        @if (isset($derivation->cred_screening_score))
                          <span class="font-medium">{{ number_format($derivation->cred_screening_score, 2) }}%</span>
                        @else
                          <span class="text-gray-400">0.00%</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>

              {{-- No results message (hidden by default) --}}
              <div id="no-results-message" class="hidden text-center py-8 text-gray-500">
                No results found for the selected filter.
              </div>

              {{-- Pagination --}}
              @if (isset($request->displayOption) && $request->displayOption == 1)
                {{-- Simple pagination --}}
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
                {{-- Advanced pagination --}}
                {{ $resultsObjects->links('pagination::tailwind') }}
              @endif
            </div>
          @else
            <div class="mt-6 text-center text-gray-500 py-8">
              <p>No PNEC3 or Derivation records found for the selected substance.</p>
            </div>
          @endif

          {{-- PNEC Table --}}
          @if($lowestPnecObjects->count() > 0)
            <div class="mt-8" id="pnec-table-container">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">PNEC Table</h3>
              <div class="overflow-x-auto">
                <table class="table-standard" id="pnec-table">
                  <thead>
                    <tr class="bg-gray-600 text-white">
                      <th>#</th>
                      <th>PNEC Type</th>
                      <th>Matrix</th>
                      <th>Country</th>
                      <th>Institution</th>
                      <th>Scientific Name</th>
                      <th>Endpoint|Duration|Effect</th>
                      <th>AF</th>
                      <th>PNEC Value</th>
                      <th>Editor</th>
                      <th>PNEC ID</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($lowestPnecObjects as $pnec)
                      <tr class="pnec-row @if($pnec->lowest_active == 1) bg-green-100 @elseif ($loop->odd) bg-slate-100 @else bg-slate-200 @endif"
                          data-matrix="{{ strtolower($pnec->matrix_type ?? 'unknown') }}">
                        <td class="p-1 text-center">{{ $loop->iteration }}</td>
                        <td class="p-1 text-center">{{ $pnec->lowest_pnec_type ?? 'N/A' }}</td>
                        <td class="p-1 text-center">{{ $pnec->matrix_type ?? 'N/A' }}</td>
                        <td class="p-1 text-center">{{ 'N/A' }}{{-- Country field needs to be mapped --}}</td>
                        <td class="p-1 text-center">{{ $pnec->lowest_institution ?? 'N/A' }}</td>
                        <td class="p-1">
                          <div class="italic">
                            @if($pnec->substance)
                              {{ $pnec->substance->name ?? 'N/A' }}
                            @elseif($pnec->originSubstance)
                              {{ $pnec->originSubstance->name ?? 'N/A' }}
                            @else
                              N/A
                            @endif
                          </div>
                        </td>
                        <td class="p-1 text-center">
                          <div class="text-sm">
                            <div><strong>Endpoint:</strong> {{ $pnec->lowest_test_endpoint ?? 'N/A' }}</div>
                            {{-- Duration and Effect would need additional fields in the database --}}
                          </div>
                        </td>
                        <td class="p-1 text-center">{{ $pnec->lowest_AF ?? 'N/A' }}</td>
                        <td class="p-1 text-center">
                          @if ($pnec->lowest_pnec_value)
                            <div class="font-medium">{{ number_format($pnec->lowest_pnec_value, 4) }}</div>
                          @else
                            <span class="text-gray-400">N/A</span>
                          @endif
                        </td>
                        <td class="p-1 text-center">
                          @if ($pnec->editor)
                            {{ $pnec->editor->name ?? $pnec->editor->email ?? 'N/A' }}
                          @else
                            N/A
                          @endif
                        </td>
                        <td class="p-1 text-center">{{ $pnec->lowest_id ?? 'N/A' }}</td>
                        <td class="p-1 text-center">{{ $pnec->formatted_date ?? 'N/A' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          @endif

        </div>
      </div>
    </div>
    
  </div>

  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tableRows = document.querySelectorAll('.quality-row');
        const pnecRows = document.querySelectorAll('.pnec-row');
        const noResultsMessage = document.getElementById('no-results-message');
        const tableBody = document.getElementById('quality-table-body');
        const pnecTableContainer = document.getElementById('pnec-table-container');

        // Function to filter table rows based on selected tab
        function filterTable(matrixFilter) {
          let visibleCount = 0;
          let visiblePnecCount = 0;

          // Filter main results table rows
          tableRows.forEach(row => {
            const rowMatrix = row.dataset.matrix;
            const rowSource = row.dataset.source;

            // Show row if it matches the filter (or if "All" is selected)
            if (matrixFilter === '') {
              // Show all rows
              row.style.display = '';
              visibleCount++;
            } else if (rowMatrix === matrixFilter) {
              row.style.display = '';
              visibleCount++;
            } else {
              row.style.display = 'none';
            }
          });

          // Filter PNEC table rows
          pnecRows.forEach(row => {
            const rowMatrix = row.dataset.matrix;

            // Show row if it matches the filter (or if "All" is selected)
            if (matrixFilter === '') {
              // Show all rows
              row.style.display = '';
              visiblePnecCount++;
            } else if (rowMatrix === matrixFilter) {
              row.style.display = '';
              visiblePnecCount++;
            } else {
              row.style.display = 'none';
            }
          });

          // Show/hide main results table and no results message
          if (visibleCount === 0) {
            noResultsMessage.classList.remove('hidden');
            tableBody.parentElement.style.display = 'none';
          } else {
            noResultsMessage.classList.add('hidden');
            tableBody.parentElement.style.display = '';
          }

          // Show/hide PNEC table
          if (pnecTableContainer) {
            if (visiblePnecCount === 0) {
              pnecTableContainer.style.display = 'none';
            } else {
              pnecTableContainer.style.display = '';
            }
          }
        }

        // Function to activate tab
        function activateTab(button) {
          const tabId = button.dataset.tab;
          const matrixFilter = button.dataset.filterMatrix;

          // Update button styles
          tabButtons.forEach(btn => {
            btn.classList.remove('border-sky-500', 'text-sky-600');
            btn.classList.add('border-transparent', 'text-gray-500');
          });

          button.classList.remove('border-transparent', 'text-gray-500');
          button.classList.add('border-sky-500', 'text-sky-600');

          // Filter table rows
          filterTable(matrixFilter);

          // Save active tab to localStorage
          localStorage.setItem('activeQualityTab', tabId);
        }

        // Add click event to tab buttons
        tabButtons.forEach(button => {
          button.addEventListener('click', function() {
            activateTab(this);
          });
        });

        // Check for saved tab in localStorage and activate it
        const savedTab = localStorage.getItem('activeQualityTab');
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
