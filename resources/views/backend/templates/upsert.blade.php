<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- Template Actions -->
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">
              {{ isset($isCreate) && $isCreate ? 'Create New Template' : 'Edit Template' }}
            </h2>
            @if(!isset($isCreate) || !$isCreate)
              <a href="{{ route('templates.show', $template) }}" class="link-lime-text">
                <i class="fa fa-eye mr-1"></i> View Details
              </a>
            @endif
          </div>

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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <!-- Left Column - Basic Information -->
              <div class="space-y-6">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-file-text mr-2 text-slate-600"></i>
                    Basic Information
                  </h3>

                  <!-- Name -->
                  <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Template Name</label>
                    <input 
                      type="text" 
                      name="name" 
                      id="name" 
                      value="{{ old('name', $template->name) }}"
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm @error('name') border-red-500 @enderror"
                      placeholder="Enter template name"
                    >
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
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm @error('description') border-red-500 @enderror"
                      placeholder="Describe the purpose and content of this template..."
                    >{{ old('description', $template->description) }}</textarea>
                    @error('description')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- Version and Validity -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-code-branch mr-2 text-slate-600"></i>
                    Version & Validity
                  </h3>

                  <!-- Version -->
                  <div class="mb-4">
                    <label for="version" class="block text-sm font-medium text-gray-700 mb-1">Version</label>
                    <input 
                      type="text" 
                      name="version" 
                      id="version" 
                      value="{{ old('version', $template->version) }}"
                      placeholder="1.0"
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm @error('version') border-red-500 @enderror"
                    >
                    <p class="mt-1 text-xs text-gray-500">Version identifier for this template</p>
                    @error('version')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>

                  <!-- Valid From -->
                  <div>
                    <label for="valid_from" class="block text-sm font-medium text-gray-700 mb-1">Valid From</label>
                    <input 
                      type="date" 
                      name="valid_from" 
                      id="valid_from" 
                      value="{{ old('valid_from', $template->valid_from ? date('Y-m-d', strtotime($template->valid_from)) : '') }}"
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm @error('valid_from') border-red-500 @enderror"
                    >
                    <p class="mt-1 text-xs text-gray-500">Date from which this template becomes valid</p>
                    @error('valid_from')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- Template File -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-file-upload mr-2 text-slate-600"></i>
                    Template File
                  </h3>

                  @if(isset($isCreate) && $isCreate)
                    <!-- File Upload - only show on create -->
                    <div>
                      <label for="template_file" class="block text-sm font-medium text-gray-700 mb-1">Select Template File</label>
                      <input 
                        type="file" 
                        name="template_file" 
                        id="template_file" 
                        class="block w-full text-sm text-gray-500 @error('template_file') border-red-500 @enderror
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-md file:border-0
                              file:text-sm file:font-semibold
                              file:bg-slate-50 file:text-slate-700
                              hover:file:bg-slate-100
                              focus:outline-hidden focus:ring-2 focus:ring-slate-500"
                        required
                      >
                      <p class="mt-2 text-sm text-gray-500">
                        <i class="fa fa-info-circle mr-1"></i>
                        Maximum file size: 10MB. Supported formats: XLSX, XLS, CSV, TXT, ZIP, RAR
                      </p>
                      @error('template_file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                      @enderror
                    </div>
                  @else
                    <!-- Current File Info - only show on edit -->
                    @if($template->file_path)
                      <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Template File</label>
                        <div class="bg-white p-3 rounded-sm border border-gray-300">
                          <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                              <i class="fa fa-file-alt text-2xl text-gray-600"></i>
                              <div>
                                <div class="font-medium text-gray-900">{{ basename($template->file_path) }}</div>
                                <div class="text-sm text-gray-500">Template file</div>
                              </div>
                            </div>
                            <a href="{{ route('templates.download', $template) }}" class="link-lime-text" title="Download">
                              <i class="fa fa-download text-lg"></i>
                            </a>
                          </div>
                        </div>
                      </div>
                    @endif
                    
                    <!-- New File Upload - only show on edit -->
                    <div>
                      <label for="template_file" class="block text-sm font-medium text-gray-700 mb-1">Replace Template File (Optional)</label>
                      <input 
                        type="file" 
                        name="template_file" 
                        id="template_file" 
                        class="block w-full text-sm text-gray-500 @error('template_file') border-red-500 @enderror
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
                      @error('template_file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                      @enderror
                    </div>
                  @endif
                </div>
              </div>

              <!-- Right Column - Associations & Settings -->
              <div class="space-y-6">
                <!-- Database Entity -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-database mr-2 text-slate-600"></i>
                    Database Entity
                  </h3>
                  <div>
                    <label for="database_entity_id" class="block text-sm font-medium text-gray-700 mb-1">Select Database Entity</label>
                    <select 
                      name="database_entity_id" 
                      id="database_entity_id" 
                      class="block w-full rounded-md border-gray-300 shadow-xs focus:border-slate-500 focus:ring-slate-500 sm:text-sm @error('database_entity_id') border-red-500 @enderror"
                    >
                      <option value="">-- No Database Entity --</option>
                      @foreach($databaseEntities as $databaseEntity)
                        <option value="{{ $databaseEntity->id }}" {{ (old('database_entity_id', $template->database_entity_id) == $databaseEntity->id) ? 'selected' : '' }}>
                          {{ $databaseEntity->name }}
                        </option>
                      @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Link this template to a specific database module</p>
                    @error('database_entity_id')
                      <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-- Template Status -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fa fa-power-off mr-2 text-slate-600"></i>
                    Template Status
                  </h3>
                  <div class="flex items-start">
                    <div class="flex items-center h-5">
                      <input 
                        type="checkbox" 
                        name="is_active" 
                        id="is_active" 
                        value="1"
                        {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}
                        class="h-4 w-4 rounded-sm border-gray-300 text-slate-600 focus:ring-slate-500"
                      >
                    </div>
                    <div class="ml-3 text-sm">
                      <label for="is_active" class="font-medium text-gray-700">Active</label>
                      <p class="text-gray-500">Inactive templates will not be shown in the template selection.</p>
                    </div>
                  </div>
                </div>

                <!-- Template Information (for edit mode) -->
                @if(!isset($isCreate) || !$isCreate)
                  <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                      <i class="fa fa-chart-line mr-2 text-slate-600"></i>
                      Template Information
                    </h3>
                    <div class="space-y-3 text-sm">
                      <div class="flex justify-between">
                        <span class="text-gray-600">Template ID:</span>
                        <span class="font-medium">#{{ $template->id }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Created:</span>
                        <span class="font-medium">{{ $template->created_at ? $template->created_at->format('M j, Y') : 'N/A' }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Last Updated:</span>
                        <span class="font-medium">{{ $template->updated_at ? $template->updated_at->format('M j, Y') : 'N/A' }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        @if($template->is_active)
                          <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Active
                          </span>
                        @else
                          <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Inactive
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
                href="{{ route('templates.index') }}" 
                class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-xs text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition"
              >
                <i class="fa fa-times mr-2"></i>
                Cancel
              </a>
              <button 
                type="submit" 
                class="btn-submit inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-xs text-sm font-medium rounded-md transition"
              >
                @if(isset($isCreate) && $isCreate)
                  <i class="fa fa-plus mr-2"></i>
                  Create Template
                @else
                  <i class="fa fa-save mr-2"></i>
                  Update Template
                @endif
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>