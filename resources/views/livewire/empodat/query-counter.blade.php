<div>
    <div wire:init="init"  class="flex">
        {{-- Show loading spinner while the countResult is being calculated --}}
        <div  class="py-2">
            Number of matched records:
        </div>
        <div wire:loading>
            <div class="flex items-center space-x-2 py-2 px-2">
                <svg class="w-4 h-4 text-gray-500 animate-spin" style="animation-duration: 1s;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <span class="text-zinc-600">
                    {{-- {{ dump($loadingMessage) }} --}}
                    @if (!is_null($loadingMessage))
                    {{ $loadingMessage }}
                    @else
                    Processing...
                    @endif
                </span>
            </div>
        </div>
        
        
        
        {{-- Show the result and the download button once ready --}}
        <div wire:loading.remove  class="py-2 mx-1 font-bold">
            @if (is_numeric($countResult))
            {{ number_format($countResult, 0, " ", " ") }}
            @else
            {{ $countResult }}
            @endif
        </div>
        
        <div class="py-2"> 
            of <span> {{number_format($empodatsCount, 0, " ", " ") }} 
                @if (is_numeric($countResult))
                @if ($countResult/$empodatsCount*100 < 0.01)
                which is &le; 0.01% of total records.
                @else
                which is {{number_format($countResult/$empodatsCount*100, 3, ".", " ") }}% of total records.
                @endif
                @endif
            </span> 
        </div>        
        
        <div class="ml-1">
            @if(auth()->check())
            <button wire:click="downloadCsv" class="btn-download py-2 px-2">
                Download IDs as CSV
            </button>
            @else
            <button class="py-2 px-2 pointer-events-none opacity-60">
                Downloads are available for registered users only.
            </button>
            @endif
        </div>
        
    </div>
</div>
