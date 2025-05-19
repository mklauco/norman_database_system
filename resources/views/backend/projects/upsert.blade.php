<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- Form Header -->
          <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-800">
              {{ isset($isCreate) && $isCreate ? 'Create New Project' : 'Edit Project' }}
            </h2>
          </div>
          
          <!-- Form -->
          <form 
            action="{{ isset($isCreate) && $isCreate ? route('projects.store') : route('projects.update', $project->id) }}" 
            method="POST" 
            class="space-y-6"
          >
            @csrf
            @if(!isset($isCreate) || !$isCreate)
              @method('PUT')
            @endif
            
            <!-- Form Fields -->
            <div class="space-y-6">
              <!-- Name -->
              <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Project Name</label>
                <input 
                  type="text" 
                  name="name" 
                  id="name" 
                  value="{{ old('name', $project->name) }}"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('name') border-red-500 @enderror"
                  required
                >
                @error('name')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>
              
              <!-- Abbreviation -->
              <div>
                <label for="abbreviation" class="block text-sm font-medium text-gray-700">Abbreviation</label>
                <input 
                  type="text" 
                  name="abbreviation" 
                  id="abbreviation" 
                  value="{{ old('abbreviation', $project->abbreviation) }}"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('abbreviation') border-red-500 @enderror"
                  required
                >
                @error('abbreviation')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>
              
              <!-- Description -->
              <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea 
                  name="description" 
                  id="description" 
                  rows="4"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('description') border-red-500 @enderror"
                >{{ old('description', $project->description) }}</textarea>
                @error('description')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>
            </div>
            
            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-3 pt-5 border-t border-gray-200">
              <a 
                href="{{ route('projects.index') }}" 
                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Cancel
              </a>
              <button 
                type="submit" 
                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                {{ isset($isCreate) && $isCreate ? 'Create Project' : 'Update Project' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>