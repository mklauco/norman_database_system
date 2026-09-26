<x-app-layout>
  <x-slot name="header">
    @include('backend.system-settings.header')
  </x-slot>

  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">

          @if (session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
              {{ session('success') }}
            </div>
          @endif

          <div class="flex justify-between items-center mb-6">
            <div>
              <a href="{{ route('backend.display.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-1"></i> Back to Modules
              </a>
              <h3 class="text-lg font-medium text-gray-900 mt-2">
                {{ $entity->name }} - Display Sections
              </h3>
            </div>
          </div>

          <p class="text-sm text-gray-600 mb-6">
            Manage display sections for the {{ $entity->name }} module. Drag to reorder sections.
          </p>

          @if($sections->count() > 0)
            <form method="POST" action="{{ route('backend.display.sections.reorder', $entity->code) }}" id="reorder-form">
              @csrf
              <table class="w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-2 py-2 w-8"></th>
                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Section</th>
                    <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase w-16">Columns</th>
                    <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase w-14">Visible</th>
                    <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase w-20">Collapsed</th>
                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Style</th>
                    <th class="px-2 py-2 w-12"></th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="sortable-sections">
                  @foreach($sections as $section)
                    <tr data-id="{{ $section->id }}">
                      <td class="px-2 py-2 cursor-move text-gray-400">
                        <i class="fas fa-grip-vertical"></i>
                        <input type="hidden" name="order[]" value="{{ $section->id }}">
                      </td>
                      <td class="px-2 py-2">
                        <div class="font-medium text-gray-900">{{ $section->effective_name }}</div>
                        <div class="text-xs text-gray-400">
                          <span class="font-mono">{{ $section->code }}</span>
                          @if($section->sectionType)
                            · {{ Str::limit($section->sectionType->default_name, 15) }}
                          @endif
                          @if($section->relationship)
                            · <span class="font-mono">{{ Str::limit($section->relationship, 15) }}</span>
                          @endif
                        </div>
                      </td>
                      <td class="px-2 py-2 text-center">
                        <a href="{{ route('backend.display.columns.index', $section->id) }}"
                           class="text-lime-600 hover:text-lime-800 hover:underline">
                          {{ $section->columns->count() }} columns
                        </a>
                      </td>
                      <td class="px-2 py-2 text-center">
                        @if($section->is_visible)
                          <span class="text-green-600"><i class="fas fa-eye"></i></span>
                        @else
                          <span class="text-gray-300"><i class="fas fa-eye-slash"></i></span>
                        @endif
                      </td>
                      <td class="px-2 py-2 text-center">
                        @if($section->is_collapsible)
                          @if($section->is_collapsed_default)
                            <span class="text-amber-500"><i class="fas fa-compress-alt"></i></span>
                          @else
                            <span class="text-gray-400"><i class="fas fa-expand-alt"></i></span>
                          @endif
                        @else
                          <span class="text-gray-300">-</span>
                        @endif
                      </td>
                      <td class="px-2 py-2">
                        <div class="flex items-center gap-1">
                          <span class="px-1.5 py-0.5 text-xs rounded {{ $section->effective_header_bg_class }} {{ $section->effective_header_text_class }}">Header</span>
                          <span class="px-1.5 py-0.5 text-xs rounded {{ $section->effective_row_even_class }} {{ $section->effective_row_text_class }}">Even</span>
                          <span class="px-1.5 py-0.5 text-xs rounded {{ $section->effective_row_odd_class }} {{ $section->effective_row_text_class }}">Odd</span>
                        </div>
                      </td>
                      <td class="px-2 py-2">
                        <a href="{{ route('backend.display.sections.edit', $section->id) }}"
                           class="text-slate-600 hover:text-slate-900">
                          Edit
                        </a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>

              <div class="mt-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700">
                  Save Order
                </button>
              </div>
            </form>
          @else
            <div class="text-center py-8">
              <p class="text-gray-500">No sections configured for this module.</p>
              <p class="text-sm text-gray-400 mt-2">Sections are created via database seeders.</p>
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const el = document.getElementById('sortable-sections');
      if (el) {
        new Sortable(el, {
          animation: 150,
          handle: '.cursor-move',
          ghostClass: 'bg-gray-100'
        });
      }
    });
  </script>
</x-app-layout>
