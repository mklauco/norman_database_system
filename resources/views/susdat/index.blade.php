<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          {{-- Results Summary --}}
          <div class="text-gray-600 flex border-l-2 border-white mb-4">
            <div class="py-2">
              Number of matched records:
            </div>
            <div class="py-2 mx-1 font-bold">
              {{ number_format($substances->total(), 0, ".", " ") }}
            </div>
            <div class="py-2">
              of <span>{{ number_format($substancesCount, 0, " ", " ") }}
                @if (is_numeric($substances->total()))
                  @if ($substances->total()/$substancesCount*100 < 0.01)
                    which is &le; 0.01% of total records.
                  @else
                    which is {{ number_format($substances->total()/$substancesCount*100, 3, ".", " ") }}% of total records.
                  @endif
                @endif
              </span>
            </div>
          </div>
          
          {{-- Search Parameters Display --}}
          @if(!empty($searchParameters))
            <div class="text-gray-600 border-l-2 border-white mb-4 p-3 bg-gray-50 rounded-r-lg">
              <div class="font-medium text-gray-700 mb-2">Search parameters:</div>
              <div class="space-y-2">
                @foreach ($searchParameters as $key => $value)
                  <div class="flex flex-wrap items-start" data-search-parameter="{{ strtolower($key) }}">
                    <span class="font-semibold text-gray-800 mr-2">{{ $key }}:</span>
                    <div class="flex-1">
                      @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
                        <span data-parameter-items>
                          @foreach ($value as $item)
                            <span data-parameter-item class="inline-block bg-white border border-gray-200 rounded-sm px-2 py-1 text-xs mr-1 mb-1 text-gray-700">{{ $item }}</span>
                          @endforeach
                        </span>
                      @else
                        <span class="inline-block bg-white border border-gray-200 rounded-sm px-2 py-1 text-xs text-gray-700">{{ $value }}</span>
                      @endif
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          @endif
          
          {{-- Search/Filter Section --}}
          @if($request->input('searchCategory') == 1 || $request->input('searchSource') == 1 || $request->input('searchSubstance') == 1)
            <div class="mb-6">
              <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <form action="{{route('substances.search.search')}}" method="GET">
                  
                  <div class="flex flex-wrap gap-4 items-end">
                    {{-- Search Type Specific Controls --}}
                    @if($request->input('searchCategory') == 1)
                      <div class="flex-1 min-w-64">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Search by Category:
                        </label>
                        <input type="hidden" value="1" name="searchCategory">
                        @include('_t.form-apline-multiselect', [
                          'tag' => 'categoriesSearch', 
                          'list' => $categoriesList, 
                          'active_ids' => $activeCategoryids, 
                          'label' => 'Category', 
                          'space' => 'request'
                        ])
                      </div>
                      
                    @elseif($request->input('searchSource') == 1)
                      <div class="flex-1 min-w-64">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Search by Source:
                        </label>
                        <input type="hidden" value="1" name="searchSource">
                        @include('_t.form-apline-multiselect', [
                          'tag' => 'sourcesSearch', 
                          'list' => $sourceList, 
                          'active_ids' => $activeSourceids, 
                          'label' => 'Source', 
                          'space' => 'request'
                        ])
                      </div>
                    @endif
                    
                    {{-- Ordering Controls --}}
                    <div class="flex gap-3">
                      <div class="min-w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Order by:
                        </label>
                        @include('_t.form-select', [
                          'tag' => 'order_by_column', 
                          'list' => $columns, 
                          'label' => 'Column', 
                          'space' => 'filter'
                        ])
                      </div>
                      
                      <div class="min-w-24">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Direction:
                        </label>
                        @include('_t.form-select', [
                          'tag' => 'order_by_direction', 
                          'list' => $orderByDirection, 
                          'label' => 'Direction', 
                          'space' => 'filter'
                        ])
                      </div>
                    </div>
                    
                    {{-- Action Buttons --}}
                    <div class="flex gap-2">
                      <button type="submit" class="btn-submit flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"></path>
                        </svg>
                        <span>Apply Filter</span>
                      </button>
                      
                      @if(isset($query_log_id) && Auth::check())
                        <a href="{{ route('susdat.csv.start', ['query_log_id' => $query_log_id]) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-xs text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                          </svg>
                          Download CSV
                        </a>
                      @endif
                      
                      <a href="{{route('substances.search.filter')}}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-xs text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Clear
                      </a>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          @else
            {{-- Ordering and Download for List of All Substances (no active search) --}}
            <div class="mb-6">
              <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <form action="{{route('substances.index')}}" method="GET">
                  <div class="flex flex-wrap gap-4 items-end">
                    {{-- Ordering Controls --}}
                    <div class="flex gap-3">
                      <div class="min-w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Order by:
                        </label>
                        @include('_t.form-select', [
                          'tag' => 'order_by_column',
                          'list' => $columns,
                          'label' => 'Column',
                          'space' => 'filter'
                        ])
                      </div>

                      <div class="min-w-24">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                          Direction:
                        </label>
                        @include('_t.form-select', [
                          'tag' => 'order_by_direction',
                          'list' => $orderByDirection,
                          'label' => 'Direction',
                          'space' => 'filter'
                        ])
                      </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex gap-2">
                      <button type="submit" class="btn-submit flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"></path>
                        </svg>
                        <span>Apply Filter</span>
                      </button>

                      @if(isset($query_log_id) && Auth::check())
                        <a href="{{ route('susdat.csv.start', ['query_log_id' => $query_log_id]) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-xs text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                          </svg>
                          Download CSV
                        </a>
                      @endif
                    </div>
                  </div>
                </form>
              </div>
            </div>
          @endif

          {{-- Results Section --}}
          <div id="displaySubstancesDiv">
            @include('susdat.display-substances', [
              'show' => [
                'duplicates' => false
              ]
            ])
          </div>
          
        </div>        
      </div>
    </div>
  </div>
</x-app-layout>