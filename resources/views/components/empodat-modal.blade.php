<!-- Empodat Record Modal Window -->
<div x-data="empodatModal()"
     x-ref="empodatModal"
     x-show="showModal"
     x-cloak
     @keydown.escape.window="closeModal()"
     @open-empodat-modal.window="openModal($event.detail)"
     class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
     style="display: none;"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-white w-11/12 md:w-3/4 lg:w-2/3 xl:w-1/2 max-w-4xl rounded-lg shadow-xl relative" 
         x-show="showModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="transform scale-95 opacity-0"
         x-transition:enter-end="transform scale-100 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="transform scale-100 opacity-100"
         x-transition:leave-end="transform scale-95 opacity-0"
         x-trap.inert="showModal"
         @click.away="closeModal()">

        <!-- Modal Header -->
        <div class="flex justify-between items-center border-b px-4 py-3 bg-stone-600 text-white rounded-t-lg">
            <div class="flex items-center space-x-4">
                <h3 class="text-lg font-semibold">Record ID: <span x-text="formatRecordId(recordId)"></span></h3>
            </div>
            <button @click="closeModal()" class="text-white hover:text-gray-200 text-2xl leading-none p-1">
                &times;
            </button>
        </div>

        <!-- Modal Content -->
        <div class="p-4 max-h-[70vh] overflow-y-auto">

            <!-- Loading State -->
            <div x-show="!record && showModal" class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-stone-600 mx-auto"></div>
                <p class="mt-2 text-gray-600">Loading record data...</p>
            </div>

            <!-- Record Content -->
            <div x-show="record" x-transition>
                
                <!-- Substance Section -->
                <div class="mb-4">
                    <div class="font-semibold text-base border-b-2 border-lime-500 text-center mb-2">Substance</div>
                    <div class="space-y-1">
                        <div class="flex justify-between py-1 px-2 text-sm bg-slate-100 rounded">
                            <div class="font-semibold">Substance</div>
                            <div x-text="record?.substance?.name || 'N/A'"></div>
                        </div>
                        <div class="flex justify-between py-1 px-2 text-sm bg-slate-200 rounded">
                            <div class="font-semibold">Code</div>
                            <a :href="record?.substance_id ? `{{ route('substances.show', ':id') }}`.replace(':id', record.substance_id) : '#'"
                               target="_blank" 
                               class="text-lime-600 hover:text-lime-700 hover:underline"
                               x-text="record?.substance?.prefixed_code || 'N/A'"></a>
                        </div>
                        <div class="flex justify-between py-1 px-2 text-sm bg-slate-100 rounded">
                            <div class="font-semibold">StdInChIKey</div>
                            <div class="font-mono text-xs" x-text="record?.substance?.stdinchikey || 'N/A'"></div>
                        </div>
                        <div class="flex justify-between py-1 px-2 text-sm bg-slate-200 rounded">
                            <div class="font-semibold">CAS Number</div>
                            <div x-text="record?.substance?.cas_number || 'N/A'"></div>
                        </div>
                    </div>
                </div>

                <!-- Concentration Section -->
                <div class="mb-4">
                    <div class="font-semibold text-base border-b-2 border-lime-500 text-center mb-2">Concentration</div>
                    <div class="space-y-1">
                        <div class="flex justify-between py-1 px-2 text-sm bg-slate-100 rounded">
                            <div class="font-semibold">Concentration</div>
                            <div>
                                <template x-if="record?.concentration_indicator_id == 0">
                                    <span>N/A</span>
                                </template>
                                <template x-if="record?.concentration_indicator_id > 1">
                                    <span x-text="record?.concentration_indicator?.name || 'N/A'"></span>
                                </template>
                                <template x-if="record?.concentration_indicator_id == 1">
                                    <span>
                                        <span class="font-medium" x-text="record?.concentration_value || 'N/A'"></span>
                                        <span class="ml-1" x-text="record?.matrix?.display_unit || ''"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                        <div class="flex justify-between py-1 px-2 text-sm bg-slate-200 rounded">
                            <div class="font-semibold">Sampling Date</div>
                            <div x-text="record?.formatted_sampling_date || 'N/A'"></div>
                        </div>
                    </div>
                </div>

                <!-- Station Section with Map -->
                <div class="mb-4">
                    <div class="font-semibold text-base border-b-2 border-lime-500 text-center mb-2">Station</div>
                    
                    <!-- Station Details -->
                    <div class="space-y-1 mb-3">
                        <template x-for="(pair, index) in stationArray" :key="index">
                            <div :class="index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-200'" 
                                 class="flex justify-between py-1 px-2 text-sm rounded">
                                <div class="font-semibold" x-text="pair[0]"></div>
                                <div x-text="pair[1]"></div>
                            </div>
                        </template>
                        
                        <!-- Coordinates Display (if valid) -->
                        <template x-if="hasValidCoordinates()">
                            <div class="space-y-1">
                                <div class="flex justify-between py-1 px-2 text-sm bg-slate-100 rounded">
                                    <div class="font-semibold">Latitude</div>
                                    <div x-text="formatCoordinate(record.station.latitude, 'latitude')"></div>
                                </div>
                                <div class="flex justify-between py-1 px-2 text-sm bg-slate-200 rounded">
                                    <div class="font-semibold">Longitude</div>
                                    <div x-text="formatCoordinate(record.station.longitude, 'longitude')"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Leaflet Map Container (only show if coordinates are valid) -->
                    <template x-if="hasValidCoordinates()">
                        <div x-transition class="relative">
                            <div id="map" class="w-full h-64 bg-gray-100 rounded-lg border border-gray-300"></div>
                            <div class="absolute top-2 right-2 bg-white px-2 py-1 rounded shadow-sm text-xs text-gray-600">
                                Click map to interact
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Analytical Method Section -->
                <div class="mb-4">
                    <div class="font-semibold text-base border-b-2 border-lime-500 text-center mb-2">Analytical Method</div>
                    <div class="space-y-1">
                        <template x-for="(pair, index) in analyticalMethodArray" :key="index">
                            <div :class="index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-200'" 
                                 class="flex justify-between py-1 px-2 text-sm rounded">
                                <div class="font-semibold" x-text="pair[0]"></div>
                                <div x-text="pair[1]"></div>
                            </div>
                        </template>
                        <template x-if="analyticalMethodArray.length === 0">
                            <div class="text-center text-gray-500 py-2">No analytical method data available</div>
                        </template>
                    </div>
                </div>

                <!-- Data Source Section -->
                <div class="mb-4">
                    <div class="font-semibold text-base border-b-2 border-lime-500 text-center mb-2">Data Source</div>
                    <div class="space-y-1">
                        <template x-for="(pair, index) in dataSourceArray" :key="index">
                            <div :class="index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-200'" 
                                 class="flex justify-between py-1 px-2 text-sm rounded">
                                <div class="font-semibold" x-text="pair[0]"></div>
                                <div x-text="pair[1]"></div>
                            </div>
                        </template>
                        <template x-if="dataSourceArray.length === 0">
                            <div class="text-center text-gray-500 py-2">No data source information available</div>
                        </template>
                    </div>
                </div>

                <!-- Additional Record Details (Minor) Section -->
                <div class="mb-4">
                    <div class="font-semibold text-base border-b-2 border-lime-500 text-center mb-2">Additional Record Details</div>
                    <div class="space-y-1">
                        <template x-for="(pair, index) in minorArray" :key="index">
                            <div :class="index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-200'"
                                 class="flex justify-between py-1 px-2 text-sm rounded">
                                <div class="font-semibold" x-text="pair[0]"></div>
                                <div x-text="pair[1]"></div>
                            </div>
                        </template>
                        <template x-if="minorArray.length === 0">
                            <div class="text-center text-gray-500 py-2">No additional details available</div>
                        </template>
                    </div>
                </div>

                <!-- Matrix Data Section -->
                <div class="mb-4">
                    <div class="font-semibold text-base border-b-2 border-teal-500 text-center mb-2">
                        Matrix Metadata
                        <template x-if="record?.matrix_data?.type">
                            <span class="text-sm font-normal text-gray-600" x-text="'(' + record.matrix_data.type.replace(/_/g, ' ') + ')'"></span>
                        </template>
                    </div>
                    <template x-if="record?.matrix_data && metaDataArray.length > 0">
                        <div class="space-y-1">
                            <template x-for="(pair, index) in metaDataArray" :key="index">
                                <div :class="index % 2 === 0 ? 'bg-teal-50' : 'bg-teal-100'"
                                     class="flex justify-between py-1 px-2 text-sm rounded">
                                    <div class="font-semibold text-teal-900" x-text="pair[0]"></div>
                                    <div class="text-teal-800" x-text="pair[1]"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!record?.matrix_data || metaDataArray.length === 0">
                        <div class="text-center text-gray-500 py-2">No matrix metadata available</div>
                    </template>
                </div>

            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-end border-t px-4 py-3 bg-gray-50 rounded-b-lg">
            <button @click="closeModal()" 
                    class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors duration-200">
                Close
            </button>
        </div>
    </div>
</div>