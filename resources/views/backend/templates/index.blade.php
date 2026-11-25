<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <div class="mb-6 flex justify-between items-center gap-4">
            <div>
              <h2 class="text-xl font-semibold text-gray-800">DCT Templates</h2>
              <p class="text-sm text-gray-600 mt-1">To replace a template, use New version: the new file becomes active and the old one is archived. Nothing is ever deleted.</p>
            </div>
            <a href="{{ route('templates.create') }}" class="btn-create whitespace-nowrap">
              Add New Template
            </a>
          </div>

          @if($activeTemplates->isNotEmpty())
            @include('backend.templates._table', ['groups' => $activeTemplates])
          @else
            <div class="bg-gray-100 p-4 rounded-sm text-center">
              <p>No active templates found.</p>
            </div>
          @endif

          @if($archivedTemplates->isNotEmpty())
            <h3 class="text-lg font-semibold text-gray-800 mt-8 mb-2">Archived Templates</h3>
            <p class="text-sm text-gray-600 mb-4">Templates without an active version. They are not offered on the public DCT pages.</p>
            @include('backend.templates._table', ['groups' => $archivedTemplates])
          @endif
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
