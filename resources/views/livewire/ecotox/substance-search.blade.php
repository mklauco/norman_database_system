<div>
<div id="searchResults" class="mb-4">
    @if(count($selectedSubstances) > 0)
        <h3 class="text-sm font-medium text-gray-700 mb-2">Selected substance:</h3>
        <div class="flex flex-wrap gap-2">
            @foreach ($selectedSubstances as $substance)
                <div class="flex items-center px-4 py-2 rounded-lg bg-sky-50 border border-sky-200 shadow-sm group hover:bg-sky-100 transition duration-150 ease-in-out">
                    <input type="hidden" name="substances[]" value="{{$substance['id']}}">
                    
                    <div class="flex flex-col">
                        <span class="font-medium text-sky-800">{{ $substance['name'] }}</span>
                        <span class="text-xs text-sky-600">CAS: {{ $substance['cas_number'] }}</span>
                    </div>
                    
                    @if(isset($substance['ecotox_record_count']))
                        <div class="ml-3 flex items-center gap-1 px-2 py-1 rounded-full bg-sky-200 text-sky-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <span class="text-xs font-medium">{{ $substance['ecotox_record_count'] }} records</span>
                        </div>
                    @endif
                    
                    <button type="button" wire:click="removeSubstance({{$substance['id']}})" class="ml-3 text-sky-800 hover:text-red-600 focus:outline-none group-hover:text-red-600 transition duration-150 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center text-gray-500">
            No substance selected. Search and select a substance below.
        </div>
    @endif
</div>
    <input type="hidden" value="1" name="searchSubstance">
    <input type="text" wire:model.live.debounce.300ms="search" name="searchSubstanceString" id="searchSubstanceString" class="w-full px-4 py-2 border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition duration-200 ease-in-out" placeholder="Search for substances with ecotox data...">
    <div class="mt-4">
        <span class="text-gray-700">Search by:</span>
        <div class="mt-2">
            <label class="inline-flex items-center">
                <input wire:model.live.debounce.100ms="searchType" type="radio" name="searchType" value="cas_number">
                <span class="ml-2">CAS</span>
            </label>
            <label class="inline-flex items-center ml-6">
                <input wire:model.live.debounce.100ms="searchType" type="radio" name="searchType" value="name" checked>
                <span class="ml-2">Name</span>
            </label>
            <label class="inline-flex items-center ml-6">
                <input wire:model.live.debounce.100ms="searchType" type="radio" name="searchType" value="stdinchikey">
                <span class="ml-2">StdInChIKey</span>
            </label>
        </div>
    </div>
    <div class="flex flex-col md:flex-row justify-between text-sm text-gray-600 mt-2">
        <span>The search is limited to 30 substances with ecotox data</span>
        <span class="text-gray-500">Search type: {{$searchType}}</span>
    </div>
    <div>
        @if($resultsAvailable == true)
        <div class="mt-2 p-2 bg-blue-50 border-l-4 border-blue-500 text-blue-800 text-sm">
            <p>Select a substance and click <span class="font-semibold">Select & Search</span> to view its ecotoxicology data.</p>
        </div>
        @endif
        
        <div class="max-h-64 overflow-y-auto mt-2 border border-gray-200 rounded-md">
            @if($results && $results->count() > 0)
            @foreach ($results as $result)
            <div class="block p-2 hover:bg-gray-50 border-b border-gray-100 last:border-b-0">
                <label class="flex items-center cursor-pointer">
                    <input 
                    wire:model.live="selectedSubstanceIds" 
                    type="radio" 
                    name="substancesSearch" 
                    value="{{ $result->id }}"
                    class="form-radio h-4 w-4 text-indigo-600"
                    >
                    <div class="ml-2 flex-grow">
                        <div class="font-medium">{{ $result->name }}</div>
                        <div class="text-sm text-gray-500">{{ $result->cas_number }} | {{ $result->stdinchikey }}</div>
                    </div>
                    @if(isset($result->ecotox_record_count))
                    <span class="ml-2 bg-green-100 px-2 py-1 rounded-full text-xs font-medium">{{ $result->ecotox_record_count }} records</span>
                    @endif
                </label>
            </div>
            @endforeach
            @elseif($search && strlen($search) > 2)
            <div class="p-4 text-center text-gray-500">
                No substances found matching "{{ $search }}"
            </div>
            @else
            <div class="p-4 text-center text-gray-500">
                Type at least 3 characters to search
            </div>
            @endif
        </div>
        
        @if($resultsAvailable == true && $results->count() > 0)
        <div class="flex justify-end mt-4 space-x-2">
            <button 
            type="button" 
            wire:click="clearFilters" 
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition duration-150 ease-in-out"
            >
            Clear
        </button>
        <button type="button" wire:click="applySubstanceFilter" class="btn-submit-sky">
            Select & Search
        </button>
    </div>
    @elseif($resultsAvailable == true)
    <div class="flex justify-end m-2">
        <span class="text-red-500">No substances found</span>
    </div>
    @endif
</div>
</div>

@script
<script>
    // Listen for the autoSubmitForm event from the Livewire component
    document.addEventListener('livewire:initialized', () => {
        @this.on('autoSubmitForm', () => {
            // Find the parent form and submit it
            const form = document.getElementById('searchEcotox');
            if (form) {
                // Small delay to ensure the Livewire component has finished updating
                setTimeout(() => {
                    form.dispatchEvent(new Event('submit', { bubbles: true }));
                }, 100);
            }
        });
    });
</script>
@endscript