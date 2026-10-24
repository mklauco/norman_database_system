@props(['authCheck' => false, 'isSuperAdmin' => false])

<!-- Modal Window -->
<div x-show="showModal" 
     x-cloak 
     @keydown.escape.window="closeModal()"
     class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50">

    <div class="bg-white w-11/12 md:w-3/4 lg:w-3/4 xl:w-2/3 rounded-sm shadow-lg relative" 
         x-transition>

        <!-- Modal Header -->
        <div class="flex justify-between items-center border-b px-4 py-2 bg-lime-600 text-white">
            <div class="flex items-center space-x-4">
                <h3 class="text-lg font-semibold">
                    Ecotox Record ID: <span x-text="recordId"></span>
                </h3>
                <h3 class="text-lg font-semibold text-lime-200">
                    Biotest ID: <span x-text="record?.ecotox_id || 'N/A'"></span>
                </h3>
            </div>
            <button @click="closeModal()" class="text-white hover:text-gray-200 text-xl">
                &times;
            </button>
        </div>

        <!-- Modal Content -->
        <div class="p-4 max-h-[70vh] overflow-y-auto">
            <!-- Loading State -->
            <div x-show="!record && showModal" class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-lime-600 mx-auto"></div>
                <p class="mt-2 text-gray-600">Loading record data...</p>
            </div>

            <!-- Table Content -->
            <div x-show="record" x-transition>
                <div class="overflow-x-auto">
                    <table class="w-full border border-gray-300 text-sm">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                                    Parameter Name
                                </th>
                                <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                                    Original
                                </th>
                                <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                                    Harmonised
                                </th>
                                <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                                    Final
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Substance Information (Static) -->
                            @include('ecotox.partials.substance-info')
                            
                            <!-- Dynamic Sections -->
                            <template x-for="row in tableRows" :key="row.id">
                                @include('ecotox.partials.table-row', [
                                    'isSuperAdmin' => $isSuperAdmin
                                ])
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-between border-t px-4 py-2">
            {{-- <button x-show="tableRows.some(r => r.isEditable)" 
                    @click="saveChanges()" 
                    class="px-4 py-2 bg-green-500 text-white rounded-sm hover:bg-green-600">
                Save Changes
            </button> --}}
            <button @click="closeModal()" 
                    class="px-4 py-2 bg-red-500 text-white rounded-sm hover:bg-red-600">
                Close
            </button>
        </div>
    </div>
</div>