<x-app-layout>
  <x-slot name="header">
    @include('empodat_suspect.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          {{-- Notification Banner --}}
          <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
            <div class="flex items-center">
              <div class="flex-shrink-0">
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

          {{-- EMPODAT Suspect Record Information at Glance --}}
          <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">EMPODAT Suspect Record Information at Glance</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              @if ($record->id)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Record ID</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->id }}</p>
                </div>
              @endif
              @if ($record->substance && $record->substance->code)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Norman SUS ID</h3>
                  <p class="text-sm text-teal-800 font-mono">
                    <a href="{{ route('substances.show', $record->substance->id) }}" class="link-lime-text">
                      NS{{ $record->substance->code }}
                    </a>
                  </p>
                </div>
              @endif
              @if ($record->substance && $record->substance->name)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Substance Name</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->substance->name }}</p>
                </div>
              @endif
              @if ($record->concentration)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Concentration</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->concentration }}</p>
                </div>
              @endif
              @if ($record->units)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Units</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->units }}</p>
                </div>
              @endif
              @if ($record->ip_max)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">IP Max</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->ip_max }}</p>
                </div>
              @endif
              @if ($record->based_on_hrms_library !== null)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Based on HRMS Library</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->based_on_hrms_library ? 'TRUE' : 'FALSE' }}</p>
                </div>
              @endif
              @if ($record->station && $record->station->country_id)
                @php
                  // Access the relationship using getRelation to avoid attribute conflict
                  $country = $record->station->getRelation('country');
                @endphp
                @if ($country)
                  <div>
                    <h3 class="text-sm font-medium text-gray-800 mb-1">Country</h3>
                    <p class="text-sm text-teal-800 font-mono">
                      {{ $country->name }}
                    </p>
                  </div>
                @endif
              @endif
              @if ($record->station && $record->station->name)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Sampling Station</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->station->name }}</p>
                </div>
              @endif
              @if ($record->station && $record->station->short_sample_code)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Sample Code</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->station->short_sample_code }}</p>
                </div>
              @endif
            </div>
          </div>

          {{-- Complete Record Details --}}
          <div class="w-full overflow-x-auto">
            <table class="table-auto w-full border-separate border-spacing-1 text-xs mt-4" style="table-layout: fixed;">
              @foreach ($record->toArray() as $key => $value)
                {{-- Skip substance and station - already shown in "at Glance" section --}}
                @if ($key === 'substance' || $key === 'station' || $key === 'xlsx_station_mapping' || $key === 'file')
                  @continue
                @endif

                {{-- Skip all ID fields except the main record ID - we'll show the relationship names instead --}}
                @if (str_ends_with($key, '_id') && $key !== 'id')
                  @continue
                @endif

                {{-- Skip null values and empty arrays --}}
                @if (is_null($value) || (is_array($value) && empty($value)) || (is_string($value) && $value === ''))
                  @continue
                @endif

                <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">{{ $key }}</td>
                  <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">
                    @if (is_array($value))
                      {{-- Check if this is a relationship array (has 'name' key) --}}
                      @if (isset($value['name']))
                        {{ $value['name'] }}
                      @else
                        {{-- For other arrays, show each item --}}
                        @foreach ($value as $item)
                          <div class="py-1">{{ is_array($item) ? json_encode($item) : $item }}</div>
                        @endforeach
                      @endif
                    @else
                      {{ $value }}
                    @endif
                  </td>
                </tr>
              @endforeach

              {{-- Add station information --}}
              @if ($record->station)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Station Information</td>
                </tr>

                {{-- Explicitly show coordinates with map if available --}}
                @if ($record->station->latitude && $record->station->longitude)
                  <tr class="bg-emerald-100">
                    <td class="p-1 font-bold" style="width: 20%; min-width: 120px;">station.coordinates</td>
                    <td class="p-1" style="width: 80%;">
                      <a href="https://www.google.com/maps?q={{ $record->station->latitude }},{{ $record->station->longitude }}"
                         target="_blank"
                         class="text-teal-700 hover:text-teal-900 hover:underline">
                        {{ $record->station->latitude }}, {{ $record->station->longitude }}
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

                @foreach ($record->station->toArray() as $key => $value)
                  {{-- Skip country (shown separately) and coordinates (shown above) --}}
                  @if (in_array($key, ['country', 'latitude', 'longitude']))
                    @continue
                  @endif

                  {{-- Skip null values and empty arrays --}}
                  @if (is_null($value) || (is_array($value) && empty($value)) || (is_string($value) && $value === ''))
                    @continue
                  @endif

                  <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">station.{{ $key }}</td>
                    <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">
                      @if (is_array($value))
                        @if (isset($value['name']))
                          {{ $value['name'] }}
                        @else
                          @foreach ($value as $item)
                            <div class="py-1">{{ is_array($item) ? json_encode($item) : $item }}</div>
                          @endforeach
                        @endif
                      @else
                        {{ $value }}
                      @endif
                    </td>
                  </tr>
                @endforeach

                {{-- Add country information if available --}}
                @if ($record->station->country_id)
                  @php
                    // Access the relationship using getRelation to avoid attribute conflict
                    $stationCountry = $record->station->getRelation('country');
                  @endphp
                  @if ($stationCountry)
                    @if ($stationCountry->name)
                      <tr class="bg-slate-100">
                        <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">station.country.name</td>
                        <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">{{ $stationCountry->name }}</td>
                      </tr>
                    @endif
                    @if ($stationCountry->code)
                      <tr class="bg-slate-200">
                        <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">station.country.code</td>
                        <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">{{ $stationCountry->code }}</td>
                      </tr>
                    @endif
                  @endif
                @endif
              @endif

              {{-- Add substance information --}}
              @if ($record->substance)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Substance Information</td>
                </tr>
                @if ($record->substance->id)
                  <tr class="bg-slate-100">
                    <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">substance.id</td>
                    <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">{{ $record->substance->id }}</td>
                  </tr>
                @endif
                @if ($record->substance->code)
                  <tr class="bg-slate-200">
                    <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">substance.code</td>
                    <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">
                      <a href="{{ route('substances.show', $record->substance->id) }}" class="link-lime-text">
                        NS{{ $record->substance->code }}
                      </a>
                    </td>
                  </tr>
                @endif
                @if ($record->substance->name)
                  <tr class="bg-slate-100">
                    <td class="p-1 font-bold" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">substance.name</td>
                    <td class="p-1" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">{{ $record->substance->name }}</td>
                  </tr>
                @endif
              @endif

              {{-- Add data source information --}}
              @if ($dataSource)
                <tr class="bg-amber-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Data Source</td>
                </tr>
                @if ($dataSource->sourceData)
                  <tr class="bg-amber-50">
                    <td class="p-1 font-bold text-amber-900" style="width: 20%; min-width: 120px;">Type of Data Source</td>
                    <td class="p-1 text-amber-800" style="width: 80%;">{{ $dataSource->sourceData->name }}</td>
                  </tr>
                @endif
                @if ($dataSource->monitoringType)
                  <tr class="bg-amber-100">
                    <td class="p-1 font-bold text-amber-900" style="width: 20%; min-width: 120px;">Type of Monitoring</td>
                    <td class="p-1 text-amber-800" style="width: 80%;">{{ $dataSource->monitoringType->name }}</td>
                  </tr>
                @endif
                @if ($dataSource->organisation)
                  <tr class="bg-amber-50">
                    <td class="p-1 font-bold text-amber-900" style="width: 20%; min-width: 120px;">Organisation</td>
                    <td class="p-1 text-amber-800" style="width: 80%;">
                      {{ $dataSource->organisation->name }}
                      @if ($dataSource->organisation->acronym)
                        ({{ $dataSource->organisation->acronym }})
                      @endif
                      @if ($dataSource->organisation->city)
                        , {{ $dataSource->organisation->city }}
                      @endif
                      @if ($dataSource->organisation->country)
                        , {{ $dataSource->organisation->country->name }}
                      @endif
                    </td>
                  </tr>
                @endif
                @if ($dataSource->laboratory)
                  <tr class="bg-amber-100">
                    <td class="p-1 font-bold text-amber-900" style="width: 20%; min-width: 120px;">Laboratory</td>
                    <td class="p-1 text-amber-800" style="width: 80%;">
                      {{ $dataSource->laboratory->name }}
                      @if ($dataSource->laboratory->city)
                        , {{ $dataSource->laboratory->city }}
                      @endif
                      @if ($dataSource->laboratory->country)
                        , {{ $dataSource->laboratory->country->name }}
                      @endif
                    </td>
                  </tr>
                @endif
              @endif

              {{-- Matrix metadata: legacy *_id columns are resolved to labels
                   via the list_* lookups in MatrixMetadataLabeller --}}
              @if (!empty($matrixMetadata))
                @foreach ($matrixMetadata as $matrixType => $matrixData)
                  @if (!empty($matrixData))
                    <tr class="bg-teal-600 text-white">
                      <td colspan="2" class="p-2 font-bold text-center">
                        Matrix: {{ ucwords(str_replace('_', ' ', $matrixType)) }}
                        <span class="text-xs font-normal">(first matching record for this station)</span>
                      </td>
                    </tr>
                    @foreach ($matrixData as $rowIndex => $field)
                      <tr class="@if ($rowIndex % 2 === 0) bg-teal-50 @else bg-teal-100 @endif">
                        <td class="p-1 font-bold text-teal-900" style="width: 20%; min-width: 120px; word-wrap: break-word; overflow-wrap: break-word;">
                          {{ $field['label'] }}
                        </td>
                        <td class="p-1 text-teal-800" style="width: 80%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all; max-width: 0;">
                          {{ $field['value'] }}
                        </td>
                      </tr>
                    @endforeach
                  @endif
                @endforeach
              @endif
            </table>

          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Initialize Leaflet map if coordinates are available --}}
  @if ($record->station && $record->station->latitude && $record->station->longitude)
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const lat = {{ $record->station->latitude }};
        const lng = {{ $record->station->longitude }};
        const stationName = @json($record->station->name ?? 'Station');
        const sampleCode = @json($record->station->short_sample_code ?? '');

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
          (sampleCode ? '<br><span class="text-sm">' + sampleCode + '</span>' : '') +
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
