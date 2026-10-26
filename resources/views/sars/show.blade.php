<x-app-layout>
  <x-slot name="header">
    @include('sars.header')
  </x-slot>

  @php
    // Calculate coordinates at the top - used for map display
    // latitude_decimal/longitude_decimal have priority
    // The _show fields are swapped in the database, so we need to correct them
    $mapLat = null;
    $mapLng = null;
    if ($sars->latitude_decimal && $sars->longitude_decimal) {
      $mapLat = $sars->latitude_decimal;
      $mapLng = $sars->longitude_decimal;
    } elseif ($sars->longitude_decimal_show && $sars->latitude_decimal_show) {
      // The _show fields are swapped: longitude_decimal_show contains latitude, latitude_decimal_show contains longitude
      $mapLat = $sars->longitude_decimal_show;
      $mapLng = $sars->latitude_decimal_show;
    }
    $hasCoordinates = $mapLat && $mapLng;
  @endphp

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
            <h2 class="text-lg font-semibold text-gray-900 mb-4">SARS-CoV-2 Record at Glance</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Record ID</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $sars->id }}</p>
              </div>
              @if ($sars->name_of_country)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Country</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $sars->name_of_country }}</p>
                </div>
              @endif
              @if ($sars->name_of_city)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">City</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $sars->name_of_city }}</p>
                </div>
              @endif
              @if ($sars->station_name)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Station</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $sars->station_name }}</p>
                </div>
              @endif
              @if ($sars->sample_matrix)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Sample Matrix</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $sars->sample_matrix }}</p>
                </div>
              @endif
              @if ($sars->sample_from_year && $sars->sample_from_month && $sars->sample_from_day)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Sampling Date</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $sars->sample_from_year }}-{{ str_pad($sars->sample_from_month, 2, '0', STR_PAD_LEFT) }}-{{ str_pad($sars->sample_from_day, 2, '0', STR_PAD_LEFT) }}</p>
                </div>
              @endif
              @if ($sars->data_provider)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Data Provider</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $sars->data_provider }}</p>
                </div>
              @endif
              @if ($sars->gene1)
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Gene 1</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $sars->gene1 }}</p>
                </div>
              @endif
            </div>
          </div>

          {{-- Action Buttons --}}
          <div class="mb-6 flex justify-end space-x-2">
            @if (auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('sars')))
              <a href="{{ route('sars.search.edit', $sars->id) }}" class="link-edit">
                <i class="fas fa-edit mr-1"></i> Edit
              </a>
            @endif
            <a href="{{ route('sars.search.search') }}" class="link-lime">
              <i class="fas fa-arrow-left mr-1"></i> Back to Search
            </a>
          </div>

          {{-- Complete Record Details --}}
          <div class="w-full overflow-x-auto">
            <table class="table-auto w-full border-separate border-spacing-1 text-xs mt-4" style="table-layout: fixed;">

              {{-- DATA SOURCE Section --}}
              @php
                $dataSourceFields = [
                  'Type of data' => $sars->type_of_data,
                  'Data provider' => $sars->data_provider,
                  'Contact person' => $sars->contact_person,
                  'Address of contact' => $sars->address_of_contact,
                  'E-mail' => $sars->email,
                  'Laboratory' => $sars->laboratory,
                ];
                $hasDataSource = collect($dataSourceFields)->filter()->isNotEmpty();
              @endphp
              @if ($hasDataSource)
                <tr class="bg-sky-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Data Source</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($dataSourceFields as $label => $value)
                  @if (is_null($value) || $value === '')
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-sky-50 @else bg-sky-100 @endif">
                    <td class="p-1 font-bold text-sky-900" style="width: 25%; min-width: 150px;">{{ $label }}</td>
                    <td class="p-1 text-sky-800" style="width: 75%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- SAMPLING SITE / STATION Section --}}
              @php
                $stationFields = [
                  'Name of country' => $sars->name_of_country,
                  'Name of the City / Municipality' => $sars->name_of_city,
                  'Station name' => $sars->station_name,
                  'National code' => $sars->national_code,
                  'Relevant EC code - WISE' => $sars->relevant_ec_code_wise,
                  'Relevant EC code - Other' => $sars->relevant_ec_code_other,
                  'Other code' => $sars->other_code,
                  'Altitude [m]' => $sars->altitude,
                  'Design capacity [P.E.]' => $sars->design_capacity,
                  'Population served [P.E.]' => $sars->population_served,
                  'Catchment size [m²]' => $sars->catchment_size,
                  'GDP [EUR]' => $sars->gdp,
                ];
                $hasStation = collect($stationFields)->filter()->isNotEmpty() || $hasCoordinates;
              @endphp
              @if ($hasStation)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Sampling Site / Station</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($stationFields as $label => $value)
                  @if (is_null($value) || $value === '')
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 25%; min-width: 150px;">{{ $label }}</td>
                    <td class="p-1" style="width: 75%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach

                {{-- Coordinates with map --}}
                @if ($hasCoordinates)
                  <tr class="bg-emerald-100">
                    <td class="p-1 font-bold" style="width: 25%; min-width: 150px;">Coordinates</td>
                    <td class="p-1" style="width: 75%;">
                      <a href="https://www.google.com/maps?q={{ $mapLat }},{{ $mapLng }}"
                         target="_blank"
                         class="text-teal-700 hover:text-teal-900 hover:underline">
                        {{ number_format($mapLat, 6) }}, {{ number_format($mapLng, 6) }}
                        <i class="fas fa-external-link-alt text-xs ml-1"></i>
                      </a>
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2" class="p-0">
                      <div id="station-map" class="w-full h-64 rounded-b-lg border border-gray-300"></div>
                    </td>
                  </tr>
                @elseif ($sars->latitude || $sars->longitude)
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 25%; min-width: 150px;">Latitude</td>
                    <td class="p-1" style="width: 75%;">{{ $sars->latitude }} {{ $sars->latitude_d }}° {{ $sars->latitude_m }}' {{ $sars->latitude_s }}"</td>
                  </tr>
                  <tr class="@if ($rowIndex % 2 === 1) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 25%; min-width: 150px;">Longitude</td>
                    <td class="p-1" style="width: 75%;">{{ $sars->longitude }} {{ $sars->longitude_d }}° {{ $sars->longitude_m }}' {{ $sars->longitude_s }}"</td>
                  </tr>
                @endif
              @endif

              {{-- SARS-CoV-2 PREVALENCE Section --}}
              @php
                $prevalenceFields = [
                  'No. of people SARS-CoV-2 POSITIVE' => $sars->people_positive,
                  'No. of people RECOVERED' => $sars->people_recovered,
                  'No. of people SARS-CoV-2 POSITIVE_PAST' => $sars->people_positive_past,
                  'No. of people RECOVERED_PAST' => $sars->people_recovered_past,
                ];
                $hasPrevalence = collect($prevalenceFields)->filter(fn($v) => !is_null($v) && $v !== '')->isNotEmpty();
              @endphp
              @if ($hasPrevalence)
                <tr class="bg-red-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">SARS-CoV-2 Prevalence Data</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($prevalenceFields as $label => $value)
                  @if (is_null($value) || $value === '')
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-red-50 @else bg-red-100 @endif">
                    <td class="p-1 font-bold text-red-900" style="width: 25%; min-width: 150px;">{{ $label }}</td>
                    <td class="p-1 text-red-800" style="width: 75%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- ECOSYSTEM / MATRIX Section --}}
              @php
                $samplingDateFrom = null;
                if ($sars->sample_from_year && $sars->sample_from_month && $sars->sample_from_day) {
                  $samplingDateFrom = $sars->sample_from_year . '-' . str_pad($sars->sample_from_month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($sars->sample_from_day, 2, '0', STR_PAD_LEFT);
                  if ($sars->sample_from_hour) {
                    $samplingDateFrom .= ' ' . $sars->sample_from_hour;
                  }
                }
                $samplingDateTo = null;
                if ($sars->sample_to_year && $sars->sample_to_month && $sars->sample_to_day) {
                  $samplingDateTo = $sars->sample_to_year . '-' . str_pad($sars->sample_to_month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($sars->sample_to_day, 2, '0', STR_PAD_LEFT);
                  if ($sars->sample_to_hour) {
                    $samplingDateTo .= ' ' . $sars->sample_to_hour;
                  }
                }
                $ecosystemFields = [
                  'Sample matrix' => $sars->sample_matrix,
                  'Sampling date FROM' => $samplingDateFrom,
                  'Sampling date TO' => $samplingDateTo,
                  'Type of sample' => $sars->type_of_sample,
                  'Type of composite sample' => $sars->type_of_composite_sample,
                  'Interval' => $sars->sample_interval,
                ];
                $hasEcosystem = collect($ecosystemFields)->filter()->isNotEmpty();
              @endphp
              @if ($hasEcosystem)
                <tr class="bg-teal-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Ecosystem / Matrix</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($ecosystemFields as $label => $value)
                  @if (is_null($value) || $value === '')
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-teal-50 @else bg-teal-100 @endif">
                    <td class="p-1 font-bold text-teal-900" style="width: 25%; min-width: 150px;">{{ $label }}</td>
                    <td class="p-1 text-teal-800" style="width: 75%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- SAMPLING CONDITIONS Section --}}
              @php
                $conditionsFields = [
                  'Flow - Total [m³]' => $sars->flow_total,
                  'Flow - Minimum [m³/h]' => $sars->flow_minimum,
                  'Flow - Maximum [m³/h]' => $sars->flow_maximum,
                  'Temperature [°C]' => $sars->temperature,
                  'COD [mg/L]' => $sars->cod,
                  'Total N / NH4-N [mg N/L]' => $sars->total_n_nh4_n,
                  'TSS [mg/L]' => $sars->tss,
                  'Dry weather conditions' => $sars->dry_weather_conditions,
                  'Last rain event [No. of days]' => $sars->last_rain_event,
                ];
                $hasConditions = collect($conditionsFields)->filter()->isNotEmpty();
              @endphp
              @if ($hasConditions)
                <tr class="bg-cyan-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Sampling Conditions</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($conditionsFields as $label => $value)
                  @if (is_null($value) || $value === '')
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-cyan-50 @else bg-cyan-100 @endif">
                    <td class="p-1 font-bold text-cyan-900" style="width: 25%; min-width: 150px;">{{ $label }}</td>
                    <td class="p-1 text-cyan-800" style="width: 75%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- DETERMINANT / MEASURAND Section --}}
              @php
                $determinantFields = [
                  'Associated phenotype' => $sars->associated_phenotype,
                  'Genetic marker' => $sars->genetic_marker,
                ];
                $hasDeterminant = collect($determinantFields)->filter()->isNotEmpty();
              @endphp
              @if ($hasDeterminant)
                <tr class="bg-purple-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Determinant / Measurand</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($determinantFields as $label => $value)
                  @if (is_null($value) || $value === '')
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-purple-50 @else bg-purple-100 @endif">
                    <td class="p-1 font-bold text-purple-900" style="width: 25%; min-width: 150px;">{{ $label }}</td>
                    <td class="p-1 text-purple-800" style="width: 75%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- SAMPLE PREPARATION Section --}}
              @php
                $samplePrepFields = [
                  'Date of sample preparation' => $sars->date_of_sample_preparation,
                  'Storage of sample' => $sars->storage_of_sample,
                  'Volume of sample' => $sars->volume_of_sample,
                  'Internal standard used' => $sars->internal_standard_used1,
                  'Method used for sample preparation' => $sars->method_used_for_sample_preparation,
                ];
                $hasSamplePrep = collect($samplePrepFields)->filter()->isNotEmpty();
              @endphp
              @if ($hasSamplePrep)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">Sample Preparation</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($samplePrepFields as $label => $value)
                  @if (is_null($value) || $value === '')
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 25%; min-width: 150px;">{{ $label }}</td>
                    <td class="p-1" style="width: 75%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- RNA EXTRACTION Section --}}
              @php
                $rnaExtractionFields = [
                  'Date of RNA extraction' => $sars->date_of_rna_extraction,
                  'Method used for RNA extraction' => $sars->method_used_for_rna_extraction,
                  'Internal standard used' => $sars->internal_standard_used2,
                  'RNA concentration' => $sars->rna1,
                  'RNA purity' => $sars->rna2,
                  'Replicates' => $sars->replicates1,
                ];
                $hasRnaExtraction = collect($rnaExtractionFields)->filter()->isNotEmpty();
              @endphp
              @if ($hasRnaExtraction)
                <tr class="bg-gray-300">
                  <td colspan="2" class="p-2 font-bold text-center">RNA Extraction</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($rnaExtractionFields as $label => $value)
                  @if (is_null($value) || $value === '')
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="p-1 font-bold" style="width: 25%; min-width: 150px;">{{ $label }}</td>
                    <td class="p-1" style="width: 75%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- ANALYTICAL METHOD Section --}}
              @php
                $analyticalFields = [
                  'Analytical method type' => $sars->analytical_method_type,
                  'Analytical method type (other)' => $sars->analytical_method_type_other,
                  'Date of analysis' => $sars->date_of_analysis,
                  'Limit of Detection (LoD) 1' => $sars->lod1,
                  'Limit of Detection (LoD) 2' => $sars->lod2,
                  'Limit of Quantification (LoQ) 1' => $sars->loq1,
                  'Limit of Quantification (LoQ) 2' => $sars->loq2,
                  'Uncertainty of the quantification' => $sars->uncertainty_of_the_quantification,
                  'Efficiency' => $sars->efficiency,
                ];
                $hasAnalytical = collect($analyticalFields)->filter()->isNotEmpty();
              @endphp
              @if ($hasAnalytical)
                <tr class="bg-amber-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Analytical Method</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($analyticalFields as $label => $value)
                  @if (is_null($value) || $value === '')
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-amber-50 @else bg-amber-100 @endif">
                    <td class="p-1 font-bold text-amber-900" style="width: 25%; min-width: 150px;">{{ $label }}</td>
                    <td class="p-1 text-amber-800" style="width: 75%;">{{ $value }}</td>
                  </tr>
                  @php $rowIndex++; @endphp
                @endforeach
              @endif

              {{-- RESULTS Section --}}
              @php
                $resultsFields = [
                  'RNA result' => $sars->rna3,
                  'Positive control used' => $sars->pos_control_used,
                  'Replicates' => $sars->replicates2,
                  'Ct value' => $sars->ct,
                  'Gene 1' => $sars->gene1,
                  'Gene 2' => $sars->gene2,
                  'Comment' => $sars->comment,
                ];
                $hasResults = collect($resultsFields)->filter()->isNotEmpty();
              @endphp
              @if ($hasResults)
                <tr class="bg-green-600 text-white">
                  <td colspan="2" class="p-2 font-bold text-center">Results</td>
                </tr>
                @php $rowIndex = 0; @endphp
                @foreach ($resultsFields as $label => $value)
                  @if (is_null($value) || $value === '')
                    @continue
                  @endif
                  <tr class="@if ($rowIndex % 2 === 0) bg-green-50 @else bg-green-100 @endif">
                    <td class="p-1 font-bold text-green-900" style="width: 25%; min-width: 150px;">{{ $label }}</td>
                    <td class="p-1 text-green-800" style="width: 75%;">{{ $value }}</td>
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
  @if ($hasCoordinates)
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const lat = {{ $mapLat }};
        const lng = {{ $mapLng }};
        const stationName = @json($sars->station_name ?? 'Station');

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
