<x-app-layout>
  <x-slot name="header">
    @include('passive.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg rounded-0">
        
        {{-- {!! dump($request) !!} --}}
        <!-- Main Search form -->
        <form  name="searchIndoor" id="searchIndoor" action="{{route('passive.search.search')}}" method="GET">
          
          <div class="p-4 text-gray-900 grid grid-cols-1 gap-4">
            <!-- Main Search form -->
            
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
                      Matrix criteria:
                    </div>
                    @include('_t.form-apline-multiselect', [
                    'tag' => 'matrixSearch', 'list' => $matrixList,
                    'active_ids' => isset($request->matrixSearch) ? $request->matrixSearch : [],
                    ])
                  </div>
                </div>
              </div>
            </div>
            
            
            <div id="searchYear" class="bg-gray-100 p-2 opacity-50 pointer-events-none">
              <div class="font-bold mb-2">
                Year:
              </div>
              <div class="w-full">
                <div class="grid grid-cols-2 gap-1">
                  <input type="number" name="year_from" value="{{ isset($request->year_from) ? $request->year_from : null }}" class="form-text" placeholder="year from" disabled>
                  <input type="number" name="year_to" value="{{ isset($request->year_to) ? $request->year_to : null }}" class="form-text" placeholder="year to" disabled>
                </div>
              </div>
            </div>
            
            <div id="searchSubstance">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Substance criteria:
                </div>
                <div>
                  @livewire('backend.substance-search', ['existingSubstances' => $request->substances])
                </div>
              </div>
            </div>
            
            
            <!-- Main Search form -->
            <div class="flex justify-end m-2">
              <a href="{{route('passive.search.filter')}}" class="btn-clear mx-2"> Reset </a>
              <button type="submit" class="btn-submit"> Search
              </button>
            </div>
            
            
            <div class="m-2">
              <ul class="list-disc list-inside text-gray-700 text-sm">
                <li>All search criteria are optional. If you do not select any criteria, all data will be displayed.</li>
                <li>Each time the search is executed, the search options are recorded in the database for future reference and performance improvements.</li>
                <li>We encourage users to register for a free account to save-&-view their search criteria and results.</li>
              </ul>
            </div>
            
            
          </div>
        </div>
        
      </form>
    </div>
  </div>
</div>
</x-app-layout>
