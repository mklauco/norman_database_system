<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          {{-- main div --}}
          <div class="text-gray-600">
            <span>Number of matched records: </span><span class="font-bold">{{$empodats->total()}}</span> of <span>{{$empodatsCount}}</span>.
          </div>

          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
                <th>ID</th>
                <th>Substance</th>
                <th>Concentration</th>
                <th>Ecosystem/Matrix</th>
                <th>Country</th>
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
                </td>
                <td class="p-1 text-center">
                  {{ $e->concentration }}
                </td>
                <td class="p-1 text-center">
                  {{ $e->matrix_name }}
                </td>
                <td class="p-1 text-center">
                  {{ $e->country }}
                </td>  
                <td class="p-1 text-center">
                  {{ $e->station_name }}
                </td>     
              </tr>
              @endforeach
            </tbody>
          </table>
          {{$empodats->links('pagination::tailwind')}}
          {{-- end of main div --}}
        </div>
      </div>
    </div>
  </div>
  
  
  
</x-app-layout>
