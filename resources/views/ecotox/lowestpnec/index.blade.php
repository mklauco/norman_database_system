<x-app-layout>
  <x-slot name="header">
    @include('ecotox.header')
  </x-slot>
  
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg" >
        <div class="p-6 text-gray-900" x-data="recordsTable()">
          {{-- main div --}}
          
          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
                <th>ID</th>
                <th>Substance</th>
                <th>Lowest PNEC Freshwater [µg/l]</th>
                <th>Lowest PNEC Marine water [µg/l]</th>
                <th>Lowest PNEC Sediments [µg/kg dw]</th>
                <th>Lowest PNEC Biota (fish) [µg/kg ww]</th>
                <th>Lowest PNEC Marine biota (fish) [µg/kg ww]</th>
                <th>Lowest PNEC Biota (mollusc) [µg/kg ww]</th>
                <th>Lowest PNEC Marine biota (mollusc) [µg/kg ww]</th>
                <th>Lowest PNEC Biota (WFD) [µg/kg ww]</th>
                <th>Type</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($lowestPNECs as $pnec)
              <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                <td class="p-1 text-center">
                  <div class="">
                    {{ $pnec->substance?->prefixed_code ?? 'Unknown' }}
                  </div>
                </td>
                <td class="p-1 text-center">
                  {{ $pnec->substance?->name ?? 'Unknown' }}
                  @role('super_admin')
                  <span class="text-xss text-gray-500"> ({{ $pnec->substance_id }})</span>
                  @endrole
                </td>
                @for ($i = 1; $i <= 8; $i++)
                <td class="p-1 text-center">
                  <span class="font-medium">{{ $pnec->{'lowest_pnec_value_'.$i} }}</span>
                </td>
                @endfor
                <td class="p-1 text-center">{{ $pnec->lowest_exp_pred  }}
                  ({{ $pnec->lowest_exp_pred == 1? 'Experimental' : 'Predicted' }})
                </td>   
              </tr>
              @endforeach
            </tbody>
          </table>
          
          @if($displayOption == 1)
          {{-- use simple output --}}
          
          <div class="flex justify-center space-x-4 mt-4">
            @if ($lowestPNECs->onFirstPage())
            <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
              Previous
            </span>
            @else
            <a href="{{ $lowestPNECs->previousPageUrl() }}" class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
              Previous
            </a>
            @endif
            
            @if ($lowestPNECs->hasMorePages())
            <a href="{{ $lowestPNECs->nextPageUrl() }}" class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
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
          {{$lowestPNECs->links('pagination::tailwind')}}
          @endif
          
          <!-- The Modal (hidden by default) will be implemented later -->
          
          {{-- end of main div --}}
        </div>
      </div>
    </div>
  </div>
  

</x-app-layout>