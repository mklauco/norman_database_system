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
                <th>Original ID</th>
                @role('super_admin')
                <th>Substance <i class="fas fa-lock ml-2"></i></th>
                @endrole
                <th>CAS</th>
                <th>Name</th>
                <th>Emissions</th>
                <th>Correct</th>
                <th>Score 1</th>
                <th>Score 2</th>
                <th>Score 3</th>
                <th>Score 4</th>
                <th>Score 5</th>
                <th>Created</th>
                <th>Updated</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($resultsObjects as $e)
              <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                <td class="p-1 text-center">{{ $e->id }}</td>
                <td class="p-1 text-center">{{ $e->pri_id }}</td>
                @role('super_admin')
                <td class="p-1">
                  @if($e->substance_id)
                    {{ $e->substance->name ?? 'N/A' }}
                  @else
                    <span class="text-gray-400">not matched</span>
                  @endif
                </td>
                @endrole
                <td class="p-1 text-center">{{ $e->pri_cas }}</td>
                <td class="p-1">{{ $e->pri_name }}</td>
                <td class="p-1">{{ $e->pri_emissions }}</td>
                <td class="p-1">{{ $e->pri_correct }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_score1, 2) }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_score2, 2) }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_score3, 2) }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_score4, 2) }}</td>
                <td class="p-1 text-right">{{ number_format($e->pri_score5, 2) }}</td>
                <td class="p-1 text-center">{{ $e->created_at->format('Y-m-d') }}</td>
                <td class="p-1 text-center">{{ $e->updated_at->format('Y-m-d') }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
          

          {{$resultsObjects->links('pagination::tailwind')}}
          
          {{-- end of main div --}}
        </div>
      </div>
    </div>
  </div>
  
</x-app-layout>