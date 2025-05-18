<div>
    <div id="searchResults">
        @foreach ($selectedSubstances as $substance)
        <div  class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700">
            <input type="hidden" name="substances[]" value="{{$substance['id']}}">
            <span class="text-sm">{{ $substance['name'] }} ({{ $substance['cas_number'] }})</span>
            <div class="ml-2">
                <button type="button" wire:click="removeSubstance({{$substance['id']}})" class="text-red-500">x</button>
            </div>
        </div>
        @endforeach
    </div>
    <input type="hidden" value="1" name="searchSubstance">
    <input type="text" wire:model.live.debounce.300ms="search" name="searchSubstanceString" id="searchSubstanceString" class="w-full px-4 py-2 border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition duration-200 ease-in-out">
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
    <span class="text-gray-600 text-sm">the search is limited to 30 substances</span>
    <span class="text-gray-500 text-sm">search type: {{$searchType}}</span>
    <div>
        @if($resultsAvailable == true)
        <span class="text-red-800 text-sm block">To include specific substance in search, please check the substance and click on <span class="font-semibold">Add Selected Substances to Search</span> at the bottom</span>
        @endif
        @foreach ($results as $result)
        <div class="block p-1 hover:bg-gray-50">
            <label class="flex items-center cursor-pointer">
                <input 
                wire:model.live="selectedSubstanceIds" 
                type="radio" 
                name="substancesSearch" 
                value="{{ $result->id }}"
                class="form-radio"
                >
                <span class="ml-1">
                    {{ $result->name }} <span class="text-sm">({{ $result->cas_number }} | {{ $result->stdinchikey }})</span>
                </span>
                @if(isset($result->ecotox_record_count))
                <span class="ml-2 bg-green-100 px-2 py-0.5 rounded text-xs">{{ $result->ecotox_record_count }} records</span>
                @endif
            </label>
        </div>
        @endforeach
        @if($resultsAvailable == true)
        @if($results->count() > 0)
        <div class="flex justify-end m-2">
            <button type="button" wire:click="applySubstanceFilter" class="btn-submit-danger"> Add Selected Substances to Search</button>
            <button 
            type="button" 
            wire:click="clearFilters" 
            class="px-4 py-2 mx-2 bg-gray-200 text-gray-800 rounded-0 hover:bg-gray-300"
            >
            Clear List
        </button>
    </div>
    @else
    <div class="flex justify-end m-2">
        <span class="text-red-500"> No substances found</span>
    </div>
    @endif
    @endif
</div>

</div>
