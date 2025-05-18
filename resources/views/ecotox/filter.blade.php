<x-app-layout>
  <x-slot name="header">
    @include('ecotox.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg rounded-0">
        
        {{-- {!! dump($request) !!} --}}
        <!-- Main Search form -->
        <form x-data="{ 
          loading: false, 
          seconds: 0,
          timerInterval: null,
          controller: null,
          startTimer() {
              this.seconds = 0;
              this.timerInterval = setInterval(() => {
                  this.seconds++;
              }, 1000);
              // Create AbortController for the fetch request
              this.controller = new AbortController();
          },
          stopTimer() {
              clearInterval(this.timerInterval);
              this.seconds = 0;
          },
          cancelRequest() {
              // Abort the fetch request if it exists
              if (this.controller) {
                  this.controller.abort();
              }
              // Stop the timer
              this.stopTimer();
              // Reset loading state
              this.loading = false;
              // Show feedback to user
              alert('Search request has been cancelled.');
          },
          formatTime(totalSeconds) {
              const minutes = Math.floor(totalSeconds / 60);
              const seconds = totalSeconds % 60;
              return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
          },
          submitForm(event) {
              // Prevent default form submission
              event.preventDefault();
              
              // Set loading state and start timer
              this.loading = true;
              this.startTimer();
              
              // Get form data
              const form = event.target;
              const formData = new FormData(form);
              const queryString = new URLSearchParams(formData).toString();
              const url = form.action + '?' + queryString;
              
              // Use fetch with AbortController
              fetch(url, {
                  method: 'GET',
                  signal: this.controller.signal
              })
              .then(response => {
                  // Handle the response
                  if (response.ok) {
                      window.location.href = url; // Navigate to results page
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
      }" 
      @submit="submitForm($event)"
      name="searchEmpodat" 
      id="searchEmpodat" 
      action="{{route('codsearch.search')}}" 
      method="GET">
  
      <!-- Full-screen overlay with timer and cancel button -->
      <div x-show="loading" 
           class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-gray-900 bg-opacity-80"
           style="display: none;">
          <div class="text-center p-8 bg-white rounded-lg shadow-xl max-w-md w-full">
              <!-- Logo or icon could go here -->
              <h2 class="text-xl font-bold text-slate-700 mb-2">ECOTOX Search</h2>
              
              <svg class="mx-auto animate-spin h-20 w-20 text-slate-500 my-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              
              <p class="text-lg font-semibold text-gray-800">Processing Your Request</p>
              <p class="text-gray-600 mt-2">Searching the database with your criteria...</p>
              
              <div class="flex items-center justify-center mt-6 space-x-2">
                  <div class="text-3xl font-mono font-bold text-slate-600" x-text="formatTime(seconds)">00:00</div>
                  <span class="text-gray-500">elapsed</span>
              </div>
              
              <div class="mt-6 text-sm text-gray-500">
                  <p>Large queries with multiple filters may take longer to process.</p>
                  <p class="mt-1">Please wait while we retrieve your results.</p>
              </div>
              
              <!-- Cancel button -->
              <button type="button" 
                      @click="cancelRequest" 
                      class="mt-6 bg-red-500 hover:bg-red-700 text-white font-medium py-2 px-6 rounded-full focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 transition duration-150 ease-in-out">
                  Cancel Search
              </button>
          </div>
      </div>
      
      <div class="p-4 text-gray-900 grid grid-cols-1 gap-4">
        <!-- Main Search form -->
        
       
        
        
        
        <div id="searchSubstance">
          <div class="bg-gray-100 p-2">
            <div class="font-bold mb-2">
              Substance criteria:
            </div>
            <div>
              @livewire('ecotox.substance-search', ['existingSubstances' => $request->substances])
            </div>
          </div>
        </div>
        
       
        
        {{-- <div id="searchYear">
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
        </div> --}}
        
        
        
        
        <!-- Main Search form -->
        <div class="flex justify-end m-2">
          <a href="{{route('ecotox.search.filter')}}" class="btn-clear mx-2"> Reset </a>
          {{-- <button type="submit" class="btn-submit"> Search
          </button> --}}
          
          <button type="submit" 
          class="btn-submit flex items-center" 
          :class="{ 'opacity-50 cursor-not-allowed': loading }" 
          :disabled="loading">
          <!-- Spinner that shows only when loading -->
          <svg x-show="loading" 
          class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" 
          xmlns="http://www.w3.org/2000/svg" 
          fill="none" 
          viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span x-text="loading ? 'Processing...' : 'Search'"></span>
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