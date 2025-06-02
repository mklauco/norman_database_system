<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg rounded-0">
        
        <!-- Main Search form -->
        <form x-data="empodatSearchForm()" 
              @submit="submitForm($event)"
              name="searchEmpodat" 
              id="searchEmpodat" 
              action="{{route('codsearch.search')}}" 
              method="GET"
              role="search"
              aria-label="EMPODAT Database Search Form">
  
          <!-- Full-screen overlay with timer and cancel button -->
          <div x-show="loading" 
               class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-gray-900 bg-opacity-80"
               style="display: none;"
               x-trap.noscroll="loading"
               role="dialog"
               aria-modal="true"
               aria-labelledby="loading-title"
               aria-describedby="loading-description">
              <div class="text-center p-6 sm:p-8 bg-white rounded-lg shadow-xl max-w-sm sm:max-w-md w-full mx-4">
                  <!-- Logo or icon could go here -->
                  <h2 id="loading-title" class="text-xl font-bold text-slate-700 mb-2">EMPODAT Search</h2>
                  
                  <svg class="mx-auto animate-spin h-20 w-20 text-slate-500 my-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  
                  <p id="loading-description" class="text-lg font-semibold text-gray-800">Processing Your Request</p>
                  <p class="text-gray-600 mt-2">Searching the database with your criteria...</p>
                  
                  <div class="flex items-center justify-center mt-6 space-x-2">
                      <div class="text-3xl font-mono font-bold text-slate-600" 
                           x-text="formatTime(seconds)"
                           aria-live="polite"
                           aria-label="Search elapsed time">00:00</div>
                      <span class="text-gray-500">elapsed</span>
                  </div>
                  
                  <div class="mt-6 text-sm text-gray-500">
                      <p>Large queries with multiple filters may take longer to process.</p>
                      <p class="mt-1">Please wait while we retrieve your results.</p>
                  </div>
                  
                  <!-- Cancel button -->
                  <button type="button" 
                          @click="cancelRequest" 
                          class="mt-6 bg-red-500 hover:bg-red-700 text-white font-medium py-2 px-6 rounded-full focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 transition duration-150 ease-in-out"
                          aria-label="Cancel search request">
                      Cancel Search
                  </button>
              </div>
          </div>
          
          <div class="p-4 text-gray-900 grid grid-cols-1 gap-4">
            
            <div id="displayOptions">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Display options:
                </div>
                <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                  <label class="inline-flex items-center">
                    <input type="radio" 
                           class="form-radio text-indigo-600" 
                           name="displayOption" 
                           value="1" 
                           @if (request('displayOption', '1') == 1) checked @endif
                           aria-describedby="fast-preview-desc">
                    <span class="ml-2">Fast data preview</span>
                  </label>
                  <label class="inline-flex items-start sm:items-center">
                    <input type="radio" 
                           class="form-radio text-indigo-600 mt-1 sm:mt-0" 
                           name="displayOption" 
                           value="0" 
                           @if (request('displayOption', '1') === '0') checked @endif
                           aria-describedby="full-output-desc">
                    <span class="ml-2">Data output with page links (might be slow)</span>
                  </label>
                </div>
                <div class="sr-only">
                  <div id="fast-preview-desc">Quick preview of search results without detailed pagination</div>
                  <div id="full-output-desc">Complete data output with pagination controls, may take longer to load</div>
                </div>
              </div>
            </div>
            
            <div id="searchGeography">
              <div class="bg-gray-100 p-2">
                <div class="flex flex-col lg:flex-row">
                  <div class="w-full">
                    <div class="font-bold mb-2">
                      Geography criteria:
                    </div>
                    <div aria-describedby="country-help">
                      @include('_t.form-apline-multiselect', [
                        'tag' => 'countrySearch', 'list' => $countryList,
                        'active_ids' => isset($request->countrySearch) ? $request->countrySearch : [],
                      ])
                    </div>
                    <div id="country-help" class="sr-only">Select one or more countries to filter results</div>
                  </div>
                  
                  <div class="w-full mt-4 lg:mt-0">
                    <div class="font-bold mb-2">
                      Ecosystem criteria:
                    </div>
                    <div aria-describedby="matrix-help">
                      @include('_t.form-apline-multiselect', [
                        'tag' => 'matrixSearch', 'list' => $matrixList,
                        'active_ids' => isset($request->matrixSearch) ? $request->matrixSearch : [],
                      ])
                    </div>
                    <div id="matrix-help" class="sr-only">Select ecosystem types to filter environmental matrices</div>
                  </div>
                </div>
              </div>
            </div>
            
            <div id="searchSubstance">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Substance criteria:
                </div>
                <div aria-describedby="substance-help">
                  @livewire('empodat.substance-search', ['existingSubstances' => $request->substances])
                </div>
                <div id="substance-help" class="sr-only">Search and select chemical substances to filter results</div>
              </div>
            </div>
            
            <div id="searchSource" class="">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  SLE Source criteria:
                </div>
                <div class="w-full" aria-describedby="source-help">
                  @include('_t.form-apline-multiselect', [
                    'tag' => 'sourceSearch', 'list' => $sourceList,
                    'active_ids' => isset($request->sourceSearch) ? $request->sourceSearch : [],
                  ])
                </div>
                <div id="source-help" class="sr-only">Select Suspect List Exchange sources to filter data</div>
              </div>
            </div>
            
            <div id="searchCategory" class="">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Search Category:
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1" 
                     role="group" 
                     aria-describedby="category-help">
                  @foreach ($categories as $category)
                  <div class="block p-1">
                    <span>
                      <input type="checkbox" 
                             name="categoriesSearch[]" 
                             value="{{$category->id}}"
                             @if (is_array(request('categoriesSearch')) && in_array($category->id, request('categoriesSearch'))) checked @endif
                             aria-describedby="category-{{$category->id}}-desc">
                    </span>
                    <span class="ml-1">
                      {!! preg_replace('/\s*\(/', '&nbsp;(', $category->name_abbreviation, 1) !!}
                    </span>
                    <span id="category-{{$category->id}}-desc" class="sr-only">
                      Category: {{$category->name_abbreviation}}
                    </span>
                  </div>
                  @endforeach
                </div>
                <div id="category-help" class="sr-only">Select substance categories to filter search results</div>
              </div>
            </div>
            
            <div id="searchYear">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2">
                  Year:
                </div>
                <div class="w-full" aria-describedby="year-help">
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">
                    <input type="number" 
                           name="year_from" 
                           value="{{ isset($request->year_from) ? $request->year_from : null }}" 
                           class="form-text" 
                           placeholder="year from"
                           aria-label="Starting year"
                           aria-describedby="year-from-desc">
                    <input type="number" 
                           name="year_to" 
                           value="{{ isset($request->year_to) ? $request->year_to : null }}" 
                           class="form-text" 
                           placeholder="year to"
                           aria-label="Ending year"
                           aria-describedby="year-to-desc">
                  </div>
                  <div class="sr-only">
                    <div id="year-from-desc">Enter the starting year for the date range filter</div>
                    <div id="year-to-desc">Enter the ending year for the date range filter</div>
                  </div>
                </div>
                <div id="year-help" class="sr-only">Filter results by sampling year range</div>
              </div>
            </div>
            
            <div id="concentrationIndicatorSearch" class="">
              <div class="flex bg-gray-100 p-2">
                <div class="w-full">
                  <div class="font-bold mb-2">
                    Concetration Indicators:
                  </div>
                  <div aria-describedby="concentration-help">
                    @include('_t.form-apline-multiselect', [
                      'tag' => 'concentrationIndicatorSearch', 'list' => $concentrationIndicatorList,
                      'active_ids' => isset($request->concentrationIndicatorSearch) ? $request->concentrationIndicatorSearch : [],
                    ])
                  </div>
                  <div id="concentration-help" class="sr-only">Select concentration measurement indicators</div>
                </div>
              </div>
            </div>
            
            <div id="empodatDataSourcesSearch" class="">
              <div class="flex flex-col lg:flex-row bg-gray-100 p-2">
                <div class="w-full">
                  <div class="font-bold mb-2 flex items-center space-x-2">
                    <div class="relative group">
                      <!-- Icon -->
                      <i class="fas fa-hourglass-half text-gray-500" aria-hidden="true"></i>
                      <!-- Tooltip -->
                      <div class="absolute hidden group-hover:block bg-gray-800 text-white text-sm rounded py-2 px-4 -top-0 left-0 transform -translate-y-full w-48 text-center z-50">
                        Including this search option will slow down the search process.
                      </div>
                    </div>
                    <!-- Text -->
                    <span>Type of data source:</span>
                  </div>
                  <div aria-describedby="type-sources-help">
                    @include('_t.form-apline-multiselect', [
                      'tag' => 'typeDataSourcesSearch', 'list' => $typeDataSourcesList,
                      'active_ids' => isset($request->typeDataSourcesSearch) ? $request->typeDataSourcesSearch : [],
                    ])
                  </div>
                  <div id="type-sources-help" class="sr-only">Select data source types - this may slow down search performance</div>
                </div>
                
                <div class="w-full mt-4 lg:mt-0">
                  <div class="font-bold mb-2 flex items-center space-x-2">
                    <div class="relative group">
                      <!-- Icon -->
                      <i class="fas fa-hourglass-half text-gray-500" aria-hidden="true"></i>
                      <!-- Tooltip -->
                      <div class="absolute hidden group-hover:block bg-gray-800 text-white text-sm rounded py-2 px-4 -top-0 left-0 transform -translate-y-full w-48 text-center z-50">
                        Including this search option will slow down the search process.
                      </div>
                    </div>
                    <!-- Text -->
                    <span>Organisation:</span>
                  </div>
                  <div aria-describedby="org-sources-help">
                    @include('_t.form-apline-multiselect', [
                      'tag' => 'dataSourceOrganisationSearch', 'list' => $dataSourceOrganisationList,
                      'active_ids' => isset($request->dataSourceOrganisationSearch) ? $request->dataSourceOrganisationSearch : [],
                    ])
                  </div>
                  <div id="org-sources-help" class="sr-only">Select organisations - this may slow down search performance</div>
                </div>
              </div>
              
              <div class="flex bg-gray-100 p-2">
                <div class="w-full">
                  <div class="font-bold mb-2 flex items-center space-x-2">
                    <div class="relative group">
                      <!-- Icon -->
                      <i class="fas fa-hourglass-half text-gray-500" aria-hidden="true"></i>
                      <!-- Tooltip -->
                      <div class="absolute hidden group-hover:block bg-gray-800 text-white text-sm rounded py-2 px-4 -top-0 left-0 transform -translate-y-full w-48 text-center z-50">
                        Including this search option will slow down the search process.
                      </div>
                    </div>
                    <!-- Text -->
                    <span>Laboratory:</span>
                  </div>
                  <div aria-describedby="lab-sources-help">
                    @include('_t.form-apline-multiselect', [
                      'tag' => 'dataSourceLaboratorySearch', 'list' => $dataSourceLaboratoryList,
                      'active_ids' => isset($request->dataSourceLaboratorySearch) ? $request->dataSourceLaboratorySearch : [],
                    ])
                  </div>
                  <div id="lab-sources-help" class="sr-only">Select laboratories - this may slow down search performance</div>
                </div>
              </div>
            </div>
            
            <div id="analyticalMethodSearch" class="">
              <div class="flex bg-gray-100 p-2">
                <div class="w-full">
                  <div class="font-bold mb-2 flex items-center space-x-2">
                    <div class="relative group">
                      <!-- Icon -->
                      <i class="fas fa-hourglass-half text-gray-500" aria-hidden="true"></i>
                      <!-- Tooltip -->
                      <div class="absolute hidden group-hover:block bg-gray-800 text-white text-sm rounded py-2 px-4 -top-0 left-0 transform -translate-y-full w-48 text-center z-50">
                        Including this search option will slow down the search process.
                      </div>
                    </div>
                    <!-- Text -->
                    <span>Analytical method:</span>
                  </div>
                  <div aria-describedby="analytical-method-help">
                    @include('_t.form-apline-multiselect', [
                      'tag' => 'analyticalMethodSearch', 'list' => $analyticalMethodsList,
                      'active_ids' => isset($request->analyticalMethodSearch) ? $request->analyticalMethodSearch : [],
                    ])
                  </div>
                  <div id="analytical-method-help" class="sr-only">Select analytical methods - this may slow down search performance</div>
                </div>
              </div>
            </div>
            
            <div id="qualityAnalyticalMethodsSearch" class="">
              <div class="flex bg-gray-100 p-2">
                <div class="w-full">
                  <div class="font-bold mb-2">
                    Quality information category:
                  </div>
                  <div aria-describedby="quality-help">
                    @include('_t.form-apline-multiselect', [
                      'tag' => 'qualityAnalyticalMethodsSearch', 'list' => $qualityAnalyticalMethodsList,
                      'active_ids' => isset($request->qualityAnalyticalMethodsSearch) ? $request->qualityAnalyticalMethodsSearch : [],
                    ])
                  </div>
                  <div id="quality-help" class="sr-only">Select quality information categories for analytical methods</div>
                </div>
              </div>
            </div>
            
            <div id="searchFiles">
              <div class="bg-gray-100 p-2">
                <div class="font-bold mb-2 flex items-center space-x-2">
                  <i class="fas fa-file text-gray-600" aria-hidden="true"></i>
                  <span>File criteria:</span>
                </div>
                <div aria-describedby="file-help">
                  {{-- <livewire:empodat.file-search :existing-files="$request->files" /> --}}
                  @livewire('empodat.file-search-test', ['existingSubstances' => $request->substances])

                </div>
                <div id="file-help" class="sr-only">Search and select files to filter records that are associated with specific files</div>
                
                <!-- File search instructions -->
                <div class="mt-2 text-xs text-gray-600">
                  <p><i class="fas fa-info-circle mr-1"></i> Search by file name or select from the dropdown. Only records associated with the selected files will be shown.</p>
                </div>
              </div>
            </div>
            
            <!-- Main Search form -->
            <div class="flex flex-col sm:flex-row justify-end m-2 gap-2">
              <a href="{{route('codsearch.filter')}}" class="btn-clear mx-2 text-center"> Reset </a>
              
              <button type="submit" 
                      class="btn-submit flex items-center justify-center" 
                      :class="{ 'opacity-50 cursor-not-allowed': loading }" 
                      :disabled="loading"
                      aria-describedby="search-help">
                <!-- Spinner that shows only when loading -->
                <svg x-show="loading" 
                     class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" 
                     xmlns="http://www.w3.org/2000/svg" 
                     fill="none" 
                     viewBox="0 0 24 24"
                     aria-hidden="true">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="loading ? 'Processing...' : 'Search'"></span>
              </button>
              <div id="search-help" class="sr-only">Submit the search form with your selected criteria</div>
            </div>
            
            <div class="m-2">
              <ul class="list-disc list-inside text-gray-700 text-sm">
                <li>All search criteria are optional. If you do not select any criteria, all data will be displayed.</li>
                <li>Each time the search is executed, the search options are recorded in the database for future reference and performance improvements.</li>
                <li>We encourage users to register for a free account to save-&-view their search criteria and results.</li>
              </ul>
            </div>
            
          </div>    
        </form>  
      </div>
    </div>
  </div>

  <script>
    function empodatSearchForm() {
      return {
        loading: false,
        seconds: 0,
        timerInterval: null,
        controller: null,
        
        startTimer() {
          this.seconds = 0;
          this.timerInterval = setInterval(() => {
            this.seconds++;
          }, 1000);
          this.controller = new AbortController();
        },
        
        stopTimer() {
          clearInterval(this.timerInterval);
          this.seconds = 0;
        },
        
        cancelRequest() {
          if (this.controller) {
            this.controller.abort();
          }
          this.stopTimer();
          this.loading = false;
          alert('Search request has been cancelled.');
        },
        
        formatTime(totalSeconds) {
          const minutes = Math.floor(totalSeconds / 60);
          const seconds = totalSeconds % 60;
          return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        },
        
        submitForm(event) {
          event.preventDefault();
          
          this.loading = true;
          this.startTimer();
          
          const form = event.target;
          const formData = new FormData(form);
          const queryString = new URLSearchParams(formData).toString();
          const url = form.action + '?' + queryString;
          
          fetch(url, {
            method: 'GET',
            signal: this.controller.signal
          })
          .then(response => {
            if (response.ok) {
              window.location.href = url;
            } else {
              throw new Error('Search failed');
            }
          })
          .catch(error => {
            if (error.name === 'AbortError') {
              console.log('Search was cancelled');
            } else {
              console.error('Error:', error);
              alert('There was an error processing your search. Please try again.');
            }
            this.loading = false;
            this.stopTimer();
          });
        }
      }
    }
  </script>
</x-app-layout>