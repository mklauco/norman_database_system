<x-app-layout>
  <x-slot name="header">
    @include('hazards.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900" x-data="hazardsSummaryTabs()">
          <a href="{{ route('hazards.data.search.filter', $request->query()) }}">
            <button type="button" class="btn-submit">Refine Search</button>
          </a>

          <div class="text-gray-600 flex border-l-2 border-white">
            <div class="flex flex-wrap items-center bg-gray-50 p-3 rounded-lg shadow-sm border border-gray-200">
              <div class="flex items-center mr-4">
                <span class="text-gray-700">Number of matched records:</span>
                <span class="font-bold text-lg ml-2 text-sky-700">{{ number_format($filteredRecordsCount, 0, '.', ' ') }}</span>
              </div>

              <div class="flex items-center">
                <span class="text-gray-700">of</span>
                <span class="font-medium ml-2 text-gray-800">{{ number_format($resultsObjectsCount, 0, '.', ' ') }}</span>

                @if ($resultsObjectsCount > 0)
                  <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                    @if (($filteredRecordsCount / $resultsObjectsCount) * 100 < 0.01)
                      &le; 0.01% of total
                    @else
                      {{ number_format(($filteredRecordsCount / $resultsObjectsCount) * 100, 2, '.', ' ') }}% of total
                    @endif
                  </span>
                @endif
              </div>
            </div>
          </div>

          @if ($otherTestTypeRowsCount > 0)
            <div class="mt-2 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded p-2">
              {{ number_format($otherTestTypeRowsCount, 0, '.', ' ') }} rows with non-standard test type were excluded from Exp/Pred summary.
            </div>
          @endif

          <div class="text-gray-600 flex border-l-2 border-white mt-2">
            Search parameters:&nbsp;<span class="font-semibold">
              @foreach ($searchParameters as $key => $value)
                @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
                  @foreach ($value as $item)
                    {{ $item }}@if (! $loop->last), @endif
                  @endforeach
                @else
                  {{ $value }}
                @endif
                @if (! $loop->last); @endif
              @endforeach
            </span>
          </div>

          <div class="mt-4">
            <div class="border-b border-gray-200">
              <nav class="-mb-px flex space-x-8">
                <button
                  class="hazards-summary-tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-sky-500 text-sky-600"
                  data-tab="all"
                  data-filter-domain="">
                  All Results
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-sky-100 text-sky-800 rounded-full">
                    {{ $domainCounts['all'] ?? 0 }}
                  </span>
                </button>

                <button
                  class="hazards-summary-tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                  data-tab="physchem"
                  data-filter-domain="physchem">
                  Phys-Chemical
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $domainCounts['physchem'] ?? 0 }}
                  </span>
                </button>

                <button
                  class="hazards-summary-tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                  data-tab="fate-transport"
                  data-filter-domain="fate_transport">
                  Fate and Transport
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $domainCounts['fate_transport'] ?? 0 }}
                  </span>
                </button>
              </nav>
            </div>
          </div>

          @if (empty($summaryBySubstance))
            <div class="mt-6 text-center py-8 text-gray-500">
              No summary data available for the selected filters.
            </div>
          @else
            @foreach ($summaryBySubstance as $substanceData)
              @php
                $substance = $substanceData['substance'] ?? null;
              @endphp
              <div class="mt-6 border border-gray-200 rounded-lg shadow-sm p-4">
                <div class="mb-3">
                  <h2 class="text-lg font-semibold text-gray-900">
                    {{ $substance?->display_name ?? $substance?->name ?? ('Substance #'.$substanceData['substance_id']) }}
                  </h2>
                  <div class="text-sm text-gray-600">
                    CAS: {{ $substance?->formatted_cas ?? 'N/A' }}
                    |
                    NORMAN SusDat ID: {{ $substance?->prefixed_code ?? 'N/A' }}
                    |
                    {{ $substance?->stdinchikey ?? 'N/A' }}
                  </div>
                </div>

                @foreach ($substanceData['domains'] as $domainData)
                  <div class="hazards-summary-domain-block mt-4" data-domain="{{ $domainData['domain_key'] }}">
                    <h3 class="font-semibold text-sm uppercase text-gray-500 mb-2">{{ $domainData['domain_label'] }}</h3>
                    @php
                      $detailBaseQuery = $request->query();
                      unset($detailBaseQuery['page']);
                    @endphp
                    <div class="overflow-x-auto">
                      <table class="table-standard text-sm">
                        <thead>
                          <tr class="bg-gray-600 text-white">
                            <th rowspan="2">Parameter</th>
                            <th rowspan="2">Unit</th>
                            <th colspan="4" class="text-center">Experimental</th>
                            <th colspan="4" class="text-center">Predicted</th>
                          </tr>
                          <tr class="bg-gray-500 text-white">
                            <th>N</th>
                            <th>Avg</th>
                            <th>Min</th>
                            <th>Max</th>
                            <th>N</th>
                            <th>Avg</th>
                            <th>Min</th>
                            <th>Max</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach ($domainData['parameters'] as $parameter)
                            @php
                              $experimentalDetailsQuery = array_merge($detailBaseQuery, [
                                'displayLayout' => 'detailed',
                                'dataDomainSearch' => [$domainData['domain_key']],
                                'normanParameterSearch' => [$parameter['norman_parameter_name']],
                                'testTypeSearch' => ['2'],
                                'sourceRecordTypeSearch' => [$domainData['domain_key'] === 'physchem' ? 'property' : 'fate'],
                              ]);

                              $predictedDetailsQuery = array_merge($detailBaseQuery, [
                                'displayLayout' => 'detailed',
                                'dataDomainSearch' => [$domainData['domain_key']],
                                'normanParameterSearch' => [$parameter['norman_parameter_name']],
                                'testTypeSearch' => ['3'],
                                'sourceRecordTypeSearch' => [$domainData['domain_key'] === 'physchem' ? 'property' : 'fate'],
                              ]);

                              if (!empty($parameter['specific_parameter_name'])) {
                                $experimentalDetailsQuery['specificParameterSearch'] = [$parameter['specific_parameter_name']];
                                $predictedDetailsQuery['specificParameterSearch'] = [$parameter['specific_parameter_name']];
                              }
                            @endphp
                            <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                              <td class="p-1">
                                <div class="font-medium">{{ $parameter['norman_parameter_name'] }}</div>
                                @if ($parameter['specific_parameter_name'])
                                  <div class="text-xs text-gray-600">{{ $parameter['specific_parameter_name'] }}</div>
                                @endif
                              </td>
                              <td class="p-1 text-center">{{ $parameter['unit'] ?? '-' }}</td>
                              <td class="p-1 text-center">
                                @if ($parameter['experimental']['count'] > 0)
                                  <a class="link-lime-text" href="{{ route('hazards.data.search.search', $experimentalDetailsQuery) }}">
                                    {{ $parameter['experimental']['count'] }}
                                  </a>
                                @else
                                  0
                                @endif
                              </td>
                              <td class="p-1 text-center">{{ $parameter['experimental']['avg'] }}</td>
                              <td class="p-1 text-center">{{ $parameter['experimental']['min'] }}</td>
                              <td class="p-1 text-center">{{ $parameter['experimental']['max'] }}</td>
                              <td class="p-1 text-center">
                                @if ($parameter['predicted']['count'] > 0)
                                  <a class="link-lime-text" href="{{ route('hazards.data.search.search', $predictedDetailsQuery) }}">
                                    {{ $parameter['predicted']['count'] }}
                                  </a>
                                @else
                                  0
                                @endif
                              </td>
                              <td class="p-1 text-center">{{ $parameter['predicted']['avg'] }}</td>
                              <td class="p-1 text-center">{{ $parameter['predicted']['min'] }}</td>
                              <td class="p-1 text-center">{{ $parameter['predicted']['max'] }}</td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  </div>
                @endforeach
              </div>
            @endforeach

            <div id="hazards-summary-no-results" class="hidden text-center py-8 text-gray-500">
              No summary results found for the selected tab.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</x-app-layout>

