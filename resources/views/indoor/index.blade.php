<x-app-layout>
  <x-slot name="header">
    @include('indoor.header')
  </x-slot>
  
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg" >
        <div class="p-6 text-gray-900" x-data="recordsTable()" x-init="initLeaflet()">
          {{-- main div --}}
          
          {{-- Action bar: Refine Search | Download CSV --}}
          <div class="flex items-start justify-between">
            {{-- Left: Refine Search --}}
            <a href="{{ route('indoor.search.filter', [
              'countrySearch'      => $countrySearch,
              'matrixSearch'       => $matrixSearch,
              'environmentTypeSearch'     => $environmentTypeSearch,
              'environmentCategorySearch' => $environmentCategorySearch,
              'substances'         => $substances ?? [],
              'concentrationIndicatorSearch' => $concentrationIndicatorSearch ?? [],
              'sourceSearch'       => $sourceSearch ?? [],
              'categoriesSearch'   => $categoriesSearch ?? [],
              'query_log_id'       => $query_log_id
            ]) }}" class="btn-submit"><i class="fas fa-filter mr-1"></i>Refine Search</a>

            {{-- Right: Download CSV --}}
            <div class="flex flex-col items-end">
              @auth
                <a href="{{ route('indoor.search.download', ['query_log_id' => $query_log_id]) }}"
                  class="btn-download"><i class="fas fa-file-csv mr-1"></i>Download CSV</a>
              @else
                <button type="button" class="btn-download" disabled>
                  <i class="fas fa-file-csv mr-1"></i>Download CSV
                </button>
                <span class="text-xs text-gray-400 mt-1">Available for logged in users only</span>
              @endauth
            </div>
          </div>

          {{-- Search parameters --}}
          <div class="flex items-center">
            @if (!empty($searchParameters))
              <span>Search parameters:</span>
              <span class="ml-1 font-bold">
                @foreach ($searchParameters as $key => $value)
                  @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
                    @foreach ($value as $item)
                      {{ $item }}@if(!$loop->last), @endif
                    @endforeach
                  @else
                    {{ $value }}
                  @endif
                  @if(!$loop->last); @endif
                @endforeach
              </span>
            @else
              <span>Search parameters:</span>
              <span class="italic text-gray-400 ml-1">no parameters have been chosen</span>
            @endif
          </div>

          {{-- Record count --}}
          <div class="mb-2">
            @if($displayOption == 1)
              {{-- Simple output - use Livewire query counter --}}
              @livewire('backend.query-counter', [
                'queryId' => $query_log_id ?? null,
                'resultsCount' => $resultsObjectsCount,
                'count_again' => request()->has('page') ? false : true
              ])
            @else
              {{-- Advanced output with pagination --}}
              <div class="flex items-center">
                <span>Number of matched records:</span>
                <span class="ml-1 mr-1 font-bold">{{ number_format($resultsObjects->total(), 0, '.', ' ') }}</span>
                @if ($resultsObjectsCount > 0)
                  <span>
                    of {{ number_format($resultsObjectsCount, 0, '.', ' ') }}
                    @if (is_numeric($resultsObjects->total()))
                      @if (($resultsObjects->total() / $resultsObjectsCount) * 100 < 0.01)
                        (&le; 0.01%)
                      @else
                        ({{ number_format(($resultsObjects->total() / $resultsObjectsCount) * 100, 2, '.', ' ') }}%)
                      @endif
                    @endif
                  </span>
                @endif
              </div>
            @endif
          </div>
        
        <table class="table-standard">
          <thead>
            <tr class="bg-gray-600 text-white">
              <th>ID</th>
              <th>Substance</th>
              <th>Concentration</th>
              <th>Matrix</th>
              <th>Type of Environment</th>
              <th>Category of Environment</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($resultsObjects as $e)
            {{-- {{ dd($e); }} --}}
            <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
              <td class="p-1 text-center">
                <div class="flex items-center justify-center space-x-2">
                  <a href="{{ route('indoor.show', $e->id) }}"
                     target="_blank"
                     class="text-teal-600 hover:text-teal-800 transition-colors"
                     title="View full record details">
                    <i class="fas fa-search"></i>
                  </a>
                  <a href="{{ route('indoor.show', $e->id) }}" target="_blank" class="font-mono text-teal-800 hover:text-teal-600 hover:underline">
                    {!! number_format($e->id, 0, '', '&nbsp;') !!}
                  </a>
                </div>
              </td>
              <td class="p-1 text-center">
                @if($e->sus_id)
                {{ $e->substance->name ?? 'N/A'}}
                @else
                <span class="text-gray-400">N/A</span>
                @endif
              </td>
              <td class="p-1 text-center">
                @if($e->dic_id == 1)
                  {{-- Individual Value - show numeric concentration with unit --}}
                  <span class="font-medium">{{ $e->concentration_value !== null ? number_format($e->concentration_value, 4, '.', ' ') : 'N/A' }}</span>&nbsp;{{ $e->concentration_unit ?? '' }}
                @elseif($e->dic_id > 1)
                  {{-- Less than LoD/LoQ - show indicator name --}}
                  @if($e->collectionCode)
                    <span class="text-amber-700 italic">{{ $e->collectionCode->name }}</span>
                  @else
                    <span class="text-gray-400">N/A</span>
                  @endif
                @else
                  {{-- dic_id == 0 or null - show raw value if available --}}
                  @if($e->concentration_value !== null)
                    <span class="font-medium">{{ number_format($e->concentration_value, 4, '.', ' ') }}</span>&nbsp;{{ $e->concentration_unit ?? '' }}
                  @else
                    <span class="text-gray-400">N/A</span>
                  @endif
                @endif
              </td>
              <td class="p-1 text-center">
                @if($e->matrix)
                {{ $e->matrix->name }}
                @elseif($e->matrix_other)
                {{ $e->matrix_other }}
                @else
                <span class="text-gray-400">N/A</span>
                @endif
              </td>
              <td class="p-1 text-center">
                @if($e->environmentType)
                {{ $e->environmentType->name }}
                @else
                <span class="text-gray-400">N/A</span>
                @endif
              </td>
              <td class="p-1 text-center">
                @if($e->environmentCategory)
                {{ $e->environmentCategory->name }}
                @elseif($e->dcoe_other)
                {{ $e->dcoe_other }}
                @else
                <span class="text-gray-400">N/A</span>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
        
        @if($displayOption == 1)
        {{-- use simple output --}}
        
        <div class="flex justify-center space-x-4 mt-4">
          @if ($resultsObjects->onFirstPage())
          <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded-sm cursor-not-allowed">
            Previous
          </span>
          @else
          <a href="{{ $resultsObjects->previousPageUrl() }}" class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded-sm hover:bg-stone-600">
            Previous
          </a>
          @endif
          
          @if ($resultsObjects->hasMorePages())
          <a href="{{ $resultsObjects->nextPageUrl() }}" class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded-sm hover:bg-stone-600">
            Next
          </a>
          @else
          <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded-sm cursor-not-allowed">
            Next
          </span>
          @endif
        </div>
        @else
        {{-- use advanced output --}}
        {{$resultsObjects->links('pagination::tailwind')}}
        @endif
        
        
        
        <!-- The Modal (hidden by default) -->
        <div class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50" x-show="showModal"x-transition>
          <div class="bg-white w-11/12 md:w-2/3 lg:w-1/2 xl:w-1/3 rounded-sm shadow-lg relative" x-trap.inert="showModal">
            <!-- Modal Header -->
            <div class="flex justify-between items-center border-b px-4 py-2">
              <h3 class="text-lg font-semibold">Record ID: <span x-text="record?.id"></span></h3>
              <button @click="closeModal()" class="text-gray-500 hover:text-gray-700 text-xl">
                &times;
              </button>
            </div>
            
            <!-- Modal Content -->
            <div class="p-4 max-h-[60vh] overflow-y-auto">
              
              <!-- Show details with Alpine binding -->
              {{-- <p><strong>Name:</strong> <span x-text="record?.name"></span></p> --}}
              
              <div class="">
                <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Substance</div>
                <div class="flex justify-between py-1 text-sm">
                  <!-- pair[0] = key, pair[1] = value -->
                  <div class="px-1 font-semibold">Substance</div>
                  <div class="px-1" x-text="record?.name"></div>
                </div>
                <div class="flex justify-between py-1 text-sm">
                  <!-- pair[0] = key, pair[1] = value -->
                  <div class="px-1 font-semibold">Code</div>
                  <!-- Dynamic link -->
                  <a
                  :href="'{{ route('substances.show', ':id') }}'.replace(':id', record?.substance_id)"
                  target="_blank"
                  class="link-lime-text px-1"
                  x-text="'NS' + record?.code"
                  ></a>
                </div>
              </div>
              
              <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Analytical Method</div>
              <!-- We'll loop over stationArray -->
              <template x-for="(pair, index) in analyticalMethodArray" :key="index">
                <!-- index = 0,1,2,... so we can do odd/even backgrounds -->
                <div :class="index % 2 === 0 ? 'py-1 bg-slate-100' : 'py-1 bg-slate-200'">
                  <div class="flex justify-between py-1 text-sm">
                    <!-- pair[0] = key, pair[1] = value -->
                    <div class="px-1 font-semibold" x-text="pair[0]"></div>
                    <div class="px-1" x-text="pair[1]"></div>
                  </div>
                </div>
              </template>
              
              <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Station</div>
              <!-- We'll loop over stationArray -->
              <template x-for="(pair, index) in stationArray" :key="index">
                <!-- index = 0,1,2,... so we can do odd/even backgrounds -->
                <div :class="index % 2 === 0 ? 'py-1 bg-slate-100' : 'py-1 bg-slate-200'">
                  <div class="flex justify-between py-1 text-sm">
                    <!-- pair[0] = key, pair[1] = value -->
                    <div class="px-1 font-semibold" x-text="pair[0]"></div>
                    <div class="px-1" x-text="pair[1]"></div>
                  </div>
                </div>
              </template>
              
              <!-- Leaflet map container -->
              <div id="map" class="mt-4 w-full h-64 bg-gray-200"></div>
              
              <div class="font-semibold text-base border-b-2 border-lime-500 text-center">Data Source</div>
              <!-- We'll loop over stationArray -->
              <template x-for="(pair, index) in dataSourceArray" :key="index">
                <!-- index = 0,1,2,... so we can do odd/even backgrounds -->
                <div :class="index % 2 === 0 ? 'py-1 bg-slate-100' : 'py-1 bg-slate-200'">
                  <div class="flex justify-between py-1 text-sm">
                    <!-- pair[0] = key, pair[1] = value -->
                    <div class="px-1 font-semibold" x-text="pair[0]"></div>
                    <div class="px-1" x-text="pair[1]"></div>
                  </div>
                </div>
              </template>
              
              
            </div>
            
            <!-- Modal Footer -->
            <div class="flex justify-end border-t px-4 py-2">
              <button @click="closeModal()"
              class="px-4 py-2 bg-red-500 text-white rounded-sm hover:bg-red-600">
              Close
            </button>
          </div>
        </div>
        
        @push('scripts')
        <script>
          // We can define a function that returns our Alpine state
          function recordsTable() {
            return {
              showModal: false,
              record: null,
              mapInstance: null,
              stationArray: [],
              analyticalMethodArray: [],
              dataSourceArray: [],
              
              initLeaflet() {
                // We'll initialize Leaflet once when component loads
                // Leaflet CSS/JS should already be in your <head> or loaded in layout
                  // but you can also add them here if needed.
                  
                  // We'll wait to set the view until after we have coordinates
                  // or we can set some default. For now, let's do a blank init.
                  this.mapInstance = L.map('map', {
                    center: [0, 0],
                    zoom: 2
                  });
                  
                  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                  }).addTo(this.mapInstance);
                },
                
                async openModal(recordId) {
                  // Fetch record data from our /records/:id/json route
                  const response = await fetch(
                  "{{ route('codsearch.show', ':id') }}"
                  .replace(':id', recordId)
                  );
                  this.record = await response.json();
                  
                  
                  // Build an array of station entries, skipping unwanted keys and empty/null values
                  if (this.record.station) {
                    const excludedKeys = ['id', 'created_at', 'updated_at'];
                    this.stationArray = Object.entries(this.record.station)
                    .filter(([key, val]) =>
                    !excludedKeys.includes(key) &&
                    val !== null &&
                    val !== ''
                    );
                  } else {
                    this.stationArray = [];
                  }
                  
                  // Build an array of analyticalMethod entries, skipping unwanted keys and empty/null values
                  // console.log(this.record);
                  if (this.record.analytical_method) {
                    const excludedKeys = ['id', 'created_at', 'updated_at'];
                    this.analyticalMethodArray = Object.entries(this.record.analytical_method)
                    .filter(([key, val]) =>
                    !excludedKeys.includes(key) &&
                    val !== null &&
                    val !== ''
                    );
                  } else {
                    this.analyticalMethodArray = [];
                  }
                  
                  // Build an array of dataSource entries, skipping unwanted keys and empty/null values
                  if (this.record.data_source) {
                    const excludedKeys = ['id', 'created_at', 'updated_at'];
                    this.dataSourceArray = Object.entries(this.record.data_source)
                    .filter(([key, val]) =>
                    !excludedKeys.includes(key) &&
                    val !== null &&
                    val !== ''
                    );
                  } else {
                    this.dataSourceArray = [];
                  }
                  
                  // Show the modal
                  this.showModal = true;
                  
                  // Now that we have record coordinates, e.g. this.record.station.latitude, this.record.station.longitude,
                  // update the map. We'll assume lat/longitude exist on the record.
                  if (this.record.station.latitude && this.record.station.longitude) {
                    // Fly or setView to the record's location
                    this.mapInstance.setView([this.record.station.latitude, this.record.station.longitude], 7);
                    
                    // Clear existing markers (if any).
                    // We'll do a simple approach each time:
                    this.mapInstance.eachLayer((layer) => {
                      if (layer instanceof L.Marker) {
                        this.mapInstance.removeLayer(layer);
                      }
                    });
                    
                    // Add a marker
                    L.marker([this.record.station.latitude, this.record.station.longitude])
                    .addTo(this.mapInstance)
                    .bindPopup(`Record ID: ${this.record.id}`);
                  }
                },
                
                closeModal() {
                  this.showModal = false;
                  this.record = null;
                  // Optionally reset map or let it persist
                }
              }
            }
          </script>
          @endpush
          
          {{-- end of main div --}}
        </div>
      </div>
    </div>
  </div>
  
</x-app-layout>
