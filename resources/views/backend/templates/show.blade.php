<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <div class="mb-6 flex flex-wrap justify-between items-start gap-4">
            <div>
              <h2 class="text-xl font-semibold text-gray-800">{{ $template->name ?? 'Unnamed Template' }}, version {{ $template->version ?? 'N/A' }}</h2>
              <p class="text-sm text-gray-600 mt-1">{{ $template->is_active ? 'Active version' : 'Archived version' }}</p>
            </div>
            <div class="flex items-center gap-2">
              <a href="{{ route('templates.index') }}" class="btn-clear">Back</a>
              <a href="{{ route('templates.versions.create', $template) }}" class="btn-create">Upload New Version</a>
            </div>
          </div>

          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
              <span class="font-medium text-gray-500 block">ID:</span>
              <span class="font-mono">{{ $template->id }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-500 block">Database Entity:</span>
              <span>{{ $template->databaseEntity->name ?? 'N/A' }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-500 block">Valid from:</span>
              <span>{{ $template->valid_from?->format('Y-m-d') ?? 'N/A' }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-500 block">File:</span>
              <span class="break-all">{{ $template->file_path ? basename($template->file_path) : 'N/A' }} ({{ $template->formatted_filesize }})</span>
            </div>
            <div>
              <span class="font-medium text-gray-500 block">Created:</span>
              <span>{{ $template->created_at?->format('Y-m-d H:i') ?? 'N/A' }}, {{ $template->creator->full_name ?? 'N/A' }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-500 block">Last updated:</span>
              <span>{{ $template->updated_at?->format('Y-m-d H:i') ?? 'N/A' }}, {{ $template->updater->full_name ?? 'N/A' }}</span>
            </div>
            @if($template->description)
              <div class="md:col-span-3">
                <span class="font-medium text-gray-500 block">Description:</span>
                <span class="whitespace-pre-line">{{ $template->description }}</span>
              </div>
            @endif
          </div>

          <h3 class="text-lg font-semibold text-gray-800 mt-8 mb-4">Version History</h3>
          @include('backend.templates._table', ['groups' => [$versions], 'expanded' => true, 'highlightId' => $template->id])
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
