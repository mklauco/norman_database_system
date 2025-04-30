<x-app-layout>
  <x-slot name="header">
    @include('MODULE_NAME.header')
  </x-slot>
  
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg" >
        <div class="p-6 text-gray-900">
          {{-- main div --}}
          
          <a href="{{ route('indoor.search.filter', [])}}">
            <button type="submit" class="btn-submit">Refine Search</button>
          </a>
          
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
                <th>Substance</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($resultsObjects as $e)
              <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                <td class="p-1 text-center">{{ $e->id }}</td>
                <td class="p-1 text-center">{{ $e->id }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
          
          @if($displayOption == 1)
          {{-- use simple output --}}
          
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
          {{-- use advanced output --}}
          {{$resultsObjects->links('pagination::tailwind')}}
          @endif
          
          {{-- end of main div --}}
        </div>
      </div>
    </div>
  </div>
  
</x-app-layout>
