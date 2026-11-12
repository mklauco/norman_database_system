<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          {{-- Header with Merge History Link --}}
          <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <h2 class="text-xl font-semibold text-gray-800">Duplicate Management</h2>
            <a href="{{ route('duplicates.mergeHistory') }}" 
               class="btn-submit flex items-center space-x-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              <span>View Merge History</span>
            </a>
          </div>
          
          {{-- Search Form --}}
          <form action="{{route('duplicates.filter')}}" method="GET" class="mb-6">
            <div class="flex flex-wrap gap-4 items-end bg-gray-50 p-4 rounded-lg">
              <div class="flex-1 min-w-48">
                <label for="pivot_id" class="block text-sm font-medium text-gray-700 mb-2">
                  Search for duplicates by:
                </label>
                <select name="pivot_id" id="pivot_id" class="form-select">
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
            <div class="mb-4 p-3 bg-lime-50 border border-lime-200 text-lime-900 rounded-sm">
              <span class="font-medium">
                Found {{ \App\Services\Susdat\SusdatDisplay::count($totalDuplicateGroups) }} duplicate groups ({{ \App\Services\Susdat\SusdatDisplay::count($totalDuplicateRecords) }} total records) for "{{ $columnLabels[$pivot] ?? ucfirst(str_replace('_', ' ', $pivot)) }}"
              </span>
            </div>

            {{-- Duplicates Table --}}
            <div class="overflow-x-auto">
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
                    <span class="inline-block rounded-sm px-2 py-0.5 text-xs font-medium
                      @if($duplicate->count > 5) bg-red-100 text-red-800
                      @elseif($duplicate->count > 2) bg-amber-100 text-amber-800
                      @else bg-slate-100 text-slate-800 @endif">
                      {{ \App\Services\Susdat\SusdatDisplay::count($duplicate->count) }}
                    </span>
                  </td>
                  <td class="px-4 py-2 text-center">
                    @if (!empty($duplicate->$pivot))
                      <a href="{{route('duplicates.records', ['pivot' => $pivot, 'pivot_value' => $duplicate->$pivot])}}" 
                         class="link-lime-text">
                        View Records
                      </a>
                    @else
                      <span class="text-gray-500">{{ \App\Services\Susdat\SusdatDisplay::EMPTY }}</span>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
            </div>

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