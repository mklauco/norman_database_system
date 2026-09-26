<x-app-layout>
  <x-slot name="header">
    @include('backend.system-settings.header')
  </x-slot>

  <div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">

          @if (session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
              {{ session('success') }}
            </div>
          @endif

          @if ($errors->any())
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
              <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-6">
            <a href="{{ route('backend.display.columns.index', $column->display_section_id) }}" class="text-sm text-gray-500 hover:text-gray-700">
              <i class="fas fa-arrow-left mr-1"></i> Back to Columns
            </a>
            <h3 class="text-lg font-medium text-gray-900 mt-2">
              Edit Column: <span class="font-mono">{{ $column->column_name }}</span>
            </h3>
            <p class="text-sm text-gray-500">
              Section: {{ $column->section->effective_name }} |
              Module: {{ $column->section->databaseEntity->name }}
            </p>
          </div>

          <form method="POST" action="{{ route('backend.display.columns.update', $column->id) }}">
            @csrf
            @method('PUT')

            <div class="space-y-6">

              {{-- Basic Settings --}}
              <div class="border-b border-gray-200 pb-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Basic Settings</h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Column Name</label>
                    <input type="text" value="{{ $column->column_name }}" disabled
                           class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 text-gray-500 sm:text-sm font-mono">
                    <p class="mt-1 text-xs text-gray-500">Cannot be changed (set via seeder)</p>
                  </div>

                  <div class="p-3 -m-1 bg-amber-50 border border-amber-200 rounded-md">
                    <label for="display_section_id" class="block text-sm font-medium text-amber-800">
                      <i class="fas fa-exchange-alt mr-1"></i> Section Assignment
                    </label>
                    <select name="display_section_id" id="display_section_id"
                            class="mt-1 block w-full rounded-md border-amber-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm">
                      @foreach($sections as $section)
                        <option value="{{ $section->id }}" {{ old('display_section_id', $column->display_section_id) == $section->id ? 'selected' : '' }}>
                          {{ $section->effective_name }}
                        </option>
                      @endforeach
                    </select>
                    <p class="mt-1 text-xs text-amber-700">Changing this will move the column</p>
                  </div>

                  <div>
                    <label for="display_label" class="block text-sm font-medium text-gray-700">Display Label</label>
                    <input type="text" name="display_label" id="display_label" value="{{ old('display_label', $column->display_label) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm"
                           placeholder="Leave empty for auto-generated label">
                    <p class="mt-1 text-xs text-gray-500">Auto-generated: {{ $column->effective_label }}</p>
                  </div>

                  <div>
                    <label for="display_order" class="block text-sm font-medium text-gray-700">Display Order</label>
                    <input type="number" name="display_order" id="display_order" value="{{ old('display_order', $column->display_order) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm"
                           min="0">
                  </div>

                  <div>
                    <label for="css_class" class="block text-sm font-medium text-gray-700">CSS Class</label>
                    <input type="text" name="css_class" id="css_class" value="{{ old('css_class', $column->css_class) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm font-mono"
                           placeholder="e.g. font-mono">
                    <p class="mt-1 text-xs text-gray-500">Additional Tailwind classes for the value cell</p>
                  </div>
                </div>
              </div>

              {{-- Visibility Settings --}}
              <div class="border-b border-gray-200 pb-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Visibility</h4>

                <div class="space-y-4">
                  <div class="flex items-center">
                    <input type="checkbox" name="is_visible" id="is_visible" value="1"
                           {{ old('is_visible', $column->is_visible) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-lime-600 focus:ring-lime-500">
                    <label for="is_visible" class="ml-2 block text-sm text-gray-700">
                      Visible in show view
                    </label>
                  </div>

                  <div class="flex items-center">
                    <input type="checkbox" name="is_glance" id="is_glance" value="1"
                           {{ old('is_glance', $column->is_glance) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-lime-600 focus:ring-lime-500">
                    <label for="is_glance" class="ml-2 block text-sm text-gray-700">
                      Show in "At Glance" summary section
                    </label>
                  </div>
                </div>
              </div>

              {{-- Formatting --}}
              <div class="border-b border-gray-200 pb-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Value Formatting</h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label for="format_type" class="block text-sm font-medium text-gray-700">Format Type</label>
                    <select name="format_type" id="format_type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm">
                      @foreach($formatTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('format_type', $column->format_type) == $value ? 'selected' : '' }}>
                          {{ $label }}
                        </option>
                      @endforeach
                    </select>
                  </div>

                  <div>
                    <label for="format_options" class="block text-sm font-medium text-gray-700">Format Options (JSON)</label>
                    <input type="text" name="format_options" id="format_options"
                           value="{{ old('format_options', $column->format_options ? json_encode($column->format_options) : '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm font-mono"
                           placeholder='{"decimals": 2}'>
                    <p class="mt-1 text-xs text-gray-500">
                      Examples: <code>{"decimals": 4}</code> for numbers, <code>{"true_label": "Yes"}</code> for boolean
                    </p>
                  </div>
                </div>

                {{-- Format Type Reference --}}
                <div class="mt-4 p-4 bg-gray-50 rounded-md text-xs">
                  <h5 class="font-medium text-gray-700 mb-2">Format Type Reference</h5>
                  <ul class="space-y-1 text-gray-600">
                    <li><strong>text:</strong> No transformation (default)</li>
                    <li><strong>number:</strong> Number formatting. Options: <code>decimals</code>, <code>decimal_separator</code>, <code>thousands_separator</code></li>
                    <li><strong>date:</strong> Date formatting (d.m.Y)</li>
                    <li><strong>datetime:</strong> DateTime formatting (d.m.Y H:i:s)</li>
                    <li><strong>boolean:</strong> Yes/No display. Options: <code>true_label</code>, <code>false_label</code></li>
                    <li><strong>coordinates:</strong> Geographic coordinates</li>
                    <li><strong>json:</strong> Pretty-print JSON</li>
                    <li><strong>link:</strong> Clickable link (uses link_route and link_param)</li>
                  </ul>
                </div>
              </div>

              {{-- Link Settings --}}
              <div class="border-b border-gray-200 pb-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Link Settings</h4>
                <p class="text-sm text-gray-500 mb-4">Make this column value a clickable link to another route.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label for="link_route" class="block text-sm font-medium text-gray-700">Route Name</label>
                    <input type="text" name="link_route" id="link_route" value="{{ old('link_route', $column->link_route) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm font-mono"
                           placeholder="e.g. substances.show">
                  </div>

                  <div>
                    <label for="link_param" class="block text-sm font-medium text-gray-700">Route Parameter</label>
                    <input type="text" name="link_param" id="link_param" value="{{ old('link_param', $column->link_param) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm font-mono"
                           placeholder="e.g. substance">
                    <p class="mt-1 text-xs text-gray-500">The parameter name that receives the record's ID</p>
                  </div>
                </div>
              </div>

              {{-- Tooltip --}}
              <div class="pb-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Help Text</h4>

                <div>
                  <label for="tooltip" class="block text-sm font-medium text-gray-700">Tooltip</label>
                  <textarea name="tooltip" id="tooltip" rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm"
                            placeholder="Optional help text shown on hover">{{ old('tooltip', $column->tooltip) }}</textarea>
                </div>
              </div>

              {{-- Submit --}}
              <div class="flex justify-end space-x-3">
                <a href="{{ route('backend.display.columns.index', $column->display_section_id) }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest bg-white hover:bg-gray-50">
                  Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-slate-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-slate-700">
                  Save Changes
                </button>
              </div>

            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
