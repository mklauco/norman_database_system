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
            <a href="{{ route('backend.display.sections', $section->databaseEntity->code) }}" class="text-sm text-gray-500 hover:text-gray-700">
              <i class="fas fa-arrow-left mr-1"></i> Back to Sections
            </a>
            <h3 class="text-lg font-medium text-gray-900 mt-2">
              Edit Section: {{ $section->effective_name }}
            </h3>
            <p class="text-sm text-gray-500">
              Module: {{ $section->databaseEntity->name }} | Code: <span class="font-mono">{{ $section->code }}</span>
            </p>
          </div>

          <form method="POST" action="{{ route('backend.display.sections.update', $section->id) }}">
            @csrf
            @method('PUT')

            <div class="space-y-6">

              {{-- Basic Settings --}}
              <div class="border-b border-gray-200 pb-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Basic Settings</h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Section Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $section->name) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm"
                           placeholder="Leave empty to use type default">
                    <p class="mt-1 text-xs text-gray-500">Override the section type's default name</p>
                  </div>

                  <div>
                    <label for="section_type_id" class="block text-sm font-medium text-gray-700">Section Type</label>
                    <select name="section_type_id" id="section_type_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm">
                      <option value="">-- Custom (no type) --</option>
                      @foreach($sectionTypes as $type)
                        <option value="{{ $type->id }}" {{ old('section_type_id', $section->section_type_id) == $type->id ? 'selected' : '' }}>
                          {{ $type->default_name }} ({{ $type->code }})
                        </option>
                      @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Determines default styling</p>
                  </div>

                  <div>
                    <label for="display_order" class="block text-sm font-medium text-gray-700">Display Order</label>
                    <input type="number" name="display_order" id="display_order" value="{{ old('display_order', $section->display_order) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm"
                           min="0">
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Relationship</label>
                    <input type="text" value="{{ $section->relationship ?? '(main record)' }}" disabled
                           class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Cannot be changed (set via seeder)</p>
                  </div>
                </div>
              </div>

              {{-- Visibility Settings --}}
              <div class="border-b border-gray-200 pb-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Visibility Settings</h4>

                <div class="space-y-4">
                  <div class="flex items-center">
                    <input type="checkbox" name="is_visible" id="is_visible" value="1"
                           {{ old('is_visible', $section->is_visible) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-lime-600 focus:ring-lime-500">
                    <label for="is_visible" class="ml-2 block text-sm text-gray-700">
                      Visible in show view
                    </label>
                  </div>

                  <div class="flex items-center">
                    <input type="checkbox" name="is_collapsible" id="is_collapsible" value="1"
                           {{ old('is_collapsible', $section->is_collapsible) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-lime-600 focus:ring-lime-500">
                    <label for="is_collapsible" class="ml-2 block text-sm text-gray-700">
                      Allow section to be collapsed
                    </label>
                  </div>

                  <div class="flex items-center ml-6">
                    <input type="checkbox" name="is_collapsed_default" id="is_collapsed_default" value="1"
                           {{ old('is_collapsed_default', $section->is_collapsed_default) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-lime-600 focus:ring-lime-500">
                    <label for="is_collapsed_default" class="ml-2 block text-sm text-gray-700">
                      Collapsed by default
                    </label>
                  </div>
                </div>
              </div>

              {{-- Style Overrides --}}
              <div class="pb-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Style Overrides</h4>
                <p class="text-sm text-gray-500 mb-4">Leave empty to use section type defaults, or select colors below.</p>

                @php
                  $colors = [
                    'gray' => ['50' => '#f9fafb', '100' => '#f3f4f6', '200' => '#e5e7eb', '300' => '#d1d5db', '400' => '#9ca3af', '500' => '#6b7280', '600' => '#4b5563', '700' => '#374151', '800' => '#1f2937', '900' => '#111827'],
                    'slate' => ['50' => '#f8fafc', '100' => '#f1f5f9', '200' => '#e2e8f0', '300' => '#cbd5e1', '400' => '#94a3b8', '500' => '#64748b', '600' => '#475569', '700' => '#334155', '800' => '#1e293b', '900' => '#0f172a'],
                    'emerald' => ['50' => '#ecfdf5', '100' => '#d1fae5', '200' => '#a7f3d0', '300' => '#6ee7b7', '400' => '#34d399', '500' => '#10b981', '600' => '#059669', '700' => '#047857', '800' => '#065f46', '900' => '#064e3b'],
                    'teal' => ['50' => '#f0fdfa', '100' => '#ccfbf1', '200' => '#99f6e4', '300' => '#5eead4', '400' => '#2dd4bf', '500' => '#14b8a6', '600' => '#0d9488', '700' => '#0f766e', '800' => '#115e59', '900' => '#134e4a'],
                    'cyan' => ['50' => '#ecfeff', '100' => '#cffafe', '200' => '#a5f3fc', '300' => '#67e8f9', '400' => '#22d3ee', '500' => '#06b6d4', '600' => '#0891b2', '700' => '#0e7490', '800' => '#155e75', '900' => '#164e63'],
                    'amber' => ['50' => '#fffbeb', '100' => '#fef3c7', '200' => '#fde68a', '300' => '#fcd34d', '400' => '#fbbf24', '500' => '#f59e0b', '600' => '#d97706', '700' => '#b45309', '800' => '#92400e', '900' => '#78350f'],
                    'rose' => ['50' => '#fff1f2', '100' => '#ffe4e6', '200' => '#fecdd3', '300' => '#fda4af', '400' => '#fb7185', '500' => '#f43f5e', '600' => '#e11d48', '700' => '#be123c', '800' => '#9f1239', '900' => '#881337'],
                    'violet' => ['50' => '#f5f3ff', '100' => '#ede9fe', '200' => '#ddd6fe', '300' => '#c4b5fd', '400' => '#a78bfa', '500' => '#8b5cf6', '600' => '#7c3aed', '700' => '#6d28d9', '800' => '#5b21b6', '900' => '#4c1d95'],
                  ];
                  $intensities = ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900'];
                @endphp

                <div class="space-y-6">
                  {{-- Header Background --}}
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Header Background</label>
                    <div class="flex items-center gap-3">
                      <select id="header_bg_color" class="rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm">
                        <option value="">-- Use default --</option>
                        @foreach($colors as $name => $shades)
                          <option value="{{ $name }}">{{ ucfirst($name) }}</option>
                        @endforeach
                      </select>
                      <select id="header_bg_intensity" class="rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm">
                        @foreach($intensities as $i)
                          <option value="{{ $i }}" {{ $i === '600' ? 'selected' : '' }}>{{ $i }}</option>
                        @endforeach
                      </select>
                      <div id="header_bg_preview" class="w-8 h-8 rounded border border-gray-300"></div>
                      <input type="hidden" name="header_bg_class" id="header_bg_class" value="{{ old('header_bg_class', $section->header_bg_class) }}">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Default: {{ $section->sectionType?->default_header_bg_class ?? 'bg-gray-300' }}</p>
                  </div>

                  {{-- Header Text --}}
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Header Text</label>
                    <div class="flex items-center gap-3">
                      <select id="header_text_color" class="rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm">
                        <option value="">-- Use default --</option>
                        <option value="white">White</option>
                        @foreach($colors as $name => $shades)
                          <option value="{{ $name }}">{{ ucfirst($name) }}</option>
                        @endforeach
                      </select>
                      <select id="header_text_intensity" class="rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm">
                        @foreach($intensities as $i)
                          <option value="{{ $i }}" {{ $i === '900' ? 'selected' : '' }}>{{ $i }}</option>
                        @endforeach
                      </select>
                      <div id="header_text_preview" class="w-8 h-8 rounded border border-gray-300 flex items-center justify-center font-bold">A</div>
                      <input type="hidden" name="header_text_class" id="header_text_class" value="{{ old('header_text_class', $section->header_text_class) }}">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Default: {{ $section->sectionType?->default_header_text_class ?? 'text-gray-900' }}</p>
                  </div>

                  {{-- Row Even Background --}}
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Even Row Background</label>
                    <div class="flex items-center gap-3">
                      <select id="row_even_color" class="rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm">
                        <option value="">-- Use default --</option>
                        @foreach($colors as $name => $shades)
                          <option value="{{ $name }}">{{ ucfirst($name) }}</option>
                        @endforeach
                      </select>
                      <select id="row_even_intensity" class="rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm">
                        @foreach($intensities as $i)
                          <option value="{{ $i }}" {{ $i === '50' ? 'selected' : '' }}>{{ $i }}</option>
                        @endforeach
                      </select>
                      <div id="row_even_preview" class="w-8 h-8 rounded border border-gray-300"></div>
                      <input type="hidden" name="row_even_class" id="row_even_class" value="{{ old('row_even_class', $section->row_even_class) }}">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Default: {{ $section->sectionType?->default_row_even_class ?? 'bg-slate-100' }}</p>
                  </div>

                  {{-- Row Odd Background --}}
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Odd Row Background</label>
                    <div class="flex items-center gap-3">
                      <select id="row_odd_color" class="rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm">
                        <option value="">-- Use default --</option>
                        @foreach($colors as $name => $shades)
                          <option value="{{ $name }}">{{ ucfirst($name) }}</option>
                        @endforeach
                      </select>
                      <select id="row_odd_intensity" class="rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm">
                        @foreach($intensities as $i)
                          <option value="{{ $i }}" {{ $i === '100' ? 'selected' : '' }}>{{ $i }}</option>
                        @endforeach
                      </select>
                      <div id="row_odd_preview" class="w-8 h-8 rounded border border-gray-300"></div>
                      <input type="hidden" name="row_odd_class" id="row_odd_class" value="{{ old('row_odd_class', $section->row_odd_class) }}">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Default: {{ $section->sectionType?->default_row_odd_class ?? 'bg-slate-200' }}</p>
                  </div>

                  {{-- Row Text --}}
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Row Text</label>
                    <div class="flex items-center gap-3">
                      <select id="row_text_color" class="rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm">
                        <option value="">-- Use default --</option>
                        @foreach($colors as $name => $shades)
                          <option value="{{ $name }}">{{ ucfirst($name) }}</option>
                        @endforeach
                      </select>
                      <select id="row_text_intensity" class="rounded-md border-gray-300 shadow-sm focus:border-lime-500 focus:ring-lime-500 sm:text-sm">
                        @foreach($intensities as $i)
                          <option value="{{ $i }}" {{ $i === '900' ? 'selected' : '' }}>{{ $i }}</option>
                        @endforeach
                      </select>
                      <div id="row_text_preview" class="w-8 h-8 rounded border border-gray-300 flex items-center justify-center font-bold">A</div>
                      <input type="hidden" name="row_text_class" id="row_text_class" value="{{ old('row_text_class', $section->row_text_class) }}">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Default: {{ $section->sectionType?->default_row_text_class ?? 'text-gray-900' }}</p>
                  </div>
                </div>

                {{-- Style Preview --}}
                <div class="mt-6">
                  <h5 class="text-sm font-medium text-gray-700 mb-2">Current Style Preview</h5>
                  <div class="border rounded-md overflow-hidden">
                    <div class="p-2 text-center font-bold {{ $section->effective_header_bg_class }} {{ $section->effective_header_text_class }}">
                      {{ $section->effective_name }}
                    </div>
                    <div class="p-2 {{ $section->effective_row_even_class }} {{ $section->effective_row_text_class }}">
                      Even row example
                    </div>
                    <div class="p-2 {{ $section->effective_row_odd_class }} {{ $section->effective_row_text_class }}">
                      Odd row example
                    </div>
                  </div>
                </div>
              </div>

              <script>
                const colorMap = @json($colors);
                const effectiveValues = {
                  header_bg: @json($section->effective_header_bg_class),
                  header_text: @json($section->effective_header_text_class),
                  row_even: @json($section->effective_row_even_class),
                  row_odd: @json($section->effective_row_odd_class),
                  row_text: @json($section->effective_row_text_class),
                };

                function setupColorPicker(prefix, type) {
                  const colorSelect = document.getElementById(`${prefix}_color`);
                  const intensitySelect = document.getElementById(`${prefix}_intensity`);
                  const preview = document.getElementById(`${prefix}_preview`);
                  const input = document.getElementById(`${prefix}_class`);

                  function updatePreview() {
                    const color = colorSelect.value;
                    const intensity = intensitySelect.value;

                    if (color && colorMap[color]) {
                      const hex = colorMap[color][intensity];
                      if (type === 'bg') {
                        preview.style.backgroundColor = hex;
                        input.value = `bg-${color}-${intensity}`;
                      } else {
                        preview.style.color = hex;
                        input.value = `text-${color}-${intensity}`;
                      }
                    } else if (color === 'white' && type === 'text') {
                      preview.style.color = '#ffffff';
                      preview.style.backgroundColor = '#374151';
                      input.value = 'text-white';
                    } else {
                      // "Use default" selected - clear the override
                      input.value = '';
                      if (type === 'bg') {
                        preview.style.backgroundColor = '#e5e7eb';
                      } else {
                        preview.style.color = '#111827';
                      }
                    }
                  }

                  function parseAndSetDropdowns(value, updateInput = true) {
                    if (!value) return false;

                    const match = value.match(/(bg|text)-(\w+)-(\d+)/);
                    if (match && colorMap[match[2]]) {
                      colorSelect.value = match[2];
                      intensitySelect.value = match[3];
                      // Update preview color
                      const hex = colorMap[match[2]][match[3]];
                      if (type === 'bg') {
                        preview.style.backgroundColor = hex;
                      } else {
                        preview.style.color = hex;
                      }
                      return true;
                    } else if (value === 'text-white') {
                      colorSelect.value = 'white';
                      preview.style.color = '#ffffff';
                      preview.style.backgroundColor = '#374151';
                      return true;
                    }
                    return false;
                  }

                  colorSelect.addEventListener('change', updatePreview);
                  intensitySelect.addEventListener('change', updatePreview);

                  // Initialize: first try override value, then effective value
                  const overrideValue = input.value;
                  if (overrideValue) {
                    parseAndSetDropdowns(overrideValue);
                  } else {
                    // No override, show effective value in dropdowns (but don't fill input)
                    const effectiveKey = prefix.replace('_class', '');
                    const effectiveValue = effectiveValues[effectiveKey];
                    if (effectiveValue) {
                      parseAndSetDropdowns(effectiveValue, false);
                    }
                  }
                }

                document.addEventListener('DOMContentLoaded', function() {
                  setupColorPicker('header_bg', 'bg');
                  setupColorPicker('header_text', 'text');
                  setupColorPicker('row_even', 'bg');
                  setupColorPicker('row_odd', 'bg');
                  setupColorPicker('row_text', 'text');
                });
              </script>

              {{-- Submit --}}
              <div class="flex justify-end space-x-3">
                <a href="{{ route('backend.display.sections', $section->databaseEntity->code) }}"
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
