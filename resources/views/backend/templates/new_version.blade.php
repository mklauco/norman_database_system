<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <h2 class="text-xl font-semibold text-gray-800">Upload New Version</h2>
          <p class="text-sm text-gray-600 mt-1 mb-6">
            {{ $current->databaseEntity->name ?? 'No database entity' }}: {{ $current->name ?? 'Unnamed Template' }},
            {{ $current->is_active ? 'active' : 'last' }} version {{ $current->version ?? 'N/A' }}.
            The new version becomes active immediately. Nothing is deleted.
          </p>

          <form action="{{ route('templates.versions.store', $current) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
              <label for="template_file" class="block text-sm font-medium text-gray-700 mb-1">Template File</label>
              <input type="file" name="template_file" id="template_file" required
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
              <p class="mt-1 text-xs text-gray-500">Maximum file size: 10 MB. Supported formats: XLSX, XLSB, XLS, CSV, TXT, ZIP, RAR</p>
              @error('template_file')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="version" class="block text-sm font-medium text-gray-700 mb-1">Version</label>
                <input type="text" name="version" id="version" value="{{ old('version', $nextVersion) }}" required maxlength="20" class="form-text">
                <p class="mt-1 text-xs text-gray-500">Already used: {{ $versions->pluck('version')->filter()->implode(', ') }}</p>
                @error('version')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>
              <div>
                <label for="valid_from" class="block text-sm font-medium text-gray-700 mb-1">Valid From</label>
                <input type="date" name="valid_from" id="valid_from" value="{{ old('valid_from', now()->format('Y-m-d')) }}" class="form-text">
                @error('valid_from')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>
            </div>

            <div>
              <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Template Name</label>
              <input type="text" name="name" id="name" value="{{ old('name', $current->name) }}" required maxlength="255" class="form-text">
              @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <div>
              <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea name="description" id="description" rows="4" class="form-text">{{ old('description', $current->description) }}</textarea>
              @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>

            @include('backend.templates._keep_active')

            <div class="flex justify-end items-center gap-2 pt-4 border-t border-gray-200">
              <a href="{{ route('templates.show', $current) }}" class="btn-clear">Cancel</a>
              <button type="submit" class="btn-submit cursor-pointer">Upload New Version</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
