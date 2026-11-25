<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\EmpodatSearchRequest;
use App\Http\Resources\v1\EmpodatResource;
use App\Models\Empodat\EmpodatMain;
use App\Models\List\Country;
use App\Models\List\DataSourceLaboratory;
use App\Models\Susdat\Substance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group EMPODAT
 *
 * APIs for retrieving environmental monitoring data from the EMPODAT database.
 * All endpoints require authentication via Sanctum token.
 */
class EmpodatController extends Controller
{
    /**
     * Search EMPODAT by substance or country
     *
     * Retrieve EMPODAT records by searching for a substance (NORMAN ID or InChIKey)
     * or by country code. Results are paginated due to potentially large datasets.
     *
     * @authenticated
     *
     * @urlParam search_type string required The type of search: "substance" or "country". Example: substance
     * @urlParam search_value string required The search value. For substance: NS code (NS00000214) or InChIKey. For country: 2-letter ISO code (SK, DE). Example: NS00000214
     *
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam per_page integer Number of records per page (max 1000, default 100). Example: 100
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "sampling_date_year": 2020,
     *       "concentration_value": 0.0025,
     *       "substance": {"norman_id": "NS00004453", "name": "Sulfaclozine"},
     *       "station": {"name": "Station A", "country": {"code": "SK", "name": "Slovakia"}},
     *       "matrix": {"name": "Surface Water"}
     *     }
     *   ],
     *   "links": {"first": "...", "last": "...", "prev": null, "next": "..."},
     *   "meta": {"current_page": 1, "last_page": 50, "per_page": 100, "total": 5000}
     * }
     * @response 404 scenario="Not Found" {
     *   "success": false,
     *   "message": "Substance not found",
     *   "search_type": "substance",
     *   "search_value": "NS99999999"
     * }
     * @response 422 scenario="Invalid Format" {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {"search_value": ["Substance must be a valid NORMAN ID or InChIKey."]}
     * }
     */
    public function search(EmpodatSearchRequest $request): AnonymousResourceCollection|JsonResponse
    {
        $searchType = $request->getSearchType();
        $searchValue = $request->getSearchValue();
        $perPage = $request->getPerPage();

        if ($searchType === 'substance') {
            return $this->searchBySubstance($request, $searchValue, $perPage);
        }

        return $this->searchByCountry($searchValue, $perPage);
    }

    private function searchBySubstance(EmpodatSearchRequest $request, string $searchValue, int $perPage): AnonymousResourceCollection|JsonResponse
    {
        // Find substance by code or inchikey
        $substanceQuery = Substance::query();

        if ($request->isSubstanceNsCode()) {
            $code = $request->getSubstanceCodeWithoutPrefix();
            $substanceQuery->where('code', $code);
        } else {
            $substanceQuery->where('stdinchikey', $searchValue);
        }

        $substance = $substanceQuery->first();

        if (! $substance) {
            return response()->json([
                'success' => false,
                'message' => 'Substance not found',
                'search_type' => 'substance',
                'search_value' => $searchValue,
            ], 404);
        }

        $empodats = EmpodatMain::query()
            ->with([
                'concentrationIndicator',
                'station.countryRelation',
                'substance',
                'matrix',
                'analyticalMethod',
                'dataSource.groups.organisation',
                'dataSource.groups.laboratory1.country',
                'dataSource.groups.laboratory2.country',
                'minor',
            ])
            ->where('substance_id', $substance->id)
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        // Process each record with field remapping
        $empodats->getCollection()->transform(function ($empodat) {
            $this->processEmpodatRecord($empodat);

            return $empodat;
        });

        return EmpodatResource::collection($empodats);
    }

