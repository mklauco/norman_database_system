<div wire:ignore.self>
  <div class="w-full">
    <!-- Search Input -->
    <div class="relative">
      <input
        type="text"
        wire:model.debounce.300ms="search"
        placeholder="Search files by name..."
        name="fileSearch"
        id="fileSearch"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
        autocomplete="off"
      >

      <!-- Search/Loading Icon -->
      <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        @if ($isLoading)
          <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
               viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        @else
          <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        @endif
      </div>
    </div>

    <!-- Debug Info -->
    <div class="text-xs text-gray-500 mt-1">
      Live value: "{{ $search }}" | Results: {{ count($searchResults) }} | Dropdown: {{ $showDropdown ? 'true' : 'false' }}
    </div>

    <!-- Dropdown Results -->
    @if ($showDropdown)
      @if (count($searchResults) > 0)
        <div class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
          @foreach ($searchResults as $file)
            <div wire:click="selectFile({{ $file['id'] }})"
                 class="px-3 py-2 cursor-pointer hover:bg-gray-100 border-b border-gray-100 last:border-b-0">
              <div class="text-sm font-medium text-gray-900">
                {{ $file['display_name'] }}
              </div>
              <div class="text-xs text-gray-500">
                {{ $file['size'] }} • {{ $file['type'] }} • {{ $file['created_at'] }}
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg p-2 text-sm text-gray-500">
          No results found.
        </div>
      @endif
    @endif

    <!-- Selected Files -->
    @if (count($selectedFilesData) > 0)
      <div class="mt-3 p-2 bg-gray-50 rounded">
        <div class="text-sm font-medium mb-1">Selected: {{ count($selectedFilesData) }} file(s)</div>
        @foreach ($selectedFilesData as $file)
          <div class="text-xs mb-1">
            {{ $file['display_name'] }} ({{ $file['size'] }} • {{ $file['type'] }} • {{ $file['created_at'] }})
          </div>
        @endforeach
      </div>
    @endif

    <!-- Hidden inputs for form submission -->
    @foreach ($selectedFiles as $fileId)
      <input type="hidden" name="fileSearch[]" value="{{ $fileId }}">
    @endforeach
  </div>
</div>
