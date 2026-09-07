<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>


  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900" x-data="{ modalComponent: null }" x-init="modalComponent = $refs.empodatModal">
          {{-- main div --}}

          {{-- Action bar: Refine Search | Download CSV --}}
          <div class="flex items-start justify-between">
            {{-- Left: Refine Search --}}
            <form method="GET" action="{{ route('codsearch.filter') }}" class="inline">
              @if(is_array($countrySearch))
                @foreach($countrySearch as $country)
                  <input type="hidden" name="countrySearch[]" value="{{ $country }}">
                @endforeach
              @else
                <input type="hidden" name="countrySearch" value="{{ $countrySearch }}">
              @endif

              @if(is_array($matrixSearch))
                @foreach($matrixSearch as $matrix)
                  <input type="hidden" name="matrixSearch[]" value="{{ $matrix }}">
                @endforeach
              @else
                <input type="hidden" name="matrixSearch" value="{{ $matrixSearch }}">
              @endif

              @if(is_array($sourceSearch))
                @foreach($sourceSearch as $source)
                  <input type="hidden" name="sourceSearch[]" value="{{ $source }}">
                @endforeach
              @else
                <input type="hidden" name="sourceSearch" value="{{ $sourceSearch }}">
              @endif

              <input type="hidden" name="year_from" value="{{ $year_from ?? '' }}">
              <input type="hidden" name="year_to" value="{{ $year_to ?? '' }}">
              <input type="hidden" name="displayOption" value="{{ $displayOption }}">

              @if(is_array($substances))
                @foreach($substances as $substance)
                  <input type="hidden" name="substances[]" value="{{ $substance }}">
                @endforeach
              @else
                <input type="hidden" name="substances" value="{{ $substances }}">
              @endif

              @if(is_array($categoriesSearch))
                @foreach($categoriesSearch as $category)
                  <input type="hidden" name="categoriesSearch[]" value="{{ $category }}">
                @endforeach
              @else
                <input type="hidden" name="categoriesSearch" value="{{ $categoriesSearch }}">
              @endif

              @if(is_array($typeDataSourcesSearch))
                @foreach($typeDataSourcesSearch as $typeDataSource)
                  <input type="hidden" name="typeDataSourcesSearch[]" value="{{ $typeDataSource }}">
                @endforeach
              @else
                <input type="hidden" name="typeDataSourcesSearch" value="{{ $typeDataSourcesSearch }}">
              @endif

              @if(is_array($concentrationIndicatorSearch))
                @foreach($concentrationIndicatorSearch as $concentrationIndicator)
                  <input type="hidden" name="concentrationIndicatorSearch[]" value="{{ $concentrationIndicator }}">
                @endforeach
              @else
                <input type="hidden" name="concentrationIndicatorSearch" value="{{ $concentrationIndicatorSearch }}">
              @endif

              @if(is_array($analyticalMethodSearch))
                @foreach($analyticalMethodSearch as $analyticalMethod)
                  <input type="hidden" name="analyticalMethodSearch[]" value="{{ $analyticalMethod }}">
                @endforeach
              @else
                <input type="hidden" name="analyticalMethodSearch" value="{{ $analyticalMethodSearch }}">
              @endif

              @if(is_array($dataSourceLaboratorySearch))
                @foreach($dataSourceLaboratorySearch as $dataSourceLaboratory)
                  <input type="hidden" name="dataSourceLaboratorySearch[]" value="{{ $dataSourceLaboratory }}">
                @endforeach
              @else
                <input type="hidden" name="dataSourceLaboratorySearch" value="{{ $dataSourceLaboratorySearch }}">
              @endif

              @if(is_array($dataSourceOrganisationSearch))
                @foreach($dataSourceOrganisationSearch as $dataSourceOrganisation)
                  <input type="hidden" name="dataSourceOrganisationSearch[]" value="{{ $dataSourceOrganisation }}">
                @endforeach
              @else
                <input type="hidden" name="dataSourceOrganisationSearch" value="{{ $dataSourceOrganisationSearch }}">
              @endif

              @if(is_array($qualityAnalyticalMethodsSearch))
                @foreach($qualityAnalyticalMethodsSearch as $qualityAnalyticalMethod)
                  <input type="hidden" name="qualityAnalyticalMethodsSearch[]" value="{{ $qualityAnalyticalMethod }}">
                @endforeach
              @else
                <input type="hidden" name="qualityAnalyticalMethodsSearch" value="{{ $qualityAnalyticalMethodsSearch }}">
              @endif

              @if(is_array($fileSearch))
                @foreach($fileSearch as $file)
                  <input type="hidden" name="fileSearch[]" value="{{ $file }}">
                @endforeach
              @elseif(!empty($fileSearch))
                <input type="hidden" name="fileSearch" value="{{ $fileSearch }}">
              @endif

              <input type="hidden" name="query_log_id" value="{{ $query_log_id }}">
              @if(request('id_type'))
                <input type="hidden" name="id_type" value="{{ request('id_type') }}">
              @endif
              @if(request('id_from'))
                <input type="hidden" name="id_from" value="{{ request('id_from') }}">
              @endif
              @if(request('id_to'))
                <input type="hidden" name="id_to" value="{{ request('id_to') }}">
              @endif
              <button type="submit" class="btn-submit"><i class="fas fa-filter mr-1"></i>Refine Search</button>
            </form>

            {{-- Right: Download CSV --}}
            <div class="flex flex-col items-end">
              @auth
                <div class="flex items-center space-x-2">
                  <a href="{{ route('codsearch.download', ['query_log_id' => $query_log_id]) }}"
                    class="btn-download"><i class="fas fa-file-csv mr-1"></i>Download CSV</a>
                  @role('super_admin|admin')
                    <a href="{{ route('codsearch.download.ids', ['query_log_id' => $query_log_id]) }}"
                      class="btn-download"><i class="fas fa-file-code mr-1"></i>Download IDs for R</a>
                  @endrole
                </div>
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
              {{-- Simple output - use Livewire query counter --}}
              @livewire('empodat.query-counter', ['queryId' => $query_log_id, 'empodatsCount' => $empodatsCount, 'count_again' => request()->has('page') ? false : true])
            @else
              {{-- Advanced output with pagination --}}
              <div class="flex items-center text-gray-600">
                <span>Number of matched records:</span>
                <span class="ml-1 mr-1 font-bold">{{ number_format($empodats->total(), 0, '.', ' ') }}</span>
                @if ($empodatsCount > 0)
                  <span>
                    of {{ number_format($empodatsCount, 0, '.', ' ') }}
                    @if (is_numeric($empodats->total()))
                      @if (($empodats->total() / $empodatsCount) * 100 < 0.01)
                        (&le; 0.01%)
                      @else
                        ({{ number_format(($empodats->total() / $empodatsCount) * 100, 2, '.', ' ') }}%)
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
                <th>Concentration</th>
                <th>Ecosystem/Matrix</th>
                <th>Country</th>
                <th>Sampling year</th>
                <th>Sampling station</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($empodats as $e)
                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                  <td class="p-1 text-center">
                    <div class="font-mono text-teal-800">
                      {!! number_format($e->id, 0, '', '&nbsp;') !!}
                      <a href="{{ route('codsearch.show', $e->id) }}" class="link-lime-text"
                        x-on:click.prevent="console.log('Clicking record:', {{ $e->id }}); $dispatch('open-empodat-modal', {{ $e->id }})">
                        <i class="fas fa-search"></i>
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
                      <span class="text-xss text-gray-500"> ({{ $e->substance_id }})</span>
                    @endrole
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->concentration_indicator_id == 0)
                      {{ $e->concentration_indicator_id }}
                    @elseif($e->concentration_indicator_id > 1)
                      @if ($e->concentrationIndicator)
                        {{ $e->concentrationIndicator->name ?? 'N/A' }}
                      @else
                        N/A
                      @endif
                    @else
                      <span
                        class="font-medium">{{ $e->concentration_value !== null ? number_format($e->concentration_value, 3, '.', '') : 'N/A' }}</span>&nbsp;{{ $e->matrix ? $e->matrix->display_unit ?? '' : '' }}
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->matrix)
                      {{ $e->matrix->name ?? 'N/A' }}
                    @else
                      N/A
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->station && $e->station->countryRelation)
                      {{ $e->station->countryRelation->name ?? 'N/A' }}
                    @else
                      N/A
                    @endif
                  </td>
                  <td class="p-1 text-center">
                    {{ $e->sampling_date_year }}
                  </td>
                  <td class="p-1 text-center">
                    @if ($e->station)
                      {{ $e->station->name ?? 'N/A' }}
                    @else
                      N/A
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>

          @if ($displayOption == 1)
            {{-- use simple output --}}

            <div class="flex justify-center space-x-4 mt-4">
              @if ($empodats->onFirstPage())
                <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
                  Previous
                </span>
              @else
                <a href="{{ $empodats->previousPageUrl() }}"
                  class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
                  Previous
                </a>
              @endif

              @if ($empodats->hasMorePages())
                <a href="{{ $empodats->nextPageUrl() }}"
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
            {{ $empodats->links('pagination::tailwind') }}
          @endif



          <!-- Include the Empodat modal component -->
          <x-empodat-modal />

        @push('scripts')
          <script>
            // Make the route available to the empodatModal component
            window.empodatRoutes = {
              show: "{{ route('codsearch.show', ':id') }}"
            };
          </script>
        @endpush

            {{-- end of main div --}}
          </div>
        </div>
      </div>
    </div>

</x-app-layout>
