<div>
    <div wire:init="init">
        {{-- Show loading spinner while the countResult is being calculated --}}
        <div wire:loading>
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-gray-500 animate-spin" style="animation-duration: 1s;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <span>Processing...</span>
            </div>
        </div>

        {{-- Show the result and the download button once ready --}}
        <div wire:loading.remove>
            @if (is_numeric($countResult))
                <p class="text-gray-800 font-bold mb-2">
                    Total Count: {{ number_format($countResult, 0, " ", " ") }}
                </p>

                {{-- Download CSV Button
                <button 
                    wire:click="downloadCsv" 
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                >
                    Download IDs as CSV
                </button> --}}
            @else
                <p class="text-red-600">{{ $countResult }}</p>
            @endif
        </div>
    </div>
</div>
