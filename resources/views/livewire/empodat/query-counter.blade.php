<div>
    <div wire:init="init">
        {{-- Show loading spinner while the countResult is being calculated --}}
        <div wire:loading>
            <svg class="w-4 h-4 text-gray-500 animate-spin" style="animation-duration: 1s;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
        </div>     

        {{-- Show the result once it's ready --}}
        <div wire:loading.remove>
            @if (is_numeric($countResult))
                {{ number_format($countResult, 0, " ", " ") }}
            @else
                <p class="text-red-600">{{ $countResult }}</p>
            @endif
        </div>
    </div>
</div>