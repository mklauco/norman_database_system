<?php

declare(strict_types=1);

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpodatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sampling_date_year' => $this->sampling_date_year,
            'formatted_sampling_date' => $this->formatted_sampling_date,
            'concentration_value' => $this->concentration_value,
            'concentration_unit' => $this->when(
                $this->relationLoaded('matrix') && $this->relationLoaded('minor'),
                fn (): ?string => $this->legacyUnit(),
            ),
            'concentration_indicator' => $this->whenLoaded('concentrationIndicator', fn () => [
                'id' => $this->concentrationIndicator->id,
                'name' => $this->concentrationIndicator->name,
                'symbol' => $this->concentrationIndicator->symbol ?? null,
            ]),
            'substance' => $this->whenLoaded('substance', fn () => [
                'norman_id' => $this->substance->prefixed_code,
                'name' => $this->substance->name,
                'cas_number' => $this->substance->cas_number,
                'stdinchikey' => $this->substance->stdinchikey,
            ]),
            'station' => $this->whenLoaded('station', fn () => [
                'id' => $this->station->id,
                'name' => $this->station->name ?? null,
                'code' => $this->station->code ?? null,
                'latitude' => $this->station->latitude ?? null,
                'longitude' => $this->station->longitude ?? null,
                'country' => $this->station->countryRelation ? [
                    'code' => $this->station->countryRelation->code,
                    'name' => $this->station->countryRelation->name,
                ] : null,
            ]),
            'matrix' => $this->whenLoaded('matrix', fn () => [
                'id' => $this->matrix->id,
                'name' => $this->matrix->name,
            ]),
            'matrix_data' => $this->when(isset($this->matrix_data), fn () => $this->matrix_data),
            'analytical_method' => $this->whenLoaded('analyticalMethod', fn () => $this->formatAnalyticalMethod()),
            'data_source' => $this->whenLoaded('dataSource', fn () => $this->formatDataSource()),
            'minor' => $this->whenLoaded('minor', fn () => $this->formatMinor()),
        ];
    }

    private function formatAnalyticalMethod(): ?array
    {
        if (! $this->analyticalMethod) {
            return null;
        }

        $method = $this->analyticalMethod;

        return array_filter([
            'id' => $method->id,
            'rating' => $method->rating,
            'lod' => $method->lod,
            'loq' => $method->loq,
            'recovery' => $method->recovery,
            'rsd' => $method->rsd,
            'uncertainty' => $method->uncertainty,
            'analytical_method' => $method->analytical_method ?? null,
            'coverage_factor_name' => $method->coverage_factor_name ?? null,
            'sample_preparation_method_name' => $method->sample_preparation_method_name ?? null,
            'standardised_method_name' => $method->standardised_method_name ?? null,
            'validated_method_name' => $method->validated_method_name ?? null,
            'corrected_recovery_name' => $method->corrected_recovery_name ?? null,
            'field_blank_name' => $method->field_blank_name ?? null,
            'iso_name' => $method->iso_name ?? null,
            'given_analyte_name' => $method->given_analyte_name ?? null,
            'laboratory_participate_name' => $method->laboratory_participate_name ?? null,
            'summary_performance_name' => $method->summary_performance_name ?? null,
            'control_charts_name' => $method->control_charts_name ?? null,
            'internal_standards_name' => $method->internal_standards_name ?? null,
            'authority_name' => $method->authority_name ?? null,
            'sampling_method_name' => $method->sampling_method_name ?? null,
            'sampling_collection_device_name' => $method->sampling_collection_device_name ?? null,
        ], fn ($value) => $value !== null);
    }

    private function formatDataSource(): ?array
    {
        if (! $this->dataSource) {
            return null;
        }

        $source = $this->dataSource;

        return array_filter([
            'id' => $source->id,
            'reference' => $source->reference ?? null,
            'type_data_source_name' => $source->type_data_source_name ?? null,
            'type_monitoring_name' => $source->type_monitoring_name ?? null,
            'data_accessibility_name' => $source->data_accessibility_name ?? null,
            'organisation_name' => $source->organisation_name ?? null,
            'laboratory_name' => $source->laboratory_name ?? null,
            'laboratory_name_2' => $source->laboratory_name_2 ?? null,
        ], fn ($value) => $value !== null);
    }

    private function formatMinor(): ?array
    {
        if (! $this->minor) {
            return null;
        }

        $minor = $this->minor;

        return array_filter([
            'sampling_date' => $this->legacySamplingDate(),
            'sampling_time' => $minor->sampling_time ?? null,
            'sample_id' => $minor->sample_id ?? null,
            'replicate' => $minor->replicate ?? null,
            'sample_depth' => $minor->sample_depth ?? null,
            'sample_depth_unit' => $minor->sample_depth_unit ?? null,
        ], fn ($value) => $value !== null);
    }
}
