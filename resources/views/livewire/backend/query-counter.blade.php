<div>
    <div wire:init="init" class="flex flex-wrap items-center bg-gray-50 p-3 rounded-lg shadow-sm border border-gray-200">
        {{-- Show loading spinner while the countResult is being calculated --}}
        <div class="text-gray-700 mr-2">
            Number of matched records:
        </div>
        
        <div wire:loading class="flex items-center space-x-2 py-1 px-2">
            <svg class="w-5 h-5 text-indigo-500 animate-spin" style="animation-duration: 1s;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span class="text-indigo-600 font-medium">
                @if (!is_null($loadingMessage))
                    {{ $loadingMessage }}
                @else
                    Processing...
                @endif
            </span>
        </div>
        
        {{-- Show the result once ready --}}
        <div wire:loading.remove class="font-bold text-lg text-indigo-700 mr-2">
            @if (is_numeric($countResult))
                {{ number_format($countResult, 0, ".", " ") }}
            @else
                {{ $countResult }}
            @endif
        </div>
        
        <div wire:loading.remove class="flex items-center"> 
            <span class="text-gray-700">of</span>
            <span class="font-medium ml-2 text-gray-800">
                {{ number_format($resultsCount, 0, ".", " ") }}
            </span>
            
            @if (is_numeric($countResult) && $resultsCount > 0)
                <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                    @if ($countResult/$resultsCount*100 < 0.01)
                        &le; 0.01% of total
                    @else
                        {{ number_format($countResult/$resultsCount*100, 2, ".", " ") }}% of total
                    @endif
                </span>
            @endif
        </div>
    </div>
</div>