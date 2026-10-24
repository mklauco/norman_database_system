<x-app-layout>
  <x-slot name="header">
    @include('passive.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          {{-- Notification Banner --}}
          <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
            <div class="flex items-center">
              <div class="shrink-0">
                <i class="fas fa-info-circle text-blue-500 text-lg"></i>
              </div>
              <div class="ml-3">
                <p class="text-sm text-blue-700">
                  <strong>Note:</strong> This record is displayed in a new browser tab.
                  To return to your search results, please switch to the previous tab instead of using the "Search" link in the navigation bar. You may use keystroke <span class="font-mono">CTRL+SHIFT+TAB</span>.
                </p>
              </div>
            </div>
          </div>

          {{-- Record Information at Glance --}}
          <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-xs">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Passive Sampling Record at Glance</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              @foreach ($glanceColumns as $column)
                @php
                  $section = $column->section;
                  $relationship = $section->relationship;
                  $source = $relationship ? $passive->{$relationship} : $passive;
                  $value = $source?->{$column->column_name};
                @endphp

                @if (!is_null($value) && $value !== '')
                  <div>
                    <h3 class="text-sm font-medium text-gray-800 mb-1">{{ $column->effective_label }}</h3>
                    <p class="text-sm text-teal-800 {{ $column->css_class }}">
                      @if ($column->format_type === 'number' && is_numeric($value))
                        {{ number_format((float) $value, $column->format_options['decimals'] ?? 2, '.', ' ') }}
                      @elseif ($column->format_type === 'date' && $value)
                        {{ $value instanceof \DateTimeInterface ? $value->format('d.m.Y') : $value }}
                      @else
                        {{ $value }}
                      @endif
                    </p>
                  </div>
                @endif
              @endforeach
            </div>
          </div>

          {{-- Full Record Details --}}
          <div class="w-full overflow-x-auto">
            <table class="table-auto w-full border-separate border-spacing-1 text-xs mt-4" style="table-layout: fixed;">

              @foreach ($sections as $section)
                @php
                  $relationship = $section->relationship;
                  $source = $relationship ? $passive->{$relationship} : $passive;

                  // Skip section if relationship is null (no related data)
                  if ($relationship && !$source) {
                      continue;
                  }

                  // Filter columns that have non-null values
                  $visibleColumns = $section->columns->filter(function ($column) use ($source) {
                      $value = $source?->{$column->column_name};
                      return !is_null($value) && $value !== '' && (!is_array($value) || !empty($value));
                  });

                  // Skip section if no columns have values
                  if ($visibleColumns->isEmpty()) {
                      continue;
                  }

                  $headerBgClass = $section->effective_header_bg_class;
                  $headerTextClass = $section->effective_header_text_class;
                  $rowEvenClass = $section->effective_row_even_class;
                  $rowOddClass = $section->effective_row_odd_class;
                @endphp

                {{-- Section Header --}}
                <tbody>
                  <tr class="{{ $headerBgClass }} {{ $headerTextClass }}">
                    <td colspan="2" class="p-2 font-bold text-center">
                      {{ $section->effective_name }}
                      @if ($section->is_collapsible)
                        <button type="button" class="ml-2 text-sm" onclick="toggleSection('section-{{ $section->id }}')">
                          <i class="fas fa-chevron-down"></i>
                        </button>
                      @endif
                    </td>
                  </tr>
                </tbody>

                {{-- Section Content --}}
                <tbody id="section-{{ $section->id }}" @if ($section->is_collapsed_default) style="display: none;" @endif>
                  @php $rowIndex = 0; @endphp

                  {{-- Special handling for location section with map --}}
                  @if ($section->code === 'location' && $passive->latitude_decimal && $passive->longitude_decimal)
                    <tr class="bg-emerald-100">
                      <td class="p-1 font-bold" style="width: 20%; min-width: 120px;">Coordinates</td>
                      <td class="p-1" style="width: 80%;">
                        <a href="https://www.google.com/maps?q={{ $passive->latitude_decimal }},{{ $passive->longitude_decimal }}"
                           target="_blank"
                           class="text-teal-700 hover:text-teal-900 hover:underline">
                          {{ $passive->latitude_decimal }}, {{ $passive->longitude_decimal }}
                          <i class="fas fa-external-link-alt text-xs ml-1"></i>
                        </a>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2" class="p-0">
                        <div id="station-map" class="w-full h-64 rounded-b-lg border border-gray-300"></div>
                      </td>
                    </tr>
                    @php $rowIndex = 2; @endphp
                  @endif

                  @foreach ($visibleColumns as $column)
                    @php
                      $value = $source?->{$column->column_name};

                      // Skip coordinates if already shown with map
                      if ($section->code === 'location' && in_array($column->column_name, ['latitude_decimal', 'longitude_decimal'])) {
                          continue;
                      }
                    @endphp

                    <tr class="{{ $rowIndex % 2 === 0 ? $rowEvenClass : $rowOddClass }}">
                      <td class="p-1 font-bold {{ $section->effective_row_text_class }}" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">
                        {{ $column->effective_label }}
                      </td>
                      <td class="p-1 {{ $section->effective_row_text_class }} {{ $column->css_class }}" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">
                        @if ($column->link_route && $source?->id)
                          <a href="{{ route($column->link_route, [$column->link_param => $source->id]) }}"
                             target="_blank"
                             class="text-teal-700 hover:text-teal-900 hover:underline">
                            @if ($column->column_name === 'code')
                              NS{{ $value }}
                            @else
                              {{ $value }}
                            @endif
                            <i class="fas fa-external-link-alt text-xs ml-1"></i>
                          </a>
                        @elseif ($column->format_type === 'number' && is_numeric($value))
                          {{ number_format((float) $value, $column->format_options['decimals'] ?? 2, '.', ' ') }}
                        @elseif ($column->format_type === 'date' && $value)
                          {{ $value instanceof \DateTimeInterface ? $value->format('d.m.Y') : $value }}
                        @elseif ($column->format_type === 'datetime' && $value)
                          {{ $value instanceof \DateTimeInterface ? $value->format('d.m.Y H:i:s') : $value }}
                        @elseif ($column->format_type === 'boolean')
                          {{ $value ? ($column->format_options['true_label'] ?? 'Yes') : ($column->format_options['false_label'] ?? 'No') }}
                        @elseif (is_array($value))
                          {{ json_encode($value) }}
                        @else
                          {{ $value }}
                        @endif
                      </td>
                    </tr>
                    @php $rowIndex++; @endphp
                  @endforeach
                </tbody>
              @endforeach

            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Collapsible section toggle script --}}
  <script>
    function toggleSection(sectionId) {
      const section = document.getElementById(sectionId);
      if (section.style.display === 'none') {
        section.style.display = '';
      } else {
        section.style.display = 'none';
      }
    }
  </script>

  {{-- Initialize Leaflet map if coordinates are available --}}
  @if ($passive->latitude_decimal && $passive->longitude_decimal)
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const lat = {{ $passive->latitude_decimal }};
        const lng = {{ $passive->longitude_decimal }};
        const stationName = @json($passive->station_name ?? 'Station');

        // Initialize the map
        const map = L.map('station-map').setView([lat, lng], 10);

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Add marker with popup
        const marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup(
          '<strong>' + stationName + '</strong>' +
          '<br><small>' + lat.toFixed(6) + ', ' + lng.toFixed(6) + '</small>'
        ).openPopup();

        // Fix for map not rendering correctly in hidden/dynamic containers
        setTimeout(function() {
          map.invalidateSize();
        }, 100);
      });
    </script>
  @endif

</x-app-layout>
