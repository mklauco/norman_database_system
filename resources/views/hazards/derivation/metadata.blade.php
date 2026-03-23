<x-app-layout>
  <x-slot name="header">
    @include('hazards.header')
  </x-slot>

  @php
    $formatDate = static function ($value) {
      if (empty($value)) {
        return 'N/A';
      }

      try {
        return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d H:i');
      } catch (\Throwable $e) {
        return (string) $value;
      }
    };

    $formatNumber = static function ($value) {
      if ($value === null || $value === '') {
        return 'N/A';
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

      $formatted = number_format($numericValue, 4, '.', '');

      return rtrim(rtrim($formatted, '0'), '.');
    };

    $sections = [
      'Source and Reference' => [
        'Selection ID' => $selection->id,
        'Bucket' => $selection->bucket,
        'Selection type' => ucfirst((string) $selection->kind),
        'Data source' => $metadata->data_source,
        'Editor' => $metadata->editor,
        'Record date' => $formatDate($metadata->record_date),
        'Reference type' => $metadata->reference_type,
        'Title' => $metadata->title,
        'Authors' => $metadata->authors,
        'Year' => $metadata->year,
        'Bibliographic source' => $metadata->bibliographic_source,
        'DOI' => $metadata->hazards_file_doi,
      ],
      'Substance and Test' => [
        'Substance name' => $metadata->substance_name,
        'CAS number' => $metadata->cas_number,
        'Test type' => $metadata->test_type,
        'Performed under GLP' => $metadata->performed_under_glp,
        'Standard test' => $metadata->standard_test,
        'Radio labeled substance' => $metadata->radio_labeled_substance,
        'Standard qualifier' => $metadata->standard_qualifier,
        'Standard used' => $metadata->standard_used,
        'Test matrix' => $metadata->test_matrix,
        'Test species' => $metadata->test_species,
        'Duration days' => $formatNumber($metadata->duration_days),
        'Exposure concentration' => $formatNumber($metadata->exposure_concentration),
        'pH' => $formatNumber($metadata->ph),
        'Temperature C' => $formatNumber($metadata->temperature_c),
        'Total organic carbon' => $formatNumber($metadata->total_organic_carbon),
      ],
      'Original and Assessment' => [
        'Original parameter name' => $metadata->original_parameter_name,
        'Original qualifier' => $metadata->original_qualifier,
        'Original value' => $formatNumber($metadata->original_value),
        'Original value range' => $metadata->original_value_range,
        'Original unit' => $metadata->original_unit,
        'Assessment parameter name' => $metadata->assessment_parameter_name,
        'Assessment qualifier' => $metadata->assessment_qualifier,
        'Assessment value' => $formatNumber($metadata->assessment_value),
        'Assessment unit' => $metadata->assessment_unit,
      ],
      'Classification and Quality' => [
        'Hazard criterion' => $metadata->hazard_criterion,
        'Original classification' => $metadata->original_classification,
        'Classification score' => $formatNumber($metadata->classification_score),
        'NORMAN classification' => $metadata->norman_classification,
        'NORMAN vote' => $metadata->norman_vote,
        'Automated expert vote' => $metadata->automated_expert_vote,
        'Applicability domain' => $metadata->applicability_domain,
        'Applicability domain score' => $formatNumber($metadata->applicability_domain_score),
        'Reliability score' => $formatNumber($metadata->reliability_score),
        'Reliability score system' => $metadata->reliability_score_system,
        'Reliability rational' => $metadata->reliability_rational,
        'Institution of reliability score' => $metadata->institution_of_reliability_score,
        'Regulatory context' => $metadata->regulatory_context,
        'Institution original classification' => $metadata->institution_original_classification,
        'General comment' => $metadata->general_comment,
      ],
    ];
  @endphp

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900 space-y-6">
          <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Hazards Derivation Metadata</h1>
              <div class="text-sm text-gray-600 mt-2">
                <span class="font-medium">{{ $substance->substance_name ?? 'Substance' }}</span>
                |
                CAS: {{ $substance->cas_no ?? 'N/A' }}
                |
                NORMAN SusDat ID: {{ $selection->susdat_substance_id ? 'NS' . str_pad((string) $selection->susdat_substance_id, 8, '0', STR_PAD_LEFT) : 'N/A' }}
                |
                {{ $substance->inchikey ?? 'N/A' }}
              </div>
            </div>

            <div class="flex gap-2">
              <a href="{{ route('hazards.derivation.index', ['susdatSubstanceId' => $selection->susdat_substance_id]) }}" class="btn-clear">Back to Derivation</a>
            </div>
          </div>

          @foreach ($sections as $sectionTitle => $fields)
            <div class="border border-gray-200 rounded-lg overflow-hidden">
              <div class="bg-slate-100 px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">{{ $sectionTitle }}</h2>
              </div>

              <div class="overflow-x-auto">
                <table class="table-standard text-sm">
                  <tbody>
                    @foreach ($fields as $label => $value)
                      <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                        <th class="p-2 text-left font-semibold w-72">{{ $label }}</th>
                        <td class="p-2">{{ filled($value) ? $value : 'N/A' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
