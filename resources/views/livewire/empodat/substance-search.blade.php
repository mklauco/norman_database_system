<div>
    <div id="searchResults">
        @foreach ($selectedSubstances as $substance)
        <div  class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700">
            <input type="hidden" name="substances[]" value="{{$substance['id']}}">
            {{ $substance['name'] }} <span class="text-sm">({{ $substance['cas_number'] }} | {{ $substance['stdinchikey'] }})</span>
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
    <span class="text-gray-500 text-sm">the search is limited to 50 substances</span>
    <span class="text-gray-500 text-sm">search type: {{$searchType}}</span>
    <div>
        @foreach ($results as $result)
        <div class="block p-1">
            <span>
                <input wire:model="selectedSubstanceIds" type="checkbox" name="substancesSearch[]" value="{{$result->id}}">
            </span>
            <span class="ml-1">
                {{$result->name}} <span class="text-sm"> ({{$result->cas_number}} | {{$result->stdinchikey}})</span>
            </span>
        </div>
        @endforeach
        @if($resultsAvailable == true)
        @if($results->count() > 0)
        <div class="flex justify-end m-2">
            <button type="button" wire:click="applySubstanceFilter" class="btn-submit"> Apply Substance Filter</button>
        </div>
        @else
        <div class="flex justify-end m-2">
            <span class="text-red-500"> No substances found</span>
        </div>
        @endif
        @endif
    </div>
    
</div>
