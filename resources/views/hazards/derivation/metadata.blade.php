<x-app-layout>
  <x-slot name="header">
    @include('hazards.header')
  </x-slot>

  <div class="container mx-auto px-4 py-8">
    @php
      $formatDate = static function ($value) {
        if (empty($value)) {
          return null;
        }

        try {
          return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Throwable $e) {
          return (string) $value;
        }
      };

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

      $numberLabel = static function ($value, int $decimals = 4) {
        if ($value === null || $value === '') {
          return null;
        }

        if (! is_numeric($value)) {
          return (string) $value;
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

      $bucketLabel = strtoupper(substr((string) $selection->bucket, 0, 1));
      $modeLabel = str_contains((string) $selection->bucket, '_exp') ? 'Experimental' : 'Predicted';
      $selectionTypeLabel = $selection->kind === 'vote' ? 'Expert Vote' : 'Auto Selection';

      $detailSections = [
        'Selection Overview' => [
          ['label' => 'Selection ID', 'value' => $selection->id],
          ['label' => 'Bucket', 'value' => $selection->bucket],
          ['label' => 'Selection Type', 'value' => $selectionTypeLabel],
          ['label' => 'Hazard Criterion', 'value' => $valueOrNull($metadata->hazard_criterion) ?? $bucketLabel],
          ['label' => 'Source Record ID', 'value' => $selection->hazards_substance_data_id],
        ],
        'Substance Identity' => [
          ['label' => 'Substance Name', 'value' => $valueOrNull($substance->substance_name) ?? $valueOrNull($metadata->substance_name)],
          ['label' => 'CAS Number', 'value' => $valueOrNull($substance->cas_no) ?? $valueOrNull($metadata->cas_number)],
          ['label' => 'InChIKey', 'value' => $valueOrNull($substance->inchikey)],
        ],
        'Parameter and Classification' => [
          ['label' => 'Assessment Parameter', 'value' => $valueOrNull($metadata->assessment_parameter_name)],
          ['label' => 'NORMAN Classification', 'value' => $valueOrNull($metadata->norman_classification)],
          ['label' => 'NORMAN Vote', 'value' => $valueOrNull($metadata->norman_vote)],
          ['label' => 'Automated Expert Vote', 'value' => $valueOrNull($metadata->automated_expert_vote)],
          ['label' => 'Original Classification', 'value' => $valueOrNull($metadata->original_classification)],
          ['label' => 'Classification Score', 'value' => $numberLabel($metadata->classification_score)],
        ],
        'Reference and Source' => [
          ['label' => 'Data Source', 'value' => $valueOrNull($metadata->data_source)],
          ['label' => 'Editor', 'value' => $valueOrNull($metadata->editor)],
          ['label' => 'Record Date', 'value' => $formatDate($metadata->record_date)],
          ['label' => 'Reference Type', 'value' => $valueOrNull($metadata->reference_type)],
          ['label' => 'Title', 'value' => $valueOrNull($metadata->title)],
          ['label' => 'Authors', 'value' => $valueOrNull($metadata->authors)],
          ['label' => 'Year', 'value' => $valueOrNull($metadata->year)],
          ['label' => 'Bibliographic Source', 'value' => $valueOrNull($metadata->bibliographic_source)],
          ['label' => 'DOI', 'value' => $valueOrNull($metadata->hazards_file_doi)],
        ],
        'Test Context' => [
          ['label' => 'Test Type', 'value' => $valueOrNull($metadata->test_type)],
          ['label' => 'Performed Under GLP', 'value' => $valueOrNull($metadata->performed_under_glp)],
          ['label' => 'Standard Test', 'value' => $valueOrNull($metadata->standard_test)],
          ['label' => 'Radio-Labeled Substance', 'value' => $valueOrNull($metadata->radio_labeled_substance)],
          ['label' => 'Standard Qualifier', 'value' => $valueOrNull($metadata->standard_qualifier)],
          ['label' => 'Standard Used', 'value' => $valueOrNull($metadata->standard_used)],
          ['label' => 'Test Matrix', 'value' => $valueOrNull($metadata->test_matrix)],
          ['label' => 'Test Species', 'value' => $valueOrNull($metadata->test_species)],
        ],
        'Reported Values and Quality Notes' => [
          ['label' => 'Original Parameter', 'value' => $valueOrNull($metadata->original_parameter_name)],
          ['label' => 'Original Qualifier', 'value' => $valueOrNull($metadata->original_qualifier)],
          ['label' => 'Original Value', 'value' => $numberLabel($metadata->original_value)],
          ['label' => 'Original Value Range', 'value' => $valueOrNull($metadata->original_value_range)],
          ['label' => 'Original Unit', 'value' => $valueOrNull($metadata->original_unit)],
          ['label' => 'Assessment Qualifier', 'value' => $valueOrNull($metadata->assessment_qualifier)],
          ['label' => 'Assessment Value', 'value' => $numberLabel($metadata->assessment_value)],
          ['label' => 'Assessment Unit', 'value' => $valueOrNull($metadata->assessment_unit)],
          ['label' => 'Duration (days)', 'value' => $numberLabel($metadata->duration_days)],
          ['label' => 'Exposure Concentration', 'value' => $numberLabel($metadata->exposure_concentration)],
          ['label' => 'pH', 'value' => $numberLabel($metadata->ph)],
          ['label' => 'Temperature (C)', 'value' => $numberLabel($metadata->temperature_c)],
          ['label' => 'Total Organic Carbon', 'value' => $numberLabel($metadata->total_organic_carbon)],
          ['label' => 'Applicability Domain', 'value' => $valueOrNull($metadata->applicability_domain)],
          ['label' => 'Applicability Domain Score', 'value' => $numberLabel($metadata->applicability_domain_score)],
          ['label' => 'Reliability Score', 'value' => $numberLabel($metadata->reliability_score)],
          ['label' => 'Reliability Score System', 'value' => $valueOrNull($metadata->reliability_score_system)],
          ['label' => 'Reliability Rational', 'value' => $valueOrNull($metadata->reliability_rational)],
          ['label' => 'Institution of Reliability Score', 'value' => $valueOrNull($metadata->institution_of_reliability_score)],
          ['label' => 'Regulatory Context', 'value' => $valueOrNull($metadata->regulatory_context)],
          ['label' => 'Institution Original Classification', 'value' => $valueOrNull($metadata->institution_original_classification)],
          ['label' => 'General Comment', 'value' => $valueOrNull($metadata->general_comment)],
        ],
      ];

      $visibleSections = collect($detailSections)
        ->map(function ($rows) {
          return array_values(array_filter($rows, static function ($row) {
            return array_key_exists('value', $row) && ! is_null($row['value']);
          }));
        })
        ->filter(static fn ($rows) => count($rows) > 0);
    @endphp

    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
      <h1 class="text-2xl font-bold text-gray-900 mb-4">Hazards Derivation Metadata</h1>

      <div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
        <span class="px-2 py-1 rounded bg-blue-100 text-blue-800">{{ $bucketLabel }}</span>
        <span class="px-2 py-1 rounded bg-emerald-100 text-emerald-800">{{ $modeLabel }}</span>
        <span class="px-2 py-1 rounded bg-slate-100 text-slate-800">{{ $selectionTypeLabel }}</span>
      </div>

      <div class="mb-6 text-sm text-gray-600">
        <span class="font-medium">{{ $substance->substance_name ?? 'Substance' }}</span>
        |
        CAS: {{ $substance->cas_no ?? 'N/A' }}
        |
        NORMAN SusDat ID: {{ $selection->susdat_substance_id ? 'NS' . str_pad((string) $selection->susdat_substance_id, 8, '0', STR_PAD_LEFT) : 'N/A' }}
        |
        {{ $substance->inchikey ?? 'N/A' }}
      </div>

      @if ($visibleSections->isNotEmpty())
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 text-sm">
          @foreach ($visibleSections as $sectionTitle => $rows)
            <div class="space-y-2">
              <h2 class="text-base font-semibold text-gray-800 border-b pb-1">{{ $sectionTitle }}</h2>
              @foreach ($rows as $row)
                <div class="leading-relaxed">
                  <span class="font-semibold">{{ $row['label'] }}:</span>
                  <span class="break-words">{{ $row['value'] }}</span>
                </div>
              @endforeach
            </div>
          @endforeach
        </div>
      @else
        <div class="text-sm text-gray-600">
          No metadata details are available for this selection.
        </div>
      @endif

      <div class="mt-6">
        <a href="{{ route('hazards.derivation.index', ['susdatSubstanceId' => $selection->susdat_substance_id]) }}" class="btn-submit">Back</a>
      </div>
    </div>
  </div>
</x-app-layout>
