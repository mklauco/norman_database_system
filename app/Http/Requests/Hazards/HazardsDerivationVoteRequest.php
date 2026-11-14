<?php

declare(strict_types=1);

namespace App\Http\Requests\Hazards;

use App\Models\Hazards\ComptoxSubstanceData;
use App\Services\Hazards\HazardsDerivationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class HazardsDerivationVoteRequest extends FormRequest
{
    private const SHORT = ['nullable', 'string', 'max:1000'];

    private const LONG = ['nullable', 'string', 'max:10000'];

    private const NUMBER = ['nullable', 'numeric', 'between:-1000000000000,1000000000000'];

    /**
     * Fields the vote form shows read-only. The server derives them itself and ignores any
     * submitted value: editor and date from the session, substance fields and criterion from
     * the substance and the chosen row, the DOI and the automated expert vote from the row.
     *
     * @var list<string>
     */
    public const SERVER_DERIVED_FIELDS = [
        'meta_editor',
        'meta_date',
        'meta_doi',
        'meta_substance_name',
        'meta_cas',
        'meta_hazard_criterion',
        'meta_automated_expert_vote',
    ];

    private ?ComptoxSubstanceData $dataRow = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'susdat_substance_id' => ['required', 'integer', 'exists:susdat_substances,id'],
            'bucket' => ['required', 'string', Rule::in(HazardsDerivationService::BUCKETS)],
            'hazards_substance_data_id' => [
                'required',
                'integer',
                Rule::exists('hazards_comptox_substance_data', 'id')->whereNull('withdrawn_at'),
            ],
            'meta_pbmt_classification_code' => self::SHORT,
            'meta_reference_type' => self::SHORT,
            'meta_title' => self::LONG,
            'meta_authors' => self::LONG,
            'meta_year' => ['nullable', 'integer', 'between:1000,2200'],
            'meta_bibliographic_source' => self::LONG,
            'meta_test_type' => self::SHORT,
            'meta_glp' => self::SHORT,
            'meta_standard_test' => self::SHORT,
            'meta_radio_labeled' => self::SHORT,
            'meta_standard_qualifier' => self::SHORT,
            'meta_standard_used' => self::SHORT,
            'meta_test_matrix' => self::SHORT,
            'meta_test_species' => self::SHORT,
            'meta_duration_days' => self::NUMBER,
            'meta_exposure_concentration' => self::NUMBER,
            'meta_ph' => self::NUMBER,
            'meta_temperature_c' => self::NUMBER,
            'meta_total_organic_carbon' => self::NUMBER,
            'meta_original_parameter_name' => self::SHORT,
            'meta_original_qualifier' => self::SHORT,
            'meta_original_value' => self::NUMBER,
            'meta_original_value_range' => self::SHORT,
            'meta_original_unit' => self::SHORT,
            'meta_assessment_parameter_name' => self::SHORT,
            'meta_assessment_qualifier' => self::SHORT,
            'meta_assessment_value' => self::NUMBER,
            'meta_assessment_unit' => self::SHORT,
            'meta_original_classification' => self::LONG,
            'meta_classification_score' => self::NUMBER,
            'meta_general_comment' => self::LONG,
            'meta_applicability_domain' => self::SHORT,
            'meta_applicability_domain_score' => self::NUMBER,
            'meta_reliability_score' => self::NUMBER,
            'meta_reliability_system' => self::SHORT,
            'meta_reliability_rational' => self::LONG,
            'meta_reliability_institution' => self::SHORT,
            'meta_regulatory_context' => self::LONG,
            'meta_institution_original_classification' => self::SHORT,
            'meta_norman_classification' => self::SHORT,
            'meta_norman_vote' => ['nullable', 'integer', 'in:1,2,3'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hazards_substance_data_id.exists' => 'This record was withdrawn from the source and can no longer be voted for.',
            'bucket.in' => 'Unknown derivation bucket.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach (array_keys(self::NUMERIC_FIELD_LOOKUP) as $key) {
            $value = $this->input($key);
            if (is_string($value)) {
                $normalized[$key] = str_replace(',', '.', trim($value));
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    private const NUMERIC_FIELD_LOOKUP = [
        'meta_duration_days' => true,
        'meta_exposure_concentration' => true,
        'meta_ph' => true,
        'meta_temperature_c' => true,
        'meta_total_organic_carbon' => true,
        'meta_original_value' => true,
        'meta_assessment_value' => true,
        'meta_classification_score' => true,
        'meta_applicability_domain_score' => true,
        'meta_reliability_score' => true,
    ];

    /**
     * The chosen row must belong to the substance and resolve to the submitted bucket.
     *
     * @return list<\Closure>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $row = ComptoxSubstanceData::query()->find((int) $this->input('hazards_substance_data_id'));
                if (! $row || (int) $row->susdat_substance_id !== (int) $this->input('susdat_substance_id')) {
                    $validator->errors()->add('hazards_substance_data_id', 'The selected record does not belong to this substance.');

                    return;
                }

                if (app(HazardsDerivationService::class)->resolveBucket($row) !== $this->input('bucket')) {
                    $validator->errors()->add('bucket', 'The selected record does not belong to this bucket.');

                    return;
                }

                $this->dataRow = $row;
            },
        ];
    }

    public function dataRow(): ComptoxSubstanceData
    {
        return $this->dataRow ?? throw new \LogicException('The vote request was not validated.');
    }

    /**
     * Validated metadata overrides for the snapshot; server-derived fields never appear here.
     *
     * @return array<string, mixed>
     */
    public function metadataOverrides(): array
    {
        $overrides = array_filter(
            $this->validated(),
            static fn (string $key): bool => str_starts_with($key, 'meta_'),
            ARRAY_FILTER_USE_KEY
        );

        return array_diff_key($overrides, array_flip(self::SERVER_DERIVED_FIELDS));
    }

    public function editorName(): string
    {
        $user = Auth::user();
        $name = trim((string) ($user?->formatted_name ?? ''));

        return $name !== '' ? $name : trim((string) ($user?->username ?? 'N/A'));
    }
}
