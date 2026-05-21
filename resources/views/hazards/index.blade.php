<x-app-layout>
  <x-slot name="header">
    @include('hazards.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900" x-data="hazardsDetailedTabs()">
          @php
            $formatHazardsNumber = static function ($value) {
              if (is_null($value) || $value === '') {
                return 'N/A';
              }

              $numericValue = (float) $value;
              $absoluteValue = abs($numericValue);

              if ($numericValue === 0.0) {
                return '0';
              }

              if ($absoluteValue > 0 && $absoluteValue < 0.001) {
                $formatted = sprintf('%.3e', $numericValue);
                $formatted = preg_replace('/\.?0+e/i', 'e', $formatted) ?? $formatted;
                $formatted = preg_replace('/e\+?(-?)0*(\d+)/i', 'e$1$2', $formatted) ?? $formatted;

                return strtolower($formatted);
              }

              $formatted = number_format($numericValue, 4, '.', '');

              return rtrim(rtrim($formatted, '0'), '.');
            };
          @endphp

          <a href="{{ route('hazards.data.search.filter', $request->query()) }}">
            <button type="submit" class="btn-submit">Refine Search</button>
          </a>

          <div class="text-gray-600 flex border-l-2 border-white">
            <div class="flex flex-wrap items-center bg-gray-50 p-3 rounded-lg shadow-sm border border-gray-200">
              <div class="flex items-center mr-4">
                <span class="text-gray-700">Number of matched records:</span>
                <span class="font-bold text-lg ml-2 text-sky-700">{{ number_format($resultsObjects->total(), 0, '.', ' ') }}</span>
              </div>

              <div class="flex items-center">
                <span class="text-gray-700">of</span>
                <span class="font-medium ml-2 text-gray-800">{{ number_format($resultsObjectsCount, 0, '.', ' ') }}</span>
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
          </div>

          <div class="text-gray-600 flex border-l-2 border-white">
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
                  class="hazards-tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-sky-500 text-sky-600"
                  data-tab="all"
                  data-filter-domain="">
                  All Results
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-sky-100 text-sky-800 rounded-full">
                    {{ $domainCounts['all'] ?? 0 }}
                  </span>
                </button>

                <button
                  class="hazards-tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                  data-tab="physchem"
                  data-filter-domain="physchem">
                  Phys-Chemical
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $domainCounts['physchem'] ?? 0 }}
                  </span>
                </button>

                <button
                  class="hazards-tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
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

          <div class="mt-4">
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th>ID</th>
                  <th>Substance</th>
                  <th>DTXID</th>
                  <th>Domain</th>
                  <th>NORMAN Parameter</th>
                  <th>Specific Parameter</th>
                  <th>Assessment Class</th>
                  <th>Assessment Value</th>
                  <th>Unit</th>
                  <th>Reference Type</th>
                  <th>Year</th>
                  <th>Data Source</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="hazards-detailed-table-body">
                @foreach ($resultsObjects as $row)
                  <tr class="hazards-detailed-row @if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif" data-domain="{{ $row->data_domain }}">
                    <td class="p-1 text-center">{{ $row->id }}</td>
                    <td class="p-1">
                      <div class="font-medium">{{ $row->substance_name ?? ($row->substance->name ?? 'N/A') }}</div>
                      @if ($row->susdat_substance_id)
                        <div class="text-xs text-gray-500">
                          <a class="link-lime-text" href="{{ route('substances.show', $row->susdat_substance_id) }}" target="_blank">NS substance</a>
                        </div>
                      @endif
                    </td>
                    <td class="p-1 text-center">{{ $row->dtxid ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $row->data_domain === 'fate_transport' ? 'Fate and Transport' : ($row->data_domain === 'physchem' ? 'Phys-Chemical' : ($row->data_domain ? ucwords(str_replace('_', ' ', $row->data_domain)) : 'N/A')) }}</td>
                    <td class="p-1 text-center">{{ $row->norman_parameter_name ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $row->specific_parameter_name ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $row->assessment_class ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $formatHazardsNumber($row->value_assessment_index) }}</td>
                    <td class="p-1 text-center">{{ $row->unit ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $row->reference_type ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $row->year ?? 'N/A' }}</td>
                    <td class="p-1 text-center">{{ $row->data_source ?? 'N/A' }}</td>
                    <td class="p-1 text-center">
                      <a href="{{ route('hazards.data.form', $row->id) }}" class="link-lime-text" title="View details">
                        <i class="fas fa-search"></i>
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div id="hazards-detailed-no-results" class="hidden text-center py-8 text-gray-500">
            No results found for the selected tab.
          </div>

          {{ $resultsObjects->links('pagination::tailwind') }}
        </div>
      </div>
    </div>
  </div>
</x-app-layout>

<script>
  function hazardsDetailedTabs() {
    return {
      init() {
        const buttons = document.querySelectorAll('.hazards-tab-button');
        const rows = document.querySelectorAll('.hazards-detailed-row');
        const noResultsMessage = document.getElementById('hazards-detailed-no-results');
        const storageKey = 'hazards-detailed-active-tab';

        const applyTab = (button) => {
          const filterDomain = button.dataset.filterDomain || '';
          let visibleRows = 0;

          buttons.forEach((tabButton) => {
            tabButton.classList.remove('border-sky-500', 'text-sky-600');
            tabButton.classList.add('border-transparent', 'text-gray-500');
          });

          button.classList.remove('border-transparent', 'text-gray-500');
          button.classList.add('border-sky-500', 'text-sky-600');

          rows.forEach((row) => {
            const matches = filterDomain === '' || row.dataset.domain === filterDomain;
            row.classList.toggle('hidden', !matches);
            if (matches) {
              visibleRows++;
            }
          });

          if (noResultsMessage) {
            noResultsMessage.classList.toggle('hidden', visibleRows > 0);
          }

          localStorage.setItem(storageKey, button.dataset.tab || 'all');
        };

        buttons.forEach((button) => {
          button.addEventListener('click', () => applyTab(button));
        });

        const savedTab = localStorage.getItem(storageKey);
        const savedButton = savedTab ? document.querySelector(`.hazards-tab-button[data-tab="${savedTab}"]`) : null;
        applyTab(savedButton || buttons[0]);
      },
    };
  }
</script>
