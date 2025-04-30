<x-app-layout>
  <x-slot name="header">
    @include('prioritisation.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg" >
        <div class="p-6 text-gray-900">
          {{-- main div --}}
          
          {{-- <a href="{{ route('prioritisation.monitoring-scarce.filter', [])}}">
            <button type="submit" class="btn-submit">Refine Search</button>
          </a> --}}
          
          <div class="text-gray-600 flex border-l-2 border-white">
            
            {{-- Filters or whatwever... --}}
            
          </div>
          
          <div class="text-gray-600 flex border-l-2 border-white">
            {{-- display search parameters if not shown in directly in filters --}}
            {{-- Search parameters:&nbsp;
            <span class="font-semibold">
              @foreach ($searchParameters as $key => $value)
              @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
              @foreach ($value as $item)
              {{ $item }}@if(!$loop->last), @endif
              @endforeach
              @else
              {{ $value }}
              @endif @if(!$loop->last); @endif
              @endforeach
            </span> --}}
          </div>
          
          {{-- display the data --}}
          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
                <th>ID</th>
                <th>Use for Priority</th>
                <th>Substance</th>
                <th>CAS No.</th>
                <th>No. Sites</th>
                <th>No. Sites (MEC>PNEC)</th>
                <th>MEC 95th</th>
                <th>MEC Site Max</th>
                <th>LOQ Min</th>
                <th>Category</th>
                <th>Lowest PNEC</th>
                <th>PNEC Type</th>
                <th>Reference PNEC</th>
                <th>Max Exceedance</th>
                <th>Extent of Exceedence</th>
                <th>Score EOE</th>
                <th>Score FOE</th>
                <th>Total Score</th>
                <th>LOQ Exceedance</th>
                <th>Substance New</th>
                <th>No. Sites MEC>PNEC</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($resultsObjects as $e)
              <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                <td class="p-1 text-center">{{ $e->pri_nr }}</td>
                <td class="p-1 text-center">{{ $e->pri_use_for_priority_list }}</td>
                <td class="p-1">{{ $e->pri_substance }}</td>
                <td class="p-1 text-center">{{ $e->pri_cas_no }}</td>
                <td class="p-1 text-center">{{ $e->pri_no_sites_new }}</td>
                <td class="p-1 text-center">{{ $e->pri_no_sites_where_mecsite_pnec_new }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_mec95_new, 6) }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_mecsite_max_new, 6) }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_loq_min, 6) }}</td>
                <td class="p-1 text-center">{{ $e->pri_cat }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_lowest_pnec, 6) }}</td>
                <td class="p-1 text-center">{{ $e->pri_pnec_type }}</td>
                <td class="p-1">{{ $e->pri_reference_pnec }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_max_exceedance, 6) }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_extent_of_exceedence, 6) }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_score_eoe, 2) }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_score_foe, 2) }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_score_total, 2) }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_loq_exceedance, 6) }}</td>
                <td class="p-1">{{ $e->pri_substance_new }}</td>
                <td class="p-1 text-center">{{ $e->pri_no_of_sites_mecsite_pnec_new }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
          
          {{-- @if($displayOption == 1)
          
          <div class="flex justify-center space-x-4 mt-4">
            @if ($resultsObjects->onFirstPage())
            <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
              Previous
            </span>
            @else
            <a href="{{ $resultsObjects->previousPageUrl() }}" class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
              Previous
            </a>
            @endif
            
            @if ($resultsObjects->hasMorePages())
            <a href="{{ $resultsObjects->nextPageUrl() }}" class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
              Next
            </a>
            @else
            <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
              Next
            </span>
            @endif
          </div>
          @else
          {{$resultsObjects->links('pagination::tailwind')}}
          @endif --}}
          
          {{-- end of main div --}}
        </div>
      </div>
    </div>
  </div>
  
</x-app-layout>