<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          {{-- Search Form --}}
          <form action="{{route('duplicates.filter')}}" method="GET" class="mb-6">
            <div class="flex space-x-4 items-end bg-gray-50 p-4 rounded-lg">
              <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Search for duplicates by:
                </label>
                <select name="pivot_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                  <option value="">Select a field...</option>
                  @foreach($getPivotableColumns as $column)
                    <option value="{{$column}}" {{ (isset($pivot_id) && $pivot_id == $column) ? 'selected' : '' }}>
                      {{ $columnLabels[$column] ?? ucfirst(str_replace('_', ' ', $column)) }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div>
                <button type="submit" class="btn-submit">Search Duplicates</button>
              </div>
            </div>
          </form>
          
          {{-- Results Section --}}
          @if (isset($duplicates) && $duplicates->count() > 0)
            {{-- Summary --}}
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
              <span class="font-medium text-blue-800">
                Found {{ $totalDuplicateGroups }} duplicate groups ({{ $totalDuplicateRecords }} total records) for "{{ $columnLabels[$pivot] ?? ucfirst(str_replace('_', ' ', $pivot)) }}"
              </span>
            </div>

            {{-- Duplicates Table --}}
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="px-4 py-2">{{ $columnLabels[$pivot] ?? ucfirst(str_replace('_', ' ', $pivot)) }}</th>
                  <th class="px-4 py-2">Count</th>
                  <th class="px-4 py-2">Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($duplicates as $duplicate)
                <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="px-4 py-2">
                    @if (empty($duplicate->$pivot))
                      <span class="text-red-500 font-medium">{{ $columnLabels[$pivot] ?? ucfirst(str_replace('_', ' ', $pivot)) }} not assigned</span>
                    @else
                      <span class="font-medium">{{ $duplicate->$pivot }}</span>
                    @endif
                  </td>
                  <td class="px-4 py-2 text-center">
                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                      @if($duplicate->count > 5) bg-red-100 text-red-800 
                      @elseif($duplicate->count > 2) bg-yellow-100 text-yellow-800 
                      @else bg-gray-100 text-gray-800 @endif">
                      {{ $duplicate->count }}
                    </span>
                  </td>
                  <td class="px-4 py-2 text-center">
                    @if (!empty($duplicate->$pivot))
                      <a href="{{route('duplicates.records', ['pivot' => $pivot, 'pivot_value' => $duplicate->$pivot])}}" 
                         class="link-edit">
                        View Records
                      </a>
                    @else
                      <span class="text-gray-400">-</span>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>

          @elseif(isset($duplicates) && $duplicates->count() == 0)
            {{-- No Duplicates Found --}}
            <div class="text-center py-8">
              <div class="text-green-600">
                <span class="font-medium">✓ No duplicates found for "{{ $columnLabels[$pivot] ?? ucfirst(str_replace('_', ' ', $pivot)) }}"</span>
                <p class="text-sm text-gray-500 mt-1">Your data is clean for this field!</p>
              </div>
            </div>

          @else
            {{-- Initial State --}}
            <div class="text-center py-12">
              <div class="text-gray-500">
                <span class="font-medium">Select a field above to search for duplicates</span>
              </div>
            </div>
          @endif
          
        </div>        
      </div>
    </div>
  </div>
</x-app-layout>