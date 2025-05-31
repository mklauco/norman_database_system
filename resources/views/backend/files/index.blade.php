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
            <h2 class="text-xl font-semibold text-gray-800">Files Management</h2>
            <div class="flex space-x-3">
              {{-- <a href="{{ route('files.deleted') }}" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition">
                View Deleted Files
              </a> --}}
              <a href="{{ route('files.create') }}" class="btn-create">
                Upload New File
              </a>
            </div>
          </div>

          <!-- Filters -->
          {{-- <div class="mb-6 bg-gray-50 p-4 rounded-lg">
            <form method="GET" action="{{ route('files.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">Filter by Project</label>
                <select name="project_id" id="project_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                  <option value="">All Projects</option>
                  @if(isset($projects))
                    @foreach($projects as $project)
                      <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                        {{ $project->name }}
                      </option>
                    @endforeach
                  @endif
                </select>
              </div>
              
              <div>
                <label for="database_entity_id" class="block text-sm font-medium text-gray-700 mb-1">Filter by Database Entity</label>
                <select name="database_entity_id" id="database_entity_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                  <option value="">All Entities</option>
                  @if(isset($databaseEntities))
                    @foreach($databaseEntities as $entity)
                      <option value="{{ $entity->id }}" {{ request('database_entity_id') == $entity->id ? 'selected' : '' }}>
                        {{ $entity->name }}
                      </option>
                    @endforeach
                  @endif
                </select>
              </div>
              
              <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                  Apply Filters
                </button>
              </div>
            </form>
          </div> --}}
          
          <!-- Files Table -->
          <div class="overflow-x-auto">
            @if($files->count() > 0)
              <table class="table-standard w-full">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th class="py-3 px-4 text-left">File</th>
                    <th class="py-3 px-4 text-left">Project</th>
                    <th class="py-3 px-4 text-left">Database Entity</th>
                    <th class="py-3 px-4 text-left">Template</th>
                    <th class="py-3 px-4 text-left">Size</th>
                    <th class="py-3 px-4 text-left">Uploaded By</th>
                    <th class="py-3 px-4 text-left">Uploaded At</th>
                    <th class="py-3 px-4 text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($files as $file)
                  <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif hover:bg-slate-300 transition">
                    <td class="py-3 px-4">
                      <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                          <i class="{{ $file->file_icon }} text-xl text-gray-600"></i>
                        </div>
                        <div>
                          <div class="font-medium text-gray-900">
                            {{ $file->name ?? $file->original_name ?? 'N/A' }}
                          </div>
                          @if($file->description)
                            <div class="text-sm text-gray-600">
                              {{ Str::limit($file->description, 60) }}
                            </div>
                          @endif
                          @if($file->original_name && $file->name !== $file->original_name)
                            <div class="text-xs text-gray-500">
                              Original: {{ Str::limit($file->original_name, 30) }}
                            </div>
                          @endif
                        </div>
                      </div>
                    </td>
                    <td class="py-3 px-4">
                      @if($file->project)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                          {{ $file->project->name }}
                        </span>
                      @else
                        <span class="text-gray-400">No Project</span>
                      @endif
                    </td>
                    <td class="py-3 px-4">
                      @if($file->databaseEntity)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          {{ $file->databaseEntity->name }}
                        </span>
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    <td class="py-3 px-4">
                      @if($file->template)
                        <span class="text-sm text-gray-900">{{ $file->template->name }}</span>
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    <td class="py-3 px-4">
                      @if($file->file_size)
                        <span class="text-sm text-gray-900">{{ $file->formatted_file_size }}</span>
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    <td class="py-3 px-4">
                      @if($file->uploader)
                        <div class="text-sm text-gray-900">{{ $file->uploader->name }}</div>
                        <div class="text-xs text-gray-500">{{ $file->uploader->email }}</div>
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    <td class="py-3 px-4">
                      @if($file->uploaded_at)
                        <div class="text-sm text-gray-900">{{ $file->uploaded_at->format('Y-m-d') }}</div>
                        <div class="text-xs text-gray-500">{{ $file->uploaded_at->format('H:i') }}</div>
                      @elseif($file->created_at)
                        <div class="text-sm text-gray-900">{{ $file->created_at->format('Y-m-d') }}</div>
                        <div class="text-xs text-gray-500">{{ $file->created_at->format('H:i') }}</div>
                      @else
                        <span class="text-gray-400">N/A</span>
                      @endif
                    </td>
                    <td class="py-3 px-4 text-center">
                      <div class="flex justify-center space-x-2">
                        <a href="{{ route('files.show', $file) }}" class="text-blue-600 hover:text-blue-800 p-1" title="View Details">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                        </a>
                        <a href="{{ route('files.edit', $file) }}" class="text-yellow-600 hover:text-yellow-800 p-1" title="Edit">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </a>
                        @if($file->file_path && $file->existsOnDisk())
                          <a href="{{ route('files.download', $file) }}" class="text-green-600 hover:text-green-800 p-1" title="Download">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                          </a>
                        @else
                          <span class="text-gray-400 p-1" title="File not available">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                            </svg>
                          </span>
                        @endif
                        {{-- <form action="{{ route('files.destroy', $file) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this file? This action can be reversed from the deleted files section.');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="text-red-600 hover:text-red-800 p-1" title="Delete (Soft Delete)">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                          </button>
                        </form> --}}
                      </div>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
              
              <!-- Pagination -->
              @if(method_exists($files, 'links'))
                <div class="mt-6">
                  {{ $files->appends(request()->query())->links('pagination::tailwind') }}
                </div>
              @endif
            @else
              <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
                <div class="mb-4">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No files found</h3>
                <p class="text-gray-600 mb-4">
                  @if(request()->hasAny(['project_id', 'database_entity_id']))
                    No files match your current filters. Try adjusting your search criteria.
                  @else
                    Upload your first file to get started with file management.
                  @endif
                </p>
                @if(request()->hasAny(['project_id', 'database_entity_id']))
                  <a href="{{ route('files.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition mr-3">
                    Clear Filters
                  </a>
                @endif
                <a href="{{ route('files.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                  Upload New File
                </a>
              </div>
            @endif
          </div>

          <!-- File Statistics -->
          {{-- @if($files->count() > 0)
            <div class="mt-6 bg-gray-50 p-4 rounded-lg">
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-center">
                <div>
                  <div class="text-2xl font-bold text-blue-600">{{ $files->total() }}</div>
                  <div class="text-sm text-gray-600">Total Files</div>
                </div>
                <div>
                  <div class="text-2xl font-bold text-green-600">
                    {{ $files->sum(function($file) { return $file->file_size ?: 0; }) > 0 ? 
                       number_format($files->sum(function($file) { return $file->file_size ?: 0; }) / (1024*1024), 1) . ' MB' : '0 MB' }}
                  </div>
                  <div class="text-sm text-gray-600">Total Size</div>
                </div>
                <div>
                  <div class="text-2xl font-bold text-purple-600">
                    {{ $files->whereNotNull('project_id')->count() }}
                  </div>
                  <div class="text-sm text-gray-600">With Projects</div>
                </div>
                <div>
                  <div class="text-2xl font-bold text-orange-600">
                    {{ $files->whereNotNull('database_entity_id')->count() }}
                  </div>
                  <div class="text-sm text-gray-600">With Entities</div>
                </div>
              </div>
            </div>
          @endif --}}
        </div>
      </div>
    </div>
  </div>
</x-app-layout>