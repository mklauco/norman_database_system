<div>
    <div id="searchResults" class="mb-4">
        @if(count($selectedSubstances) > 0)
            <h3 class="text-sm font-medium text-gray-700 mb-2">Selected substance (only one allowed):</h3>
            <div class="flex flex-wrap gap-2">
                @foreach ($selectedSubstances as $substance)
                    <div class="flex items-center px-4 py-2 rounded-lg bg-lime-50 border border-lime-200 shadow-xs group hover:bg-lime-100 transition duration-150 ease-in-out">
                        <input type="hidden" name="substances[]" value="{{$substance['id']}}">
                        
                        <div class="flex flex-col">
                            <span class="font-medium text-lime-800">{{ $substance['name'] }}</span>
                            <span class="text-xs text-lime-600">CAS: {{ $substance['cas_number'] ?? 'N/A' }}</span>
                        </div>
                        
                        <button type="button" wire:click="removeSubstance({{$substance['id']}})" class="ml-3 text-lime-800 hover:text-red-600 focus:outline-hidden group-hover:text-red-600 transition duration-150 ease-in-out">
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
    <input type="text" wire:model.live.debounce.300ms="search" name="searchSubstanceString" id="searchSubstanceString" class="w-full px-4 py-2 border border-gray-300 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-lime-500 focus:border-transparent transition duration-200 ease-in-out" placeholder="Search for substances...">
    
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
            <label class="inline-flex items-center ml-6">
                <input wire:model.live.debounce.100ms="searchType" type="radio" name="searchType" value="code">
                <span class="ml-2">NORMAN SusDat ID</span>
            </label>
        </div>
    </div>
    
    <div class="flex flex-col md:flex-row justify-between text-sm text-gray-600 mt-2">
        <span>The search is limited to 30 substances</span>
        <span class="text-gray-500">Search type: {{$searchType}}</span>
    </div>
    
    <div>
        @if($resultsAvailable == true)
        <div class="mt-2 p-2 bg-lime-50 border-l-4 border-lime-500 text-lime-800 text-sm">
            <p><strong>Note:</strong> Only one substance can be selected at a time. Select a substance and click <span class="font-semibold">Select & Search</span> to view its factsheet data.</p>
        </div>
        @endif
        
        <div class="h-full max-h-80 overflow-y-auto mt-2 border border-gray-200 rounded-md">
            @if($results && $results->count() > 0)
            @foreach ($results as $result)
            <div class="block p-2 hover:bg-gray-50 bg-white border-b border-gray-100 last:border-b-0">
                <label class="flex items-center cursor-pointer">
                    <input 
                    wire:model.live="selectedSubstanceIds" 
                    type="radio" 
                    name="substances" 
                    value="{{ $result->id }}"
                    class="form-radio h-4 w-4 text-green-600"
                    >
                    <div class="ml-2 grow">
                        <div class="font-medium">{{ $result->name }}</div>
                        <div class="text-sm text-gray-500">
                            @if($result->cas_number)
                                CAS: {{ $result->cas_number }}
                            @endif
                            @if($result->stdinchikey)
                                @if($result->cas_number) | @endif
                                InChIKey: {{ $result->stdinchikey }}
                            @endif
                            @if($result->code)
                                @if($result->cas_number || $result->stdinchikey) | @endif
                                NORMAN ID: NS{{ $result->code }}
                            @endif
                        </div>
                    </div>
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
        
        @if($resultsAvailable == true && is_array($selectedSubstanceIds) && count($selectedSubstanceIds) > 0)
        <div class="mt-4 text-center">
            <button type="button" wire:click="applySubstanceFilter" class="btn-submit">
                Select & Search
            </button>
        </div>
        @endif
    </div>
</div>
