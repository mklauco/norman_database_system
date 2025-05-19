<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- Template Header -->
          <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-800">{{ $databaseEntity->name }} Templates</h2>
            <p class="text-gray-600 mt-1">Active templates available for download</p>
          </div>
          
          <!-- Templates Section -->
          <div class="overflow-x-auto">
            @if($templates->count() > 0)
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($templates as $template)
                  <div class="bg-gray-50 border rounded-lg overflow-hidden hover:shadow-md transition">
                    <div class="p-4 border-b">
                      <h3 class="font-medium text-lg text-gray-800">{{ $template->name ?? 'Unnamed Template' }}</h3>
                      <p class="text-sm text-gray-600 mt-1">{{ Str::limit($template->description, 100) }}</p>
                    </div>
                    
                    <div class="px-4 py-3 bg-gray-100">
                      <div class="grid grid-cols-2 gap-2 text-sm mb-2">
                        <span class="text-gray-600">Version:</span>
                        <span class="font-medium">{{ $template->version ?? 'N/A' }}</span>
                      </div>
                      
                      <div class="grid grid-cols-2 gap-2 text-sm mb-2">
                        <span class="text-gray-600">Valid From:</span>
                        <span class="font-medium">{{ $template->valid_from ? date('Y-m-d', strtotime($template->valid_from)) : 'N/A' }}</span>
                      </div>
                      
                      <div class="grid grid-cols-2 gap-2 text-sm mb-2">
                        <span class="text-gray-600">Created By:</span>
                        <span class="font-medium">{{ $template->creator->name ?? 'N/A' }}</span>
                      </div>
                      
                      <div class="grid grid-cols-2 gap-2 text-sm mb-2">
                        <span class="text-gray-600">File Type:</span>
                        <span class="font-medium">{{ $template->file_path ? strtoupper(pathinfo($template->file_path, PATHINFO_EXTENSION)) : 'N/A' }}</span>
                      </div>
                    </div>
                    
                    <div class="px-4 py-3 border-t flex justify-end">
                      <a 
                        href="{{ route('templates.download', $template) }}" 
                        class="flex items-center text-sm px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download Template
                      </a>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="bg-gray-100 p-4 rounded text-center">
                <p>No active templates found for {{ $databaseEntity->name }}.</p>
              </div>
            @endif
          </div>
          
          <!-- Back Button -->
          <div class="mt-6">
            <a href="{{ url()->previous() }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-800">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Back
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>