<x-app-layout>
  <x-slot name="header">
    @include('literature.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- Header -->
          <div class="mb-6 flex justify-between items-center">
            <div>
              @if(!$isCreate)
                <div class="mb-2">
                  <span class="font-mono text-lg font-bold text-gray-900 bg-gray-200 px-3 py-1 rounded-sm">ID: {{ $lifeStage->id }}</span>
                </div>
              @endif
              <h2 class="text-xl font-semibold text-gray-800">
                {{ $isCreate ? 'Add New Life Stage' : 'Edit Life Stage' }}
              </h2>
            </div>
          </div>

          <form
            action="{{ $isCreate ? route('literature.life_stages.store') : route('literature.life_stages.update', $lifeStage) }}"
            method="POST"
            class="space-y-6"
          >
            @csrf
            @if(!$isCreate)
              @method('PUT')
            @endif

            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">
                Life Stage Information
              </h3>

              <!-- Name -->
              <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  name="name"
                  id="name"
                  value="{{ old('name', $lifeStage->name) }}"
                  class="block w-full rounded-md border-gray-300 shadow-xs focus:border-gray-500 focus:ring-gray-500 sm:text-sm @error('name') border-red-500 @enderror"
                  placeholder="e.g., adult, juvenile, hatchling, imago, larvae"
                  required
                >
                <p class="mt-1 text-xs text-gray-500">Enter the life stage name (e.g., adult, juvenile, hatchling, imago, larvae)</p>
                @error('name')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
              <a
                href="{{ route('literature.life_stages.index') }}"
                class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-xs text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition"
              >
                Cancel
              </a>
              <button
                type="submit"
                class="btn-submit"
              >
                {{ $isCreate ? 'Create Life Stage' : 'Update Life Stage' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
