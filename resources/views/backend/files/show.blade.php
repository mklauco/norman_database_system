<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- File Details Header -->
          <div class="mb-6 flex justify-between items-start">
            <div>
              <div class="mb-2">
                <span class="font-mono text-lg font-bold text-gray-900 bg-gray-200 px-3 py-1 rounded-sm">ID: {{ $file->id }}</span>
                @if($file->is_deleted)
                  <span class="ml-2 text-sm text-white bg-red-600 px-2 py-1 rounded-sm">DELETED</span>
                @endif
                @if($file->is_protected)
                  <span class="ml-2 text-sm text-white bg-red-600 px-2 py-1 rounded-sm">PROTECTED</span>
                @endif
              </div>
              <h2 class="text-2xl font-semibold text-gray-800">{{ $file->name ?? $file->original_name ?? 'Unnamed File' }}</h2>
            </div>
            <div class="flex space-x-2">
              @if(!$file->is_deleted)
                <a href="{{ route('files.edit', $file) }}" class="px-4 py-2 bg-gray-600 text-white rounded-sm hover:bg-gray-700 transition">
                  Edit
                </a>
                @if($file->file_path)
                  <a href="{{ route('files.download', $file) }}" class="btn-submit">
                    Download
                  </a>
                @else
                  <span class="px-4 py-2 bg-gray-400 text-white rounded-sm cursor-not-allowed">
                    No File Path
                  </span>
                @endif
              @else
                <form action="{{ route('files.restore', $file) }}" method="POST" class="inline">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-sm hover:bg-gray-700 transition">
                    Restore
                  </button>
                </form>
                <form action="{{ route('files.forceDestroy', $file) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete this file? This action cannot be undone!');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-sm hover:bg-gray-900 transition">
                    Delete Forever
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
                <h3 class="font-semibold text-lg text-gray-800 mb-4">Basic Information</h3>

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

                    <div>
                      <span class="text-sm font-medium text-gray-500 block">File Path:</span>
                      <span class="text-sm text-gray-900 break-all">{{ $file->file_path ?? 'N/A' }}</span>
                    </div>

                    <div>
                      <span class="text-sm font-medium text-gray-500 block">DOI:</span>
                      <span class="text-sm text-gray-900 font-mono">{{ $file->doi ?? 'N/A' }}</span>
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
                      <span class="text-sm font-medium text-gray-500 block">File Type:</span>
                      <span class="text-sm text-gray-900">
                        @if($file->is_image)
                          Image
                        @elseif($file->is_document)
                          Document
                        @elseif($file->is_spreadsheet)
                          Spreadsheet
                        @else
                          Other
                        @endif
                      </span>
                    </div>

                    <div>
                      <span class="text-sm font-medium text-gray-500 block">File Exists on Disk:</span>
                      @if($file->existsOnDisk())
                        <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-green-100 text-green-800">Yes</span>
                      @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-red-100 text-red-800">No</span>
                      @endif
                    </div>

                    <div>
                      <span class="text-sm font-medium text-gray-500 block">List Type:</span>
                      <span class="text-sm text-gray-900">{{ $file->list_type ?? 'N/A' }}</span>
                    </div>

                    <div>
                      <span class="text-sm font-medium text-gray-500 block">Matrice DCT:</span>
                      <span class="text-sm text-gray-900 font-mono">{{ $file->matrice_dct ?? 'N/A' }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Statistics -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-4">Statistics</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-3">
                    <div>
                      <span class="text-sm font-medium text-gray-500 block">Number of Records:</span>
                      <span class="text-sm text-gray-900 font-semibold font-mono">{{ number_format($file->number_of_records ?? 0, 0, '.', ' ') }}</span>
                    </div>

                    <div>
                      <span class="text-sm font-medium text-gray-500 block">Analysis Number:</span>
                      <span class="text-sm text-gray-900 font-mono">{{ $file->analysis_number ? number_format($file->analysis_number, 0, '.', ' ') : 'N/A' }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ID Ranges -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-4">ID Ranges</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <!-- Main ID Range -->
                  <div class="bg-white p-3 rounded-sm border border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Main ID Range</h4>
                    <div class="space-y-2">
                      <div class="flex justify-between">
                        <span class="text-sm text-gray-500">From:</span>
                        <span class="text-sm text-gray-900 font-mono">{{ $file->main_id_from ? number_format($file->main_id_from, 0, '.', ' ') : 'N/A' }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-sm text-gray-500">To:</span>
                        <span class="text-sm text-gray-900 font-mono">{{ $file->main_id_to ? number_format($file->main_id_to, 0, '.', ' ') : 'N/A' }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Source ID Range -->
                  <div class="bg-white p-3 rounded-sm border border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Source ID Range</h4>
                    <div class="space-y-2">
                      <div class="flex justify-between">
                        <span class="text-sm text-gray-500">From:</span>
                        <span class="text-sm text-gray-900 font-mono">{{ $file->source_id_from ? number_format($file->source_id_from, 0, '.', ' ') : 'N/A' }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-sm text-gray-500">To:</span>
                        <span class="text-sm text-gray-900 font-mono">{{ $file->source_id_to ? number_format($file->source_id_to, 0, '.', ' ') : 'N/A' }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Number:</span>
                        <span class="text-sm text-gray-900 font-mono">{{ $file->source_number ? number_format($file->source_number, 0, '.', ' ') : 'N/A' }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Method ID Range -->
                  <div class="bg-white p-3 rounded-sm border border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Method ID Range</h4>
                    <div class="space-y-2">
                      <div class="flex justify-between">
                        <span class="text-sm text-gray-500">From:</span>
                        <span class="text-sm text-gray-900 font-mono">{{ $file->method_id_from ? number_format($file->method_id_from, 0, '.', ' ') : 'N/A' }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-sm text-gray-500">To:</span>
                        <span class="text-sm text-gray-900 font-mono">{{ $file->method_id_to ? number_format($file->method_id_to, 0, '.', ' ') : 'N/A' }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Number:</span>
                        <span class="text-sm text-gray-900 font-mono">{{ $file->method_number ? number_format($file->method_number, 0, '.', ' ') : 'N/A' }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Description -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-3">Description</h3>
                <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ $file->description ?? 'No description provided.' }}</div>
              </div>

              <!-- Processing Notes -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-3">Processing Notes</h3>
                <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ $file->processing_notes ?? 'No processing notes available.' }}</div>
              </div>

              <!-- Note -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-3">Note</h3>
                <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ $file->note ?? 'No note available.' }}</div>
              </div>
            </div>

            <!-- Right Column - Associations & Metadata -->
            <div class="space-y-6">
              <!-- File Status -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-3">File Status</h3>
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Status:</span>
                    @if($file->is_deleted)
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-medium bg-red-600 text-white">Deleted</span>
                    @else
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-medium bg-green-100 text-green-800">Active</span>
                    @endif
                  </div>

                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Protected:</span>
                    @if($file->is_protected)
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-medium bg-red-100 text-red-800">Yes</span>
                    @else
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-medium bg-green-100 text-green-800">No</span>
                    @endif
                  </div>

                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">File Path:</span>
                    @if($file->file_path)
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-medium bg-green-100 text-green-800">Set</span>
                    @else
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-medium bg-gray-200 text-gray-800">Missing</span>
                    @endif
                  </div>
                </div>
              </div>

              <!-- Project Association -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-3">Project</h3>
                @if($file->project)
                  <div class="bg-white p-3 rounded-sm border">
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
                <h3 class="font-semibold text-lg text-gray-800 mb-3">Database Entity</h3>
                @if($file->databaseEntity)
                  <div class="bg-white p-3 rounded-sm border">
                    <div class="font-medium text-gray-900">{{ $file->databaseEntity->name }}</div>
                    @if($file->databaseEntity->code)
                      <div class="text-sm text-gray-600 mt-1">Code: {{ $file->databaseEntity->code }}</div>
                    @endif
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
                <h3 class="font-semibold text-lg text-gray-800 mb-3">Template</h3>
                @if($file->template)
                  <div class="bg-white p-3 rounded-sm border">
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
                <h3 class="font-semibold text-lg text-gray-800 mb-3">Upload Information</h3>
                <div class="space-y-3">
                  <div>
                    <span class="text-sm font-medium text-gray-500 block">Uploaded By:</span>
                    @if($file->uploader)
                      <div class="text-sm text-gray-900">{{ $file->uploader->first_name }} {{ $file->uploader->last_name }}</div>
                      <div class="text-xs text-gray-600">{{ $file->uploader->email }}</div>
                    @else
                      <span class="text-sm text-gray-500">Unknown</span>
                    @endif
                  </div>

                  <div>
                    <span class="text-sm font-medium text-gray-500 block">Upload Date:</span>
                    <span class="text-sm text-gray-900">
                      @if($file->uploaded_at)
                        {{ $file->uploaded_at->format('Y-m-d H:i:s') }}
                        <div class="text-xs text-gray-600">{{ $file->uploaded_at->diffForHumans() }}</div>
                      @elseif($file->created_at)
                        {{ $file->created_at->format('Y-m-d H:i:s') }}
                        <div class="text-xs text-gray-600">{{ $file->created_at->diffForHumans() }}</div>
                      @else
                        Unknown
                      @endif
                    </span>
                  </div>

                  <div>
                    <span class="text-sm font-medium text-gray-500 block">Created At:</span>
                    <span class="text-sm text-gray-900">
                      {{ $file->created_at->format('Y-m-d H:i:s') }}
                      <div class="text-xs text-gray-600">{{ $file->created_at->diffForHumans() }}</div>
                    </span>
                  </div>

                  <div>
                    <span class="text-sm font-medium text-gray-500 block">Last Modified:</span>
                    <span class="text-sm text-gray-900">
                      {{ $file->updated_at->format('Y-m-d H:i:s') }}
                      <div class="text-xs text-gray-600">{{ $file->updated_at->diffForHumans() }}</div>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Related Records -->
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="font-semibold text-lg text-gray-800 mb-3">Related Records</h3>
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Empodat Records:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-medium bg-gray-200 text-gray-800 font-mono">
                      {{ number_format($file->empodat_records_count ?? 0, 0, '.', ' ') }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Back Button -->
          <div class="mt-8 pt-6 border-t border-gray-200">
            <a href="{{ route('files.index') }}" class="link-lime-text">
              &larr; Back to Files
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
