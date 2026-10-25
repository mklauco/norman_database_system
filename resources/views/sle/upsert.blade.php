<x-app-layout>
  <x-slot name="header">
    @include('sle.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- SLE Source Actions -->
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">
              {{ isset($isCreate) && $isCreate ? 'Create New SLE Source' : 'Edit SLE Source' }}
            </h2>
            @if(!isset($isCreate) || !$isCreate)
              <a href="{{ route('sle.sources.show', $sleSource) }}" class="text-sky-600 hover:text-sky-800">
                <i class="fa fa-eye mr-1"></i> View Details
              </a>
            @endif
          </div>

          <form 
            action="{{ isset($isCreate) && $isCreate ? route('sle.sources.store') : route('sle.sources.update', $sleSource) }}" 
            method="POST" 
            class="space-y-6"
          >
            @csrf
            @if(!isset($isCreate) || !$isCreate)
              @method('PUT')
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <!-- Left Column - Basic Information -->
              <div class="space-y-6">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-info-circle mr-2 text-sky-600"></i>
                    Basic Information
                  </h3>

                  <!-- Code -->
                  <div class="mb-4">
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                    <input 
                      type="text" 
                      name="code" 
                      id="code" 
                      value="{{ old('code', $sleSource->code) }}"
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-sky-500 focus:ring-sky-500 sm:text-sm @error('code') border-red-500 @enderror"
                      placeholder="Enter source code"
                    >
                    @error('code')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Name -->
                  <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input 
                      type="text" 
                      name="name" 
                      id="name" 
                      value="{{ old('name', $sleSource->name) }}"
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-sky-500 focus:ring-sky-500 sm:text-sm @error('name') border-red-500 @enderror"
                      placeholder="Enter source name"
                    >
                    @error('name')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Description -->
                  <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea 
                      name="description" 
                      id="description" 
                      rows="4" 
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-sky-500 focus:ring-sky-500 sm:text-sm @error('description') border-red-500 @enderror"
                      placeholder="Describe the source..."
                    >{{ old('description', $sleSource->description) }}</textarea>
                    @error('description')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Order -->
                  <div class="mb-4">
                    <label for="order" class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                    <input 
                      type="number" 
                      name="order" 
                      id="order" 
                      value="{{ old('order', $sleSource->order) }}"
                      min="0"
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-sky-500 focus:ring-sky-500 sm:text-sm @error('order') border-red-500 @enderror"
                      placeholder="Enter display order"
                    >
                    @error('order')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Show/Hide -->
                  <div class="flex items-center">
                    <input 
                      type="checkbox" 
                      name="show" 
                      id="show" 
                      value="1"
                      {{ old('show', $sleSource->show) ? 'checked' : '' }}
                      class="h-4 w-4 text-sky-600 focus:ring-sky-500 border-gray-300 rounded-sm"
                    >
                    <label for="show" class="ml-2 block text-sm text-gray-900">
                      Show this source
                    </label>
                  </div>
                </div>
              </div>

              <!-- Right Column - Link Information -->
              <div class="space-y-6">
                <!-- Link Full List -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-link mr-2 text-green-600"></i>
                    Link Full List
                  </h3>
                  <div>
                    <label for="link_full_list" class="block text-sm font-medium text-gray-700 mb-1">Full List Link (HTML)</label>
                    <textarea 
                      name="link_full_list" 
                      id="link_full_list" 
                      rows="6" 
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-sky-500 focus:ring-sky-500 sm:text-sm @error('link_full_list') border-red-500 @enderror"
                      placeholder="Enter HTML content for the full list link..."
                    >{{ old('link_full_list', $sleSource->link_full_list) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">This field accepts HTML content for links</p>
                    @error('link_full_list')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- Link InChI Key List -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-key mr-2 text-purple-600"></i>
                    Link InChI Key List
                  </h3>
                  <div>
                    <label for="link_inchikey_list" class="block text-sm font-medium text-gray-700 mb-1">InChI Key List Link (HTML)</label>
                    <textarea 
                      name="link_inchikey_list" 
                      id="link_inchikey_list" 
                      rows="6" 
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-sky-500 focus:ring-sky-500 sm:text-sm @error('link_inchikey_list') border-red-500 @enderror"
                      placeholder="Enter HTML content for the InChI key list link..."
                    >{{ old('link_inchikey_list', $sleSource->link_inchikey_list) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">This field accepts HTML content for links</p>
                    @error('link_inchikey_list')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- Link References -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-book mr-2 text-orange-600"></i>
                    Link References
                  </h3>
                  <div>
                    <label for="link_references" class="block text-sm font-medium text-gray-700 mb-1">References Link (HTML)</label>
                    <textarea 
                      name="link_references" 
                      id="link_references" 
                      rows="6" 
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-sky-500 focus:ring-sky-500 sm:text-sm @error('link_references') border-red-500 @enderror"
                      placeholder="Enter HTML content for the references link..."
                    >{{ old('link_references', $sleSource->link_references) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">This field accepts HTML content for links</p>
                    @error('link_references')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- Source Information (for edit mode) -->
                @if(!isset($isCreate) || !$isCreate)
                  <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                      <i class="fa fa-info-circle mr-2 text-gray-600"></i>
                      Source Information
                    </h3>
                    <div class="space-y-3 text-sm">
                      <div class="flex justify-between">
                        <span class="text-gray-600">Source ID:</span>
                        <span class="font-medium">#{{ $sleSource->id }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Created:</span>
                        <span class="font-medium">{{ $sleSource->created_at ? $sleSource->created_at->format('M j, Y') : 'N/A' }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Last Updated:</span>
                        <span class="font-medium">{{ $sleSource->updated_at ? $sleSource->updated_at->format('M j, Y') : 'N/A' }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        @if($sleSource->show)
                          <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Visible
                          </span>
                        @else
                          <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Hidden
                          </span>
                        @endif
                      </div>
                    </div>
                  </div>
                @endif
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
              <a 
                href="{{ route('sle.sources.index') }}" 
                class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-xs text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition"
              >
                <i class="fa fa-times mr-2"></i>
                Cancel
              </a>
              <button 
                type="submit" 
                class="inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-xs text-sm font-medium rounded-md text-white bg-sky-600 hover:bg-sky-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition"
              >
                @if(isset($isCreate) && $isCreate)
                  <i class="fa fa-plus mr-2"></i>
                  Create Source
                @else
                  <i class="fa fa-save mr-2"></i>
                  Update Source
                @endif
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
