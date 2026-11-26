<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-sm">
              {{ session('error') }}
            </div>
          @endif

          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">DCT Files Audit Log</h2>
            <a href="{{ route('files.index') }}" class="link-lime-text">&larr; Back to Files</a>
          </div>

          <form method="GET" action="{{ route('files.audits.index') }}" class="mb-6 flex flex-wrap items-end gap-4">
            <div>
              <label for="user_id" class="block text-sm font-medium text-gray-700">User</label>
              <select name="user_id" id="user_id" class="mt-1 rounded-md border-gray-300 shadow-xs focus:border-lime-500 focus:ring-lime-500 text-sm">
                <option value="">All users</option>
                <option value="{{ \App\Http\Controllers\Backend\FileAuditController::SYSTEM_USER }}" @selected($userId === \App\Http\Controllers\Backend\FileAuditController::SYSTEM_USER)>System (seeders, commands)</option>
                @foreach($users as $u)
                  <option value="{{ $u->id }}" @selected($userId === $u->id)>{{ $u->last_name }}, {{ $u->first_name }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label for="file_id" class="block text-sm font-medium text-gray-700">File ID</label>
              <input type="number" name="file_id" id="file_id" value="{{ $fileId }}" min="1" class="mt-1 w-32 rounded-md border-gray-300 shadow-xs focus:border-lime-500 focus:ring-lime-500 text-sm font-mono">
            </div>
            <button type="submit" class="btn-submit">Filter</button>
            @if($userId !== null || $fileId !== null)
              <a href="{{ route('files.audits.index') }}" class="link-lime-text text-sm">Clear filters</a>
            @endif
          </form>

          @if($file)
            <p class="mb-4 text-sm text-gray-700">
              File <span class="font-mono">{{ $file->id }}</span>:
              <a href="{{ route('files.show', $file) }}" class="link-lime-text">{{ $file->name ?? $file->original_name }}</a>
            </p>
          @endif

          <p class="mb-2 text-sm text-gray-600">
            Changes: <span class="font-mono">{{ number_format($audits->total(), 0, '.', ' ') }}</span>
          </p>

          @if($audits->count() > 0)
            @include('backend.files._audits_table', ['audits' => $audits, 'showFile' => true])

            <div class="mt-6">
              {{ $audits->links() }}
            </div>
          @else
            <p class="text-gray-500">No changes recorded.</p>
          @endif
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
