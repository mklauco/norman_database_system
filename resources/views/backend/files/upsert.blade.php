<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- File Actions -->
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">
              {{ isset($isCreate) && $isCreate ? 'Upload New File' : 'Edit File' }}
            </h2>
            @if(!isset($isCreate) || !$isCreate)
              <a href="{{ route('files.show', $file) }}" class="text-indigo-600 hover:text-indigo-800">
                <i class="fa fa-eye mr-1"></i> View Details
              </a>
            @endif
          </div>

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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <!-- Left Column - Basic Information -->
              <div class="space-y-6">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-info-circle mr-2 text-blue-600"></i>
                    Basic Information
                  </h3>

                  <!-- Name -->
                  <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
                    <input 
                      type="text" 
                      name="name" 
                      id="name" 
                      value="{{ old('name', $file->name) }}"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('name') border-red-500 @enderror"
                      placeholder="Enter a display name for the file"
                    >
                    <p class="mt-1 text-xs text-gray-500">Leave empty to use the original filename</p>
                    @error('name')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Description -->
                  <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea 
                      name="description" 
                      id="description" 
                      rows="4" 
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('description') border-red-500 @enderror"
                      placeholder="Describe the content and purpose of this file..."
                    >{{ old('description', $file->description) }}</textarea>
                    @error('description')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- File Upload Section -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-upload mr-2 text-green-600"></i>
                    File Upload
                  </h3>

                  @if(isset($isCreate) && $isCreate)
                    <!-- File Upload - only show on create -->
                    <div>
                      <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Select File</label>
                      <input 
                        type="file" 
                        name="file" 
                        id="file" 
                        class="block w-full text-sm text-gray-500 @error('file') border-red-500 @enderror
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-md file:border-0
                              file:text-sm file:font-semibold
                              file:bg-indigo-50 file:text-indigo-700
                              hover:file:bg-indigo-100
                              focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        required
                      >
                      <p class="mt-2 text-sm text-gray-500">
                        <i class="fa fa-info-circle mr-1"></i>
                        Maximum file size: 20MB. Supported formats: CSV, Excel, PDF, Word, Text, ZIP
                      </p>
                      @error('file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                      @enderror
                    </div>
                  @else
                    <!-- Current File Info - only show on edit -->
                    <div class="mb-4">
                      <label class="block text-sm font-medium text-gray-700 mb-2">Current File</label>
                      <div class="bg-white p-3 rounded border border-gray-300">
                        <div class="flex items-center justify-between">
                          <div class="flex items-center space-x-3">
                            <i class="{{ $file->file_icon }} text-2xl text-gray-600"></i>
                            <div>
                              <div class="font-medium text-gray-900">{{ $file->original_name }}</div>
                              <div class="text-sm text-gray-500">
                                {{ $file->formatted_file_size }} • {{ $file->mime_type }}
                              </div>
                            </div>
                          </div>
                          @if($file->file_path && $file->existsOnDisk())
                            <a href="{{ route('files.download', $file) }}" class="text-indigo-600 hover:text-indigo-800" title="Download">
                              <i class="fa fa-download text-lg"></i>
                            </a>
                          @else
                            <span class="text-red-500" title="File not available">
                              <i class="fa fa-times text-lg"></i>
                            </span>
                          @endif
                        </div>
                      </div>
                    </div>
                    
                    <!-- New File Upload - only show on edit -->
                    <div>
                      <label for="new_file" class="block text-sm font-medium text-gray-700 mb-1">Replace File (Optional)</label>
                      <input 
                        type="file" 
                        name="new_file" 
                        id="new_file" 
                        class="block w-full text-sm text-gray-500 @error('new_file') border-red-500 @enderror
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-md file:border-0
                              file:text-sm file:font-semibold
                              file:bg-orange-50 file:text-orange-700
                              hover:file:bg-orange-100"
                      >
                      <p class="mt-2 text-sm text-gray-500">
                        <i class="fa fa-exclamation-triangle mr-1"></i>
                        Leave empty to keep the current file. Uploading a new file will replace the existing one.
                      </p>
                      @error('new_file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                      @enderror
                    </div>
                  @endif
                </div>

                <!-- Processing Notes -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-sticky-note mr-2 text-orange-600"></i>
                    Processing Notes
                  </h3>
                  <textarea 
                    name="processing_notes" 
                    id="processing_notes" 
                    rows="4" 
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('processing_notes') border-red-500 @enderror"
                    placeholder="Add any processing notes, validation results, or special instructions..."
                  >{{ old('processing_notes', $file->processing_notes) }}</textarea>
                  @error('processing_notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                  @enderror
                </div>
              </div>

              <!-- Right Column - Associations -->
              <div class="space-y-6">
                <!-- Project Assignment -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-project-diagram mr-2 text-blue-600"></i>
                    Project Assignment
                  </h3>
                  <div>
                    <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">Select Project</label>
                    <select 
                      name="project_id" 
                      id="project_id" 
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('project_id') border-red-500 @enderror"
                    >
                      <option value="">-- No Project --</option>
                      @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ (old('project_id', $file->project_id) == $project->id) ? 'selected' : '' }}>
                          {{ $project->name }}
                          @if($project->abbreviation)
                            ({{ $project->abbreviation }})
                          @endif
                        </option>
                      @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Associate this file with a specific project</p>
                    @error('project_id')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- Database Entity -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-database mr-2 text-green-600"></i>
                    Database Entity
                  </h3>
                  <div>
                    <label for="database_entity_id" class="block text-sm font-medium text-gray-700 mb-1">Select Database Entity</label>
                    <select 
                      name="database_entity_id" 
                      id="database_entity_id" 
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('database_entity_id') border-red-500 @enderror"
                    >
                      <option value="">-- No Database Entity --</option>
                      @foreach($databaseEntities as $databaseEntity)
                        <option value="{{ $databaseEntity->id }}" {{ (old('database_entity_id', $file->database_entity_id) == $databaseEntity->id) ? 'selected' : '' }}>
                          {{ $databaseEntity->name }}
                        </option>
                      @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Link this file to a specific database module</p>
                    @error('database_entity_id')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- Template -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-file-alt mr-2 text-purple-600"></i>
                    Template
                  </h3>
                  <div>
                    <label for="template_id" class="block text-sm font-medium text-gray-700 mb-1">Select Template</label>
                    <select 
                      name="template_id" 
                      id="template_id" 
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('template_id') border-red-500 @enderror"
                    >
                      <option value="">-- No Template --</option>
                      @foreach($templates as $template)
                        <option value="{{ $template->id }}" {{ (old('template_id', $file->template_id) == $template->id) ? 'selected' : '' }}>
                          {{ $template->name }}
                          @if(isset($template->version))
                            (v{{ $template->version }})
                          @endif
                        </option>
                      @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Associate with a data collection template if applicable</p>
                    @error('template_id')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- File Preview/Info (for edit mode) -->
                @if(!isset($isCreate) || !$isCreate)
                  <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                      <i class="fa fa-info-circle mr-2 text-gray-600"></i>
                      File Information
                    </h3>
                    <div class="space-y-3 text-sm">
                      <div class="flex justify-between">
                        <span class="text-gray-600">File ID:</span>
                        <span class="font-medium">#{{ $file->id }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Upload Date:</span>
                        <span class="font-medium">{{ $file->uploaded_at ? $file->uploaded_at->format('M j, Y') : 'N/A' }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Uploaded By:</span>
                        <span class="font-medium">{{ $file->uploader->name ?? 'Unknown' }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        @if($file->is_deleted)
                          <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Deleted
                          </span>
                        @else
                          <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Active
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
                href="{{ route('files.index') }}" 
                class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition"
              >
                <i class="fa fa-times mr-2"></i>
                Cancel
              </a>
              <button 
                type="submit" 
                class="inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition"
              >
                @if(isset($isCreate) && $isCreate)
                  <i class="fa fa-upload mr-2"></i>
                  Upload File
                @else
                  <i class="fa fa-save mr-2"></i>
                  Update File
                @endif
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>