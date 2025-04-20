<x-app-layout>
  <x-slot name="header">
    @include('bioassay.header')
  </x-slot>
  
   
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg" >
        <div class="p-6 text-gray-900" x-data="recordsTable()" x-init="initLeaflet()">
          {{-- main div --}}
          
          <a href="{{ route('bioassay.search.filter', [
          'countrySearch'      => $countrySearch,
          'bioassayNameSearch' => $bioassayNameSearch,
          'endpointSearch'     => $endpointSearch,
          'determinandSearch'  => $determinandSearch,
          'year_from'          => $year_from ?? '',
          'year_to'            => $year_to ?? '',
          'query_log_id'       => $query_log_id
          ]) }}">
          <button type="submit" class="btn-submit">Refine Search</button>
        </a>
        
        <div class="text-gray-600 flex border-l-2 border-white">
          @if($displayOption == 1)
          {{-- use simple output --}}
          @livewire('backend.query-counter', ['queryId' => $query_log_id, 'resultsCount' => $resultsObjectsCount, 'count_again' => request()->has('page') ? false : true])
          
          @else
          {{-- use advanced output --}}
          {{-- <span>Number of matched records: </span><span class="font-bold">&nbsp;{{number_format($resultsObjects->total(), 0, " ", " ") ?? ''}}&nbsp;</span> <span> of {{number_format($resultsObjectsCount, 0, " ", " ") }}</span>. --}}
          
          <div  class="py-2">
            Number of matched records:
          </div>
          <div class="py-2 mx-1 font-bold">
            {{ number_format($resultsObjects->total(), 0, ".", " ") }}
          </div>
          
          <div  class="py-2">
            of <span> {{number_format($resultsObjectsCount, 0, " ", " ") }}
              @if (is_numeric($resultsObjects->total()))
              @if ($resultsObjects->total()/$resultsObjectsCount*100 < 0.01)
              which is &le; 0.01% of total records.
              @else
              which is {{number_format($resultsObjects->total()/$resultsObjectsCount*100, 3, ".", " ") }}% of total records.
              @endif
              @endif
            </span>
          </div>
          
          @endif
          
          @auth
          <div class="py-2 px-2"><a href="{{ route('codsearch.download', ['query_log_id' => $query_log_id]) }}" class="btn-download">Download</a></div>
          @else
          <div class="py-2 px-2 text-gray-400">Downloads are available for registered users only</div>
          @endauth
          
          
        </div>
        
        <div class="text-gray-600 flex border-l-2 border-white">
          Search parameters:&nbsp;<span class="font-semibold">
            @foreach ($searchParameters as $key => $value)
            {{-- if value is array|collection then use for each, othervise display value --}}
            @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
            {{-- If $value is an array or collection, loop over each element --}}
            @foreach ($value as $item)
            {{ $item }}@if(!$loop->last), @endif
            @endforeach
            @else
            {{-- Otherwise, just display the single value --}}
            {{ $value }}
            @endif @if(!$loop->last); @endif
            @endforeach
          </span>
        </div>
        
        <table class="table-standard">
          <thead>
            <tr class="bg-gray-600 text-white">
              <th>ID</th>
              <th>Country</th>
              {{-- <th>Ecosystem/Matrix</th> --}}
              <th>Station Name</th>
              <th>Bioassay Name</th>
              <th>Endpoint</th>
              <th>Main Determinant</th>
              <th>Year</th>
              <th>Organisation</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($resultsObjects as $e)
            <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
              <td class="p-1 text-center">
                <div  class="">
                  {{ $e->id }}
                  {{-- <livewire:bioassay.show-sars-entry :recordId="$e->id" /> --}}
                  {{-- <a href="{{ route('codsearch.show', $e->id) }}" class="link-lime-text" x-on:click.prevent="openModal({{ $e->id }})">
                    <i class="fas fa-search"></i>
                  </a> --}}
                </div>
              </td>
              <td class="p-1 text-center">
                {{ $e->sampleData->country->name }}
              </td>
              <td class="p-1 text-center">
                {{ $e->sampleData->station_name }}
              </td>
              <td class="p-1 text-center">
                {{ $e->bioassayName->name }}
              </td>
              <td class="p-1 text-center">
                {{ $e->endpoint->name }}
              </td>
              <td class="p-1 text-center">
                {{ $e->mainDeterminand->name }}
              </td>
              </td>
              <td class="p-1 text-center">
                {{ $e->date_performed_year }}
              </td>
              <td class="p-1 text-center">
                {{ $e->sampleData->dataSource->m_ds_organisation }}
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
        
        @if($displayOption == 1)
        {{-- use simple output --}}
        
        <div class="flex justify-center space-x-4 mt-4">
          @if ($resultsObjects->onFirstPage())
          <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
            Previous
          </span>
          @else
          <a href="{{ $resultsObjects->previousPageUrl() }}" class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
            Previous
          </a>
          @endif
          
          @if ($resultsObjects->hasMorePages())
          <a href="{{ $resultsObjects->nextPageUrl() }}" class="w-32 px-4 py-2 text-center text-white bg-stone-500 rounded hover:bg-stone-600">
            Next
          </a>
          @else
          <span class="w-32 px-4 py-2 text-center text-gray-400 bg-gray-200 rounded cursor-not-allowed">
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
          <div class="bg-white w-11/12 md:w-2/3 lg:w-1/2 xl:w-1/3 rounded shadow-lg relative" x-trap.inert="showModal">
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
              class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
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
