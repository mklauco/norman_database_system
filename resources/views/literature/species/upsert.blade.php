<x-app-layout>
  <x-slot name="header">
    @include('literature.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- Header -->
          <div class="mb-6 flex justify-between items-center">
            <div>
              @if(!$isCreate)
                <div class="mb-2">
                  <span class="font-mono text-lg font-bold text-gray-900 bg-gray-200 px-3 py-1 rounded-sm">ID: {{ $species->id }}</span>
                </div>
              @endif
              <h2 class="text-xl font-semibold text-gray-800">
                {{ $isCreate ? 'Add New Species' : 'Edit Species' }}
              </h2>
            </div>
          </div>

          <form
            action="{{ $isCreate ? route('literature.species.store') : route('literature.species.update', $species) }}"
            method="POST"
            class="space-y-6"
          >
            @csrf
            @if(!$isCreate)
              @method('PUT')
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Left Column - Basic Information -->
              <div class="space-y-6">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    Basic Information
                  </h3>

                  <!-- Name -->
                  <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Common Name</label>
                    <input
                      type="text"
                      name="name"
                      id="name"
                      value="{{ old('name', $species->name) }}"
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('name') border-red-500 @enderror"
                      placeholder="e.g., Common carp"
                    >
                    @error('name')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Latin Name -->
                  <div>
                    <label for="name_latin" class="block text-sm font-medium text-gray-700 mb-1">Latin Name</label>
                    <input
                      type="text"
                      name="name_latin"
                      id="name_latin"
                      value="{{ old('name_latin', $species->name_latin) }}"
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('name_latin') border-red-500 @enderror"
                      placeholder="e.g., Cyprinus carpio"
                    >
                    @error('name_latin')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>
              </div>

              <!-- Right Column - Taxonomic Classification -->
              <div class="space-y-6">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    Taxonomic Classification
                  </h3>

                  <!-- Kingdom -->
                  <div class="mb-4">
                    <label for="kingdom" class="block text-sm font-medium text-gray-700 mb-1">Kingdom</label>
                    <input
                      type="text"
                      name="kingdom"
                      id="kingdom"
                      value="{{ old('kingdom', $species->kingdom) }}"
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('kingdom') border-red-500 @enderror"
                      placeholder="e.g., Animalia"
                    >
                    @error('kingdom')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Phylum -->
                  <div class="mb-4">
                    <label for="phylum" class="block text-sm font-medium text-gray-700 mb-1">Phylum</label>
                    <input
                      type="text"
                      name="phylum"
                      id="phylum"
                      value="{{ old('phylum', $species->phylum) }}"
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('phylum') border-red-500 @enderror"
                      placeholder="e.g., Chordata"
                    >
                    @error('phylum')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Class -->
                  <div class="mb-4">
                    <label for="class" class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                    <input
                      type="text"
                      name="class"
                      id="class"
                      value="{{ old('class', $species->class) }}"
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('class') border-red-500 @enderror"
                      placeholder="e.g., Actinopterygii"
                    >
                    @error('class')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Order -->
                  <div class="mb-4">
                    <label for="order" class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                    <input
                      type="text"
                      name="order"
                      id="order"
                      value="{{ old('order', $species->order) }}"
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('order') border-red-500 @enderror"
                      placeholder="e.g., Cypriniformes"
                    >
                    @error('order')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Genus -->
                  <div>
                    <label for="genus" class="block text-sm font-medium text-gray-700 mb-1">Genus</label>
                    <input
                      type="text"
                      name="genus"
                      id="genus"
                      value="{{ old('genus', $species->genus) }}"
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('genus') border-red-500 @enderror"
                      placeholder="e.g., Cyprinus"
                    >
                    @error('genus')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
              <a
                href="{{ route('literature.species.index') }}"
                class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-xs text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition"
              >
                Cancel
              </a>
              <button
                type="submit"
                class="btn-submit"
              >
                {{ $isCreate ? 'Create Species' : 'Update Species' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
