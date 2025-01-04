<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg rounded-0">
        
        {!! dump($request) !!}
        <!-- Main Search form -->
        <form  name="searchEmpodat" id="searchEmpodat" action="{{route('codsearch.search')}}" method="GET">
          
          <div class="p-4 text-gray-900 grid grid-cols-1 gap-4">
            <!-- Main Search form -->
            <form  name="searchEmpodat" id="searchEmpodat" action="{{route('codsearch.search')}}" method="GET">
              <div class="grid grid-cols-1 gap-5">
                
                <div id="searchOptions" class="pointer-events-none opacity-50">
                  <div class="bg-gray-100 p-2">
                    <div class="font-bold mb-2">
                      Search options:
                    </div>
                    <div class="flex items-center space-x-4">
                      <label class="inline-flex items-center">
                        <input type="radio" class="form-radio text-indigo-600" name="searchOption" value="option1">
                        <span class="ml-2"><strong>AND</strong> condition to all criteria</span>
                      </label>
                      <label class="inline-flex items-center">
                        <input type="radio" class="form-radio text-indigo-600" name="searchOption" value="option2">
                        <span class="ml-2"><strong>OR</strong> conditions to all criteria</span>
                      </label>
                    </div>
                  </div>
                </div>
                
              </div>
              
              <div id="displayOptions">
                <div class="bg-gray-100 p-2">
                  <div class="font-bold mb-2">
                    Display options:
                  </div>
                  <div class="flex items-center space-x-4">
                    <label class="inline-flex items-center">
                      <input 
                      type="radio" 
                      class="form-radio text-indigo-600" 
                      name="displayOption" 
                      value="1" 
                      @if (request('displayOption', '1') == 1) checked @endif
                      >
                      <span class="ml-2">Fast data preview</span>
                    </label>
                    <label class="inline-flex items-center">
                      <input 
                      type="radio" 
                      class="form-radio text-indigo-600" 
                      name="displayOption" 
                      value="0" 
                      @if (request('displayOption', '1') === '0') checked @endif
                      >
                      <span class="ml-2">Data output with page links (might be slow)</span>
                    </label>
                  </div>
                </div>
              </div>
              
              <div id="searchGeography">
                <div class="bg-gray-100 p-2">
                  
                  <div class="flex">
                    <div class="w-full">
                      <div class="font-bold mb-2">
                        Geography criteria:
                      </div>
                      @include('_t.form-apline-multiselect', [
                      'tag' => 'countrySearch', 'list' => $countryList,
                      'active_ids' => isset($request->countrySearch) ? $request->countrySearch : [],
                      ])
                    </div>
                    
                    <div class="w-full">
                      <div class="font-bold mb-2">
                        Ecosystem criteria:
                      </div>
                      @include('_t.form-apline-multiselect', [
                      'tag' => 'matrixSearch', 'list' => $matrixList,
                      'active_ids' => isset($request->matrixSearch) ? $request->matrixSearch : [],
                      ])
                    </div>
                  </div>
                  {{-- <div class="flex">
                    <div class="w-full">
                      <div class="font-bold mb-2">
                        Sampling station:
                      </div>
                      @include('_t.form-apline-multiselect', [
                      'tag' => 'matrixSearchx', 'list' => $matrixList,
                      'active_ids' => isset($request->matrixSearch) ? $request->matrixSearch : [],
                      ])
                    </div>
                  </div> --}}
                  
                </div>
              </div>
              
              <div id="searchSubstance">
                <div class="bg-gray-100 p-2">
                  <div class="font-bold mb-2">
                    Substance criteria:
                  </div>
                  <div>
                    @livewire('empodat.substance-search', ['existingSubstances' => $request->substances])
                  </div>
                </div>
              </div>
              
              <div id="searchSource" class="">
                <div class="bg-gray-100 p-2">
                  <div class="font-bold mb-2">
                    Source criteria:
                  </div>
                  <div class="w-full">
                    @include('_t.form-apline-multiselect', [
                    'tag' => 'sourceSearch', 'list' => $sourceList,
                    'active_ids' => isset($request->sourceSearch) ? $request->sourceSearch : [],
                    ])
                  </div>
                </div>
              </div>
              
              <div id="searchCategory" class="">
                <div class="bg-gray-100 p-2">
                  <div class="font-bold mb-2">
                    Search Category:
                  </div>
                  <div class="grid grid-cols-3 gap-1">
                    <input type="hidden" value="1" name="search">
                    @foreach ($categories as $category)
                    <div class="block p-1">
                      <span>
                        <input type="checkbox" name="categoriesSearch[]" value="{{$category->id}}"
                        @if (is_array(request('categoriesSearch')) && in_array($category->id, request('categoriesSearch'))) checked @endif
                        >
                      </span>
                      <span class="ml-1">
                        {{-- remove space before parentheses, and ensure non-breakable space before parentheses --}}
                        {!! preg_replace('/\s*\(/', '&nbsp;(', $category->name_abbreviation, 1) !!}
                      </span>
                    </div>
                    @endforeach
                  </div>
                </div>
              </div>
              
              <div id="searchYear">
                <div class="bg-gray-100 p-2">
                  <div class="font-bold mb-2">
                    Year:
                  </div>
                  <div class="w-full">
                    <div class="grid grid-cols-2 gap-1">
                      <input type="number" name="year_from" value="{{ isset($request->year_from) ? $request->year_from : null }}" class="form-text" placeholder="year from">
                      <input type="number" name="year_to" value="{{ isset($request->year_to) ? $request->year_to : null }}" class="form-text" placeholder="year to">
                    </div>
                  </div>
                </div>
              </div>
              
              <div>
                SEARCH based on: list_concentration_indicators
              </div>

              <div id="typeDataSourcesSearch" class="">
                <div class="flex bg-gray-100 p-2">
                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Type of data source:
                    </div>
                    @include('_t.form-apline-multiselect', [
                    'tag' => 'typeDataSourcesSearch', 'list' => $typeDataSourcesList,
                    'active_ids' => isset($request->typeDataSourcesSearch) ? $request->typeDataSourcesSearch : [],
                    ])
                  </div>
                  
                  <div class="w-full pointer-events-none opacity-50">
                    <div class="font-bold mb-2">
                      Organisation:
                    </div>
                    @include('_t.form-apline-multiselect', [
                    'tag' => 'organisationList', 'list' => $matrixList,
                    'active_ids' => isset($request->matrixSearch) ? $request->matrixSearch : [],
                    ])
                  </div>
                </div>
              </div>
              
              <div id="searchLaboratory" class="pointer-events-none opacity-50">
                <div class="flex bg-gray-100 p-2">
                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Laboratory:
                    </div>
                    @include('_t.form-apline-multiselect', [
                    'tag' => 'laboratoryList', 'list' => $sourceList,
                    'active_ids' => isset($request->matrixSearch) ? $request->matrixSearch : [],
                    ])
                  </div>
                </div>
              </div>
              
              {{-- <div id="searchQaQc" class="">
                <div class="flex bg-gray-100 p-2">
                  <div class="w-full">
                    <span>Limit of Detection (LoD) [µg/m3, µg/l or µg/kg] concentration_indicator_id = 2</span>
                    0. JOIN empodat_main.method_id == empodat_analytical_methods.id
                    1. empodat_analytical_methods.lod > ?
                    @include('_t.form-select', ['tag' => 'concentration_data', 'space' => 'empodat', 'list' => $getEqualitySigns])
                    @include('_t.form-text', ['tag' => 'concentration_data', 'space' => 'empodat'])
                  </div>
                  <div class="w-full">
                    <span>Limit of Quantification (LoQ) [µg/m3, µg/l or µg/kg] concentration_indicator_id = 3</span>
                    0. JOIN empodat_main.method_id == empodat_analytical_methods.id
                    1. empodat_analytical_methods.loq > ?
                    @include('_t.form-select', ['tag' => 'concentration_data', 'space' => 'empodat', 'list' => $getEqualitySigns])
                    @include('_t.form-text', ['tag' => 'concentration_data', 'space' => 'empodat'])
                  </div>
                </div>
              </div> --}}
              
              <div id="searchAnalyticaMethod" class="pointer-events-none opacity-50">
                <div class="flex bg-gray-100 p-2">
                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Analytical method:
                      TABLE: list_analytical_methods
                    </div>
                    @include('_t.form-apline-multiselect', [
                    'tag' => 'analyticalMethodList', 'list' => $sourceList,
                    'active_ids' => isset($request->matrixSearch) ? $request->matrixSearch : [],
                    ])
                  </div>
                </div>
              </div>
              
              <div id="searchQualityInformationCategory" class="pointer-events-none opacity-50">
                <div class="flex bg-gray-100 p-2">
                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Quality information category:
                      TABLE: list_quality_empodat_analytical_methods
                    </div>
                    @include('_t.form-apline-multiselect', [
                    'tag' => 'qualityInformationCategoryList', 'list' => $sourceList,
                    'active_ids' => isset($request->matrixSearch) ? $request->matrixSearch : [],
                    ])
                  </div>
                </div>
              </div>
              
              
              
              <!-- Main Search form -->
              <div class="flex justify-end m-2">
                <a href="{{route('codsearch.filter')}}" class="btn-clear mx-2"> Reset </a>
                <button type="submit" class="btn-submit"> Search
                </button>
              </div>
              
            </div>    
          </div>

        </form>  
      </div>
    </div>
  </div>
</x-app-layout>