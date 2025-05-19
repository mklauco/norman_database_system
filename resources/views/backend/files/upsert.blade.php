<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <form 
            action="{{ isset($isCreate) && $isCreate ? route('files.store') : route('files.update', $file) }}" 
            method="POST" 
            enctype="multipart/form-data"
            class="space-y-6"
          >
            @csrf
            @if(!isset($isCreate) || !$isCreate)
              @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Left Column -->
              <div class="space-y-6">
                <!-- Name -->
                <div>
                  <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                  <input 
                    type="text" 
                    name="name" 
                    id="name" 
                    value="{{ old('name', $file->name) }}"
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
                  >{{ old('description', $file->description) }}</textarea>
                  @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                </div>

                @if(isset($isCreate) && $isCreate)
                  <!-- File Upload - only show on create -->
                  <div>
                    <label for="file" class="block text-sm font-medium text-gray-700">File</label>
                    <input 
                      type="file" 
                      name="file" 
                      id="file" 
                      class="mt-1 block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-md file:border-0
                            file:text-sm file:font-semibold
                            file:bg-indigo-50 file:text-indigo-700
                            hover:file:bg-indigo-100"
                    >
                    <p class="mt-1 text-sm text-gray-500">Max file size: 20MB</p>
                    @error('file')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                @else
                  <!-- Current File Info - only show on edit -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Current File</label>
                    <div class="mt-1 flex items-center">
                      <span class="block text-sm font-medium text-gray-700">{{ $file->original_name }}</span>
                      <span class="ml-2 text-xs text-gray-500">({{ number_format($file->file_size / 1024, 2) }} KB)</span>
                      <a href="{{ route('files.download', $file) }}" class="ml-2 text-indigo-600 hover:text-indigo-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                      </a>
                    </div>
                  </div>
                  
                  <!-- New File Upload - only show on edit -->
                  <div>
                    <label for="new_file" class="block text-sm font-medium text-gray-700">Replace File (Optional)</label>
                    <input 
                      type="file" 
                      name="new_file" 
                      id="new_file" 
                      class="mt-1 block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-md file:border-0
                            file:text-sm file:font-semibold
                            file:bg-indigo-50 file:text-indigo-700
                            hover:file:bg-indigo-100"
                    >
                    <p class="mt-1 text-sm text-gray-500">Leave empty to keep current file</p>
                    @error('new_file')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                @endif
              </div>

              <!-- Right Column -->
              <div class="space-y-6">
                <!-- Template -->
                <div>
                  <label for="template_id" class="block text-sm font-medium text-gray-700">Template</label>
                  <select 
                    name="template_id" 
                    id="template_id" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  >
                    <option value="">Select Template</option>
                    @foreach($templates as $template)
                      <option value="{{ $template->id }}" {{ (old('template_id', $file->template_id) == $template->id) ? 'selected' : '' }}>
                        {{ $template->name }} ({{ $template->version }})
                      </option>
                    @endforeach
                  </select>
                  @error('template_id')
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
                      <option value="{{ $databaseEntity->id }}" {{ (old('database_entity_id', $file->database_entity_id) == $databaseEntity->id) ? 'selected' : '' }}>
                        {{ $databaseEntity->name }}
                      </option>
                    @endforeach
                  </select>
                  @error('database_entity_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                </div>

                <!-- Projects -->
                <div>
                  <label for="project_ids" class="block text-sm font-medium text-gray-700">Projects</label>
                  <select 
                    name="project_ids[]" 
                    id="project_ids" 
                    multiple
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    size="5"
                  >
                    @foreach($projects as $project)
                      <option value="{{ $project->id }}" {{ (in_array($project->id, old('project_ids', $selectedProjects ?? []))) ? 'selected' : '' }}>
                        {{ $project->name }}
                      </option>
                    @endforeach
                  </select>
                  <p class="mt-1 text-sm text-gray-500">Hold Ctrl/Cmd to select multiple projects</p>
                  @error('project_ids')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                </div>

                <!-- Processing Notes -->
                <div>
                  <label for="processing_notes" class="block text-sm font-medium text-gray-700">Processing Notes</label>
                  <textarea 
                    name="processing_notes" 
                    id="processing_notes" 
                    rows="3" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  >{{ old('processing_notes', $file->processing_notes) }}</textarea>
                  @error('processing_notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-5 border-t border-gray-200">
              <a 
                href="{{ route('files.index') }}" 
                class="inline-flex justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
              >
                Cancel
              </a>
              <button 
                type="submit" 
                class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
              >
                {{ isset($isCreate) && $isCreate ? 'Upload' : 'Update' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>