<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          {{-- Search/Filter Section --}}
          @if(isset($request))
            <div class="mb-6">
              <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <form action="{{route('substances.search')}}" method="GET">
                  
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
                      <div>
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
                      
                      <div>
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
                      
                      <a href="{{route('substances.filter')}}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
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
          @endif
          
          {{-- Results Section --}}
          <div id="displaySubstancesDiv">
            @include('susdat.display-substances', [
              'show' => [
                'substances' => true, 
                'sources' => true, 
                'duplicates' => false
              ]
            ])
          </div>
          
        </div>        
      </div>
    </div>
  </div>
</x-app-layout>