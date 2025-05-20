<x-app-layout>
  <x-slot name="header">
    @if (request()->is('empodat/templates/entity/empodat*'))
    @include('empodat.header')
    @else
    @include('dashboard.header')
    @endif
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- Template Header -->
          <div class="mb-6 flex justify-between items-center">
            <div>
              <h2 class="text-xl font-semibold text-gray-800">{{ $databaseEntity->name }} Templates</h2>
              <p class="text-gray-600 mt-1">Active templates available for download</p>
            </div>
            
            <!-- Back Button -->
            <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Back
            </a>
          </div>
          
          <!-- Templates Section -->
          <div class="overflow-x-auto">
            @if($templates->count() > 0)
              <table class="table-standard w-full">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th class="py-2 px-4 text-left">Template Name</th>
                    <th class="py-2 px-4 text-left">Version</th>
                    <th class="py-2 px-4 text-left">Valid From</th>
                    <th class="py-2 px-4 text-left">File Type</th>
                    <th class="py-2 px-4 text-left">Created By</th>
                    <th class="py-2 px-4 text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($templates as $template)
                    <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif hover:bg-slate-300 transition">
                      <td class="py-2 px-4">
                        <div class="font-medium">{{ $template->name ?? 'Unnamed Template' }}</div>
                        <div class="text-xs text-gray-600 mt-1">{{ Str::limit($template->description, 60) }}</div>
                      </td>
                      <td class="py-2 px-4">{{ $template->version ?? 'N/A' }}</td>
                      <td class="py-2 px-4">{{ $template->valid_from ? date('Y-m-d', strtotime($template->valid_from)) : 'N/A' }}</td>
                      <td class="py-2 px-4">
                        @if($template->file_path)
                          <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full font-medium">
                            {{ strtoupper(pathinfo($template->file_path, PATHINFO_EXTENSION)) }}
                          </span>
                        @else
                          N/A
                        @endif
                      </td>
                      <td class="py-2 px-4">{{ $template->creator->name ?? 'N/A' }}</td>
                      <td class="py-2 px-4 text-center">
                        <a href="{{ route('templates.download', $template) }}" 
                           class="inline-flex items-center px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                          </svg>
                          Download
                        </a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            @else
              <div class="bg-gray-100 p-4 rounded text-center">
                <p>No active templates found for {{ $databaseEntity->name }}.</p>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>