    private function searchByCountry(string $countryCode, int $perPage): AnonymousResourceCollection|JsonResponse
    {
        $country = Country::where('code', strtoupper($countryCode))->first();

        if (! $country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found',
                'search_type' => 'country',
                'search_value' => $countryCode,
            ], 404);
        }

        $empodats = EmpodatMain::query()
            ->with([
                'concentrationIndicator',
                'station.countryRelation',
                'substance',
                'matrix',
                'analyticalMethod',
                'dataSource.groups.organisation',
                'dataSource.groups.laboratory1.country',
                'dataSource.groups.laboratory2.country',
                'minor',
            ])
            ->join('empodat_stations', 'empodat_main.station_id', '=', 'empodat_stations.id')
            ->where('empodat_stations.country_id', $country->id)
            ->select('empodat_main.*')
            ->orderBy('empodat_main.id', 'asc')
            ->paginate($perPage);

        // Process each record with field remapping
        $empodats->getCollection()->transform(function ($empodat) {
            $this->processEmpodatRecord($empodat);

            return $empodat;
        });

        return EmpodatResource::collection($empodats);
    }

    /**
     * Process a single empodat record - load matrix data and remap fields
     */
    private function processEmpodatRecord(EmpodatMain $empodat): void
    {
        // Load matrix metadata
        if ($empodat->matrix && $empodat->matrix->empodat_matrix_link) {
            $matrixLink = $empodat->matrix->empodat_matrix_link;
            $empodat->matrix_data = $this->loadMatrixMetadataByLink($empodat, $matrixLink);
        }

        // Remap analytical method and data source fields
        $this->remapFieldsOptimized($empodat);
    }

    /**
     * Load matrix metadata based on empodat_matrix_link value
     */
    private function loadMatrixMetadataByLink(EmpodatMain $empodat, string $matrixLink): ?array
    {
        $matrixRelationshipMap = [
            'air' => 'matrixAir',
            'biota' => 'matrixBiota',
            'sediments' => 'matrixSediments',
            'sewage_sludge' => 'matrixSewageSludge',
            'soil' => 'matrixSoil',
            'suspended_matter' => 'matrixSuspendedMatter',
            'water_surface' => 'matrixWaterSurface',
            'water_ground' => 'matrixWaterGround',
            'water_waste' => 'matrixWaterWaste',
        ];

        $normalizedLink = strtolower(trim($matrixLink));

        if (str_starts_with($normalizedLink, 'empodat_matrix_')) {
            $normalizedLink = substr($normalizedLink, strlen('empodat_matrix_'));
        }

        if (! isset($matrixRelationshipMap[$normalizedLink])) {
            return null;
        }

        $relationshipName = $matrixRelationshipMap[$normalizedLink];
        $empodat->load($relationshipName);

        $matrixModel = $empodat->{$relationshipName};

        if (! $matrixModel) {
            return null;
        }

        $attributes = $matrixModel->getAttributes();
        $metaData = [];
        foreach ($attributes as $key => $value) {
            if ($key === 'id' || $value === null || $value === '' || $value === 0 || $value === '0') {
                continue;
            }
            $metaData[$key] = $value;
        }

        return [
            'type' => $normalizedLink,
            'meta_data' => ! empty($metaData) ? $metaData : null,
        ];
    }

    /**
     * Remap analytical method and data source fields
     */
    private function remapFieldsOptimized(EmpodatMain $empodat): void
    {
        $lookupsByModel = [];

        // Collect analytical method lookups
        if ($empodat->analyticalMethod) {
            $fieldsMap = $this->fieldMapAnalyticalMethods();
            foreach ($fieldsMap as $field => $meta) {
                $fieldId = data_get($empodat->analyticalMethod, $field);
                if (! empty($fieldId)) {
                    $modelClass = $meta['model'];
                    if (! isset($lookupsByModel[$modelClass])) {
                        $lookupsByModel[$modelClass] = ['ids' => [], 'fields' => []];
                    }
                    $lookupsByModel[$modelClass]['ids'][] = $fieldId;
                    $lookupsByModel[$modelClass]['fields'][] = [
                        'source' => 'analyticalMethod',
                        'field' => $field,
                        'targetAttribute' => $meta['targetAttribute'],
                        'id' => $fieldId,
                    ];
                }
            }
        }

        // Collect data source lookups
        if ($empodat->dataSource) {
            $fieldsMap = $this->fieldMapEmpodatDataSources();
            foreach ($fieldsMap as $field => $meta) {
                if (str_contains($field, 'laboratory')) {
                    continue;
                }
                $fieldId = data_get($empodat->dataSource, $field);
                if (! empty($fieldId)) {
                    $modelClass = $meta['model'];
                    if (! isset($lookupsByModel[$modelClass])) {
                        $lookupsByModel[$modelClass] = ['ids' => [], 'fields' => []];
                    }
                    $lookupsByModel[$modelClass]['ids'][] = $fieldId;
                    $lookupsByModel[$modelClass]['fields'][] = [
                        'source' => 'dataSource',
                        'field' => $field,
                        'targetAttribute' => $meta['targetAttribute'],
                        'id' => $fieldId,
                    ];
                }
            }
        }

        // Execute batch queries
        foreach ($lookupsByModel as $modelClass => $data) {
            $uniqueIds = array_unique($data['ids']);
            $names = $modelClass::whereIn('id', $uniqueIds)->pluck('name', 'id');

            foreach ($data['fields'] as $fieldInfo) {
                $name = $names[$fieldInfo['id']] ?? null;
                if ($name) {
                    $source = $fieldInfo['source'] === 'analyticalMethod' ? $empodat->analyticalMethod : $empodat->dataSource;
                    data_set($source, $fieldInfo['targetAttribute'], $name);
                    data_set($source, $fieldInfo['field'], null);
                }
            }
        }

        // Handle laboratory fields
        if ($empodat->dataSource) {
            $labFields = ['laboratory1_id', 'laboratory2_id'];
            $labIds = [];
            foreach ($labFields as $field) {
                $fieldId = data_get($empodat->dataSource, $field);
                if (! empty($fieldId)) {
                    $labIds[$field] = $fieldId;
                }
            }

            if (! empty($labIds)) {
                $labs = DataSourceLaboratory::with('country')->whereIn('id', array_values($labIds))->get()->keyBy('id');
                $targetMap = ['laboratory1_id' => 'laboratory_name', 'laboratory2_id' => 'laboratory_name_2'];
                foreach ($labIds as $field => $id) {
                    $lab = $labs[$id] ?? null;
                    if ($lab) {
                        data_set($empodat->dataSource, $targetMap[$field], $lab->full_name);
                        data_set($empodat->dataSource, $field, null);
                    }
                }
            }
        }

        $this->fillNamesFromGroups($empodat);

        if ($empodat->analyticalMethod) {
            $this->remapRatingField($empodat->analyticalMethod);
        }
    }

    /**
     * Fill organisation and laboratory names from the data source groups
     * (eager loaded) when the source columns themselves are empty.
     */
    private function fillNamesFromGroups(EmpodatMain $empodat): void
    {
        $source = $empodat->dataSource;

        if (! $source || ! $source->relationLoaded('groups') || $source->groups->isEmpty()) {
            return;
        }

        $names = [
            'organisation_name' => $source->groups->map(fn ($group) => $group->organisation?->name),
            'laboratory_name' => $source->groups->map(fn ($group) => $group->laboratory1?->full_name),
            'laboratory_name_2' => $source->groups->map(fn ($group) => $group->laboratory2?->full_name),
        ];

        foreach ($names as $attribute => $values) {
            if (! empty($source->{$attribute})) {
                continue;
            }

            $joined = $values->filter()->unique()->implode('; ');

            if ($joined !== '') {
                $source->{$attribute} = $joined;
            }
        }

        $source->unsetRelation('groups');
    }

    private function remapRatingField($analyticalMethod): void
    {
        if (! $analyticalMethod || ! isset($analyticalMethod->rating)) {
            return;
        }

        $rating = $analyticalMethod->rating;

        $ratingRanges = [
            ['min' => 68, 'max' => 100, 'description' => 'Adequately supported by quality-related information'],
            ['min' => 52, 'max' => 68, 'description' => 'Supported by limited quality-related information'],
            ['min' => 22, 'max' => 52, 'description' => 'Minimal quality-related information'],
            ['min' => 0, 'max' => 22, 'description' => 'Not supported by quality-related information'],
        ];

        foreach ($ratingRanges as $range) {
            if ($rating >= $range['min'] && $rating < $range['max']) {
                $analyticalMethod->rating = $rating.' - '.$range['description'];
                break;
            }
        }
    }

    private function fieldMapAnalyticalMethods(): array
    {
        return [
            'coverage_factor_id' => ['model' => \App\Models\List\CoverageFactor::class, 'targetAttribute' => 'coverage_factor_name'],
            'sample_preparation_method_id' => ['model' => \App\Models\List\SamplePreparationMethod::class, 'targetAttribute' => 'sample_preparation_method_name'],
            'analytical_method_id' => ['model' => \App\Models\List\AnalyticalMethod::class, 'targetAttribute' => 'analytical_method'],
            'standardised_method_id' => ['model' => \App\Models\List\StandardisedMethod::class, 'targetAttribute' => 'standardised_method_name'],
            'validated_method_id' => ['model' => \App\Models\List\ValidatedMethod::class, 'targetAttribute' => 'validated_method_name'],
            'corrected_recovery_id' => ['model' => \App\Models\List\CorrectedRecovery::class, 'targetAttribute' => 'corrected_recovery_name'],
            'field_blank_id' => ['model' => \App\Models\List\FieldBlank::class, 'targetAttribute' => 'field_blank_name'],
            'iso_id' => ['model' => \App\Models\List\Iso::class, 'targetAttribute' => 'iso_name'],
            'given_analyte_id' => ['model' => \App\Models\List\GivenAnalyte::class, 'targetAttribute' => 'given_analyte_name'],
            'laboratory_participate_id' => ['model' => \App\Models\List\LaboratoryParticipate::class, 'targetAttribute' => 'laboratory_participate_name'],
            'summary_performance_id' => ['model' => \App\Models\List\SummaryPerformance::class, 'targetAttribute' => 'summary_performance_name'],
            'control_charts_id' => ['model' => \App\Models\List\ControlChart::class, 'targetAttribute' => 'control_charts_name'],
            'internal_standards_id' => ['model' => \App\Models\List\InternalStandard::class, 'targetAttribute' => 'internal_standards_name'],
            'authority_id' => ['model' => \App\Models\List\Authority::class, 'targetAttribute' => 'authority_name'],
            'sampling_method_id' => ['model' => \App\Models\List\SamplingMethod::class, 'targetAttribute' => 'sampling_method_name'],
            'sampling_collection_device_id' => ['model' => \App\Models\List\SamplingCollectionDevice::class, 'targetAttribute' => 'sampling_collection_device_name'],
        ];
    }

    private function fieldMapEmpodatDataSources(): array
    {
        return [
            'type_data_source_id' => ['model' => \App\Models\List\TypeDataSource::class, 'targetAttribute' => 'type_data_source_name'],
            'type_monitoring_id' => ['model' => \App\Models\List\TypeMonitoring::class, 'targetAttribute' => 'type_monitoring_name'],
            'data_accessibility_id' => ['model' => \App\Models\List\DataAccesibility::class, 'targetAttribute' => 'data_accessibility_name'],
            'organisation_id' => ['model' => \App\Models\List\DataSourceOrganisation::class, 'targetAttribute' => 'organisation_name'],
            'laboratory1_id' => ['model' => DataSourceLaboratory::class, 'targetAttribute' => 'laboratory_name'],
            'laboratory2_id' => ['model' => DataSourceLaboratory::class, 'targetAttribute' => 'laboratory_name_2'],
        ];
    }
}
