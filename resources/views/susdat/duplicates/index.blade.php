<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  
  <div class="py-12">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          
          <form action="{{route('duplicates.filter')}}" method="GET">
            <div class="flex space-x-2 items-center">
              <div>
                <span class="text-sm font-bold mr-2">Search for duplicates according to:</span>
              </div>
              <div id="select2">
                @include('_t.form-select', ['tag' => 'pivot_id', 'list' => $getPivotableColumns, 'label' => 'Category', 'space' => 'filter'])
              </div>
              <button type="submit" class="btn-submit"> Search for duplicates
              </button>
            </div>
          </form>
          
          <div>
            {{-- @include('susdat.display-substances') --}}
            
            @if (isset($duplicates) && $duplicates->count() > 0)
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="px-4 py-2">{{$pivot}}</th>
                  <th class="px-4 py-2">Number of duplicates</th>
                  <!-- Add more columns as needed -->
                </tr>
              </thead>
              <tbody>
                @foreach ($duplicates as $duplicate)
                <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="border px-4 py-2">
                    @if (empty($duplicate->$pivot) || is_null($duplicate->$pivot))
                    <span class="text-red-500"><span class="font-bold">{{$pivot}}</span> not assigned</span>
                    @else
                    <a class="link-edit" href="{{route('duplicates.records', ['pivot' => $pivot, 'value' => $duplicate->$pivot])}}">{{$duplicate->$pivot}}</a>
                    @endif
                  </td>
                  <td class="border px-4 py-2">{{ $duplicate->count }}</td>
                  {{-- <td class="border px-4 py-2">{{ $duplicate->column3 }}</td> --}}
                  <!-- Add more columns as needed -->
                </tr>
                @endforeach
              </tbody>
            </table>
            @elseif(isset($duplicates) && $duplicates->count() == 0)
            <div class="text-red-500">
              <span>No duplicates found for:</span> <span class="font-bold">{{$pivot}}</span>
            </div>
            @else
            <span class="text-red-500">No duplicates found, please apply the search filter</span>
            @endif
            
          </div>
        </div>        
      </div>
    </div>
  </div>
</x-app-layout>
{{-- href="https://comptox.epa.gov/dashboard/dsstoxdb/results?&search=DTXSID0032601" --}}