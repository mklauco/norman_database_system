<x-app-layout>
  <x-slot name="header">
    @include('arbg.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg rounded-0">

        {{-- {!! dump($request) !!} --}}
        <!-- Main Search form -->
        <form  name="searchIndoor" id="searchIndoor" action="{{route('arbg.bacteria.search.search')}}" method="GET">

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
              {{-- <div class="bg-gray-100 p-2">
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
              </div> --}}
            </div>

            <div id="searchEnvironmentType">
              {{-- <div class="bg-gray-100 p-2">
                <div class="flex">
                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Environment Type criteria:
                    </div>
                    @include('_t.form-apline-multiselect', [
                    'tag' => 'environmentTypeSearch', 'list' => $environmentTypeList,
                    'active_ids' => isset($request->environmentTypeSearch) ? $request->environmentTypeSearch : [],
                    ])
                  </div>
                

                
                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Environment Category criteria:
                    </div>
                    @include('_t.form-apline-multiselect', [
                    'tag' => 'environmentCategorySearch', 'list' => $environmentCategoryList,
                    'active_ids' => isset($request->environmentCategorySearch) ? $request->environmentCategorySearch : [],
                    ])
                  </div>
                </div>
              </div> --}}
            </div>
          

            {{-- <div id="searchGeography">
              <div class="bg-gray-100 p-2">

                <div class="flex">
                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Bioassay name:
                    </div>
                    @include('_t.form-apline-multiselect', [
                    'tag' => 'bioassayNameSearch', 'list' => $bioassayNameList,
                    'active_ids' => isset($request->bioassayNameSearch) ? $request->bioassayNameSearch : [],
                    ])
                  </div>

                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Endpoint name:
                    </div>
                    @include('_t.form-apline-multiselect', [
                    'tag' => 'endpointSearch', 'list' => $endpointList,
                    'active_ids' => isset($request->endpointSearch) ? $request->endpointSearch : [],
                    ])
                  </div>

                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Main determinant name:
                    </div>
                    @include('_t.form-apline-multiselect', [
                    'tag' => 'determinandSearch', 'list' => $determinandList,
                    'active_ids' => isset($request->determinandSearch) ? $request->determinandSearch : [],
                    ])
                  </div>
                </div>


              </div>
            </div> --}}





            <!-- Main Search form -->
            <div class="flex justify-end m-2">
              <a href="{{route('indoor.search.filter')}}" class="btn-clear mx-2"> Reset </a>
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
