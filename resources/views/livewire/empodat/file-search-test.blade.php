<div>
  <!-- Selected Files Section -->
  @if(count($selectedFiles) > 0)
    <div id="selectedFilesSection" class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-sm">
      <div class="flex items-center justify-between mb-2">
        <h4 class="text-sm font-semibold text-gray-700">
          <i class="fas fa-check-circle text-green-600 mr-1"></i>
          Selected Files ({{ count($selectedFiles) }})
        </h4>
        <button
          type="button"
          wire:click="clearFilters"
          class="text-xs text-gray-600 hover:text-red-600 underline"
        >
          Clear All
        </button>
      </div>
      <div class="space-y-1">
        @foreach ($selectedFiles as $file)
          <div class="flex items-center justify-between px-3 py-2 bg-white border border-gray-200 rounded-sm hover:bg-gray-50">
            <!-- Hidden input for form submission -->
            <input type="hidden" name="fileSearch[]" value="{{ $file['id'] }}">

            <div class="flex-1 flex items-center">
              <i class="fas fa-file text-sky-500 mr-2"></i>
              <div>
                <span class="text-sm font-medium text-gray-800">{{ $file['name'] }}</span>
                @if($file['uploaded_at'])
                  <span class="text-xs text-gray-500 ml-2">• {{ $file['uploaded_at'] }}</span>
                @endif
              </div>
            </div>

            <button
              type="button"
              wire:click="removeFile({{ $file['id'] }})"
              class="ml-3 text-red-500 hover:text-red-700 transition-colors"
              title="Remove file"
            >
              <i class="fas fa-times-circle text-lg"></i>
            </button>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  <!-- Search Input -->
  <div class="relative">
    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
    <input
      type="text"
      wire:model.live.debounce.300ms="search"
      name="searchFileString"
      id="searchFileString"
      placeholder="Search files by name or original name... (min 3 characters)"
      class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-sm shadow-xs focus:outline-hidden focus:ring-2 focus:ring-sky-500 focus:border-transparent transition duration-200 ease-in-out"
    >
    @if(strlen($search) > 0 && strlen($search) <= 2)
      <div class="absolute right-3 top-3 text-xs text-orange-600">
        Type {{ 3 - strlen($search) }} more character{{ 3 - strlen($search) > 1 ? 's' : '' }}
      </div>
    @endif
  </div>

  <!-- Info -->
  <div class="mt-2">
    <span class="text-gray-600 text-xs">
      <i class="fas fa-info-circle mr-1"></i>
      Type at least 3 characters to search. You can paste full filenames.
    </span>
    @if(strlen($search) > 2)
      <span class="ml-2 text-sky-600 text-xs">
        <i class="fas fa-spinner fa-spin mr-1" wire:loading wire:target="search"></i>
        <span wire:loading wire:target="search">Searching...</span>
      </span>
    @endif
  </div>

  <!-- Search Results -->
  <div class="mt-3">
    @if($resultsAvailable)
      @if($results->count() > 0)
        <div class="bg-sky-50 border border-sky-200 rounded-sm p-3 mb-3">
          <div class="text-sm text-gray-700 mb-2">
            <strong>{{ $results->count() }}</strong> file(s) found. Select files to include in your search:
          </div>
        </div>

        <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-sm">
          @foreach ($results as $result)
            <div class="block p-2 border-b border-gray-100 hover:bg-gray-50">
              <label class="flex items-center space-x-3 cursor-pointer">
                <input
                  wire:model="selectedFileIds"
                  type="checkbox"
                  value="{{ $result->id }}"
                  class="h-4 w-4 text-sky-600 focus:ring-sky-500 border-gray-300 rounded-sm"
                >
                <div class="flex-1">
                  <div class="flex items-center">
                    <i class="fas fa-file text-gray-400 mr-2"></i>
                    <span class="text-sm font-medium text-gray-800">{{ $result->original_name ?: $result->name }}</span>
                    <span class="text-xs text-gray-500 ml-2">• {{ optional($result->uploaded_at)->format('Y-m-d') ?: 'N/A' }}</span>
                  </div>
                </div>
              </label>
            </div>
          @endforeach
        </div>

        <div class="flex justify-between mt-3 gap-2">
          <button
            type="button"
            wire:click="selectAllDisplayed"
            class="px-4 py-2 bg-green-600 text-white rounded-sm hover:bg-green-700 transition-colors flex items-center"
          >
            <i class="fas fa-check-double mr-2"></i>
            Select All Displayed
          </button>

          <div class="flex gap-2">
            <button
              type="button"
              wire:click="applyFileFilter"
              class="px-4 py-2 bg-sky-600 text-white rounded-sm hover:bg-sky-700 transition-colors flex items-center"
            >
              <i class="fas fa-plus-circle mr-2"></i>
              Add Selected Files
            </button>

            <button
              type="button"
              wire:click="clearFilters"
              class="px-4 py-2 bg-gray-200 text-gray-800 rounded-sm hover:bg-gray-300 transition-colors"
            >
              <i class="fas fa-times mr-2"></i>
              Clear All
            </button>
          </div>
        </div>
      @else
        <div class="text-sm text-red-600 mt-2 p-2 bg-red-50 border border-red-200 rounded-sm">
          <i class="fas fa-exclamation-circle mr-1"></i>
          No files found matching your search criteria
        </div>
      @endif
    @endif
  </div>
</div>
