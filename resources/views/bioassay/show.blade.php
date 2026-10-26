<x-app-layout>
  <x-slot name="header">
    @include('bioassay.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          {{-- Notification Banner --}}
          <div class="mb-6 bg-sky-50 border-l-4 border-sky-500 p-4 rounded-r-lg">
            <div class="flex items-center">
              <div class="shrink-0">
                <i class="fas fa-info-circle text-sky-500 text-lg"></i>
              </div>
              <div class="ml-3">
                <p class="text-sm text-sky-700">
                  <strong>Note:</strong> This record is displayed in a new browser tab.
                  To return to your search results, please switch to the previous tab instead of using the "Search" link in the navigation bar. You may use keystroke <span class="font-mono">CTRL+SHIFT+TAB</span>.
                </p>
              </div>
            </div>
          </div>

          {{-- Record Information at Glance --}}
          <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-xs">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Bioassay Field Study Record at Glance</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              @if ($record->id)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Record ID</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->id }}</p>
                </div>
              @endif
              @if ($record->bioassayName)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Bioassay Name</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->bioassayName->name }}</p>
                </div>
              @endif
              @if ($record->endpoint)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Endpoint</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->endpoint->name }}</p>
                </div>
              @endif
              @if ($record->mainDeterminand)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Main Determinand</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->mainDeterminand->name }}</p>
                </div>
              @endif
              @if ($record->sampleData && $record->sampleData->country)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Country</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->sampleData->country->name }}</p>
                </div>
              @endif
              @if ($record->sampleData && $record->sampleData->station_name)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Station Name</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->sampleData->station_name }}</p>
                </div>
              @endif
              @if ($record->date_performed_year)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Year Performed</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->date_performed_year }}</p>
                </div>
              @endif
              @if ($record->sampleData && $record->sampleData->dataSource)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Organisation</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->sampleData->dataSource->m_ds_organisation }}</p>
                </div>
              @endif
            </div>
          </div>

          {{-- Complete Record Details --}}
          <div class="w-full overflow-x-auto">
            <table class="table-auto w-full border-separate border-spacing-1 text-xs mt-4" style="table-layout: fixed;">
              @php
                $rowIndex = 0;
                $excludedKeys = ['sample_data', 'data_source', 'bioassay_type', 'bioassay_name', 'adverse_outcome', 'endpoint', 'main_determinand', 'created_at', 'updated_at'];
              @endphp

              @foreach ($record->toArray() as $key => $value)
                {{-- Skip relationships and system fields --}}
                @if (in_array($key, $excludedKeys))
                  @continue
                @endif

                {{-- Skip ID fields except the main record ID --}}
                @if (str_ends_with($key, '_id') && $key !== 'id')
                  @continue
                @endif

                {{-- Skip null values and empty arrays --}}
                @if (is_null($value) || (is_array($value) && empty($value)) || (is_string($value) && $value === ''))
                  @continue
                @endif

                <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                  <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">
                    @if (is_array($value))
                      {{ json_encode($value) }}
                    @else
                      {{ $value }}
                    @endif
                  </td>
                </tr>
                @php $rowIndex++; @endphp
              @endforeach

              {{-- Sample Data / Station Information --}}
              @if ($record->sampleData)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Sample / Station Information</td>
                </tr>

                {{-- Show coordinates with map link if available --}}
                @if ($record->sampleData->latitude && $record->sampleData->longitude)
                  <tr class="bg-emerald-100">
                    <td class="p-1 font-bold" style="width: 20%; min-width: 120px;">Coordinates</td>
                    <td class="p-1" style="width: 80%;">
                      <a href="https://www.google.com/maps?q={{ $record->sampleData->latitude }},{{ $record->sampleData->longitude }}"
                         target="_blank"
                         class="text-teal-700 hover:text-teal-900 hover:underline">
                        {{ $record->sampleData->latitude }}, {{ $record->sampleData->longitude }}
                        <i class="fas fa-external-link-alt text-xs ml-1"></i>
                      </a>
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2" class="p-0">
                      <div id="station-map" class="w-full h-64 rounded-b-lg border border-gray-300"></div>
                    </td>
                  </tr>
                @endif

                @php $rowIndex = 0; @endphp
                @foreach ($record->sampleData->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'latitude', 'longitude', 'country', 'data_source', 'sample_matrix', 'type_sampling', 'sampling_technique', 'fraction', 'precision_coordinates', 'proxy_pressures', 'created_at', 'updated_at']))
                    @continue
                  @endif

                  @if (str_ends_with($key, '_id'))
                    @continue
                  @endif

                  @if (is_null($value) || (is_array($value) && empty($value)) || (is_string($value) && $value === ''))
                    @continue
                  @endif

                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%; min-width: 120px;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1" style="width: 80%;">
                      @if (is_array($value))
                        {{ json_encode($value) }}
                      @else
                        {{ $value }}
                      @endif
                    </td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- Bioassay Name Information --}}
              @if ($record->bioassayName)
                <tr class="bg-teal-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Bioassay Name</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->bioassayName->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'created_at', 'updated_at']))
                    @continue
                  @endif
                  @if (is_null($value) || (is_array($value) && empty($value)) || (is_string($value) && $value === ''))
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-teal-50 @else bg-teal-100 @endif">
                    <td class="p-1 font-bold text-teal-900" style="width: 20%;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1 text-teal-800" style="width: 80%;">
                      @if (is_array($value))
                        {{ json_encode($value) }}
                      @else
                        {{ $value }}
                      @endif
                    </td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- Endpoint Information --}}
              @if ($record->endpoint)
                <tr class="bg-teal-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Endpoint</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->endpoint->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'created_at', 'updated_at']))
                    @continue
                  @endif
                  @if (is_null($value) || (is_array($value) && empty($value)) || (is_string($value) && $value === ''))
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-teal-50 @else bg-teal-100 @endif">
                    <td class="p-1 font-bold text-teal-900" style="width: 20%;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1 text-teal-800" style="width: 80%;">
                      @if (is_array($value))
                        {{ json_encode($value) }}
                      @else
                        {{ $value }}
                      @endif
                    </td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- Main Determinand Information --}}
              @if ($record->mainDeterminand)
                <tr class="bg-teal-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Main Determinand</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->mainDeterminand->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'created_at', 'updated_at']))
                    @continue
                  @endif
                  @if (is_null($value) || (is_array($value) && empty($value)) || (is_string($value) && $value === ''))
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-teal-50 @else bg-teal-100 @endif">
                    <td class="p-1 font-bold text-teal-900" style="width: 20%;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1 text-teal-800" style="width: 80%;">
                      @if (is_array($value))
                        {{ json_encode($value) }}
                      @else
                        {{ $value }}
                      @endif
                    </td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- Country Information --}}
              @if ($record->sampleData && $record->sampleData->country)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Country</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->sampleData->country->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'created_at', 'updated_at']))
                    @continue
                  @endif
                  @if (is_null($value) || (is_array($value) && empty($value)) || (is_string($value) && $value === ''))
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1" style="width: 80%;">
                      @if (is_array($value))
                        {{ json_encode($value) }}
                      @else
                        {{ $value }}
                      @endif
                    </td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- Data Source Information --}}
              @if ($record->sampleData && $record->sampleData->dataSource)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Data Source</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->sampleData->dataSource->toArray() as $key => $value)
                  @if (in_array($key, ['id', 'created_at', 'updated_at']))
                    @continue
                  @endif
                  @if (is_null($value) || (is_array($value) && empty($value)) || (is_string($value) && $value === ''))
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%;">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                    <td class="p-1" style="width: 80%;">
                      @if (is_array($value))
                        {{ json_encode($value) }}
                      @else
                        {{ $value }}
                      @endif
                    </td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Initialize Leaflet map if coordinates are available --}}
  @if ($record->sampleData && $record->sampleData->latitude && $record->sampleData->longitude)
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const lat = {{ $record->sampleData->latitude }};
        const lng = {{ $record->sampleData->longitude }};
        const stationName = @json($record->sampleData->station_name ?? 'Station');

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
