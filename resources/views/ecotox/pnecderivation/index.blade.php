<x-app-layout>
  <x-slot name="header">
    @include('ecotox.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <div class="flex gap-2">
            <a
              href="{{ route('ecotox.pnecderivation.search.filter', [
                  'substances' => $request->input('substances'),
                  'query_log_id' => $query_log_id,
              ]) }}">
              <button type="submit" class="btn-submit">Refine Search</button>
            </a>
            
            @auth
              <button type="button" onclick="submitQualityVotes()" class="btn-submit">Save Quality Votes</button>
            @endauth
          </div>

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
                        {{ number_format(($resultsObjects->total() / $resultsObjectsCount) * 100, 2, '.', ' ') }}% of
                        total
                      @endif
                    </span>
                  @endif
                </div>
              </div>
            @endif

            @auth
              {{-- <div class="py-2 px-2"><a href="{{ route('ecotox.pnecderivation.search.download', ['query_log_id' => $query_log_id]) }}" class="btn-download">Download</a></div> --}}
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

          {{-- Tabs for different matrix-habitat combinations --}}
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

                <button
                  class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                  data-tab="freshwater" data-filter-matrix="freshwater">
                  Freshwater
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $resultsObjects->where('matrix_habitat', 'freshwater')->count() }}
                  </span>
                </button>

                <button
                  class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                  data-tab="marine" data-filter-matrix="marine water">
                  Marine Water
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $resultsObjects->where('matrix_habitat', 'marine water')->count() }}
                  </span>
                </button>

                <button
                  class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                  data-tab="sediments" data-filter-matrix="sediments">
                  Sediments
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $resultsObjects->where('matrix_habitat', 'sediments')->count() }}
                  </span>
                </button>
              </nav>
            </div>
          </div>

          <!-- Results Table -->
          <div class="mt-4">
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th>PNEC ID</th>
                  <th>PNEC Type</th>
                  <th>Matrix/Habitat</th>
                  <th>Country/Region</th>
                  <th>Taxonomic Group</th>
                  <th>Scientific Name|Endpoint</th>
                  <th>Effect Measurement|Duration</th>
                  <th>Derivation Method</th>
                  <th>AF</th>
                  <th>PNEC Value [µg/L]</th>
                  <th>Data Source</th>
                  <th>Year</th>
                  @if (auth()->check() &&
                          (auth()->user()->hasRole('super_admin') ||
                              auth()->user()->hasRole('admin') ||
                              auth()->user()->hasRole('ecotox')))
                    <th>Reliability</th>
                    <th>Use of Study</th>
                    <th>Quality Vote</th>
                  @endif
                </tr>
              </thead>
              <tbody id="pnec-table-body">
                @foreach ($resultsObjects as $p)
                  <tr class="pnec-row @if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif"
                    data-matrix="{{ $p->matrix_habitat }}">
                    <td class="p-1 text-center">{{ $p->norman_pnec_id ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $p->pnec_type ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $p->matrix_habitat ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $p->country_or_region ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $p->taxonomic_group ?? 'N/A' }}</td>
                    <td class="p-1">
                      <div class="italic">{{ $p->scientific_name ?? 'N/A' }}</div>
                      <div class="text-xs text-gray-600 mt-1">
                        <span class="font-medium">Endpoint:</span><span class="text-teal-800 font-mono"> {{ $p->endpoint ?? 'N/A' }} </span>
                      </div>
                    </td>
                    <td class="p-1">
                      <div class="text-xs">
                        <div><span class="font-medium">Effect:</span> <span class="text-teal-800 font-mono"> {{ $p->effect_measurement ?? 'N/A' }} </span></div>
                        <div><span class="font-medium">Duration:</span> <span class="text-teal-800 font-mono"> {{ $p->duration ?? 'N/A' }} </span></div>
                      </div>
                    </td>
                    <td class="p-1 text-center">{{ $p->derivation_method ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $p->AF ?? 'N/A' }}</td>
                    <td class="p-1 text-center">
                      @if ($p->value)
                        <div>{{ number_format($p->value, 4) }}</div>
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    <td class="p-1 text-center">
                      @if ($p->data_source_name)
                        <div>{{ $p->data_source_name }}</div>
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    <td class="p-1 text-center">{{ $p->year ?? 'N/A' }}</td>
                    @if (auth()->check() &&
                            (auth()->user()->hasRole('super_admin') ||
                                auth()->user()->hasRole('admin') ||
                                auth()->user()->hasRole('ecotox')))
                      <td class="p-1 text-center">{{ $p->reliability_score ?? 'N/A' }}</td>
                      <td class="p-1 text-center">{{ $p->use_study ?? 'N/A' }}</td>
                      <td class="p-1 text-center">
                        <select name="quality_vote[{{ $p->id }}]" 
                                class="quality-vote-input w-16 px-1 py-1 text-sm border border-gray-300 rounded-sm focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
                                data-pnec-id="{{ $p->id }}">
                          <option value="">-</option>
                          <option value="I">I</option>
                          <option value="N">N</option>
                          <option value="1">1</option>
                          <option value="2">2</option>
                          <option value="3">3</option>
                        </select>
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

        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tableRows = document.querySelectorAll('.pnec-row');
        const noResultsMessage = document.getElementById('no-results-message');
        const tableBody = document.getElementById('pnec-table-body');

        // Function to filter table rows based on selected tab
        function filterTable(matrixFilter) {
          let visibleCount = 0;

          tableRows.forEach(row => {
            const rowMatrix = row.dataset.matrix;

            // Show row if it matches the filters (or if "All" is selected)
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
          localStorage.setItem('activePNECDerivationTab', tabId);
        }

        // Add click event to tab buttons
        tabButtons.forEach(button => {
          button.addEventListener('click', function() {
            activateTab(this);
          });
        });

        // Check for saved tab in localStorage and activate it
        const savedTab = localStorage.getItem('activePNECDerivationTab');
        if (savedTab) {
          const savedButton = document.querySelector(`[data-tab="${savedTab}"]`);
          if (savedButton) {
            activateTab(savedButton);
          }
        }
      });

      // Function to submit quality votes
      function submitQualityVotes() {
        const voteInputs = document.querySelectorAll('.quality-vote-input');
        const votes = {};
        
        voteInputs.forEach(input => {
          const pnecId = input.dataset.pnecId;
          const value = input.value;
          
          // Only include votes that have been set
          if (value !== '') {
            votes[pnecId] = value;
          }
        });

        if (Object.keys(votes).length === 0) {
          alert('No quality votes to save. Please select at least one vote.');
          return;
        }

        // Send AJAX request
        fetch('{{ route("ecotox.pnecderivation.saveQualityVotes") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({ votes: votes })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Quality votes saved successfully!');
          } else {
            alert('Error saving quality votes: ' + (data.message || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while saving quality votes.');
        });
      }
    </script>
  @endpush
</x-app-layout>
