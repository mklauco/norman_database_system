<?php

namespace App\Http\Controllers\Empodat;

use App\Http\Controllers\Controller;
use App\Jobs\Empodat\EmpodatCsvExportJob;
use App\Models\Backend\File;
use App\Models\Backend\QueryLog;
use App\Models\DatabaseEntity;
use App\Models\Empodat\EmpodatMain;
use App\Models\Empodat\SearchCountries;
use App\Models\Empodat\SearchMatrix;
use App\Models\List\AnalyticalMethod;
use App\Models\List\AnalyticalMethod as AnalyticalMethodList;
use App\Models\List\Authority;
use App\Models\List\ConcentrationIndicator;
use App\Models\List\ControlChart;
use App\Models\List\CorrectedRecovery;
use App\Models\List\Country;
use App\Models\List\CoverageFactor;
use App\Models\List\DataAccesibility;
use App\Models\List\DataSourceLaboratory;
use App\Models\List\DataSourceOrganisation;
use App\Models\List\FieldBlank;
use App\Models\List\GivenAnalyte;
use App\Models\List\InternalStandard;
use App\Models\List\Iso;
use App\Models\List\LaboratoryParticipate;
use App\Models\List\Matrix;
use App\Models\List\QualityEmpodatAnalyticalMethods;
use App\Models\List\SamplePreparationMethod;
use App\Models\List\SamplingCollectionDevice;
use App\Models\List\SamplingMethod;
use App\Models\List\StandardisedMethod;
use App\Models\List\SummaryPerformance;
use App\Models\List\TypeDataSource;
use App\Models\List\TypeMonitoring;
use App\Models\List\ValidatedMethod;
use App\Models\SLE\SuspectListExchangeSource;
use App\Models\Susdat\Category;
use App\Models\Susdat\Substance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmpodatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $empodat = EmpodatMain::query()
          // Apply user permissions filter
            ->byUserPermissions()

          // Eager load relationships (as needed)
            ->with('concentrationIndicator')
            ->with('station.countryRelation')
            ->with('substance')
            ->with('matrix')
            ->with('analyticalMethod')
            ->with('dataSource')
            ->with('minor')

          // Finally, constrain it to a single empodat_main.id
            ->where('empodat_main.id', $id)

          // Execute
            ->first();  // or ->get(), depending on whether you expect one record or multiple

        // No record visible to this user (or doesn't exist) — short-circuit
        // so the downstream `$empodat->...` assignments don't NPE.
        if ($empodat === null) {
            return response()->json(null, 404);
        }

        // Load the appropriate matrix metadata based on list_matrices.empodat_matrix_link
        $matrixMetadata = null;
        if ($empodat->matrix && $empodat->matrix->empodat_matrix_link) {
            $matrixLink = $empodat->matrix->empodat_matrix_link;
            $matrixMetadata = $this->loadMatrixMetadataByLink($empodat, $matrixLink);
        }

        // ==============================
        // REMAP ANALYTICAL METHOD & DATA SOURCE FIELDS (batch optimized)
        // ==============================

        $this->remapFieldsOptimized($empodat);

        // ==============================
        // END REMAP FIELDS
        // ==============================

        // ==============================
        // CONSOLIDATE MATRIX DATA
        // ==============================

        // Resolve raw FK integer ids in matrix metadata against PG list_* tables
        // (data_kingdom -> list_kingdoms, data_phylum -> list_phyla, etc.).
        // Legacy renders these as human-readable names ("Kingdom: Animalia");
        // this enriches the response so the modal does the same. Imported via
        // Phase 6 of the legacy migration; see LEGACY_MIGRATION_PLAN.md.
        if ($matrixMetadata !== null) {
            $matrixMetadata['meta_data'] = $this->enrichLookupIds(
                $matrixMetadata['meta_data'] ?? [],
                $this->lookupConfigForMatrix($matrixMetadata['type'] ?? ''),
            );
        }

        // Set matrix_data from the loaded matrix metadata
        $empodat->matrix_data = $matrixMetadata;

        // Same enrichment for empodat_minor: replace cryptic dpc_id/dcod_id/
        // etc. integer ids with the corresponding labelled name. Exposed as
        // `additional_details` so the public API resource (EmpodatResource)
        // and CSV export (EmpodatCsvExportJob) — which both read `minor`
        // directly — see unchanged raw rows.
        $empodat->additional_details = $empodat->minor
            ? $this->enrichLookupIds(
                $this->stripMinorEnvelopeAttributes($empodat->minor->getAttributes()),
                $this->lookupConfigForMinor(),
            )
            : [];

        // ==============================
        // END CONSOLIDATE MATRIX DATA
        // ==============================

        return response()->json($empodat);
    }

    /**
     * Drop the columns from `empodat_minor->getAttributes()` that the modal
     * shouldn't render in "Additional Record Details" (the PK / timestamps
     * the modal would otherwise list as cryptic rows).
     *
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed>
     */
    private function stripMinorEnvelopeAttributes(array $attrs): array
    {
        unset($attrs['id'], $attrs['created_at'], $attrs['updated_at'], $attrs['empodat_main_id']);

        return $attrs;
    }

    /**
     * Walk a flat (column => value) array. For each key configured as a
     * lookup FK id:
     *   - if value is zero/null/empty: drop the field
     *   - if the configured list_* table is null (e.g. data_order has no
     *     PG counterpart): drop the field
     *   - else look up the name in the configured table and emit a row keyed
     *     by the friendly label (e.g. "Kingdom: Animalia")
     * Keys not in the config pass through verbatim (text fields, dates,
     * numeric measurements like biota_weight). Lookup queries are batched —
     * one SELECT WHERE id IN (...) per distinct list_* table involved.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, array{label:string,table:?string}>  $config
     * @return array<string, mixed>
     */
    private function enrichLookupIds(array $data, array $config): array
    {
        // Pass 1 — collect (table, id) pairs we'll need.
        $pendingByTable = [];
        foreach ($data as $key => $value) {
            if (! isset($config[$key]) || $config[$key]['table'] === null) {
                continue;
            }
            if ($value === null || $value === '' || $value === 0 || $value === '0') {
                continue;
            }
            $pendingByTable[$config[$key]['table']][] = (int) $value;
        }

        // Pass 2 — bulk-load names (id => name) per table.
        // Wrap each table in its own try/catch so a missing list_* table on a
        // partially-migrated environment (e.g. a deploy where the Phase 6c
        // schema migration hasn't run yet) degrades to "no enrichment for
        // this field" instead of a 500. Other tables in the same request still
        // resolve normally.
        $nameCache = [];
        foreach ($pendingByTable as $table => $ids) {
            try {
                $nameCache[$table] = DB::table($table)
                    ->whereIn('id', array_values(array_unique($ids)))
                    ->pluck('name', 'id')
                    ->all();
            } catch (\Throwable $e) {
                Log::warning('enrichLookupIds: lookup table query failed', [
                    'table' => $table,
                    'error' => $e->getMessage(),
                ]);
                // Leave $nameCache[$table] unset so the pass-3 branch treats
                // each id as "no matching row" and hides the field.
            }
        }

        // Pass 3 — emit the resolved structure, preserving order.
        $result = [];
        foreach ($data as $key => $value) {
            if (isset($config[$key])) {
                $cfg = $config[$key];
                if ($cfg['table'] === null) {
                    continue; // No PG lookup; hide the cryptic id.
                }
                if ($value === null || $value === '' || $value === 0 || $value === '0') {
                    continue;
                }
                $name = $nameCache[$cfg['table']][(int) $value] ?? null;
                if ($name !== null) {
                    $result[$cfg['label']] = $name;
                }

                // No row found in lookup → hide field (consistent with legacy).
                continue;
            }
            // Pass-through (text fields, dates, numeric measurements).
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Per-matrix-type lookup configuration: maps each legacy FK id column on
     * an `empodat_matrix_*` table to its PG `list_*` table and the
     * user-facing label. `table => null` means the column has no PG
     * counterpart (legacy `data_order` / `data_family` / `data_habitat_type`
     * dumps are not available); those rows get hidden in the modal output.
     *
     * @return array<string, array{label:string,table:?string}>
     */
    private function lookupConfigForMatrix(string $matrixType): array
    {
        return match (strtolower($matrixType)) {
            'biota' => [
                'dki_id' => ['label' => 'Kingdom', 'table' => 'list_kingdoms'],
                'dph_id' => ['label' => 'Phylum', 'table' => 'list_phyla'],
                'dcla_id' => ['label' => 'Class', 'table' => 'list_classes'],
                'dord_id' => ['label' => 'Order', 'table' => null],
                'dfam_id' => ['label' => 'Family', 'table' => null],
                'dspc_id' => ['label' => 'Species', 'table' => 'list_biota_species'],
                'diop_id' => ['label' => 'Individual or pooled', 'table' => 'list_individual_or_pooled'],
                'dcat_id' => ['label' => 'Category', 'table' => 'list_categories'],
                'dht_id' => ['label' => 'Habitat type', 'table' => null],
                'dmeas_id' => ['label' => 'Basis of measurement', 'table' => 'list_measurements'],
                'dtiel_id' => ['label' => 'Tissue element', 'table' => 'list_tissues'],
                'dpr_id' => ['label' => 'Proxy pressures', 'table' => 'list_proxy_pressures'],
                'dsgr_id' => ['label' => 'Species group', 'table' => 'list_species_groups'],
            ],
            'soil' => [
                'de_id' => ['label' => 'Depth sampling type', 'table' => 'list_depths'],
                'dps_id' => ['label' => 'Particle size', 'table' => 'list_particle_sizes'],
                'dgra_id' => ['label' => 'Grain size distribution', 'table' => 'list_grain_size_distributions'],
                'dsot_id' => ['label' => 'Soil texture', 'table' => 'list_soil_textures'],
                'dcnps_id' => ['label' => 'Conc. normalised (particle size)', 'table' => 'list_conc_normal_particle_sizes'],
                'dcat_id' => ['label' => 'Category', 'table' => 'list_categories'],
                'dtbu_id' => ['label' => 'Treatment before use', 'table' => null],
                'dpr_id' => ['label' => 'Proxy pressures', 'table' => 'list_proxy_pressures'],
            ],
            'sediments' => [
                'dpr_id' => ['label' => 'Proxy pressures', 'table' => 'list_proxy_pressures'],
                'de_id' => ['label' => 'Depth sampling type', 'table' => 'list_depths'],
                'df_id' => ['label' => 'Fraction', 'table' => 'list_fractions'],
                'dcat_id' => ['label' => 'Category', 'table' => 'list_categories'],
                'dtbu_id' => ['label' => 'Treatment before use', 'table' => null],
            ],
            default => [],
        };
    }

    /**
     * Lookup configuration for the `empodat_minor` FK id columns the modal
     * exposes via "Additional Record Details". Same conventions as
     * lookupConfigForMatrix.
     *
     * @return array<string, array{label:string,table:?string}>
     */
    private function lookupConfigForMinor(): array
    {
        return [
            'dpc_id' => ['label' => 'Precision of coordinates', 'table' => 'list_coordinate_precisions'],
            'dcod_id' => ['label' => 'Concentration data', 'table' => 'list_concentration_data'],
            'dst_id' => ['label' => 'Sampling technique', 'table' => 'list_sampling_techniques'],
            'dplu_id' => ['label' => 'Prevalent land use', 'table' => 'list_prevalent_land_uses'],
            'dtl_id' => ['label' => 'Treatment less', 'table' => 'list_treatment_less'],
            'dtod_id' => ['label' => 'Type of data', 'table' => null],   // no PG counterpart
            'dtos_id' => ['label' => 'Type of sampling', 'table' => null], // no PG counterpart
            'dmm_id' => ['label' => 'Aggregation type', 'table' => null],  // no PG counterpart
        ];
    }

    /**
     * Load matrix metadata based on empodat_matrix_link value from list_matrices
     */
    private function loadMatrixMetadataByLink($empodat, string $matrixLink): ?array
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

        // Normalize the link value (lowercase, trim, remove prefix if present)
        $normalizedLink = strtolower(trim($matrixLink));

        // Remove 'empodat_matrix_' prefix if present
        if (str_starts_with($normalizedLink, 'empodat_matrix_')) {
            $normalizedLink = substr($normalizedLink, strlen('empodat_matrix_'));
        }

        if (! isset($matrixRelationshipMap[$normalizedLink])) {
            Log::warning('Unknown empodat_matrix_link value', [
                'empodat_id' => $empodat->id,
                'matrix_link' => $matrixLink,
            ]);

            return null;
        }

        $relationshipName = $matrixRelationshipMap[$normalizedLink];

        // Load the specific relationship
        $empodat->load($relationshipName);

        $matrixModel = $empodat->{$relationshipName};

        if (! $matrixModel) {
            return null;
        }

        // Get all attributes except 'id' and filter out null/empty/zero values
        $attributes = $matrixModel->getAttributes();
        $metaData = [];
        foreach ($attributes as $key => $value) {
            if ($key === 'id') {
                continue;
            }
            // Skip null, empty strings, and zero values (including string '0')
            if ($value === null || $value === '' || $value === 0 || $value === '0') {
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
     * Remap analytical method and data source fields using batch queries (optimized)
     */
    private function remapFieldsOptimized($empodat): void
    {
        // Group all lookups by model class to minimize queries
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

        // Collect data source lookups (excluding laboratory fields which need special handling)
        if ($empodat->dataSource) {
            $fieldsMap = $this->fieldMapEmpodatDataSources();
            foreach ($fieldsMap as $field => $meta) {
                if (str_contains($field, 'laboratory')) {
                    continue; // Handle separately
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

        // Execute batch queries (one query per model class)
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

        // Handle laboratory fields separately (need full_name accessor)
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

        // Map rating value to descriptive text
        if ($empodat->analyticalMethod) {
            $this->remapRatingField($empodat->analyticalMethod);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function startDownloadJob($query_log_id)
    {

        if (! Auth::check()) {
            session()->flash('error', 'You must be logged in to download the CSV file.');

            return back();
        }
        // $q = QueryLog::find($query_log_id);
        // dd($query_log_id, $q->query);
        // Generate filename at dispatch time to avoid timing issues
        $filename = 'empodat_export_uid_'.Auth::id().'_'.now()->format('YmdHis').'.csv';

        // Dispatch the job to the queue
        $user = Auth::user();
        // dd($user->email);
        EmpodatCsvExportJob::dispatch($query_log_id, $user, $filename);

        session()->flash('success', 'The CSV file is being generated. You will receive an email once it is ready for download, or check the "My downloads" page for the status.');

        return back();
    }

    public function startDownloadIdsJob($query_log_id)
    {
        if (! Auth::check()) {
            session()->flash('error', 'You must be logged in to download the CSV file.');

            return back();
        }

        // Check if user is admin
        if (! Auth::user()->hasRole('super_admin') && ! Auth::user()->hasRole('admin')) {
            session()->flash('error', 'You do not have permission to access this feature.');

            return back();
        }

        $filename = 'empodat_ids_uid_'.Auth::id().'_'.now()->format('YmdHis').'.csv';

        $user = Auth::user();
        \App\Jobs\Empodat\EmpodatIdsCsvExportJob::dispatch($query_log_id, $user, $filename);

        session()->flash('success', 'The IDs CSV file is being generated. You will receive an email once it is ready for download.');

        return back();
    }

    public function downloadCsv($filename)
    {
        $directory = 'exports/empodat';
        $path = Storage::path("{$directory}/{$filename}");

        // Debug logging for file availability
        Log::info("Download request for: {$filename}", [
            'path' => $path,
            'exists' => file_exists($path),
            'directory_contents' => Storage::files($directory),
        ]);

        if (! file_exists($path)) {
            // Try to find similar files for debugging
            $similarFiles = collect(Storage::files($directory))
                ->filter(function ($file) use ($filename) {
                    $fileBasename = basename($file);
                    $requestBasename = basename($filename);

                    // Check if the filename pattern matches (same user and similar timestamp)
                    return str_contains($fileBasename, explode('_', $requestBasename)[3] ?? '') ||
                           str_contains($fileBasename, explode('_', $requestBasename)[2] ?? '');
                })
                ->values();

            Log::warning("File not found: {$filename}", [
                'path' => $path,
                'similar_files' => $similarFiles->toArray(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'error' => 'File not found',
                'message' => 'The requested CSV file does not exist. It may have expired or failed to generate.',
                'similar_files' => $similarFiles->map(function ($file) {
                    return basename($file);
                }),
            ], 404);
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Helper method to sort dropdown lists alphabetically with NR/Other at the bottom
     *
     * @param  array  $list  The associative array to sort (id => name)
     * @return array Sorted array
     */
    private function sortDropdownList(array $list): array
    {
        // Separate items into regular and special (NR/Other)
        $regular = [];
        $special = [];

        foreach ($list as $id => $name) {
            $nameLower = strtolower($name);
            // Check if the name contains 'nr' or 'other' as whole words or standalone
            if (preg_match('/\b(nr|other)\b/i', $name) ||
                preg_match('/^(nr|other)$/i', trim($name))) {
                $special[$id] = $name;
            } else {
                $regular[$id] = $name;
            }
        }

        // Sort regular items alphabetically (case-insensitive)
        uasort($regular, function ($a, $b) {
            return strcasecmp($a, $b);
        });

        // Sort special items alphabetically (case-insensitive)
        uasort($special, function ($a, $b) {
            return strcasecmp($a, $b);
        });

        // Merge: regular items first, then special items
        return $regular + $special;
    }

    public function filter(Request $request)
    {
        // dd($request->all());
        // =======
        // Search fields
        $countries = SearchCountries::with('country')->get();

        $countryList = [];
        foreach ($countries as $s) {
            $countryList[$s->country_id] = $s->country->name;
        }
        $countryList = $this->sortDropdownList($countryList);

        $matrices = SearchMatrix::join('list_matrices', 'empodat_search_matrices.matrix_id', '=', 'list_matrices.id')
            ->orderBy('list_matrices.name')
            ->select('empodat_search_matrices.*')
            ->with('matrix')
            ->get();
        $matrixList = [];
        foreach ($matrices as $s) {
            if ($s->matrix) {
                $matrixList[$s->matrix_id] = $s->matrix->name;
            }
        }

        $sources = SuspectListExchangeSource::where('show', 1)->whereNotNull('order')->orderBy('order', 'asc')->get();
        $sourceList = [];
        foreach ($sources as $s) {
            // Filter to ensure only letters and numbers are allowed
            $code = preg_replace('/[^a-zA-Z0-9]/', '', $s->code);
            $name = preg_replace('/[^a-zA-Z0-9]/', '', $s->name);
            $sourceList[$s->id] = $code.' - '.$name;
        }
        $sourceList = $this->sortDropdownList($sourceList);

        $categoriesList = [];
        $categories = Category::select('id', 'name', 'abbreviation')->get()->keyBy('id');
        foreach ($categories as $s) {
            $categoriesList[$s->id] = $s->name;
        }
        $categoriesList = $this->sortDropdownList($categoriesList);

        $typeDataSourcesList = [];
        $typeSources = TypeDataSource::all();
        foreach ($typeSources as $s) {
            $typeDataSourcesList[$s->id] = $s->name;
        }
        $typeDataSourcesList = $this->sortDropdownList($typeDataSourcesList);

        $concentrationIndicatorList = [];
        $concentrationIndicator = ConcentrationIndicator::all();
        foreach ($concentrationIndicator as $s) {
            $concentrationIndicatorList[$s->id] = $s->name;
        }
        $concentrationIndicatorList = $this->sortDropdownList($concentrationIndicatorList);

        $analyticalMethodsList = [];
        $analyticalMethods = AnalyticalMethod::all();
        foreach ($analyticalMethods as $s) {
            $analyticalMethodsList[$s->id] = $s->name;
        }
        $analyticalMethodsList = $this->sortDropdownList($analyticalMethodsList);

        $qualityAnalyticalMethodsList = [];
        $qualityAnalyticalMethods = QualityEmpodatAnalyticalMethods::all();
        foreach ($qualityAnalyticalMethods as $method) {
            $qualityAnalyticalMethodsList[$method->id] = $method->name;
        }
        $qualityAnalyticalMethodsList = $this->sortDropdownList($qualityAnalyticalMethodsList);

        $dataSourceLaboratoryList = [];
        $dataSourceLaboratories = DataSourceLaboratory::all();
        foreach ($dataSourceLaboratories as $laboratory) {
            $dataSourceLaboratoryList[$laboratory->id] = $laboratory->name;
        }
        $dataSourceLaboratoryList = $this->sortDropdownList($dataSourceLaboratoryList);

        $dataSourceOrganisationList = [];
        $dataSourceOrganisations = DataSourceOrganisation::all();
        foreach ($dataSourceOrganisations as $organisation) {
            $dataSourceOrganisationList[$organisation->id] = $organisation->name;
        }
        $dataSourceOrganisationList = $this->sortDropdownList($dataSourceOrganisationList);

        $selectList = ['0' => 0, '1' => 1, '2' => 2];

        // End of  Search fields
        // =======

        return view('empodat.filter', [
            'request' => $request,
            'countryList' => $countryList,
            'matrixList' => $matrixList,
            'sourceList' => $sourceList,
            'categoriesList' => $categoriesList,
            'categories' => $categories,
            'selectList' => $selectList,
            'analyticalMethodsList' => $analyticalMethodsList,
            'concentrationIndicatorList' => $concentrationIndicatorList,
            'dataSourceLaboratoryList' => $dataSourceLaboratoryList,
            'typeDataSourcesList' => $typeDataSourcesList,
            'qualityAnalyticalMethodsList' => $qualityAnalyticalMethodsList,
            'dataSourceOrganisationList' => $dataSourceOrganisationList,
            'getEqualitySigns' => $this->getEqualitySigns(),
        ]);
    }

    public function search(Request $request)
    {
        try {
            // Set database timeout to prevent connection resets
            try {
                DB::statement('SET statement_timeout = 300000'); // 5 minutes timeout
            } catch (\Exception $timeoutError) {
                // If statement_timeout is not supported, log it but continue
                Log::warning('Database timeout setting not supported: '.$timeoutError->getMessage());
            }

            // Define search fields with their default values
            $searchFields = [
                'countrySearch' => [],
                'matrixSearch' => [],
                'sourceSearch' => [],
                'analyticalMethodSearch' => [],
                'categoriesSearch' => [],
                'typeDataSourcesSearch' => [],
                'concentrationIndicatorSearch' => [],
                'qualityAnalyticalMethodsSearch' => [],
                'dataSourceLaboratorySearch' => [],
                'dataSourceOrganisationSearch' => [],
                'fileSearch' => [], // Only file IDs
                'id_from' => null,
                'id_to' => null,
            ];

            // Process all search inputs
            $searchInputs = $this->processSearchInput($request, $searchFields);

            // Build query using optimized approach with pre-loading relationships
            $empodats = EmpodatMain::query();

            // Apply user permissions filter first to restrict data access
            $empodats = $empodats->byUserPermissions();

            // Apply filters that use JOINs first to optimize query plan
            $empodats = $empodats->byCountries($searchInputs['countrySearch'])
                ->byMatrices($searchInputs['matrixSearch'])
                ->bySubstances($request->input('substances', []))
                ->byConcentrationIndicators($searchInputs['concentrationIndicatorSearch'])
                ->byYearRange($request->input('year_from'), $request->input('year_to'))
                ->byCategories($searchInputs['categoriesSearch'])
                ->bySources($searchInputs['sourceSearch'])
                ->byDataSourceFilters(
                    $searchInputs['typeDataSourcesSearch'],
                    $searchInputs['dataSourceLaboratorySearch'],
                    $searchInputs['dataSourceOrganisationSearch']
                )
                ->byAnalyticalMethods($searchInputs['analyticalMethodSearch'])
                ->byFiles($searchInputs['fileSearch']); // Simple file search by IDs only

            // Add NORMAN relevance filter if no specific filters are applied
            // Commented out to prevent conflicting JOINs for now
            // if (!$this->hasSpecificFilters($searchInputs)) {
            //     $empodats = $empodats->normanRelevant();
            // }

            // Apply ID search if provided and user has admin privileges
            if (Auth::check() && (Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin'))) {
                $empodats = $this->applyIdSearch($empodats, $searchInputs);
            }

            // Load additional relationships only for show method (single record)
            // For search results, we'll load matrix data conditionally later

            // Handle quality ratings separately as it needs the ratings collection
            if (! empty($searchInputs['qualityAnalyticalMethodsSearch'])) {
                $ratings = QualityEmpodatAnalyticalMethods::whereIn('id', $searchInputs['qualityAnalyticalMethodsSearch'])->get();
                $empodats = $empodats->byQualityRatings($ratings);
            }

            // Build search parameters for display
            $searchParameters = $this->buildSearchParameters($searchInputs, $request);

            // Prepare request data for logging
            $mainRequest = $this->prepareRequestData($request, $searchInputs);

            // Log query if not paginated request
            $queryLogId = $this->logQuery($empodats, $mainRequest, $request);

            // Debug: Log the SQL query
            if (config('app.debug')) {
                Log::info('Empodat query SQL: '.$empodats->toSql());
                Log::info('Empodat query bindings: '.json_encode($empodats->getBindings()));
            }

            // Apply pagination
            $empodats = $this->applyPagination($empodats, $request);

            // Eager load all necessary relationships after pagination to avoid N+1 problems
            $empodats->load([
                'concentrationIndicator',
                'substance',
                'matrix',
                'station.countryRelation',
                'analyticalMethod',
                'dataSource',
            ]);

            // Load matrix data conditionally for paginated results
            $this->loadMatrixDataConditionally($empodats);

            // Apply rating remapping to search results
            $this->remapRatingFieldsForSearchResults($empodats);

            // Get total count
            $empodatsCount = $this->getDatabaseEntityCount('empodat');

            return view('empodat.index', array_merge([
                'empodats' => $empodats,
                'empodatsCount' => $empodatsCount,
                'query_log_id' => $queryLogId,
                'searchParameters' => $searchParameters,
            ], $mainRequest));

        } catch (\Exception $e) {
            Log::error('Empodat search failed: '.$e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return to filter page with error message
            return redirect()->route('codsearch.filter')
                ->with('error', 'Search failed due to a database error. Please try again with fewer filters or contact support if the problem persists.');
        }
    }

    /**
     * Process search inputs from request, handling both array and JSON string formats
     */
    private function processSearchInput(Request $request, array $fields): array
    {
        $processed = [];

        foreach ($fields as $field => $defaultValue) {
            $value = $request->input($field);

            if (is_null($value)) {
                $processed[$field] = $defaultValue;
            } elseif (is_array($value)) {
                $processed[$field] = $value;
            } else {
                // For simple string values (like id_type), use the value directly
                // Only try JSON decoding for fields that might contain JSON arrays
                if (str_ends_with($field, 'Search') || str_ends_with($field, '[]')) {
                    $decoded = json_decode($value, true);
                    $processed[$field] = $decoded ?? $defaultValue;
                } else {
                    // Use the string value as-is for simple fields
                    $processed[$field] = $value;
                }
            }
        }

        return $processed;
    }

    /**
     * Build search parameters for display in the view
     */
    private function buildSearchParameters(array $searchInputs, Request $request): array
    {
        $searchParameters = [];

        // Country parameters
        if (! empty($searchInputs['countrySearch'])) {
            $searchParameters['countrySearch'] = Country::whereIn('id', $searchInputs['countrySearch'])->pluck('name');
        }

        // Matrix parameters
        if (! empty($searchInputs['matrixSearch'])) {
            $searchParameters['matrixSearch'] = Matrix::whereIn('id', $searchInputs['matrixSearch'])->pluck('name');
        }

        // Substance parameters
        if (! empty($request->input('substances'))) {
            $searchParameters['substances'] = Substance::whereIn('id', $request->input('substances'))->pluck('name');
        }

        // Data source parameters
        if (! empty($searchInputs['typeDataSourcesSearch'])) {
            $searchParameters['typeDataSourcesSearch'] = TypeDataSource::whereIn('id', $searchInputs['typeDataSourcesSearch'])->pluck('name');
        }

        if (! empty($searchInputs['dataSourceLaboratorySearch'])) {
            $searchParameters['dataSourceLaboratorySearch'] = DataSourceLaboratory::whereIn('id', $searchInputs['dataSourceLaboratorySearch'])->pluck('name');
        }

        if (! empty($searchInputs['dataSourceOrganisationSearch'])) {
            $searchParameters['dataSourceOrganisationSearch'] = DataSourceOrganisation::whereIn('id', $searchInputs['dataSourceOrganisationSearch'])->pluck('name');
        }

        // Analytical method parameters
        if (! empty($searchInputs['analyticalMethodSearch'])) {
            $searchParameters['analyticalMethodSearch'] = AnalyticalMethod::whereIn('id', $searchInputs['analyticalMethodSearch'])->pluck('name');
        }

        // Category parameters
        if (! empty($searchInputs['categoriesSearch'])) {
            $searchParameters['categoriesSearch'] = Category::whereIn('id', $searchInputs['categoriesSearch'])->pluck('name');
        }

        // Concentration indicator parameters
        if (! empty($searchInputs['concentrationIndicatorSearch'])) {
            $searchParameters['concentrationIndicatorSearch'] = ConcentrationIndicator::whereIn('id', $searchInputs['concentrationIndicatorSearch'])->pluck('name');
        }

        // Source parameters
        if (! empty($searchInputs['sourceSearch'])) {
            $searchParameters['sourceSearch'] = SuspectListExchangeSource::whereIn('id', $searchInputs['sourceSearch'])->pluck('code');
        }

        // Quality parameters
        if (! empty($searchInputs['qualityAnalyticalMethodsSearch'])) {
            $searchParameters['ratings'] = QualityEmpodatAnalyticalMethods::whereIn('id', $searchInputs['qualityAnalyticalMethodsSearch'])->get();
        }

        // Year parameters
        if (! is_null($request->input('year_from'))) {
            $searchParameters['year_from'] = $request->input('year_from');
        }

        if (! is_null($request->input('year_to'))) {
            $searchParameters['year_to'] = $request->input('year_to');
        }

        // ID search parameters (only for admin users)
        if (Auth::check() && (Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin'))) {
            if (! is_null($searchInputs['id_from'])) {
                $searchParameters['id_from'] = $searchInputs['id_from'];
            }

            if (! is_null($searchInputs['id_to'])) {
                $searchParameters['id_to'] = $searchInputs['id_to'];
            }
        }

        // File parameters (by ID only)
        if (! empty($searchInputs['fileSearch'])) {
            // Ensure it's an array
            $fileIds = is_array($searchInputs['fileSearch']) ? $searchInputs['fileSearch'] : [$searchInputs['fileSearch']];

            $searchParameters['fileSearch'] = File::whereIn('id', $fileIds)
                ->pluck('original_name')
                ->map(function ($name) {
                    return $name ?: 'Unnamed file';
                });
        }

        return $searchParameters;
    }

    /**
     * Prepare request data for logging
     */
    private function prepareRequestData(Request $request, array $searchInputs): array
    {
        $requestData = array_merge($searchInputs, [
            'year_from' => $request->input('year_from'),
            'year_to' => $request->input('year_to'),
            'displayOption' => $request->input('displayOption'),
            'substances' => $request->input('substances'),
        ]);

        // Add ID search fields if user has admin privileges
        if (Auth::check() && (Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin'))) {
            $requestData['id_from'] = $request->input('id_from');
            $requestData['id_to'] = $request->input('id_to');
        }

        return $requestData;
    }

    /**
     * Log the query for analytics and caching
     */
    private function logQuery($query, array $mainRequest, Request $request): ?int
    {
        if ($request->has('page')) {
            return QueryLog::orderBy('id', 'desc')->first()?->id;
        }

        $databaseKey = 'empodat';
        $empodatsCount = $this->getDatabaseEntityCount($databaseKey);
        $now = now();
        $bindings = $query->getBindings();

        // Convert boolean values to PostgreSQL-compatible strings before interpolation
        $formattedBindings = array_map(function ($binding) {
            if (is_bool($binding)) {
                return $binding ? 'true' : 'false';
            }

            return $binding;
        }, $bindings);

        $sql = vsprintf(str_replace('?', "'%s'", $query->toSql()), $formattedBindings);
        $queryHash = hash('sha256', $sql);

        // Check for existing query with same hash
        $actualCount = QueryLog::where('query_hash', $queryHash)
            ->where('total_count', $empodatsCount)
            ->value('actual_count');

        try {
            QueryLog::insert([
                'content' => json_encode(['request' => $mainRequest, 'bindings' => $bindings]),
                'query' => $sql,
                'user_id' => Auth::id(),
                'total_count' => $empodatsCount,
                'actual_count' => $actualCount,
                'database_key' => $databaseKey,
                'query_hash' => $queryHash,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return QueryLog::orderBy('id', 'desc')->first()->id;

        } catch (\Exception $e) {
            Log::error('Query logging failed: '.$e->getMessage(), [
                'query_hash' => $queryHash,
                'user_id' => Auth::id(),
            ]);

            session()->flash('error', 'An error occurred while processing your request.');

            return null;
        }
    }

    /**
     * Apply pagination based on display option
     */
    private function applyPagination($query, Request $request)
    {
        $orderBy = $query->orderBy('empodat_main.id', 'asc');

        if ($request->input('displayOption') == 1) {
            return $orderBy->simplePaginate(200)->withQueryString();
        } else {
            return $orderBy->paginate(200)->withQueryString();
        }
    }

    /**
     * Get database entity record count
     */
    private function getDatabaseEntityCount(string $databaseKey): int
    {
        return DatabaseEntity::where('code', $databaseKey)->value('number_of_records') ?? 0;
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1); // Ensure we don't exceed array bounds

        return number_format($bytes / pow(1024, $power), 2, '.', ',').' '.$units[$power];
    }

    /**
     * Get file statistics for the search results
     */
    private function getFileStatistics($empodatRecords): array
    {
        $totalFiles = 0;
        $totalFileSize = 0;
        $fileTypes = [];
        $recordsWithFiles = 0;

        foreach ($empodatRecords as $record) {
            $filesCount = $record->files->count();
            if ($filesCount > 0) {
                $recordsWithFiles++;
                $totalFiles += $filesCount;

                foreach ($record->files as $file) {
                    $totalFileSize += $file->size ?? 0;
                    if ($file->mime_type) {
                        $fileTypes[] = $file->mime_type;
                    }
                }
            }
        }

        return [
            'total_files' => $totalFiles,
            'total_size' => $this->formatFileSize($totalFileSize),
            'file_types' => array_unique($fileTypes),
            'records_with_files' => $recordsWithFiles,
            'total_records' => $empodatRecords->count(),
            'files_percentage' => $empodatRecords->count() > 0 ?
                round(($recordsWithFiles / $empodatRecords->count()) * 100, 2) : 0,
        ];
    }

    public function getSearchParameters()
    {
        $p = [];

        return $p;
    }

    public function fieldMapAnalyticalMethods()
    {
        // 1) Map each *_id field to its model & target attribute name:
        return [
            'coverage_factor_id' => [
                'model' => CoverageFactor::class,
                'targetAttribute' => 'coverage_factor_name',
            ],
            'sample_preparation_method_id' => [
                'model' => SamplePreparationMethod::class,
                'targetAttribute' => 'sample_preparation_method_name',
            ],
            'analytical_method_id' => [
                'model' => AnalyticalMethodList::class,
                'targetAttribute' => 'analytical_method',
            ],
            'standardised_method_id' => [
                'model' => StandardisedMethod::class,
                'targetAttribute' => 'standardised_method_name',
            ],
            'validated_method_id' => [
                'model' => ValidatedMethod::class,
                'targetAttribute' => 'validated_method_name',
            ],
            'corrected_recovery_id' => [
                'model' => CorrectedRecovery::class,
                'targetAttribute' => 'corrected_recovery_name',
            ],
            'field_blank_id' => [
                'model' => FieldBlank::class,
                'targetAttribute' => 'field_blank_name',
            ],
            'iso_id' => [
                'model' => Iso::class,
                'targetAttribute' => 'iso_name',
            ],
            'given_analyte_id' => [
                'model' => GivenAnalyte::class,
                'targetAttribute' => 'given_analyte_name',
            ],
            'laboratory_participate_id' => [
                'model' => LaboratoryParticipate::class,
                'targetAttribute' => 'laboratory_participate_name',
            ],
            'summary_performance_id' => [
                'model' => SummaryPerformance::class,
                'targetAttribute' => 'summary_performance_name',
            ],
            'control_charts_id' => [
                'model' => ControlChart::class,
                'targetAttribute' => 'control_charts_name',
            ],
            'internal_standards_id' => [
                'model' => InternalStandard::class,
                'targetAttribute' => 'internal_standards_name',
            ],
            'authority_id' => [
                'model' => Authority::class,
                'targetAttribute' => 'authority_name',
            ],
            'sampling_method_id' => [
                'model' => SamplingMethod::class,
                'targetAttribute' => 'sampling_method_name',
            ],
            'sampling_collection_device_id' => [
                'model' => SamplingCollectionDevice::class,
                'targetAttribute' => 'sampling_collection_device_name',
            ],
        ];
    }

    public function fieldMapEmpodatDataSources()
    {
        // 1) Map each *_id field to its model & target attribute name:
        return [
            'type_data_source_id' => [
                'model' => TypeDataSource::class,
                'targetAttribute' => 'type_data_source_name',
            ],
            'type_monitoring_id' => [
                'model' => TypeMonitoring::class,
                'targetAttribute' => 'type_monitoring_name',
            ],
            'data_accessibility_id' => [
                'model' => DataAccesibility::class,
                'targetAttribute' => 'data_accessibility_name',
            ],
            'organisation_id' => [
                'model' => DataSourceOrganisation::class,
                'targetAttribute' => 'organisation_name',
            ],
            'laboratory1_id' => [
                'model' => DataSourceLaboratory::class,
                'targetAttribute' => 'laboratory_name',
            ],
            'laboratory2_id' => [
                'model' => DataSourceLaboratory::class,
                'targetAttribute' => 'laboratory_name_2',
            ],
        ];
    }

    /**
     * Apply ID search filtering to the query
     */
    private function applyIdSearch($query, array $searchInputs)
    {
        $idFrom = $searchInputs['id_from'];
        $idTo = $searchInputs['id_to'];

        // Only apply if at least one ID field is provided
        if (empty($idFrom) && empty($idTo)) {
            return $query;
        }

        // Always use 'id' field for searching, qualified with table name to avoid ambiguity in JOINs
        $fieldName = 'empodat_main.id';

        // Apply range filtering
        if (! empty($idFrom) && ! empty($idTo)) {
            // Both from and to values provided - range search
            $query->whereBetween($fieldName, [$idFrom, $idTo]);
        } elseif (! empty($idFrom)) {
            // Only from value provided - search from this ID onwards
            $query->where($fieldName, '>=', $idFrom);
        } elseif (! empty($idTo)) {
            // Only to value provided - search up to this ID
            $query->where($fieldName, '<=', $idTo);
        }

        return $query;
    }

    /**
     * Remap rating field value to descriptive text based on quality ranges
     */
    private function remapRatingField($analyticalMethod)
    {
        if (! $analyticalMethod || ! isset($analyticalMethod->rating)) {
            return;
        }

        $rating = $analyticalMethod->rating;

        // Define the rating ranges and their descriptions
        $ratingRanges = [
            ['min' => 68, 'max' => 100, 'description' => 'Adequately supported by quality-related information'],
            ['min' => 52, 'max' => 68, 'description' => 'Supported by limited quality-related information'],
            ['min' => 22, 'max' => 52, 'description' => 'Minimal quality-related information'],
            ['min' => 0, 'max' => 22, 'description' => 'Not supported by quality-related information'],
        ];

        // Find the matching range and replace the rating with composite information
        foreach ($ratingRanges as $range) {
            if ($rating >= $range['min'] && $rating < $range['max']) {
                // Replace the rating value with the composite information
                $analyticalMethod->rating = $rating.' - '.$range['description'];
                break;
            }
        }

        // If no range matches (edge case), set a default description
        if (is_numeric($analyticalMethod->rating)) {
            $analyticalMethod->rating = $rating.' - Rating value out of range';
        }
    }

    /**
     * Remap rating fields for search results (multiple records)
     */
    private function remapRatingFieldsForSearchResults($empodats)
    {
        if (! $empodats) {
            return;
        }

        // Handle both single record and collection
        if ($empodats instanceof \Illuminate\Database\Eloquent\Collection) {
            foreach ($empodats as $empodat) {
                if (isset($empodat->analyticalMethod)) {
                    $this->remapRatingField($empodat->analyticalMethod);
                }
            }
        } elseif (is_object($empodats) && isset($empodats->analyticalMethod)) {
            $this->remapRatingField($empodats->analyticalMethod);
        }
    }

    /**
     * Load matrix data conditionally to avoid N+1 problems
     */
    private function loadMatrixDataConditionally($empodats)
    {
        if (! $empodats || ! $empodats->count()) {
            return;
        }

        // Collect all unique matrix IDs to determine which matrix tables we need
        $matrixIds = $empodats->pluck('matrix_id')->filter()->unique();

        // Map matrix IDs to their corresponding relationship names
        $matrixRelationshipMap = [
            // You'll need to adjust these based on your actual matrix ID mappings
            // This is just an example - you should map actual matrix IDs to relationships
            1 => 'matrixWater',
            2 => 'matrixAir',
            3 => 'matrixSoil',
            4 => 'matrixSediments',
            5 => 'matrixBiota',
            6 => 'matrixSuspendedMatter',
            7 => 'matrixSewageSludge',
        ];

        // Load only the matrix relationships that are actually needed
        $relationshipsToLoad = [];
        foreach ($matrixIds as $matrixId) {
            if (isset($matrixRelationshipMap[$matrixId])) {
                $relationshipsToLoad[] = $matrixRelationshipMap[$matrixId];
            }
        }

        // Load files and minor data for all records
        $relationshipsToLoad = array_merge($relationshipsToLoad, ['files', 'minor']);

        if (! empty($relationshipsToLoad)) {
            $empodats->load($relationshipsToLoad);
        }
    }
}
