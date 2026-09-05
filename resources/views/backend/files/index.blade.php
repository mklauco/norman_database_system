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

            @if($showingEmpodatSuspect)
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
          {{-- EVERY column carries a width, File Name included.
               With table-fixed, a column without one absorbs all remaining
               space: File Name was taking ~950px on a wide screen while Date,
               Uploaded By and DB wrapped onto two lines. With all widths
               declared, the browser distributes any surplus proportionally
               instead of dumping it into one column.
               The widths below are sized to real content at text-xs:
               "Empodat Suspect", "Martin Klaučo", "2026-08-19", 11-digit ids.
               They total 1358px, which is what min-w-[1360px] holds — below
               that the container scrolls rather than scaling every column
               down and pushing the Actions icons outside their cell. --}}
          <div class="overflow-x-auto">
            <table class="table-standard w-full min-w-[1360px] text-xs table-fixed">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="px-1 py-1 text-left w-[60px]">ID</th>
                  <th class="px-1 py-1 text-left w-[300px]">File Name</th>
                  <th class="px-1 py-1 text-left w-[70px]">Project</th>
                  <th class="px-1 py-1 text-left w-[100px]">DB</th>
                  <th class="px-1 py-1 text-right w-[90px] whitespace-nowrap">ID From</th>
                  <th class="px-1 py-1 text-right w-[90px] whitespace-nowrap">ID To</th>
                  <th class="px-1 py-1 text-right w-[90px] whitespace-nowrap">Records</th>
                  <th class="px-1 py-1 text-center w-[70px]">Protected</th>
                  <th class="px-1 py-1 text-center w-[60px]">Deleted</th>
                  <th class="px-1 py-1 text-left w-[100px]">Uploaded By</th>
                  <th class="px-1 py-1 text-left w-[80px]">Date</th>
                  @if($showPrioritisationCoverage)
                    <th class="px-1 py-1 text-right w-[90px]" title="Rows this file contributes to empodat_suspect_prioritisation_dataset, as recorded by its last successful rebuild">
                      Prioritisation rows
                    </th>
                  @endif
                  @if($showingEmpodatSuspect)
                    @role('super_admin')
                      <th class="px-1 py-1 text-center w-[80px]">Prioritisation</th>
                    @endrole
                  @endif
                  {{-- 4 icons x 16px + 3 gaps x 2px + px-1 padding = 78px.
                       Anything narrower and the icons overflow the cell,
                       because table-fixed will not grow a column to fit its
                       content. --}}
                  <th class="px-1 py-1 text-center w-[78px]">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($files as $file)
                  <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif hover:bg-slate-300">
                    <td class="px-1 py-1 font-mono break-all">{{ $file->id }}</td>
                    <td class="px-1 py-1 break-words">{{ $file->original_name ?? $file->name ?? '-' }} @if($file->formatted_file_size)<span class="text-gray-500">({{ $file->formatted_file_size }})</span>@endif</td>
                    <td class="px-1 py-1 truncate" title="{{ $file->project->name ?? '' }}">{{ $file->project->name ?? '-' }}</td>
                    <td class="px-1 py-1 truncate" title="{{ $file->databaseEntity->name ?? '' }}">{{ $file->databaseEntity->name ?? '-' }}</td>
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
                    @php
                      $uploaderName = $file->uploader ? $file->uploader->first_name.' '.$file->uploader->last_name : '-';
                    @endphp
                    <td class="px-1 py-1 truncate" title="{{ $uploaderName }}">{{ $uploaderName }}</td>
                    <td class="px-1 py-1 whitespace-nowrap font-mono">{{ $file->uploaded_at ? $file->uploaded_at->format('Y-m-d') : '-' }}</td>
                    @if($showPrioritisationCoverage)
                      @php
                        $coverage = $prioritisationCoverage[$file->id] ?? null;
                      @endphp
                      <td class="px-1 py-1 text-right whitespace-nowrap">
                        @if($coverage === null)
                          <span class="text-gray-400" title="No rebuild has ever been recorded for this file">Never built</span>
                        @elseif($coverage['row_count'] === null)
                          <span class="text-red-700" title="Every recorded rebuild for this file failed or was interrupted">No successful build</span>
                        @else
                          <span class="font-mono">{{ number_format($coverage['row_count'], 0, '.', ' ') }}</span>
                          @if($coverage['is_stale'])
                            <span class="text-amber-700" title="The most recent rebuild attempt did not succeed ({{ $coverage['latest_status'] }}). This count is from an earlier build.">&#9888;</span>
                          @endif
                          <span class="block text-gray-500 whitespace-nowrap">{{ $coverage['built_at']?->format('Y-m-d') ?? '-' }}</span>
                          @if($showBuildDuration && $coverage['duration_ms'] !== null)
                            {{-- Own line: the column is 90px and the date
                                 already fills it. --}}
                            <span class="block text-gray-500 font-mono whitespace-nowrap"
                                  title="Wall-clock time of the last successful rebuild of this partition. Excludes the shared basin lookup, which is rebuilt once per command run.">{{ number_format($coverage['duration_ms'] / 1000, 1, '.', ' ') }} s</span>
                          @endif
                        @endif
                      </td>
                    @endif
                    @if($showingEmpodatSuspect)
                      @role('super_admin')
                        <td class="px-1 py-2 text-center">
                          {{-- x-data="{}" so Alpine initialises the button and
                               $dispatch resolves; the modal itself is a single
                               instance rendered once below the table. --}}
                          <button type="button"
                                  x-data="{}"
                                  @disabled($hasActiveEmpodatSuspectRun)
                                  @if(! $hasActiveEmpodatSuspectRun)
                                    @click="$dispatch('empodat-suspect-rebuild-requested', {
                                      fileId: {{ $file->id }},
                                      fileName: @js($file->original_name ?? $file->name ?? ''),
                                      rowCount: @js(isset($prioritisationCoverage[$file->id]['row_count'])
                                          ? number_format($prioritisationCoverage[$file->id]['row_count'], 0, '.', ' ')
                                          : null),
                                      action: @js(route('files.refresh_prioritisation', $file))
                                    })"
                                  @endif
                                  title="{{ $hasActiveEmpodatSuspectRun
                                      ? 'Another EMPODAT Suspect command is queued or running.'
                                      : 'Queue empodat-suspect:refresh-prioritisation --file=' . $file->id }}"
                                  class="px-2 py-1 rounded text-xs font-medium border
                                    {{ $hasActiveEmpodatSuspectRun
                                        ? 'border-gray-300 text-gray-400 bg-gray-100 cursor-not-allowed'
                                        : 'border-lime-600 text-lime-700 bg-white hover:bg-lime-50' }}">
                            Rebuild
                          </button>
                        </td>
                      @endrole
                    @endif
                    <td class="px-1 py-2 text-center">
                      @role(['admin', 'super_admin'])
                      {{-- Fixed four-slot grid, not a flex row: Download and
                           Rescan are conditional, and a flex row collapses the
                           gap when one is absent, so the icons stopped lining
                           up column-to-column. Every row now reserves all four
                           slots and renders an empty one where the action does
                           not apply. --}}
                      <div class="inline-grid grid-cols-4 gap-0.5 items-center">
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
                        @else
                          <span class="block h-4 w-4" title="File not found on disk" aria-hidden="true"></span>
                        @endif
                        @if($file->database_entity_id)
                          <form action="{{ route('files.rescan', $file) }}" method="POST">
                            @csrf
                            <button type="submit" class="block text-purple-600 hover:text-purple-800" title="Rescan">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                              </svg>
                            </button>
                          </form>
                        @else
                          <span class="block h-4 w-4" aria-hidden="true"></span>
                        @endif
                      </div>
                      @endrole
                    </td>
                  </tr>
                @empty
                  @php
                    // 12 base columns, plus the coverage column, plus the
                    // super_admin-only rebuild column.
                    $emptyColspan = 12
                        + ($showPrioritisationCoverage ? 1 : 0)
                        + ($showingEmpodatSuspect && auth()->user()?->hasRole('super_admin') ? 1 : 0);
                  @endphp
                  <tr class="bg-slate-100">
                    <td colspan="{{ $emptyColspan }}" class="py-6 px-4 text-center text-gray-500">
                      No files found.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if($showingEmpodatSuspect)
            @role('super_admin')
              <x-empodat-suspect-rebuild-modal />
            @endrole
          @endif

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
