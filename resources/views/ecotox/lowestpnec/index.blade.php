<x-app-layout>
  <x-slot name="header">
    @include('ecotox.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900" x-data="lowestPnecTable()" x-init="init()">
          
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800 mr-4">Lowest PNEC Database</h2>
          </div>
          
          <!-- Informative Text -->
          <div class="mb-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
            <div class="text-sm text-gray-700 space-y-3">
              <p>
                Environmental Quality Standards (EQS) or Predicted No-Effect Concentrations (PNEC) are sometimes available from different EU Member States or chemical regulations (e.g. PPP, BPD or REACH). 
                The Lowest PNEC shown here are agreed by NORMAN experts to be used preliminary for prioritisation purposes. If the measured environmental concentrations (MEC) in the NDS exceed a Lowest 
                PNEC which is not robust, a review is required to determine whether a regulatory concern exists. The lowest PNECs are preferably based on experimental eco-toxicity data, but in case of no or 
                insufficient empirical endpoints, QSAR predictions were used to estimate a provisional P-PNEC value to allow for a first screening.
              </p>
              <p>
                Most of the Lowest PNECs were derived for freshwater. Unless there is an experimental value for other matrices, the following calculations were used for derivation of the Lowest PNECs in:
              </p>
              <ul class="ml-6 space-y-1 list-disc">
                <li><strong>Marine water</strong> – Lowest PNECfw/10</li>
                <li><strong>Sediments</strong> – Lowest PNECfw*2.6*(0.615+0.019*Koc)</li>
                <li><strong>Biota (fish)</strong> – PNECfw*BCF</li>
                <li><strong>Marine biota (fish)</strong> – PNECfw*BCF/10</li>
                <li><strong>Biota (mollusc)</strong> – PNECfw*BCF/4</li>
                <li><strong>Marine biota (mollusc)</strong> – PNECfw*BCF/10/4</li>
              </ul>
              <p>
                All of the above mentioned PNECs refer to long-term exposure. A short overview on the methodology to derive the different PNECs can be downloaded <b><a href="docs/Fact-sheet-EQS-Derivation.pdf" target="_blank" class="link-lime-text">here</a></b>.
              
              </p>
            </div>
          </div>
          
          <!-- Search and Filter Controls -->
          <div class="mb-6 flex justify-between items-start">
            <div class="flex space-x-4 flex-1">
              <div class="w-32">
                <label for="perPage" class="block text-sm font-medium text-gray-700">Show</label>
                <select id="perPage" x-model="perPage" @change="changePage(1)" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 rounded-md focus:outline-hidden focus:ring-stone-500 focus:border-stone-500 sm:text-sm">
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>
              
              <div class="flex-1">
                <label for="search" class="block text-sm font-medium text-gray-700">Search Substance Name</label>
                <input type="text" 
                id="search" 
                x-model="search" 
                @input="debounceSearch()" 
                placeholder="Search by substance name (e.g., Benzene, Atrazine...)" 
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-xs py-2 px-3 focus:outline-hidden focus:ring-stone-500 focus:border-stone-500 sm:text-sm">
                <div class="text-xs text-gray-600 mt-1">Only searches substances with PNEC data</div>
              </div>
              
              <div class="w-48">
                <label for="expPred" class="block text-sm font-medium text-gray-700">Data Type</label>
                <select id="expPred" x-model="expPred" @change="changePage(1)" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 rounded-md focus:outline-hidden focus:ring-stone-500 focus:border-stone-500 sm:text-sm">
                  <option value="">All Records</option>
                  <option value="1">Experimental Only</option>
                  <option value="2">Predicted Only</option>
                </select>
              </div>
            </div>
            
            <!-- Download Button -->
            <div class="ml-4 flex flex-col justify-end">
              <div class="h-5"></div> <!-- Spacer to align with other controls -->
              @auth
              <form method="POST" action="{{ route('ecotox.lowestpnec.csv.export') }}">
                @csrf
                <input type="hidden" name="search" x-model="search">
                <input type="hidden" name="exp_pred" x-model="expPred">
                <button type="submit" class="btn-submit inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-slate-600 hover:bg-slate-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  Download CSV
                </button>
              </form>
              @else
              <div class="text-xs text-gray-500 text-center">
                <a href="{{ route('login') }}" class="link-lime-text">Login</a> to download
              </div>
              @endauth
            </div>
          </div>
          
          <div class="overflow-x-auto relative">
            <!-- Loading overlay -->
            <div x-show="loading" class="absolute inset-0 bg-gray-100 bg-opacity-50 flex items-center justify-center z-10">
              <svg class="animate-spin h-8 w-8 text-stone-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
            
            <table class="table-standard w-full">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="py-2 px-1 text-center cursor-pointer text-xs" @click="sortBy('sus_id')">
                    <div class="flex items-center justify-center">
                      <span>Norman SusDat ID</span>
                      <template x-if="sortColumn === 'sus_id'">
                        <span class="ml-1">
                          <template x-if="sortDirection === 'asc'">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                          </template>
                          <template x-if="sortDirection === 'desc'">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                          </template>
                        </span>
                      </template>
                    </div>
                  </th>
                  <th class="py-2 px-1 text-center">Substance</th>
                  <th class="py-2 px-1 text-center text-xs">Freshwater [µg/l]</th>
                  <th class="py-2 px-1 text-center text-xs">Marine water [µg/l]</th>
                  <th class="py-2 px-1 text-center text-xs">Sediments [µg/kg dw]</th>
                  <th class="py-2 px-1 text-center text-xs">Biota (fish) [µg/kg ww]</th>
                  <th class="py-2 px-1 text-center text-xs">Marine biota (fish) [µg/kg ww]</th>
                  <th class="py-2 px-1 text-center text-xs">Biota (mollusc) [µg/kg ww]</th>
                  <th class="py-2 px-1 text-center text-xs">Marine biota (mollusc) [µg/kg ww]</th>
                  {{-- <th class="py-2 px-1 text-center text-xs">Biota (WFD) [µg/kg ww]</th> --}}
                  <th class="py-2 px-1 text-center cursor-pointer" @click="sortBy('lowest_exp_pred')">
                    <div class="flex items-center justify-center">
                      <span>Type</span>
                      <template x-if="sortColumn === 'lowest_exp_pred'">
                        <span class="ml-1">
                          <template x-if="sortDirection === 'asc'">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                          </template>
                          <template x-if="sortDirection === 'desc'">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                          </template>
                        </span>
                      </template>
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <template x-for="(pnec, index) in pnecs" :key="pnec.id">
                  <tr class="hover:bg-slate-300 transition" :class="index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-200'">
                    <td class="p-1 text-center text-xs">
                      <div x-text="pnec.substance ? pnec.substance.prefixed_code : 'Unknown'"></div>
                      <a :href="'{{ route('ecotox.lowestpnec.show', ['sus_id' => '__SUS_ID__']) }}'.replace('__SUS_ID__', pnec.sus_id)" class="link-lime-text">
                        <i class="fas fa-search"></i>
                      </a>
                    </td>
                    <td class="p-1 text-center text-xs">
                      <span x-text="pnec.substance ? pnec.substance.name : 'Unknown'"></span>
                      @role('super_admin')
                      <span class="text-xss text-gray-500" x-text="pnec.substance_id ? ' (' + pnec.substance_id + ')' : ''"></span>
                      @endrole
                    </td>
                    <td class="p-1 text-center text-xs">
                      <span class="font-medium" x-text="pnec.lowest_pnec_value_1 || ''"></span>
                    </td>
                    <td class="p-1 text-center text-xs">
                      <span class="font-medium" x-text="pnec.lowest_pnec_value_2 || ''"></span>
                    </td>
                    <td class="p-1 text-center text-xs">
                      <span class="font-medium" x-text="pnec.lowest_pnec_value_3 || ''"></span>
                    </td>
                    <td class="p-1 text-center text-xs">
                      <span class="font-medium" x-text="pnec.lowest_pnec_value_4 || ''"></span>
                    </td>
                    <td class="p-1 text-center text-xs">
                      <span class="font-medium" x-text="pnec.lowest_pnec_value_5 || ''"></span>
                    </td>
                    <td class="p-1 text-center text-xs">
                      <span class="font-medium" x-text="pnec.lowest_pnec_value_6 || ''"></span>
                    </td>
                    <td class="p-1 text-center text-xs">
                      <span class="font-medium" x-text="pnec.lowest_pnec_value_7 || ''"></span>
                    </td>
                    {{-- <td class="p-1 text-center text-xs">
                      <span class="font-medium" x-text="pnec.lowest_pnec_value_8 || ''"></span>
                    </td> --}}
                    <td class="p-1 text-center">
                      <span class="px-2 py-1 rounded-sm text-xs font-medium" 
                        :class="pnec.lowest_exp_pred == 1 ? 'bg-green-100 text-green-800' : 'bg-stone-100 text-stone-800'"
                        x-text="pnec.lowest_exp_pred == 1 ? 'Experimental' : 'Predicted'">
                      </span>
                    </td>
                  </tr>
                </template>
                
                <!-- Empty state -->
                <tr x-show="!loading && pnecs.length === 0" class="bg-slate-100">
                  <td colspan="11" class="py-6 px-4 text-center text-gray-500">
                    <p class="text-base">No PNEC records found</p>
                    <p class="text-sm mt-1">Try adjusting your search or filter to find what you're looking for.</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <div class="mt-4 flex justify-between items-center">
            <div class="text-sm text-gray-700" x-show="totalPnecs > 0">
              Showing <span x-text="(currentPage - 1) * perPage + 1"></span> to <span x-text="Math.min(currentPage * perPage, totalPnecs)"></span> of <span x-text="totalPnecs"></span> records
            </div>
            <div class="flex space-x-1" x-show="totalPages > 1">
              <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1" :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''" class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Previous
              </button>
              
              <template x-for="page in paginationButtons" :key="page">
                <template x-if="page === '...'">
                  <span class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white">...</span>
                </template>
                <template x-if="page !== '...'">
                  <button @click="changePage(page)" :class="page === currentPage ? 'bg-stone-50 border-stone-500 text-stone-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'" class="px-3 py-2 border rounded-md text-sm font-medium">
                    <span x-text="page"></span>
                  </button>
                </template>
              </template>
              
              <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages" :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''" class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Next
              </button>
            </div>
          </div>
          
          
        </div>
      </div>
    </div>
  </div>
  
  @push('scripts')
  <script>
    function lowestPnecTable() {
      return {
        pnecs: [],
        loading: true,
        perPage: 25,
        currentPage: 1,
        totalPnecs: 0,
        totalPages: 1,
        search: '',
        expPred: '',
        searchTimeout: null,
        sortColumn: 'id',
        sortDirection: 'asc',
        
        init() {
          console.log('Initializing LowestPNEC table');
          this.fetchPnecs();
        },
        
        fetchPnecs() {
          this.loading = true;
          console.log('Fetching PNECs with search:', this.search, 'expPred:', this.expPred);
          
          // Build URL with search parameters
          const url = new URL('{{ route("ecotox.lowestpnec.data") }}', window.location.origin);
          url.searchParams.append('page', this.currentPage);
          url.searchParams.append('per_page', this.perPage);
          url.searchParams.append('sort', this.sortColumn);
          url.searchParams.append('direction', this.sortDirection);
          
          // Only append search param if it's not empty
          if (this.search && this.search.trim() !== '') {
            url.searchParams.append('search', this.search.trim());
          }
          
          // Only append exp_pred param if it's not empty
          if (this.expPred && this.expPred !== '') {
            url.searchParams.append('exp_pred', this.expPred);
          }
          
          console.log('Fetching from URL:', url.toString());
          
          fetch(url.toString())
          .then(response => {
            if (!response.ok) {
              throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
          })
          .then(data => {
            console.log('Data received:', data);
            this.pnecs = data.data;
            this.totalPnecs = data.total;
            this.totalPages = data.last_page;
            this.loading = false;
          })
          .catch(error => {
            console.error('Error fetching PNECs:', error);
            this.loading = false;
            alert('Error loading PNEC data: ' + error.message);
          });
        },
        
        debounceSearch() {
          clearTimeout(this.searchTimeout);
          this.searchTimeout = setTimeout(() => {
            console.log('Search term:', this.search);
            this.currentPage = 1; // Reset to first page when searching
            this.fetchPnecs();
          }, 500); // 500ms debounce delay
        },
        
        sortBy(column) {
          if (this.sortColumn === column) {
            // Toggle direction if same column
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
          } else {
            // Default to ascending for new column
            this.sortColumn = column;
            this.sortDirection = 'asc';
          }
          this.fetchPnecs();
        },
        
        changePage(page) {
          if (page < 1 || page > this.totalPages) {
            return;
          }
          this.currentPage = page;
          this.fetchPnecs();
        },
        
        get paginationButtons() {
          const buttons = [];
          const totalPages = this.totalPages;
          const currentPage = this.currentPage;
          
          // Always show first page
          buttons.push(1);
          
          // Show dots if needed (between first and currentPage - 1)
          if (currentPage > 3) {
            buttons.push('...');
          }
          
          // Show page before current if it exists and isn't the first page
          if (currentPage - 1 > 1) {
            buttons.push(currentPage - 1);
          }
          
          // Show current page if it isn't the first or last
          if (currentPage !== 1 && currentPage !== totalPages) {
            buttons.push(currentPage);
          }
          
          // Show page after current if it exists and isn't the last page
          if (currentPage + 1 < totalPages) {
            buttons.push(currentPage + 1);
          }
          
          // Show dots if needed (between currentPage + 1 and last page)
          if (currentPage < totalPages - 2) {
            buttons.push('...');
          }
          
          // Always show last page if there is more than one page
          if (totalPages > 1) {
            buttons.push(totalPages);
          }
          
          return buttons;
        }
      };
    }
  </script>
  @endpush
</x-app-layout>