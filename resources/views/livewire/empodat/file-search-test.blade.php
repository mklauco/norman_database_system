<div>
  <!-- Selected Files -->
  <div id="searchResults" class="mb-4">
    @foreach ($selectedFiles as $file)
      <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700 mb-1 mr-2">
        <input type="hidden" name="fileSearch" value="{{ $file['id'] }}">
        <span class="text-sm">{{ $file['name'] }} ({{ $file['size'] }} • {{ $file['type'] }})</span>
        <button type="button" wire:click="removeFile({{ $file['id'] }})" class="ml-2 text-red-500 hover:text-red-700">x</button>
      </div>
    @endforeach
  </div>

  <!-- Search Input -->
  <input
    type="text"
    wire:model.live.debounce.300ms="search"
    name="searchFileString"
    id="searchFileString"
    placeholder="Search files by name..."
    class="w-full px-4 py-2 border border-gray-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200 ease-in-out"
  >

  <!-- Info -->
  <div class="mt-4">
    <span class="text-gray-600 text-sm">The search is limited to 10 files</span>
  </div>

  <!-- Search Results -->
  <div class="mt-2">
    @if($resultsAvailable)
      @if($results->count() > 0)
        <div class="text-sm text-gray-700 mb-2">
          To include specific files in search, check them and click <strong>Add Selected Files to Search</strong>
        </div>

        @foreach ($results as $result)
          <div class="block p-1 border-b border-gray-100">
            <label class="flex items-center space-x-2">
              <input
                wire:model="selectedFileIds"
                type="checkbox"
                name="filesSearch[]"
                value="{{ $result->id }}"
              >
              <span>{{ $result->name }}</span>
              <span class="text-xs text-gray-500">({{ $result->file_size }} • {{ $result->mime_type }} • {{ optional($result->uploaded_at)->format('Y-m-d') }})</span>
            </label>
          </div>
        @endforeach

        <div class="flex justify-end mt-3">
          <button
            type="button"
            wire:click="applyFileFilter"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            Add Selected Files to Search
          </button>

          <button
            type="button"
            wire:click="clearFilters"
            class="px-4 py-2 ml-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300"
          >
            Clear List
          </button>
        </div>
      @else
        <div class="text-sm text-red-600 mt-2">No files found</div>
      @endif
    @endif
  </div>
</div>
