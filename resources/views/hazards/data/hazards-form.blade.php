<x-app-layout>
  <x-slot name="header">
    @include('hazards.header')
  </x-slot>

  <div class="container mx-auto px-4 py-8">
    <nav class="mb-6">
      <ol class="flex items-center space-x-2 text-sm text-gray-500">
        <li>
          <a href="{{ route('hazards.data.search.filter') }}" class="link-lime-text hover:text-lime-700">Hazards Data</a>
        </li>
        <li><span class="mx-2">/</span></li>
        <li class="text-gray-800 font-medium">Record Details for {{ $recordId }}</li>
      </ol>
    </nav>

    @php
      $domainLabel = $record->data_domain === 'fate_transport'
        ? 'Fate and Transport'
        : ($record->data_domain === 'physchem'
          ? 'Phys-Chemical'
          : ($record->data_domain ? ucwords(str_replace('_', ' ', $record->data_domain)) : null));

      $testTypeLabel = match ((string) $record->test_type) {
        '2' => 'Experimental',
        '3' => 'Predicted',
        default => ($record->test_type ?: null),
      };

      $resultTypeLabel = $testTypeLabel ?: (is_string($record->reference_type) && trim($record->reference_type) !== ''
        ? trim($record->reference_type)
        : null);

      $valueOrNull = static function ($value) {
        if (is_null($value)) {
          return null;
        }

        if (is_string($value)) {
          $value = trim($value);
          return $value === '' ? null : $value;
        }

        return $value;
      };

      $boolLabel = static fn ($value) => is_null($value) ? null : ($value ? 'Yes' : 'No');
      $numberLabel = static function ($value, int $decimals = 4) {
        if (is_null($value) || $value === '') {
          return null;
        }

        $numericValue = (float) $value;
        $absoluteValue = abs($numericValue);

        if ($numericValue === 0.0) {
          return '0';
        }

        if ($absoluteValue > 0 && $absoluteValue < 0.001) {
          $formatted = sprintf('%.3e', $numericValue);
          $formatted = preg_replace('/\.?0+e/i', 'e', $formatted) ?? $formatted;
          $formatted = preg_replace('/e\+?(-?)0*(\d+)/i', 'e$1$2', $formatted) ?? $formatted;

          return strtolower($formatted);
        }

        $formatted = number_format($numericValue, $decimals, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
      };

      $substance = $record->substance;
      $comptoxDashboardUrl = $record->dtxid
        ? 'https://comptox.epa.gov/dashboard/chemical/details/' . $record->dtxid
        : null;

      $detailSections = [
        'Substance Identity' => [
          ['label' => 'Substance Name', 'value' => $valueOrNull($record->substance_name) ?? $valueOrNull($substance?->display_name) ?? $valueOrNull($substance?->name)],
          ['label' => 'NORMAN SusDat Code', 'value' => $valueOrNull($substance?->prefixed_code)],
          ['label' => 'CAS Number', 'value' => $valueOrNull($record->cas_no) ?? $valueOrNull($substance?->formatted_cas)],
          ['label' => 'CompTox DTXID', 'value' => $valueOrNull($record->dtxid) ?? $valueOrNull($substance?->dtxid)],
          ['label' => 'InChIKey', 'value' => $valueOrNull($record->inchikey) ?? $valueOrNull($substance?->stdinchikey)],
          ['label' => 'SMILES', 'value' => $valueOrNull($record->smiles) ?? $valueOrNull($substance?->smiles_dashboard) ?? $valueOrNull($substance?->smiles)],
          ['label' => 'CompTox Dashboard', 'value' => $valueOrNull($comptoxDashboardUrl), 'link' => true],
        ],
        'Parameter and Classification' => [
          ['label' => 'Data Domain', 'value' => $valueOrNull($domainLabel)],
          ['label' => 'NORMAN Parameter', 'value' => $valueOrNull($record->norman_parameter_name)],
          ['label' => 'Specific Parameter', 'value' => $valueOrNull($record->specific_parameter_name)],
          ['label' => 'Assessment Class', 'value' => $valueOrNull($record->assessment_class)],
          ['label' => 'Assessment Qualifier', 'value' => $valueOrNull($record->assessment_qualifier)],
          ['label' => 'Assessment Value', 'value' => $numberLabel($record->value_assessment_index)],
          ['label' => 'Unit', 'value' => $valueOrNull($record->unit)],
        ],
        'Reference and Source' => [
          ['label' => 'Data Source', 'value' => $valueOrNull($record->data_source)],
          ['label' => 'Reference Type', 'value' => $valueOrNull($record->reference_type)],
          ['label' => 'Bibliographic Source', 'value' => $valueOrNull($record->bibliographic_source)],
          ['label' => 'Authors', 'value' => $valueOrNull($record->authors)],
          ['label' => 'Year', 'value' => $valueOrNull($record->year)],
          ['label' => 'DOI', 'value' => $valueOrNull($record->physico_chemical_source_doi)],
          ['label' => 'Regulatory Purpose', 'value' => $valueOrNull($record->regulatory_purpose)],
          ['label' => 'Use of Study', 'value' => $valueOrNull($record->use_of_study)],
        ],
        'Test Context' => [
          ['label' => 'Test Type', 'value' => $valueOrNull($testTypeLabel)],
          ['label' => 'Test Matrix', 'value' => $valueOrNull($record->test_matrix)],
          ['label' => 'Test Species', 'value' => $valueOrNull($record->test_species)],
          ['label' => 'Standard Used', 'value' => $valueOrNull($record->standard_used)],
          ['label' => 'Standard Qualifier', 'value' => $valueOrNull($record->standard_qualifier)],
          ['label' => 'Standard Test', 'value' => $boolLabel($record->standard_test)],
          ['label' => 'Performed Under GLP', 'value' => $boolLabel($record->performed_under_glp)],
          ['label' => 'Radio-Labeled Substance', 'value' => $boolLabel($record->radio_labeled_substance)],
        ],
        'Reported Values and Quality Notes' => [
          ['label' => 'Original Parameter', 'value' => $valueOrNull($record->original_parameter_name)],
          ['label' => 'Original Qualifier', 'value' => $valueOrNull($record->original_qualifier)],
          ['label' => 'Original Value', 'value' => $numberLabel($record->original_value)],
          ['label' => 'Original Unit', 'value' => $valueOrNull($record->original_unit)],
          ['label' => 'Original Range', 'value' => $valueOrNull($record->original_value_range)],
          ['label' => 'Duration (days)', 'value' => $numberLabel($record->duration_days)],
          ['label' => 'Exposure Concentration', 'value' => $numberLabel($record->exposure_concentration)],
          ['label' => 'pH', 'value' => $numberLabel($record->ph)],
          ['label' => 'Temperature (C)', 'value' => $numberLabel($record->temperature_c)],
          ['label' => 'Total Organic Carbon', 'value' => $numberLabel($record->total_organic_carbon)],
          ['label' => 'Applicability Domain', 'value' => $valueOrNull($record->applicability_domain)],
          ['label' => 'Applicability Domain Score', 'value' => $numberLabel($record->applicability_domain_score)],
          ['label' => 'Reliability Score', 'value' => $numberLabel($record->reliability_score)],
          ['label' => 'Reliability System', 'value' => $valueOrNull($record->reliability_score_system)],
          ['label' => 'Reliability Rationale', 'value' => $valueOrNull($record->reliability_rational)],
          ['label' => 'Reliability Institution', 'value' => $valueOrNull($record->institution_of_reliability_score)],
          ['label' => 'General Comment', 'value' => $valueOrNull($record->general_comment)],
        ],
      ];

      $visibleSections = collect($detailSections)
        ->map(function ($rows) {
          return array_values(array_filter($rows, static function ($row) {
            return array_key_exists('value', $row) && !is_null($row['value']);
          }));
        })
        ->filter(static fn ($rows) => count($rows) > 0);
    @endphp

    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
      <h1 class="text-2xl font-bold text-gray-900 mb-4">Hazards Record Details</h1>

      <div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
        @if ($domainLabel)
          <span class="px-2 py-1 rounded bg-blue-100 text-blue-800">{{ $domainLabel }}</span>
        @endif
        @if ($resultTypeLabel)
          <span class="px-2 py-1 rounded bg-emerald-100 text-emerald-800">{{ $resultTypeLabel }}</span>
        @endif
      </div>

      @if ($visibleSections->isNotEmpty())
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 text-sm">
          @foreach ($visibleSections as $sectionTitle => $rows)
            <div class="space-y-2">
              <h2 class="text-base font-semibold text-gray-800 border-b pb-1">{{ $sectionTitle }}</h2>
              @foreach ($rows as $row)
                <div class="leading-relaxed">
                  <span class="font-semibold">{{ $row['label'] }}:</span>
                  @if (!empty($row['link']))
                    <a href="{{ $row['value'] }}" target="_blank" rel="noopener" class="link-lime-text break-all">{{ $row['value'] }}</a>
                  @else
                    <span class="break-words">{{ $row['value'] }}</span>
                  @endif
                </div>
              @endforeach
            </div>
          @endforeach
        </div>
      @else
        <div class="text-sm text-gray-600">
          No descriptive details are available for this record.
        </div>
      @endif

      <div class="mt-6">
        <a href="{{ url()->previous() }}" class="btn-submit">Back</a>
      </div>
    </div>
  </div>
</x-app-layout>
