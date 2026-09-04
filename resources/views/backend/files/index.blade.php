<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- Flash Messages -->
          @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
              {{ session('success') }}
            </div>
          @endif
          @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
              {{ session('error') }}
            </div>
          @endif

          <!-- File Actions -->
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Files Management</h2>
            <div class="flex space-x-3">
              @php
                $exportParams = array_filter([
                    'database_entity_id' => $databaseEntityId,
                    'search' => $search,
                    'sort' => $sort,
                    'direction' => $direction,
                ], fn($v) => $v !== '' && $v !== null);
              @endphp
              <a href="{{ route('files.export.csv', $exportParams) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50" title="Download filtered list as CSV">
                Export CSV
              </a>
              <a href="{{ route('files.export.markdown', $exportParams) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50" title="Download filtered list as Markdown">
                Export Markdown
              </a>
              <a href="{{ route('files.create') }}" class="btn-submit">
                Upload New File
              </a>
            </div>
          </div>

          <!-- Database Filter (Master) -->
          <div class="mb-4 flex items-end gap-4">
            <div>
              <label for="database_entity_id" class="block text-sm font-medium text-gray-700">Database</label>
              <select id="database_entity_id" onchange="window.location.href='{{ route('files.index') }}?database_entity_id=' + this.value" class="mt-1 block w-64 pl-3 pr-10 py-2 text-base border-gray-300 rounded-md focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm">
                <option value="">All Databases</option>
                @foreach($databaseEntities as $entity)
                  <option value="{{ $entity->id }}" {{ $databaseEntityId == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                @endforeach
              </select>
            </div>

            @if((int) $databaseEntityId === config('empodat_suspect.database_entity_id'))
              <x-empodat-suspect-onboarding-modal />
            @endif
          </div>

          <!-- Search and Filter Form -->
          <form method="GET" action="{{ route('files.index') }}" id="filterForm" class="mb-6">
            <input type="hidden" name="database_entity_id" value="{{ $databaseEntityId }}">
            <div class="flex justify-between items-center">
              <div class="flex space-x-4 flex-1">
                <div class="w-32">
                  <label for="perPage" class="block text-sm font-medium text-gray-700">Show</label>
                  <select name="per_page" id="perPage" onchange="document.getElementById('filterForm').submit()" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 rounded-md focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                  </select>
                </div>

                <div class="flex-1">
                  <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                  <input type="text"
                    name="search"
                    id="search"
                    value="{{ $search }}"
                    placeholder="Search by name or description..."
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm">
                </div>

                <div class="flex items-end">
                  <button type="submit" class="btn-submit px-4 py-2">
                    Search
                  </button>
                  @if($search || $perPage != 100)
                    <a href="{{ route('files.index', ['database_entity_id' => $databaseEntityId]) }}" class="ml-2 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                      Clear
                    </a>
                  @endif
                </div>
              </div>
            </div>

            <input type="hidden" name="sort" id="sortField" value="{{ $sort }}">
            <input type="hidden" name="direction" id="sortDirection" value="{{ $direction }}">
          </form>

          <!-- Files Table -->
          <div>
            <table class="table-standard w-full text-xs table-fixed">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="px-1 py-1 text-left w-[50px]">ID</th>
                  <th class="px-1 py-1 text-left">File Name</th>
                  <th class="px-1 py-1 text-left w-[80px]">Project</th>
                  <th class="px-1 py-1 text-left w-[70px]">DB</th>
                  <th class="px-1 py-1 text-right w-[85px] whitespace-nowrap">ID From</th>
                  <th class="px-1 py-1 text-right w-[85px] whitespace-nowrap">ID To</th>
                  <th class="px-1 py-1 text-right w-[85px] whitespace-nowrap">Records</th>
                  <th class="px-1 py-1 text-center w-[55px]">Protected</th>
                  <th class="px-1 py-1 text-center w-[50px]">Deleted</th>
                  <th class="px-1 py-1 text-left w-[80px]">Uploaded By</th>
                  <th class="px-1 py-1 text-left w-[65px]">Date</th>
                  <th class="px-1 py-1 text-center w-[70px]">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($files as $file)
                  <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif hover:bg-slate-300">
                    <td class="px-1 py-1 font-mono break-all">{{ $file->id }}</td>
                    <td class="px-1 py-1 break-words">{{ $file->original_name ?? $file->name ?? '-' }} @if($file->formatted_file_size)<span class="text-gray-500">({{ $file->formatted_file_size }})</span>@endif</td>
                    <td class="px-1 py-1 break-words">{{ $file->project->name ?? '-' }}</td>
                    <td class="px-1 py-1 break-words">{{ $file->databaseEntity->name ?? '-' }}</td>
                    <td class="px-1 py-1 text-right font-mono whitespace-nowrap">{{ $file->main_id_from ? number_format($file->main_id_from, 0, '.', ' ') : '-' }}</td>
                    <td class="px-1 py-1 text-right font-mono whitespace-nowrap">{{ $file->main_id_to ? number_format($file->main_id_to, 0, '.', ' ') : '-' }}</td>
                    <td class="px-1 py-1 text-right font-mono whitespace-nowrap">{{ number_format($file->number_of_records ?? 0, 0, '.', ' ') }}</td>
                    <td class="px-1 py-1 text-center">{{ $file->is_protected ? 'Yes' : 'No' }}</td>
                    <td class="px-1 py-1 text-center">
                      @if($file->is_deleted)
                        <span class="px-1 py-0.5 bg-red-600 text-white text-xs font-medium rounded">Yes</span>
                      @else
                        No
                      @endif
                    </td>
                    <td class="px-1 py-1 break-words">{{ $file->uploader ? $file->uploader->first_name . ' ' . $file->uploader->last_name : '-' }}</td>
                    <td class="px-1 py-1 break-words">{{ $file->uploaded_at ? $file->uploaded_at->format('Y-m-d') : '-' }}</td>
                    <td class="px-1 py-2 text-center">
                      @role(['admin', 'super_admin'])
                      <div class="flex justify-center space-x-1">
                        <a href="{{ route('files.show', $file) }}" class="text-gray-600 hover:text-gray-900" title="View">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                        </a>
                        <a href="{{ route('files.edit', $file) }}" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </a>
                        @if($file->file_path && Storage::disk('public')->exists($file->file_path))
                          <a href="{{ route('files.download', $file) }}" class="text-green-600 hover:text-green-800" title="Download">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                          </a>
                        @endif
                        @if($file->database_entity_id)
                          <form action="{{ route('files.rescan', $file) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-purple-600 hover:text-purple-800" title="Rescan">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                              </svg>
                            </button>
                          </form>
                        @endif
                      </div>
                      @endrole
                    </td>
                  </tr>
                @empty
                  <tr class="bg-slate-100">
                    <td colspan="12" class="py-6 px-4 text-center text-gray-500">
                      No files found.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="mt-4">
            {{ $files->links('pagination::tailwind') }}
          </div>

          <div class="mt-2 text-sm text-gray-700 text-center">
            @if($files->total() > 0)
              Showing {{ $files->firstItem() }} to {{ $files->lastItem() }} of {{ $files->total() }} files
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
