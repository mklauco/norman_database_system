<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
        
        <div class="p-6 text-gray-900">
          
          
          
          <!-- Main Search form -->
          <div class="grid grid-cols-1 gap-5">
            
            <div id="searchOptions">
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
            
            <div id="searchGeography">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Geography criteria:
                </div>
                
                <div class="flex">
                  <div class="w-full">
                    @include('_t.form-apline-multiselect', [
                    'tag' => 'countrySearch', 'list' => $countryList,
                    'active_ids' => isset($request->countrySearch) ? $request->countrySearch : [],
                    ])
                  </div>
                  
                  <div class="w-full">
                    @include('_t.form-apline-multiselect', [
                    'tag' => 'ecosystemSearch', 'list' => $ecosystemSearch,
                    'active_ids' => isset($request->ecosystemSearch) ? $request->ecosystemSearch : [],
                    ])
                  </div>
                </div>
                
              </div>
            </div>
            
            <div id="searchSubstance">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Substance criteria:
                </div>
                
              </div>
            </div>
            
            <div id="searchSource">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Source criteria:
                </div>
                <div class="w-full">
                  @include('_t.form-apline-multiselect', [
                  'tag' => 'sourcesList', 'list' => $sourcesList,
                  'active_ids' => isset($request->ecosystemSearch) ? $request->ecosystemSearch : [],
                  ])
                </div>
              </div>
            </div>
            
            <div id="searchCategory">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Search Category:
                </div>
                <div>
                  <input type="hidden" value="1" name="search">
                  @foreach ($categories as $category)
                  <div class="block p-1">
                    <span>
                      <input type="checkbox" name="categoriesSearch[]" value="{{$category->id}}">
                    </span>
                    <span class="ml-1">
                      {{$category->name}} 
                    </span>
                  </div>
                  @endforeach
                </div>
              </div>
            </div>
            
            <div id="searchSource">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Year:
                </div>
                <div class="w-full">
                  <div class="grid grid-cols-2 gap-1">
                    <input type="number" name="year_from" value="" class="form-text">
                    <input type="number" name="year_to" value="" class="form-text">
                  </div>
                </div>
                <div class="flex">
                  <div class="w-full">
                    <span>Concentration data:</span>
                    @include('_t.form-select', ['tag' => 'concentration_data', 'space' => 'empodat', 'list' => $selectList])
                  </div>
                  <div class="w-full">
                    <span>Concentration equal or higher than:</span>
                    @include('_t.form-select', ['tag' => 'concentration_data', 'space' => 'empodat', 'list' => $selectList])
                  </div>
                </div>
              </div>
            </div>
            
            
            
            
            
            
            <!-- Main Search form -->
            
          </div>        
        </div>
      </div>
    </div>
  </x-app-layout>