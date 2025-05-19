<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
      {{ isset($isCreate) && $isCreate ? 'Create New Template' : 'Edit Template' }}
    </h2>
  </x-slot>

  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <form 
            action="{{ isset($isCreate) && $isCreate ? route('templates.store') : route('templates.update', $template) }}" 
            method="POST" 
            enctype="multipart/form-data"
            class="space-y-6"
          >
            @csrf
            @if(!isset($isCreate) || !$isCreate)
              @method('PUT')
            @endif

            <!-- Name -->
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
              <input 
                type="text" 
                name="name" 
                id="name" 
                value="{{ old('name', $template->name) }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
              @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <!-- Description -->
            <div>
              <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
              <textarea 
                name="description" 
                id="description" 
                rows="3" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >{{ old('description', $template->description) }}</textarea>
              @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <!-- Version -->
            <div>
              <label for="version" class="block text-sm font-medium text-gray-700">Version</label>
              <input 
                type="text" 
                name="version" 
                id="version" 
                value="{{ old('version', $template->version) }}"
                placeholder="1.0"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
              @error('version')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <!-- Database Entity -->
            <div>
              <label for="database_entity_id" class="block text-sm font-medium text-gray-700">Database Entity</label>
              <select 
                name="database_entity_id" 
                id="database_entity_id" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="">Select Database Entity</option>
                @foreach($databaseEntities as $databaseEntity)
                  <option value="{{ $databaseEntity->id }}" {{ (old('database_entity_id', $template->database_entity_id) == $databaseEntity->id) ? 'selected' : '' }}>
                    {{ $databaseEntity->name }}
                  </option>
                @endforeach
              </select>
              @error('database_entity_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <!-- Template File -->
            <div>
              <label for="template_file" class="block text-sm font-medium text-gray-700">
                Template File {{ $template->file_path ? '(Current: ' . basename($template->file_path) . ')' : '' }}
              </label>
              <input 
                type="file" 
                name="template_file" 
                id="template_file" 
                class="mt-1 block w-full text-sm text-gray-500
                       file:mr-4 file:py-2 file:px-4
                       file:rounded-md file:border-0
                       file:text-sm file:font-semibold
                       file:bg-indigo-50 file:text-indigo-700
                       hover:file:bg-indigo-100"
              >
              <p class="mt-1 text-sm text-gray-500">Accepted file types: .xlsx, .xls, .csv, .txt (Max 10MB)</p>
              @error('template_file')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <!-- Is Active -->
            <div class="flex items-start">
              <div class="flex items-center h-5">
                <input 
                  type="checkbox" 
                  name="is_active" 
                  id="is_active" 
                  value="1"
                  {{ old('is_active', $template->is_active) ? 'checked' : '' }}
                  class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                >
              </div>
              <div class="ml-3 text-sm">
                <label for="is_active" class="font-medium text-gray-700">Active</label>
                <p class="text-gray-500">Inactive templates will not be shown in the template selection.</p>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3">
              <a 
                href="{{ route('templates.index') }}" 
                class="inline-flex justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
              >
                Cancel
              </a>
              <button 
                type="submit" 
                class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
              >
                {{ isset($isCreate) && $isCreate ? 'Create' : 'Update' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>