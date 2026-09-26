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
              <a href="{{ route('backend.display.sections', $section->databaseEntity->code) }}" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left mr-1"></i> Back to Sections
              </a>
              <h3 class="text-lg font-medium text-gray-900 mt-2">
                {{ $section->effective_name }} - Columns
              </h3>
              <p class="text-sm text-gray-500">
                Module: {{ $section->databaseEntity->name }}
                @if($section->relationship)
                  | Relationship: <span class="font-mono">{{ $section->relationship }}</span>
                @endif
              </p>
            </div>
          </div>

          <p class="text-sm text-gray-600 mb-6">
            Manage columns displayed in this section. Drag to reorder. Click "Edit" to change settings.
          </p>

          @if($columns->count() > 0)
            <form method="POST" action="{{ route('backend.display.columns.reorder', $section->id) }}" id="reorder-form">
              @csrf
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10"></th>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Column</th>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Format</th>
                      <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Visible</th>
                      <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Glance</th>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CSS Class</th>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Link</th>
                      <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200" id="sortable-columns">
                    @foreach($columns as $column)
                      <tr data-id="{{ $column->id }}" class="{{ !$column->is_visible ? 'bg-gray-50 text-gray-400' : '' }}">
                        <td class="px-3 py-3 cursor-move text-gray-400">
                          <i class="fas fa-grip-vertical"></i>
                          <input type="hidden" name="order[]" value="{{ $column->id }}">
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap font-mono text-xs">
                          {{ $column->column_name }}
                        </td>
                        <td class="px-3 py-3">
                          <span class="{{ $column->display_label ? '' : 'text-gray-400 italic' }}">
                            {{ $column->display_label ?? $column->effective_label }}
                          </span>
                          @if(!$column->display_label)
                            <span class="text-xs text-gray-400">(auto)</span>
                          @endif
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap">
                          <span class="px-2 py-1 text-xs rounded bg-gray-100">{{ $column->format_type }}</span>
                          @if($column->format_options)
                            <span class="text-xs text-gray-400" title="{{ json_encode($column->format_options) }}">
                              <i class="fas fa-cog"></i>
                            </span>
                          @endif
                        </td>
                        <td class="px-3 py-3 text-center">
                          @if($column->is_visible)
                            <span class="text-green-600"><i class="fas fa-eye"></i></span>
                          @else
                            <span class="text-gray-400"><i class="fas fa-eye-slash"></i></span>
                          @endif
                        </td>
                        <td class="px-3 py-3 text-center">
                          @if($column->is_glance)
                            <span class="text-amber-600"><i class="fas fa-star"></i></span>
                          @else
                            <span class="text-gray-300"><i class="far fa-star"></i></span>
                          @endif
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap font-mono text-xs">
                          {{ $column->css_class ?? '-' }}
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap text-xs">
                          @if($column->link_route)
                            <span class="text-teal-600" title="{{ $column->link_route }}">
                              <i class="fas fa-link"></i> {{ Str::limit($column->link_route, 20) }}
                            </span>
                          @else
                            <span class="text-gray-400">-</span>
                          @endif
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap">
                          <a href="{{ route('backend.display.columns.edit', $column->id) }}"
                             class="text-slate-600 hover:text-slate-900">
                            Edit
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>

              <div class="mt-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700">
                  Save Order
                </button>
              </div>
            </form>

            {{-- Legend --}}
            <div class="mt-6 p-4 bg-gray-50 rounded-md">
              <h5 class="text-sm font-medium text-gray-700 mb-2">Legend</h5>
              <div class="flex flex-wrap gap-4 text-xs text-gray-600">
                <span><i class="fas fa-eye text-green-600"></i> Visible in show view</span>
                <span><i class="fas fa-eye-slash text-gray-400"></i> Hidden</span>
                <span><i class="fas fa-star text-amber-600"></i> Shown in "At Glance" summary</span>
                <span><i class="far fa-star text-gray-300"></i> Not in glance</span>
                <span><i class="fas fa-link text-teal-600"></i> Has link</span>
              </div>
            </div>
          @else
            <div class="text-center py-8">
              <p class="text-gray-500">No columns configured for this section.</p>
              <p class="text-sm text-gray-400 mt-2">Columns are created via database seeders.</p>
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const el = document.getElementById('sortable-columns');
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
