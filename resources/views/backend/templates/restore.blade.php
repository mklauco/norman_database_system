<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <h2 class="text-xl font-semibold text-gray-800">Restore Version</h2>
          <p class="text-sm text-gray-600 mt-1 mb-6">
            {{ $template->databaseEntity->name ?? 'No database entity' }}: {{ $template->name ?? 'Unnamed Template' }}, version {{ $template->version ?? 'N/A' }}
            becomes active and is offered on the public DCT page again. Nothing is deleted.
          </p>

          <form action="{{ route('templates.restore', $template) }}" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')

            @include('backend.templates._keep_active')

            <div class="flex justify-end items-center gap-2 pt-4 border-t border-gray-200">
              <a href="{{ route('templates.show', $template) }}" class="btn-clear">Cancel</a>
              <button type="submit" class="btn-submit cursor-pointer">Restore Version</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
