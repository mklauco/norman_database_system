<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  
  
  <div class="py-4"  wire:ignore>
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          {{-- main div --}}
          
          <a href="{{ route('codsearch.filter', [
            'countrySearch'                   => $countrySearch,
            'matrixSearch'                    => $matrixSearch,
            'sourceSearch'                    => $sourceSearch,
            'year_from'                       => $year_from ?? '',
            'year_to'                         => $year_to ?? '',
            'displayOption'                   => $displayOption,
            'substances'                      => $substances,
            'categoriesSearch'                => $categoriesSearch,
            'typeDataSourcesSearch'           => $typeDataSourcesSearch,
            'concentrationIndicatorSearch'    => $concentrationIndicatorSearch,
            'analyticalMethodSearch'          => $analyticalMethodSearch,
            'dataSourceLaboratorySearch'      => $dataSourceLaboratorySearch,
            'dataSourceOrganisationSearch'      => $dataSourceOrganisationSearch,
          ]) }}">
          <button type="submit" class="btn-submit">Refine Search</button>
        </a>
        
        <div class="text-gray-600 flex">
          @if($displayOption == 1)
          {{-- use simple output --}}
          <div class="flex">Number of matched records:&nbsp;@livewire('empodat.query-counter', ['queryId' => $query_log_id]) &nbsp;of&nbsp;<span> {{number_format($empodatsCount, 0, " ", " ") }}</span> </div>.
          {{-- <livewire:empodat.query-counter :queryId="$query_log_id" /> --}}
          @else
          {{-- use advanced output --}}
          <span>Number of matched records: </span><span class="font-bold">{{$empodats->total() ?? ''}}</span> of <span>{{number_format($empodatsCount, 0, " ", " ") }}</span>.
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
            <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
              <td class="p-1 text-center">
                {{ $e->id }}
              </td>
              <td class="p-1 text-center">
                {{ $e->substance_name }}
                @role('super_admin')
                <span class="text-xss text-gray-500"> ({{ $e->substance_id }})</span>
                @endrole
              </td>
              <td class="p-1 text-center">
                @if($e->concentration_indicator_id == 0) {{ $e->concentration_indicator_id }} @endif
                @if($e->concentration_indicator_id > 1)
                {{ $e->concetrationIndicator->name }}
                @else
                {{ $e->concentration_value }}
                @endif
              </td>
              <td class="p-1 text-center">
                {{ $e->matrix_name }}
              </td>
              <td class="p-1 text-center">
                {{ $e->country }}
              </td> 
              <td class="p-1 text-center">
                {{ $e->sampling_date_year }}
              </td>  
              <td class="p-1 text-center">
                {{ $e->station_name }}
              </td>     
            </tr>
            @endforeach
          </tbody>
        </table>
        
        @if($displayOption == 1)
        {{-- use simple output --}}
        
        <div class="flex justify-center space-x-4 mt-4">
          @if ($empodats->onFirstPage())
          <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
            Previous
          </span>
          @else
          <a href="{{ $empodats->previousPageUrl() }}" class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
            Previous
          </a>
          @endif
          
          @if ($empodats->hasMorePages())
          <a href="{{ $empodats->nextPageUrl() }}" class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
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
        {{$empodats->links('pagination::tailwind')}}
        @endif
        
        {{-- end of main div --}}
      </div>
    </div>
  </div>
</div>



</x-app-layout>
