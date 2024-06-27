<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          
          @if(isset($request))
          <div class="bg-gray-100 p-2">
            <form action="{{route('substances.search')}}" method="GET">
              
              <div class="flex space-x-2 items-center">
                @if($request->input('searchCategory') == 1)
                <div>
                  <span class="text-sm font-bold">Search Category:</span>
                </div>
                <div id="select2">
                  <input type="hidden" value="1" name="searchCategory">
                  @include('_t.form-apline-multiselect', ['tag' => 'categoriesSearch', 'list' => $categoriesList, 'active_ids' => $activeCategoryids, 'label' => 'Category', 'space' => 'request'])
                </div>
                @elseif($request->input('searchSource') == 1)
                <div>
                  <span class="text-sm font-bold">Search Source:</span>
                </div>
                <div id="select2">
                  <input type="hidden" value="1" name="searchSource">
                  @include('_t.form-apline-multiselect', ['tag' => 'sourcesSearch', 'list' => $sourceList, 'active_ids' => $activeSourceids, 'label' => 'Category', 'space' => 'request'])
                </div>
                @else
                {{-- <div class="">
                  <span class="text-sm font-bold">List of specific substances |</span>
                </div> --}}
                @endif
                <div>
                  <span class="text-sm font-bold">Order by:</span>
                </div>
                <div>
                  @include('_t.form-select', ['tag' => 'order_by_column', 'list' => $columns, 'label' => 'Category', 'space' => 'filter'])
                </div>
                <div>
                  @include('_t.form-select', ['tag' => 'order_by_direction', 'list' => $orderByDirection, 'label' => 'Category', 'space' => 'filter'])
                </div>
                
                <div id="submit_button">
                  <button type="submit" class="btn-submit"> Apply Filter 
                  </button>
                </div>
                <div id="submit_button">
                  <a href="{{route('substances.filter')}}" class="btn-submit"> Cancel Search
                  </a>
                </div>
              </div>
            </form>
          </div>
          @endif
          
          <div id="displaySubstancesDiv">
            @include('susdat.display-substances')
          </div>
          
        </div>        
      </div>
    </div>
  </div>
</x-app-layout>
{{-- href="https://comptox.epa.gov/dashboard/dsstoxdb/results?&search=DTXSID0032601" --}}