<script>
  function hazardsSummaryTabs() {
    return {
      init() {
        const buttons = document.querySelectorAll('.hazards-summary-tab-button');
        const domainBlocks = document.querySelectorAll('.hazards-summary-domain-block');
        const noResultsMessage = document.getElementById('hazards-summary-no-results');
        const storageKey = 'hazards-summary-active-tab';

        const applyTab = (button) => {
          const filterDomain = button.dataset.filterDomain || '';
          let visibleBlocks = 0;

          buttons.forEach((tabButton) => {
            tabButton.classList.remove('border-sky-500', 'text-sky-600');
            tabButton.classList.add('border-transparent', 'text-gray-500');
          });

          button.classList.remove('border-transparent', 'text-gray-500');
          button.classList.add('border-sky-500', 'text-sky-600');

          domainBlocks.forEach((block) => {
            const matches = filterDomain === '' || block.dataset.domain === filterDomain;
            block.classList.toggle('hidden', !matches);
            if (matches) {
              visibleBlocks++;
            }
          });

          if (noResultsMessage) {
            noResultsMessage.classList.toggle('hidden', visibleBlocks > 0);
          }

          localStorage.setItem(storageKey, button.dataset.tab || 'all');
        };

        buttons.forEach((button) => {
          button.addEventListener('click', () => applyTab(button));
        });

        const savedTab = localStorage.getItem(storageKey);
        const savedButton = savedTab ? document.querySelector(`.hazards-summary-tab-button[data-tab="${savedTab}"]`) : null;
        applyTab(savedButton || buttons[0]);
      },
    };
  }
</script>
