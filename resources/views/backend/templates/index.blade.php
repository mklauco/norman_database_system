<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- Template Actions -->
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Templates</h2>
            <a href="{{ route('templates.create') }}" class="btn-create">
              Add New Template
            </a>
          </div>
          
          <!-- Templates Table -->
          <div class="overflow-x-auto">
            @if($templates->count() > 0)
              <table class="table-standard w-full">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th class="py-2 px-4 text-left">Name</th>
                    <th class="py-2 px-4 text-left">Version</th>
                    <th class="py-2 px-4 text-left">Database Entity</th>
                    <th class="py-2 px-4 text-left">Status</th>
                    <th class="py-2 px-4 text-left">Valid from</th>
                    <th class="py-2 px-4 text-left">Created By</th>
                    <th class="py-2 px-4 text-left">Created At</th>
                    <th class="py-2 px-4 text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($templates as $template)
                  <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif hover:bg-slate-300 transition">
                    <td class="py-2 px-4">
                      <div class="font-medium">{{ $template->name ?? 'N/A' }}</div>
                      <div class="text-sm text-gray-600">{{ Str::limit($template->description, 50) }}</div>
                    </td>
                    <td class="py-2 px-4">{{ $template->version ?? 'N/A' }}</td>
                    <td class="py-2 px-4">{{ $template->databaseEntity->name ?? 'N/A' }}</td>
                    <td class="py-2 px-4">
                      @if($template->is_active)
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                          Active
                        </span>
                      @else
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                          Inactive
                        </span>
                      @endif
                    </td>
                    <td class="py-2 px-4">
                      @if($template->valid_from)
                        {{ $template->valid_from->format('Y-m-d') }}
                      @else
                        N/A
                      @endif
                    <td class="py-2 px-4">{{ $template->creator->full_name ?? 'N/A' }}</td>
                    <td class="py-2 px-4">{{ $template->created_at ? $template->created_at->format('Y-m-d H:i') : 'N/A' }}</td>
                    <td class="py-2 px-4 text-center">
                      <div class="flex justify-center space-x-2">
                        <a href="{{ route('templates.show', $template) }}" class="text-blue-600 hover:text-blue-800" title="View">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                        </a>
                        <a href="{{ route('templates.edit', $template) }}" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </a>
                        @if($template->file_path)
                          <a href="{{ route('templates.download', $template) }}" class="text-green-600 hover:text-green-800" title="Download">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                          </a>
                        @endif
                        <form action="{{ route('templates.destroy', $template) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this template?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
              
              <!-- Pagination Links - if you're using pagination -->
              @if(method_exists($templates, 'links'))
                <div class="mt-4">
                  {{ $templates->links() }}
                </div>
              @endif
            @else
              <div class="bg-gray-100 p-4 rounded text-center">
                <p>No templates found. Create your first template to get started.</p>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>