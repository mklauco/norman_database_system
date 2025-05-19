<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- File Details Header -->
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">File Details</h2>
            <div class="flex space-x-2">
              <a href="{{ route('files.edit', $file) }}" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition">
                Edit
              </a>
              <a href="{{ route('files.download', $file) }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                Download
              </a>
              <form action="{{ route('files.destroy', $file) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this file?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                  Delete
                </button>
              </form>
            </div>
          </div>
          
          <!-- File Details -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-4">
              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg text-gray-800 mb-2">Basic Information</h3>
                
                <div class="grid grid-cols-3 gap-2 mb-2">
                  <span class="text-sm font-medium text-gray-500">Name:</span>
                  <span class="col-span-2 text-sm">{{ $file->name ?? 'N/A' }}</span>
                </div>
                
                <div class="grid grid-cols-3 gap-2 mb-2">
                  <span class="text-sm font-medium text-gray-500">Original Filename:</span>
                  <span class="col-span-2 text-sm">{{ $file->original_name ?? 'N/A' }}</span>
                </div>
                
                <div class="grid grid-cols-3 gap-2 mb-2">
                  <span class="text-sm font-medium text-gray-500">File Size:</span>
                  <span class="col-span-2 text-sm">{{ $file->file_size ? number_format($file->file_size / 1024, 2) . ' KB' : 'N/A' }}</span>
                </div>
                
                <div class="grid grid-cols-3 gap-2 mb-2">
                  <span class="text-sm font-medium text-gray-500">MIME Type:</span>
                  <span class="col-span-2 text-sm">{{ $file->mime_type ?? 'N/A' }}</span>
                </div>
              </div>
              
              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg text-gray-800 mb-2">Description</h3>
                <p class="text-sm">{{ $file->description ?? 'No description provided.' }}</p>
              </div>
              
              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg text-gray-800 mb-2">Processing Notes</h3>
                <p class="text-sm">{{ $file->processing_notes ?? 'No processing notes available.' }}</p>
              </div>
            </div>
            
            <!-- Right Column -->
            <div class="space-y-4">
              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg text-gray-800 mb-2">Associations</h3>
                
                <div class="grid grid-cols-3 gap-2 mb-2">
                  <span class="text-sm font-medium text-gray-500">Template:</span>
                  <span class="col-span-2 text-sm">{{ $file->template->name ?? 'N/A' }}</span>
                </div>
                
                <div class="grid grid-cols-3 gap-2 mb-2">
                  <span class="text-sm font-medium text-gray-500">Database Entity:</span>
                  <span class="col-span-2 text-sm">{{ $file->databaseEntity->name ?? 'N/A' }}</span>
                </div>
              </div>
              
              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg text-gray-800 mb-2">Upload Information</h3>
                
                <div class="grid grid-cols-3 gap-2 mb-2">
                  <span class="text-sm font-medium text-gray-500">Uploaded By:</span>
                  <span class="col-span-2 text-sm">{{ $file->uploader->name ?? 'N/A' }}</span>
                </div>
                
                <div class="grid grid-cols-3 gap-2 mb-2">
                  <span class="text-sm font-medium text-gray-500">Uploaded At:</span>
                  <span class="col-span-2 text-sm">{{ $file->uploaded_at ? $file->uploaded_at->format('Y-m-d H:i:s') : ($file->created_at ? $file->created_at->format('Y-m-d H:i:s') : 'N/A') }}</span>
                </div>
              </div>
              
              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg text-gray-800 mb-2">Linked Projects</h3>
                @if($file->projects->count() > 0)
                  <ul class="list-disc list-inside text-sm">
                    @foreach($file->projects as $project)
                      <li>{{ $project->name }}</li>
                    @endforeach
                  </ul>
                @else
                  <p class="text-sm text-gray-500">No projects linked to this file.</p>
                @endif
              </div>
              
              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg text-gray-800 mb-2">Related Empodat Records</h3>
                @if($file->empodatRecords->count() > 0)
                  <div class="text-sm">
                    <p>There are {{ $file->empodatRecords->count() }} empodat records linked to this file.</p>
                    <!-- Optionally add a link to view these records -->
                  </div>
                @else
                  <p class="text-sm text-gray-500">No empodat records linked to this file.</p>
                @endif
              </div>
            </div>
          </div>
          
          <!-- Back Button -->
          <div class="mt-6">
            <a href="{{ route('files.index') }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-800">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Back to files
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>