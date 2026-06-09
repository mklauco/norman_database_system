<x-app-layout>
  <x-slot name="header">
    @include('empodat_suspect.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          {{-- Action bar: Refine Search | Download CSV --}}
          <div class="flex items-start justify-between">
            {{-- Left: Refine Search --}}
            <form method="GET" action="{{ route('empodat_suspect.search.filter') }}" class="inline">
              @if(is_array($countrySearch))
                @foreach($countrySearch as $country)
                  <input type="hidden" name="countrySearch[]" value="{{ $country }}">
                @endforeach
              @else
                <input type="hidden" name="countrySearch" value="{{ $countrySearch ?? '' }}">
              @endif

              @if(is_array($matrixSearch))
                @foreach($matrixSearch as $matrix)
                  <input type="hidden" name="matrixSearch[]" value="{{ $matrix }}">
                @endforeach
              @else
                <input type="hidden" name="matrixSearch" value="{{ $matrixSearch ?? '' }}">
              @endif

              @if(is_array($sourceSearch))
                @foreach($sourceSearch as $source)
                  <input type="hidden" name="sourceSearch[]" value="{{ $source }}">
                @endforeach
              @else
                <input type="hidden" name="sourceSearch" value="{{ $sourceSearch ?? '' }}">
              @endif

              <input type="hidden" name="year_from" value="{{ $year_from ?? '' }}">
              <input type="hidden" name="year_to" value="{{ $year_to ?? '' }}">
              <input type="hidden" name="displayOption" value="{{ $displayOption }}">

              @if(is_array($substances))
                @foreach($substances as $substance)
                  <input type="hidden" name="substances[]" value="{{ $substance }}">
                @endforeach
              @else
                <input type="hidden" name="substances" value="{{ $substances ?? '' }}">
              @endif

              @if(is_array($categoriesSearch))
                @foreach($categoriesSearch as $category)
                  <input type="hidden" name="categoriesSearch[]" value="{{ $category }}">
                @endforeach
              @else
                <input type="hidden" name="categoriesSearch" value="{{ $categoriesSearch ?? '' }}">
              @endif

              @if(is_array($typeDataSourcesSearch))
                @foreach($typeDataSourcesSearch as $typeDataSource)
                  <input type="hidden" name="typeDataSourcesSearch[]" value="{{ $typeDataSource }}">
                @endforeach
              @else
                <input type="hidden" name="typeDataSourcesSearch" value="{{ $typeDataSourcesSearch ?? '' }}">
              @endif

              @if(is_array($concentrationIndicatorSearch))
                @foreach($concentrationIndicatorSearch as $concentrationIndicator)
                  <input type="hidden" name="concentrationIndicatorSearch[]" value="{{ $concentrationIndicator }}">
                @endforeach
              @else
                <input type="hidden" name="concentrationIndicatorSearch" value="{{ $concentrationIndicatorSearch ?? '' }}">
              @endif

              @if(is_array($analyticalMethodSearch))
                @foreach($analyticalMethodSearch as $analyticalMethod)
                  <input type="hidden" name="analyticalMethodSearch[]" value="{{ $analyticalMethod }}">
                @endforeach
              @else
                <input type="hidden" name="analyticalMethodSearch" value="{{ $analyticalMethodSearch ?? '' }}">
              @endif

              @if(is_array($dataSourceLaboratorySearch))
                @foreach($dataSourceLaboratorySearch as $dataSourceLaboratory)
                  <input type="hidden" name="dataSourceLaboratorySearch[]" value="{{ $dataSourceLaboratory }}">
                @endforeach
              @else
                <input type="hidden" name="dataSourceLaboratorySearch" value="{{ $dataSourceLaboratorySearch ?? '' }}">
              @endif

              @if(is_array($dataSourceOrganisationSearch))
                @foreach($dataSourceOrganisationSearch as $dataSourceOrganisation)
                  <input type="hidden" name="dataSourceOrganisationSearch[]" value="{{ $dataSourceOrganisation }}">
                @endforeach
              @else
                <input type="hidden" name="dataSourceOrganisationSearch" value="{{ $dataSourceOrganisationSearch ?? '' }}">
              @endif

              @if(is_array($qualityAnalyticalMethodsSearch))
                @foreach($qualityAnalyticalMethodsSearch as $qualityAnalyticalMethod)
                  <input type="hidden" name="qualityAnalyticalMethodsSearch[]" value="{{ $qualityAnalyticalMethod }}">
                @endforeach
              @else
                <input type="hidden" name="qualityAnalyticalMethodsSearch" value="{{ $qualityAnalyticalMethodsSearch ?? '' }}">
              @endif

              <input type="hidden" name="query_log_id" value="{{ $query_log_id }}">
              <button type="submit" class="btn-submit"><i class="fas fa-filter mr-1"></i>Refine Search</button>
            </form>

            {{-- Right: Download CSV --}}
            <div class="flex flex-col items-end">
              @auth
                @php
                  $canDownload = true;
                  $downloadBlockReason = null;

                  // Check if filters are applied
                  if (!isset($hasFilters) || !$hasFilters) {
                      $canDownload = false;
                      $downloadBlockReason = 'no_filters';
                  }

                  // Check record count limit (only when we have the count from paginated results)
                  if ($canDownload && isset($actualRecordCount) && $actualRecordCount > $maxDownloadRecords) {
                      $canDownload = false;
                      $downloadBlockReason = 'too_many_records';
                  }
                @endphp

                @if ($canDownload)
                  <a href="{{ route('empodat_suspect.search.download', ['query_log_id' => $query_log_id]) }}"
                    class="btn-download"><i class="fas fa-file-csv mr-1"></i>Download CSV</a>
                @else
                  <button type="button" class="btn-download opacity-50 cursor-not-allowed" disabled>
                    <i class="fas fa-file-csv mr-1"></i>Download CSV
                  </button>
                  @if ($downloadBlockReason === 'no_filters')
                    <span class="text-xs text-amber-600 mt-1 text-right max-w-xs">
                      <i class="fas fa-exclamation-triangle mr-1"></i>
                      Download is not available for unfiltered data. Please apply at least one search criterion.
                    </span>
                  @elseif ($downloadBlockReason === 'too_many_records')
                    <span class="text-xs text-amber-600 mt-1 text-right max-w-xs">
                      <i class="fas fa-exclamation-triangle mr-1"></i>
                      The number of records ({{ number_format($actualRecordCount, 0, '.', ' ') }}) exceeds {{ number_format($maxDownloadRecords, 0, '.', ' ') }}.
                      Please use the API or contact the administrator.
                    </span>
                  @endif
                @endif
              @else
                <button type="button" class="btn-download opacity-50 cursor-not-allowed" disabled>
                  <i class="fas fa-file-csv mr-1"></i>Download CSV
                </button>
                <span class="text-xs text-gray-400 mt-1">Available for logged in users only</span>
              @endauth
            </div>
          </div>

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
              {{-- Simple output - use Livewire query counter --}}
              @livewire('backend.query-counter', [
                  'queryId' => $query_log_id ?? null,
                  'resultsCount' => $empodatSuspectsCount,
                  'count_again' => request()->has('page') ? false : true,
              ])
            @else
              {{-- Advanced output with pagination --}}
              <div class="flex items-center">
                <span>Number of matched records:</span>
                <span class="ml-1 mr-1 font-bold">{{ number_format($empodatSuspects->total(), 0, '.', ' ') }}</span>
                @if ($empodatSuspectsCount > 0)
                  <span>
                    of {{ number_format($empodatSuspectsCount, 0, '.', ' ') }}
                    @if (is_numeric($empodatSuspects->total()))
                      @if (($empodatSuspects->total() / $empodatSuspectsCount) * 100 < 0.01)
                        (&le; 0.01%)
                      @else
                        ({{ number_format(($empodatSuspects->total() / $empodatSuspectsCount) * 100, 2, '.', ' ') }}%)
                      @endif
                    @endif
                  </span>
                @endif
              </div>
            @endif
          </div>

          {{-- Sorting legend --}}
          <div class="mb-2 text-xs text-gray-600 flex items-start gap-2">
            <i class="fas fa-info-circle text-gray-400 mt-0.5"></i>
            <span>
              <strong>Sorting</strong> reorders <em>all</em> matching records, so it can take a while on broad searches —
              the more filters you apply, the faster it gets.
              The columns marked <i class="fas fa-lock text-[10px] opacity-60"></i>
              (<span class="text-amber-700">Substance, Country, Year, Sample Code, Sampling Station</span>)
              can only be sorted after you apply a filter, and are the slowest.
            </span>
          </div>

          <table class="table-standard">
            <thead>
              @php
                // Keep these in sync with EmpodatSuspectController DIRECT/RELATED_SORT_COLUMNS.
                $directSortCols = ['id', 'concentration', 'units', 'ip_max', 'hrms'];
                $relatedSortCols = ['substance', 'country', 'sample_code', 'station', 'year'];
                $columnLabels = [
                    'id' => 'ID',
                    'substance' => 'Substance',
                    'concentration' => 'Concentration',
                    'units' => 'Units',
                    'ip_max' => 'IP_max',
                    'hrms' => 'HRMS Library',
                    'country' => 'Country',
                    'year' => 'Year',
                    'sample_code' => 'Sample Code',
                    'station' => 'Sampling Station',
                ];
                $currentSort = request('sort', 'id');
                $currentDir = strtolower(request('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
              @endphp
              <tr class="bg-gray-600 text-white">
                @foreach ($columnLabels as $key => $label)
                  @php
                    $isSortable = in_array($key, $directSortCols, true)
                        || ($hasFilters && in_array($key, $relatedSortCols, true));
                    $isActive = $isSortable && $currentSort === $key;
                    $nextDir = ($isActive && $currentDir === 'asc') ? 'desc' : 'asc';
                    $icon = $isActive
                        ? ($currentDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down')
                        : ($isSortable ? 'fa-sort' : 'fa-lock');
                  @endphp
                  <th class="whitespace-nowrap">
                    @if ($isSortable)
                      <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'direction' => $nextDir, 'page' => 1]) }}"
                         class="inline-flex items-center gap-1 hover:underline {{ $isActive ? 'font-bold' : '' }}">
                        {{ $label }}<i class="fas {{ $icon }} text-xs {{ $isActive ? '' : 'opacity-50' }}"></i>
                      </a>
                    @else
                      <span class="inline-flex items-center gap-1 cursor-help"
                            title="Apply at least one filter to sort by this column">
                        {{ $label }}<i class="fas {{ $icon }} text-xs opacity-40"></i>
                      </span>
                    @endif
                  </th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @forelse ($empodatSuspects as $e)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                  <td class="p-1 text-center">
                    <div class="flex items-center justify-center space-x-2">
                      <a href="{{ route('empodat_suspect.search.show', $e->id) }}"
                         target="_blank"
                         class="text-teal-600 hover:text-teal-800 transition-colors"
                         title="View full record details">
                        <i class="fas fa-search"></i>
                      </a>
                      <a href="{{ route('empodat_suspect.search.show', $e->id) }}" target="_blank" class="font-mono text-teal-800 hover:text-teal-600 hover:underline">
                        {!! number_format($e->id, 0, '', '&nbsp;') !!}
                      </a>
                    </div>
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->substance)
                      {{ $e->substance->name ?? 'N/A' }}
                    @else
                      N/A (ID: {{ $e->substance_id }})
                    @endif
                    @role('super_admin')
                      <span class="text-xs text-gray-500"> ({{ $e->substance_id }})</span>
                    @endrole
                  </td>
                  <td class="p-1 text-center">
                    <span class="font-medium">{{ $e->concentration ?? 'N/A' }}</span>
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->units ? preg_replace(['/\bdry weight\b/i', '/\bwet weight\b/i'], ['dw', 'ww'], $e->units) : 'N/A' }}
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->ip_max !== null ? number_format($e->ip_max, 2) : 'N/A' }}
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->based_on_hrms_library ? 'TRUE' : 'FALSE' }}
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->station && $e->station->country_id)
                      @php
                        $country = $e->station->getRelation('country');
                      @endphp
                      @if ($country)
                        {{ $country->name ?? '' }}
                      @else
                        N/A
                      @endif
                    @else
                      N/A
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->sampling_year ?? 'N/A' }}
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->station)
                      <span class="font-mono">{{ $e->station->short_sample_code ?? 'N/A' }}</span>
                    @else
                      N/A
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->station)
                      {{ $e->station->name ?? 'N/A' }}
                    @else
                      N/A
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="10" class="p-4 text-center text-gray-500">
                    No results found. Please adjust your search criteria.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>

          @if ($displayOption == 1)
            {{-- use simple output --}}
            <div class="flex justify-center space-x-4 mt-4">
              @if ($empodatSuspects->onFirstPage())
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
                  Previous
                </span>
              @else
                <a href="{{ $empodatSuspects->previousPageUrl() }}"
                  class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
                  Previous
                </a>
              @endif

              @if ($empodatSuspects->hasMorePages())
                <a href="{{ $empodatSuspects->nextPageUrl() }}"
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
            {{ $empodatSuspects->links('pagination::tailwind') }}
          @endif

        </div>
      </div>
    </div>
  </div>

</x-app-layout>
