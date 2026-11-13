<x-app-layout>
  <x-slot name="header">
    @include('indoor.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-visible shadow-lg rounded-0">

        {{-- {!! dump($request) !!} --}}
        <!-- Main Search form -->
        <form  name="searchIndoor" id="searchIndoor" action="{{route('indoor.search.search')}}" method="GET">

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
                    class="form-radio text-sky-600"
                    name="displayOption"
                    value="1"
                    @if (request('displayOption', '1') == 1) checked @endif
                    >
                    <span class="ml-2">Fast data preview (no page links)</span>
                  </label>
                  <label class="inline-flex items-center">
                    <input
                    type="radio"
                    class="form-radio text-sky-600"
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
                      Country:
                    </div>
                    <x-multiselect
                      name="countrySearch"
                      :options="$countryList"
                      :selected="isset($request->countrySearch) ? $request->countrySearch : []"
                    />
                  </div>
                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Matrix:
                    </div>
                    <x-multiselect
                      name="matrixSearch"
                      :options="$matrixList"
                      :selected="isset($request->matrixSearch) ? $request->matrixSearch : []"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div id="searchEnvironmentType">
              <div class="bg-gray-100 p-2">
                <div class="flex">
                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Environment Type:
                    </div>
                    <x-multiselect
                      name="environmentTypeSearch"
                      :options="$environmentTypeList"
                      :selected="isset($request->environmentTypeSearch) ? $request->environmentTypeSearch : []"
                    />
                  </div>
                

                
                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Environment Category:
                    </div>
                    <x-multiselect
                      name="environmentCategorySearch"
                      :options="$environmentCategoryList"
                      :selected="isset($request->environmentCategorySearch) ? $request->environmentCategorySearch : []"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div id="searchSubstance">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Substance:
                </div>
                @livewire('backend.substance-search')
              </div>
            </div>

            <div id="searchConcentrationIndicator">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Concentration Indicator:
                </div>
                <x-multiselect
                  name="concentrationIndicatorSearch"
                  :options="$concentrationIndicatorList"
                  :selected="isset($request->concentrationIndicatorSearch) ? $request->concentrationIndicatorSearch : []"
                />
              </div>
            </div>

            <div id="searchSource">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  SLE Source:
                </div>
                <div class="w-full">
                  <x-multiselect
                    name="sourceSearch"
                    :options="$sourceList"
                    :selected="isset($request->sourceSearch) ? $request->sourceSearch : []"
                  />
                </div>
              </div>
            </div>

            <div id="searchCategory">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Search Use category:
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1">
                  @foreach ($categories as $category)
                  <div class="block p-1">
                    <span>
                      <input type="checkbox"
                             name="categoriesSearch[]"
                             value="{{$category->id}}"
                             @if (is_array(request('categoriesSearch')) && in_array($category->id, request('categoriesSearch'))) checked @endif>
                    </span>
                    <span class="ml-1">
                      {!! str_replace(' (', '&nbsp;(', $category->name_abbreviation) !!}
                    </span>
                  </div>
                  @endforeach
                </div>
              </div>
            </div>


            {{-- <div id="searchGeography">
              <div class="bg-gray-100 p-2">

                <div class="flex">
                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Bioassay name:
                    </div>
                    <x-multiselect
                      name="bioassayNameSearch"
                      :options="$bioassayNameList"
                      :selected="isset($request->bioassayNameSearch) ? $request->bioassayNameSearch : []"
                    />
                  </div>

                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Endpoint name:
                    </div>
                    <x-multiselect
                      name="endpointSearch"
                      :options="$endpointList"
                      :selected="isset($request->endpointSearch) ? $request->endpointSearch : []"
                    />
                  </div>

                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Main determinant name:
                    </div>
                    <x-multiselect
                      name="determinandSearch"
                      :options="$determinandList"
                      :selected="isset($request->determinandSearch) ? $request->determinandSearch : []"
                    />
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
