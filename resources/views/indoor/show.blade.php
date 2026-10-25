<x-app-layout>
  <x-slot name="header">
    @include('indoor.header')
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
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Indoor Record at Glance</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              @if ($record->id)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Record ID</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->id }}</p>
                </div>
              @endif
              @if ($record->substance)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Substance</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->substance->name }}</p>
                </div>
              @endif
              @if ($record->concentration_value)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Concentration</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ number_format($record->concentration_value, 4) }} {{ $record->concentration_unit }}</p>
                </div>
              @endif
              @if ($record->matrix)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Matrix</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->matrix->name }}</p>
                </div>
              @elseif ($record->matrix_other)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Matrix</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->matrix_other }}</p>
                </div>
              @endif
              @if ($record->environmentType)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Type of Environment</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->environmentType->name }}</p>
                </div>
              @endif
              @if ($record->environmentCategory)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Category of Environment</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->environmentCategory->name }}</p>
                </div>
              @elseif ($record->dcoe_other)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Category of Environment</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->dcoe_other }}</p>
                </div>
              @endif
              @if ($record->sampling_date)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Sampling Date</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->sampling_date }}</p>
                </div>
              @endif
              @if ($record->station_name)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Station Name</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->station_name }}</p>
                </div>
              @endif
            </div>
          </div>

          {{-- Complete Record Details --}}
          <div class="w-full overflow-x-auto">
            <table class="table-auto w-full border-separate border-spacing-1 text-xs mt-4" style="table-layout: fixed;">
              @php
                $rowIndex = 0;
                $excludedKeys = ['country', 'matrix', 'environment_type', 'environment_category', 'substance', 'purpose_code', 'observation_type', 'collection_code', 'analytical_method', 'data_source', 'country_record', 'country_other_record', 'created_at', 'updated_at'];
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

              {{-- Location/Station Information --}}
              @if ($record->latitude_decimal && $record->longitude_decimal)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Location Information</td>
                </tr>

                <tr class="bg-emerald-100">
                  <td class="p-1 font-bold" style="width: 20%; min-width: 120px;">Coordinates</td>
                  <td class="p-1" style="width: 80%;">
                    <a href="https://www.google.com/maps?q={{ $record->latitude_decimal }},{{ $record->longitude_decimal }}"
                       target="_blank"
                       class="text-teal-700 hover:text-teal-900 hover:underline">
                      {{ $record->latitude_decimal }}, {{ $record->longitude_decimal }}
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

              {{-- Substance Information --}}
              @if ($record->substance)
                <tr class="bg-teal-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Substance</td>
                </tr>
                <tr class="bg-teal-50">
                  <td class="p-1 font-bold text-teal-900" style="width: 20%;">Name</td>
                  <td class="p-1 text-teal-800" style="width: 80%;">{{ $record->substance->name }}</td>
                </tr>
                @if ($record->substance->cas_number)
                  <tr class="bg-teal-100">
                    <td class="p-1 font-bold text-teal-900" style="width: 20%;">CAS Number</td>
                    <td class="p-1 text-teal-800 font-mono" style="width: 80%;">{{ $record->substance->formatted_cas }}</td>
                  </tr>
                @endif
                <tr class="bg-teal-50">
                  <td class="p-1 font-bold text-teal-900" style="width: 20%;">NORMAN SusDat ID</td>
                  <td class="p-1 text-teal-800" style="width: 80%;">
                    <a href="{{ route('substances.show', $record->substance->id) }}"
                       target="_blank"
                       class="text-teal-700 hover:text-teal-900 hover:underline font-mono">
                      NS{{ $record->substance->code }}
                      <i class="fas fa-external-link-alt text-xs ml-1"></i>
                    </a>
                  </td>
                </tr>
              @endif

              {{-- Data Source Information --}}
              @if ($record->dataSource)
                @php
                  $ds = $record->dataSource;
                  $dsFields = [
                    'Type of data source' => $ds->typeOfDataSource?->name ?: null,
                    'Title of project' => $ds->title_project ?: null,
                    'Organisation' => $ds->organisation ?: null,
                    'E-mail' => $ds->email ?: null,
                    'Laboratory' => $ds->laboratory_name ?: null,
                    'Laboratory ID' => $ds->laboratory_id ?: null,
                    'References / literature 1' => $ds->literature1 ?: null,
                    'References / literature 2' => $ds->literature2 ?: null,
                    'Author' => $ds->author ?: null,
                  ];
                @endphp
                <tr class="bg-sky-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Data Source</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($dsFields as $label => $value)
                  @if (is_null($value) || $value === '')
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-sky-50 @else bg-sky-100 @endif">
                    <td class="p-1 font-bold text-sky-900" style="width: 20%; min-width: 120px;">{{ $label }}</td>
                    <td class="p-1 text-sky-800" style="width: 80%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- Matrix Information --}}
              @if ($record->matrix)
                <tr class="bg-teal-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Matrix</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->matrix->toArray() as $key => $value)
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

              {{-- Environment Type Information --}}
              @if ($record->environmentType)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Type of Environment</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->environmentType->toArray() as $key => $value)
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

              {{-- Environment Category Information --}}
              @if ($record->environmentCategory)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Category of Environment</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->environmentCategory->toArray() as $key => $value)
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

              {{-- Purpose Code Information --}}
              @if ($record->purposeCode)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Purpose Code</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->purposeCode->toArray() as $key => $value)
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

              {{-- Observation Type Information --}}
              @if ($record->observationType)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Observation Type</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->observationType->toArray() as $key => $value)
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

              {{-- Collection Code Information --}}
              @if ($record->collectionCode)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Collection Code</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($record->collectionCode->toArray() as $key => $value)
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

              {{-- Analytical Method / QA/QC Information --}}
              @if ($record->analyticalMethod)
                @php
                  $am = $record->analyticalMethod;
                  $amFields = [
                    'Limit of Detection (LoD)' => $am->am_lod ? $am->am_lod . ' ' . $am->am_unit : null,
                    'Limit of Quantification (LoQ)' => $am->am_loq ? $am->am_loq . ' ' . $am->am_unit : null,
                    'Uncertainty at LoQ [%]' => $am->am_uncertainty_loq ?: null,
                    'Coverage factor' => $am->coverageFactor?->name ?: null,
                    'Sampling method 1' => $am->samplingMethod1?->name ?: ($am->dsm1_other ?: null),
                    'Sampling method 2' => $am->samplingMethod2?->name ?: ($am->dsm2_other ?: null),
                    'Sample preparation method' => $am->samplePreparationMethod?->name ?: ($am->dpm_other ?: null),
                    'Analytical method' => $am->analyticalMethod?->name ?: ($am->dam_other ?: null),
                    'Has standardised analytical method been used?' => $am->standardisedMethod?->name ?: ($am->dsm_other ?: null),
                    'Code' => $am->am_number ?: null,
                    'Has the used method been validated?' => $am->am_validated_method ?: null,
                    'Have the results been corrected for extraction recovery?' => $am->am_corrected_recovery ?: null,
                    'Was a field blank checked?' => $am->am_field_blank ?: null,
                    'Is the laboratory accredited according to ISO 17025?' => $am->am_iso ?: null,
                    'Is the laboratory accredited for the given analyte?' => $am->am_given_analyte ?: null,
                    'Does the laboratory participate in interlaboratory studies?' => $am->am_laboratory_participate ?: null,
                    'Summary of performance' => $am->am_summary_performance ?: null,
                    'Are control charts recorded?' => $am->am_control_charts ?: null,
                    'Are the data controlled by competent authority?' => $am->am_authority ?: null,
                    'Remark' => $am->am_remark ?: null,
                  ];
                @endphp
                <tr class="bg-amber-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">QA/QC Information - Analytical Method</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($amFields as $label => $value)
                  @if (is_null($value) || $value === '' || $value === '0' || $value === 0)
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-amber-50 @else bg-amber-100 @endif">
                    <td class="p-1 font-bold text-amber-900" style="width: 20%; min-width: 120px;">{{ $label }}</td>
                    <td class="p-1 text-amber-800" style="width: 80%;">{{ $value }}</td>
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
  @if ($record->latitude_decimal && $record->longitude_decimal)
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const lat = {{ $record->latitude_decimal }};
        const lng = {{ $record->longitude_decimal }};
        const stationName = @json($record->station_name ?? 'Station');

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
