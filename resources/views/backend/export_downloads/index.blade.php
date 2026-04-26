<x-app-layout>


  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $isSuperAdmin ? 'Downloads' : 'My Downloads' }}</h1>

            @if($isSuperAdmin && $usersWithExports->count() > 0)
              <div class="mt-3 p-4 bg-lime-50 rounded-lg border border-lime-200">
                <form method="GET" action="{{ route('export_downloads.index') }}" class="flex items-center gap-4">
                  <label for="user_id" class="text-sm font-medium text-gray-700">Filter by User:</label>
                  <select name="user_id" id="user_id" class="rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 text-sm" onchange="this.form.submit()">
                    <option value="">-- Select User --</option>
                    @foreach($usersWithExports as $u)
                      <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>
                        {{ $u->last_name }}, {{ $u->first_name }} ({{ $u->email }}) - {{ $u->export_downloads_count }} downloads
                      </option>
                    @endforeach
                  </select>
                </form>
              </div>
            @endif

            @if($user)
              <div class="mt-3 p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 mb-3"><em>All times displayed in Central European Time (CET/CEST)</em></p>
                <div class="flex flex-wrap gap-6">
                  <div>
                    <span class="text-sm font-medium text-gray-600">Name:</span>
                    <span class="text-sm text-gray-800 ml-2">{{ $user->full_name }}</span>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-gray-600">Email:</span>
                    <span class="text-sm text-gray-800 ml-2">{{ $user->email }}</span>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-gray-600">Total Downloads:</span>
                    <span class="text-sm text-gray-800 ml-2 font-mono">{{ $exportDownloads instanceof \Illuminate\Pagination\LengthAwarePaginator ? $exportDownloads->total() : $exportDownloads->count() }}</span>
                  </div>
                </div>
              </div>
            @endif
          </div>

          @if($exportDownloads->count() > 0)
            <div class="overflow-x-auto">
              <table class="table-standard w-full">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th class="py-2 px-2 text-left">ID</th>
                    <th class="py-2 px-2 text-left">Filename</th>
                    <th class="py-2 px-2 text-left">Format</th>
                    <th class="py-2 px-2 text-left">Database</th>
                    <th class="py-2 px-2 text-left">Records</th>
                    <th class="py-2 px-2 text-left">File Size</th>
                    <th class="py-2 px-2 text-left">Duration</th>
                    <th class="py-2 px-2 text-left">Status</th>
                    <th class="py-2 px-2 text-left">Created (CET)</th>
                    <th class="py-2 px-2 text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($exportDownloads as $download)
                    <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif hover:bg-slate-300 transition">
                      <td class="py-2 px-2 font-mono">{{ $download->id }}</td>
                      <td class="py-2 px-2 font-medium">
                        <span class="text-gray-800">{{ $download->filename }}</span>
                      </td>
                      <td class="py-2 px-2">
                        <span class="bg-gray-100 px-1 py-0.5 rounded uppercase">{{ $download->format }}</span>
                      </td>
                      <td class="py-2 px-2">{{ $download->database_key }}</td>
                      <td class="py-2 px-2">
                        @if($download->record_count)
                          {{ number_format($download->record_count) }}
                        @else
                          <span class="text-gray-400">-</span>
                        @endif
                      </td>
                      <td class="py-2 px-2">
                        @if($download->formatted_file_size)
                          <span class="text-gray-700 font-medium">{{ $download->formatted_file_size }}</span>
                        @else
                          <span class="text-gray-400">-</span>
                        @endif
                      </td>
                      <td class="py-2 px-2">
                        @if($download->duration)
                          <span class="text-gray-700">{{ $download->duration }}</span>
                        @else
                          <span class="text-gray-400">-</span>
                        @endif
                      </td>
                      <td class="py-2 px-2">
                        @if($download->status === 'completed')
                          <span class="bg-green-100 text-green-800 px-1 py-0.5 rounded">Completed</span>
                        @elseif($download->status === 'processing')
                          <span class="bg-yellow-100 text-yellow-800 px-1 py-0.5 rounded">Processing</span>
                        @elseif($download->status === 'failed')
                          <span class="bg-red-100 text-red-800 px-1 py-0.5 rounded">Failed</span>
                        @else
                          <span class="bg-gray-100 text-gray-800 px-1 py-0.5 rounded">{{ ucfirst($download->status) }}</span>
                        @endif
                      </td>
                      <td class="py-2 px-2 text-gray-600">
                        <div>{{ $download->created_at }}</div>
                        @if($download->started_at && $download->completed_at)
                          <div class="text-gray-500 mt-0.5">
                            Started: {{ $download->started_at }}<br>
                            Completed: {{ $download->completed_at }}
                          </div>
                        @elseif($download->started_at)
                          <div class="text-gray-500 mt-0.5">
                            Started: {{ $download->started_at }}
                          </div>
                        @endif
                      </td>
                      <td class="py-2 px-2 text-center">
                        @if($download->status === 'completed' && $download->file_size_bytes === null)
                          <span class="text-gray-500 text-xs italic" title="Export files are deleted automatically 24 hours after creation.">
                            Expired (auto-deleted after 24h)
                          </span>
                        @elseif($download->status === 'completed')
                          @php
                            $downloadRoute = '';
                            try {
                              switch($download->database_key) {
                                case 'empodat':
                                  $downloadRoute = route('csv.download', ['filename' => $download->filename]);
                                  break;
                                case 'empodat_suspect':
                                  $downloadRoute = route('empodat_suspect.csv.download', ['filename' => $download->filename]);
                                  break;
                                case 'sars':
                                  $downloadRoute = route('sars.csv.download', ['filename' => $download->filename]);
                                  break;
                                case 'arbg.bacteria':
                                case 'arbg.gene':
                                case 'arbg':
                                  // ARBG modules might use a general download route if implemented
                                  $downloadRoute = '#'; // TODO: Add ARBG download route when implemented
                                  break;
                                case 'ecotox':
                                case 'ecotox.ecotox':
                                case 'ecotox.ecotox_pnec3':
                                  // Ecotox modules might use a general download route if implemented
                                  $downloadRoute = '#'; // TODO: Add Ecotox download route when implemented
                                  break;
                                case 'passive':
                                case 'indoor':
                                case 'bioassay':
                                  // Other modules might use general download routes if implemented
                                  $downloadRoute = '#'; // TODO: Add download routes when implemented
                                  break;
                                default:
                                  $downloadRoute = '#';
                              }
                            } catch (Exception $e) {
                              $downloadRoute = '#';
                            }
                          @endphp
                          @if($downloadRoute !== '#')
                            <a href="{{ $downloadRoute }}" class="text-green-600 hover:text-green-800" title="Download">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                              </svg>
                            </a>
                          @else
                            <span class="text-gray-400 text-xs">
                              @if(in_array($download->database_key, ['arbg.bacteria', 'arbg.gene', 'arbg', 'ecotox', 'ecotox.ecotox', 'ecotox.ecotox_pnec3', 'passive', 'indoor', 'bioassay']))
                                Download not implemented
                              @else
                                No download available
                              @endif
                            </span>
                          @endif
                        @else
                          <span class="text-gray-400 text-xs">
                            @if($download->status === 'processing')
                              Processing...
                            @elseif($download->status === 'failed')
                              Failed
                            @else
                              {{ ucfirst($download->status) }}
                            @endif
                          </span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            @if($exportDownloads instanceof \Illuminate\Pagination\LengthAwarePaginator && $exportDownloads->hasPages())
              <div class="mt-6">
                {{ $exportDownloads->appends(['user_id' => $userId])->links() }}
              </div>
            @endif
          @else
            <div class="text-center py-8">
              <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                @if($isSuperAdmin && !$userId)
                  <h3 class="mt-2 text-sm font-medium text-gray-900">Select a user</h3>
                  <p class="mt-1 text-sm text-gray-500">Select a user from the dropdown above to view their downloads.</p>
                @else
                  <h3 class="mt-2 text-sm font-medium text-gray-900">No downloads found</h3>
                  <p class="mt-1 text-sm text-gray-500">No export downloads were found for this user.</p>
                @endif
              </div>
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
