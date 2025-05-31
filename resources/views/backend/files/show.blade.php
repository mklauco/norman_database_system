<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- File Details Header -->
          <div class="mb-6 flex justify-between items-start">
            <div class="flex items-center space-x-4">
              <div class="flex-shrink-0">
                <i class="{{ $file->file_icon }} text-3xl text-gray-600"></i>
              </div>
              <div>
                <h2 class="text-2xl font-semibold text-gray-800">{{ $file->name ?? $file->original_name ?? 'Unnamed File' }}</h2>
                <p class="text-sm text-gray-600 mt-1">
                  File ID: #{{ $file->id }} • 
                  @if($file->is_deleted)
                    <span class="text-red-600 font-medium">DELETED</span>
                  @else
                    <span class="text-green-600">Active</span>
                  @endif
                </p>
              </div>
            </div>
            <div class="flex space-x-2">
              @if(!$file->is_deleted)
                <a href="{{ route('files.edit', $file) }}" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition">
                  <i class="fa fa-edit mr-1"></i> Edit
                </a>
                @if($file->file_path && $file->existsOnDisk())
                  <a href="{{ route('files.download', $file) }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                    <i class="fa fa-download mr-1"></i> Download
                  </a>
                @else
                  <span class="px-4 py-2 bg-gray-400 text-white rounded cursor-not-allowed">
                    <i class="fa fa-times mr-1"></i> File Missing
                  </span>
                @endif
                {{-- <form action="{{ route('files.destroy', $file) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this file? This action can be reversed.');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                    <i class="fa fa-trash mr-1"></i> Delete
                  </button>
                </form> --}}
              @else
                <form action="{{ route('files.restore', $file) }}" method="POST" class="inline">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fa fa-undo mr-1"></i> Restore
                  </button>
                </form>
                <form action="{{ route('files.forceDestroy', $file) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete this file? This action cannot be undone!');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="px-4 py-2 bg-red-800 text-white rounded hover:bg-red-900 transition">
                    <i class="fa fa-trash-alt mr-1"></i> Delete Forever
                  </button>
                </form>
              @endif
            </div>
          </div>
          
          <!-- File Details -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - File Information -->
            <div class="lg:col-span-2 space-y-6">
              <!-- Basic Information -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-4 flex items-center">
                  <i class="fa fa-info-circle mr-2 text-blue-600"></i>
                  Basic Information
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-3">
                    <div>
                      <span class="text-sm font-medium text-gray-500 block">Display Name:</span>
                      <span class="text-sm text-gray-900">{{ $file->name ?? 'N/A' }}</span>
                    </div>
                    
                    <div>
                      <span class="text-sm font-medium text-gray-500 block">Original Filename:</span>
                      <span class="text-sm text-gray-900">{{ $file->original_name ?? 'N/A' }}</span>
                    </div>
                    
                    <div>
                      <span class="text-sm font-medium text-gray-500 block">File Extension:</span>
                      <span class="text-sm text-gray-900">
                        @if($file->file_extension)
                          .{{ strtoupper($file->file_extension) }}
                        @else
                          N/A
                        @endif
                      </span>
                    </div>
                  </div>
                  
                  <div class="space-y-3">
                    <div>
                      <span class="text-sm font-medium text-gray-500 block">File Size:</span>
                      <span class="text-sm text-gray-900">{{ $file->formatted_file_size ?? 'N/A' }}</span>
                    </div>
                    
                    <div>
                      <span class="text-sm font-medium text-gray-500 block">MIME Type:</span>
                      <span class="text-sm text-gray-900">{{ $file->mime_type ?? 'N/A' }}</span>
                    </div>
                    
                    <div>
                      <span class="text-sm font-medium text-gray-500 block">File Path:</span>
                      <span class="text-sm text-gray-900 break-all">{{ $file->file_path ?? 'N/A' }}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Description -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-3 flex items-center">
                  <i class="fa fa-align-left mr-2 text-green-600"></i>
                  Description
                </h3>
                <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ $file->description ?? 'No description provided.' }}</div>
              </div>
              
              <!-- Processing Notes -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-3 flex items-center">
                  <i class="fa fa-sticky-note mr-2 text-orange-600"></i>
                  Processing Notes
                </h3>
                <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ $file->processing_notes ?? 'No processing notes available.' }}</div>
              </div>
              
              <!-- Related Records -->
              @if($file->empodatRecords->count() > 0)
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <h3 class="font-semibold text-lg text-gray-800 mb-3 flex items-center">
                    <i class="fa fa-link mr-2 text-purple-600"></i>
                    Related Empodat Records
                  </h3>
                  <div class="text-sm">
                    <p class="mb-2">There are <strong>{{ number_format($file->empodatRecords->count()) }}</strong> empodat records linked to this file.</p>
                    @if($file->empodatRecords->count() <= 10)
                      <ul class="list-disc list-inside space-y-1">
                        @foreach($file->empodatRecords->take(10) as $record)
                          <li>Record #{{ $record->id }}</li>
                        @endforeach
                      </ul>
                    @else
                      <p class="text-gray-600">Too many records to display individually.</p>
                    @endif
                  </div>
                </div>
              @endif
            </div>
            
            <!-- Right Column - Associations & Metadata -->
            <div class="space-y-6">
              <!-- Project Association -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-3 flex items-center">
                  <i class="fa fa-project-diagram mr-2 text-blue-600"></i>
                  Project
                </h3>
                @if($file->project)
                  <div class="bg-white p-3 rounded border">
                    <div class="font-medium text-gray-900">{{ $file->project->name }}</div>
                    @if($file->project->abbreviation)
                      <div class="text-sm text-gray-600">{{ $file->project->abbreviation }}</div>
                    @endif
                    @if($file->project->description)
                      <div class="text-sm text-gray-600 mt-2">{{ Str::limit($file->project->description, 100) }}</div>
                    @endif
                  </div>
                @else
                  <p class="text-sm text-gray-500">No project assigned to this file.</p>
                @endif
              </div>
              
              <!-- Database Entity -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-3 flex items-center">
                  <i class="fa fa-database mr-2 text-green-600"></i>
                  Database Entity
                </h3>
                @if($file->databaseEntity)
                  <div class="bg-white p-3 rounded border">
                    <div class="font-medium text-gray-900">{{ $file->databaseEntity->name }}</div>
                    @if($file->databaseEntity->description)
                      <div class="text-sm text-gray-600 mt-1">{{ $file->databaseEntity->description }}</div>
                    @endif
                  </div>
                @else
                  <p class="text-sm text-gray-500">No database entity assigned.</p>
                @endif
              </div>
              
              <!-- Template -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-3 flex items-center">
                  <i class="fa fa-file-alt mr-2 text-purple-600"></i>
                  Template
                </h3>
                @if($file->template)
                  <div class="bg-white p-3 rounded border">
                    <div class="font-medium text-gray-900">{{ $file->template->name }}</div>
                    @if($file->template->description)
                      <div class="text-sm text-gray-600 mt-1">{{ $file->template->description }}</div>
                    @endif
                  </div>
                @else
                  <p class="text-sm text-gray-500">No template assigned.</p>
                @endif
              </div>
              
              <!-- Upload Information -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-3 flex items-center">
                  <i class="fa fa-upload mr-2 text-indigo-600"></i>
                  Upload Information
                </h3>
                <div class="space-y-3">
                  <div>
                    <span class="text-sm font-medium text-gray-500 block">Uploaded By:</span>
                    @if($file->uploader)
                      <div class="text-sm text-gray-900">{{ $file->uploader->name }}</div>
                      <div class="text-xs text-gray-600">{{ $file->uploader->email }}</div>
                    @else
                      <span class="text-sm text-gray-500">Unknown</span>
                    @endif
                  </div>
                  
                  <div>
                    <span class="text-sm font-medium text-gray-500 block">Upload Date:</span>
                    <span class="text-sm text-gray-900">
                      @if($file->uploaded_at)
                        {{ $file->uploaded_at->format('F j, Y \a\t g:i A') }}
                        <div class="text-xs text-gray-600">{{ $file->uploaded_at->diffForHumans() }}</div>
                      @elseif($file->created_at)
                        {{ $file->created_at->format('F j, Y \a\t g:i A') }}
                        <div class="text-xs text-gray-600">{{ $file->created_at->diffForHumans() }}</div>
                      @else
                        Unknown
                      @endif
                    </span>
                  </div>
                  
                  <div>
                    <span class="text-sm font-medium text-gray-500 block">Last Modified:</span>
                    <span class="text-sm text-gray-900">
                      {{ $file->updated_at->format('F j, Y \a\t g:i A') }}
                      <div class="text-xs text-gray-600">{{ $file->updated_at->diffForHumans() }}</div>
                    </span>
                  </div>
                </div>
              </div>
              
              <!-- File Status -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-3 flex items-center">
                  <i class="fa fa-info-circle mr-2 text-gray-600"></i>
                  File Status
                </h3>
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Status:</span>
                    @if($file->is_deleted)
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        <i class="fa fa-trash mr-1"></i> Deleted
                      </span>
                    @else
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fa fa-check mr-1"></i> Active
                      </span>
                    @endif
                  </div>
                  
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">File on Disk:</span>
                    @if($file->existsOnDisk())
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fa fa-check mr-1"></i> Available
                      </span>
                    @else
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        <i class="fa fa-times mr-1"></i> Missing
                      </span>
                    @endif
                  </div>
                  
                  @if($file->is_image)
                    <div class="flex items-center justify-between">
                      <span class="text-sm text-gray-600">File Type:</span>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <i class="fa fa-image mr-1"></i> Image
                      </span>
                    </div>
                  @elseif($file->is_document)
                    <div class="flex items-center justify-between">
                      <span class="text-sm text-gray-600">File Type:</span>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        <i class="fa fa-file-text mr-1"></i> Document
                      </span>
                    </div>
                  @elseif($file->is_spreadsheet)
                    <div class="flex items-center justify-between">
                      <span class="text-sm text-gray-600">File Type:</span>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                        <i class="fa fa-table mr-1"></i> Spreadsheet
                      </span>
                    </div>
                  @endif
                </div>
              </div>
            </div>
          </div>
          
          <!-- Back Button -->
          <div class="mt-8 pt-6 border-t border-gray-200">
            <a href="{{ route('files.index') }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 transition">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Back to Files
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>