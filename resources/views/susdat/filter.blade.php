<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
        
        <div class="p-6 text-gray-900">
          
          
          <!-- Main Search form -->
          <div class="grid grid-cols-3 gap-5">
            
            <div id="searchAccordingToCategoryForm">
              <div class="bg-gray-50 p-2">
                <form  name="searchAccordingToCategoryForm" id="searchAccordingToCategoryForm" action="{{route('substances.search')}}" method="GET">
                  <input type="hidden" value="1" name="searchCategory">
                  <div class="text-lg font-bold:">
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
                  
                  
                  <div class="flex justify-end m-2">
                    <button type="submit" class="btn-submit"> Apply Category Filter 
                    </button>
                  </div>
                </form>
              </div>
            </div>
            
            
            
            <div id="searchAccordingToSource">
              <div class="bg-gray-50 p-2">
                <div class="text-lg font-bold:">
                  Search according to source:
                </div>
                <form name="searchAccordingToSourceForm" id="searchAccordingToSourceForm" action="{{route('substances.search')}}" method="GET">
                  <input type="hidden" value="1" name="searchSource">
                  <div class="w-full">
                    @include('_t.form-apline-multiselect', [
                      'tag' => 'sourcesSearch', 'list' => $sourceList,
                      'active_ids' => isset($request->sourcesSearch) ? $request->sourcesSearch : [],
                      ])
                  </div>
                  
                  <div class="flex justify-end m-2">
                    <button type="submit" class="btn-submit"> Apply Source Filter</button>
                  </div>
                </form>
              </div>
              
            </div>
            
            <div id="searchSpecificSubstance">
              <div class="bg-gray-50 p-2">
                <div class="text-lg font-bold:">
                  Interactive search for a specific substance:
                </div>                  
                <div>
                  @livewire('susdat.substance-search')
                </div>
              </div>
            </div>              
            
            <!-- Main Search form -->
            
          </div>        
        </div>
      </div>
    </div>
  </x-app-layout>
  