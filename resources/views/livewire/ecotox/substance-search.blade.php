<div>
    <div id="searchResults">
        @foreach ($selectedSubstances as $substance)
        <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700 mb-2">
            <input type="hidden" name="substances[]" value="{{$substance['id']}}">
            <span class="text-sm">{{ $substance['name'] }} ({{ $substance['cas_number'] }})</span>
            <div class="ml-2">
                <button type="button" wire:click="removeSubstance({{$substance['id']}})" class="text-red-500">x</button>
            </div>
            @if(isset($substance['ecotox_record_count']))
            <span class="ml-2 bg-blue-200 px-2 py-0.5 rounded-full text-xs">{{ $substance['ecotox_record_count'] }} records</span>
            @endif
        </div>
        @endforeach
    </div>
    <input type="hidden" value="1" name="searchSubstance">
    <input type="text" wire:model.live.debounce.300ms="search" name="searchSubstanceString" id="searchSubstanceString" class="w-full px-4 py-2 border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition duration-200 ease-in-out" placeholder="Search for substances with ecotox data...">
    <div class="mt-4">
        <span class="text-gray-700">Search by:xxx</span>
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
    <span class="text-gray-600 text-sm">The search is limited to 30 substances with ecotox data</span>
    <span class="text-gray-500 text-sm block">search type: {{$searchType}}</span>
    <div>
        @if($resultsAvailable == true)
        <span class="text-red-800 text-sm block">To select a substance, click the radio button and then "Select This Substance" at the bottom</span>
        @endif
        
        <div class="mt-2">
            @if($results->count() > 0)
                @foreach ($results as $result)
                <div class="block p-1 hover:bg-gray-50">
                    <span>
                        <input wire:model="selectedSubstanceIds" type="radio" name="substancesSearch" value="{{$result->id}}"
                            {{ in_array($result->id, $selectedSubstanceIds) ? 'checked' : '' }}>
                    </span>
                    <span class="ml-1">
                        {{$result->name}} <span class="text-sm"> ({{$result->cas_number}})</span>
                    </span>
                    @if(isset($result->ecotox_record_count))
                    <span class="ml-2 bg-green-100 px-2 py-0.5 rounded text-xs">{{ $result->ecotox_record_count }} records</span>
                    @endif
                </div>
                @endforeach
            @else
                <div class="p-4 text-center text-gray-500">
                    No substances found matching your search criteria
                </div>
            @endif
        </div>
        
        @if($resultsAvailable == true && $results->count() > 0)
        <div class="flex justify-end m-2">
            <button type="button" wire:click="applySubstanceFilter" class="btn-submit-danger">Select This Substance</button>
            <button 
                type="button" 
                wire:click="clearFilters" 
                class="px-4 py-2 mx-2 bg-gray-200 text-gray-800 rounded-0 hover:bg-gray-300"
            >
                Clear
            </button>
        </div>
        @elseif($resultsAvailable == true)
        <div class="flex justify-end m-2">
            <span class="text-red-500">No substances found</span>
        </div>
        @endif
    </div>
</div>