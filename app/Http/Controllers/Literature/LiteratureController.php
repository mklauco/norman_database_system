<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\Backend\ExportDownload;
use App\Models\Backend\Project;
use App\Models\Backend\QueryLog;
use App\Models\DatabaseEntity;
use App\Models\List\Country;
use App\Models\List\Matrix;
use App\Models\List\Tissue;
use App\Models\Literature\LiteratureTempMain;
use App\Models\Literature\Species;
use App\Models\Literature\TypeOfNumericQuantity;
use App\Models\Susdat\Category;
use App\Models\Susdat\Substance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LiteratureController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->checkModuleAccess();
    }

    /**
     * Check if user has access to the Literature module
     */
    private function checkModuleAccess(): void
    {
        $databaseEntity = DatabaseEntity::where('code', 'literature')->first();

        if (! $databaseEntity) {
            abort(403, 'Module not found.');
        }

        // If module is public, allow access to everyone
        if ($databaseEntity->is_public === true) {
            return;
        }

        // Module is private - check if user is logged in
        if (! Auth::check()) {
            abort(403, 'You must be logged in to access this module.');
        }

        $user = Auth::user();

        // Always allow admin and super_admin
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return;
        }

        // Check if user has the specific module role
        if ($user->hasRole('literature')) {
            return;
        }

        // User doesn't have permission
        abort(403, 'You do not have permission to access this module.');
    }

    public function filter(Request $request)
    {
        // Get all countries that have literature records
        $countries = Country::query()
            ->join('literature_temp_main', 'list_countries.id', '=', 'literature_temp_main.country_id')
            ->select('list_countries.id', 'list_countries.name', 'list_countries.code')
            ->distinct()
            ->orderBy('list_countries.name', 'asc')
            ->get();

        $countryList = [];
        foreach ($countries as $country) {
            $countryList[$country->id] = $country->name;
        }

        // Get all species that have literature records
        $species = Species::query()
            ->join('literature_temp_main', 'list_species.id', '=', 'literature_temp_main.species_id')
            ->select('list_species.id', 'list_species.name_latin', 'list_species.name', 'list_species.class')
            ->distinct()
            ->orderBy('list_species.name', 'asc')
            ->get();

        $speciesList = [];
        $speciesWithClass = [];
        foreach ($species as $s) {
            // Build label: Name (Latin) [Class]
            $label = $s->name;
            if ($s->name_latin) {
                $label .= ' ('.$s->name_latin.')';
            }
            if ($s->class) {
                $label .= ' ['.$s->class.']';
            }

            $speciesList[$s->id] = $label;
            $speciesWithClass[] = [
                'id' => $s->id,
                'label' => $label,
                'class' => $s->class,
            ];
        }

        // Get all type of numeric quantities that have literature records
        $typeOfNumericQuantities = TypeOfNumericQuantity::query()
            ->join('literature_temp_main', 'list_type_of_numeric_quantities.id', '=', 'literature_temp_main.type_of_numeric_quantity_id')
            ->select('list_type_of_numeric_quantities.id', 'list_type_of_numeric_quantities.name')
            ->distinct()
            ->orderBy('list_type_of_numeric_quantities.name', 'asc')
            ->get();

        $typeOfNumericQuantityList = [];
        foreach ($typeOfNumericQuantities as $t) {
            $typeOfNumericQuantityList[$t->id] = $t->name;
        }

        // Get all unique classes from species that have literature records
        $classes = Species::query()
            ->join('literature_temp_main', 'list_species.id', '=', 'literature_temp_main.species_id')
            ->select('list_species.class')
            ->whereNotNull('list_species.class')
            ->distinct()
            ->orderBy('list_species.class', 'asc')
            ->pluck('list_species.class');

        $classList = [];
        foreach ($classes as $class) {
            $classList[$class] = $class;
        }

        // Get all tissues that have literature records
        $tissues = Tissue::query()
            ->join('literature_temp_main', 'list_tissues.id', '=', 'literature_temp_main.tissue_id')
            ->select('list_tissues.id', 'list_tissues.name')
            ->distinct()
            ->orderBy('list_tissues.name', 'asc')
            ->get();

        $tissueList = [];
        foreach ($tissues as $t) {
            $tissueList[$t->id] = $t->name;
        }

        // Get all matrices that have literature records
        $matrices = Matrix::query()
            ->join('literature_temp_main', 'list_matrices.id', '=', 'literature_temp_main.matrix_id')
            ->select('list_matrices.id', 'list_matrices.name')
            ->distinct()
            ->orderBy('list_matrices.name', 'asc')
            ->get();

        $matrixList = [];
        foreach ($matrices as $m) {
            $matrixList[$m->id] = $m->name;
        }

        // Get all projects that have files associated with literature records
        $projects = Project::query()
            ->join('files', 'projects.id', '=', 'files.project_id')
            ->join('literature_temp_main', 'files.id', '=', 'literature_temp_main.file_id')
            ->select('projects.id', 'projects.name', 'projects.abbreviation')
            ->distinct()
            ->orderBy('projects.name', 'asc')
            ->get();

        $projectList = [];
        foreach ($projects as $project) {
            $projectList[$project->id] = $project->name;
        }

        // Get all categories (from SUSDAT)
        $categories = Category::orderBy('name', 'asc')
            ->select('id', 'name', 'abbreviation')
            ->get()
            ->keyBy('id');

        return view('literature.filter', [
            'request' => $request,
            'countryList' => $countryList,
            'speciesList' => $speciesList,
            'speciesWithClass' => $speciesWithClass,
            'typeOfNumericQuantityList' => $typeOfNumericQuantityList,
            'classList' => $classList,
            'tissueList' => $tissueList,
            'matrixList' => $matrixList,
            'projectList' => $projectList,
            'categories' => $categories,
        ]);
    }

    public function search(Request $request)
    {
        try {
            // Define search fields with their default values
            $searchFields = [
                'countrySearch' => [],
                'speciesSearch' => [],
                'typeOfNumericQuantitySearch' => [],
                'classSearch' => [],
                'tissueSearch' => [],
                'matrixSearch' => [],
                'categoriesSearch' => [],
                'fileSearch' => [],
                'projectSearch' => [],
            ];

            // Process all search inputs
            $searchInputs = $this->processSearchInput($request, $searchFields);

            // Build query
            $literatureRecords = LiteratureTempMain::query();

            // Apply filters using the scopes
            $literatureRecords = $literatureRecords
                ->byCountries($searchInputs['countrySearch'])
                ->bySubstances($request->input('substances', []))
                ->bySpecies($searchInputs['speciesSearch'])
                ->byTypeOfNumericQuantity($searchInputs['typeOfNumericQuantitySearch'])
                ->byClasses($searchInputs['classSearch'])
                ->byTissues($searchInputs['tissueSearch'])
                ->byMatrices($searchInputs['matrixSearch'])
                ->byCategories($searchInputs['categoriesSearch'])
                ->byFiles($searchInputs['fileSearch'])
                ->byProjects($searchInputs['projectSearch']);

            // Build search parameters for display
            $searchParameters = $this->buildSearchParameters($searchInputs, $request);

            // Prepare request data for logging
            $mainRequest = $this->prepareRequestData($request, $searchInputs);

            // Log query if not paginated request
            $queryLogId = $this->logQuery($literatureRecords, $mainRequest, $request);

            // Get the total count before pagination (for "Fast data preview (no page links)" mode)
            $literatureMatchedCount = $literatureRecords->count();

            // Apply pagination
            $literatureRecords = $this->applyPagination($literatureRecords, $request);

            // Eager load all necessary relationships after pagination to avoid N+1 problems
            $literatureRecords->load([
                'country',
                'species',
                'substance',
                'tissue',
                'concentrationUnit',
                'sex',
                'lifeStage',
                'habitatType',
                'commonName',
            ]);

            // Get total count
            $database_key = 'literature';
            $literatureObjectsCount = $this->getDatabaseEntityCount($database_key);

            return view('literature.index', [
                'literatureRecords' => $literatureRecords,
                'literatureObjectsCount' => $literatureObjectsCount,
                'literatureMatchedCount' => $literatureMatchedCount,
                'query_log_id' => $queryLogId,
                'searchParameters' => $searchParameters,
                'request' => $request,
            ] + $mainRequest);

        } catch (\Exception $e) {
            Log::error('Literature search failed: '.$e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return to filter page with error message
            return redirect()->route('literature.search.filter')
                ->with('error', 'Search failed due to a database error. Please try again or contact support if the problem persists.');
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
                // For simple string values, use the value directly
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

        // Species parameters
        if (! empty($searchInputs['speciesSearch'])) {
            $searchParameters['speciesSearch'] = Species::whereIn('id', $searchInputs['speciesSearch'])->pluck('name');
        }

        // Type of numeric quantity parameters
        if (! empty($searchInputs['typeOfNumericQuantitySearch'])) {
            $searchParameters['typeOfNumericQuantitySearch'] = TypeOfNumericQuantity::whereIn('id', $searchInputs['typeOfNumericQuantitySearch'])->pluck('name');
        }

        // Class parameters
        if (! empty($searchInputs['classSearch'])) {
            $searchParameters['classSearch'] = collect($searchInputs['classSearch']);
        }

        // Tissue parameters
        if (! empty($searchInputs['tissueSearch'])) {
            $searchParameters['tissueSearch'] = Tissue::whereIn('id', $searchInputs['tissueSearch'])->pluck('name');
        }

        // Matrix parameters
        if (! empty($searchInputs['matrixSearch'])) {
            $searchParameters['matrixSearch'] = Matrix::whereIn('id', $searchInputs['matrixSearch'])->pluck('name');
        }

        // Substance parameters
        if (! empty($request->input('substances'))) {
            $searchParameters['substances'] = Substance::whereIn('id', $request->input('substances'))->pluck('name');
        }

        // Category parameters
        if (! empty($searchInputs['categoriesSearch'])) {
            $searchParameters['categoriesSearch'] = Category::whereIn('id', $searchInputs['categoriesSearch'])->pluck('name');
        }

        // File parameters
        if (! empty($searchInputs['fileSearch'])) {
            $searchParameters['fileSearch'] = \App\Models\Backend\File::whereIn('id', $searchInputs['fileSearch'])->pluck('name');
        }

        // Project parameters
        if (! empty($searchInputs['projectSearch'])) {
            $searchParameters['projectSearch'] = Project::whereIn('id', $searchInputs['projectSearch'])->pluck('name');
        }

        return $searchParameters;
    }

    /**
     * Prepare request data for logging
     */
    private function prepareRequestData(Request $request, array $searchInputs): array
    {
        $requestData = array_merge($searchInputs, [
            'displayOption' => $request->input('displayOption'),
            'substances' => $request->input('substances'),
        ]);

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

        $databaseKey = 'literature';
        $literatureCount = $this->getDatabaseEntityCount($databaseKey);
        $now = now();
        $bindings = $query->getBindings();
        $sql = vsprintf(str_replace('?', "'%s'", $query->toSql()), $bindings);
        $queryHash = hash('sha256', $sql);

        // Check for existing query with same hash
        $actualCount = QueryLog::where('query_hash', $queryHash)
            ->where('total_count', $literatureCount)
            ->value('actual_count');

        try {
            QueryLog::insert([
                'content' => json_encode(['request' => $mainRequest, 'bindings' => $bindings]),
                'query' => $sql,
                'user_id' => Auth::id(),
                'total_count' => $literatureCount,
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
        $orderBy = $query->orderBy('literature_temp_main.id', 'asc');

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

    public function startDownloadJob($query_log_id)
    {
        if (! Auth::check()) {
            session()->flash('error', 'You must be logged in to download the CSV file.');

            return back();
        }

        try {
            // Get the query log record
            $queryLog = QueryLog::findOrFail($query_log_id);

            // Generate filename
            $filename = 'literature_export_uid_'.Auth::id().'_'.now()->format('YmdHis').'.csv';

            // Get request information for logging
            $ip = request()->ip();
            $userAgent = request()->userAgent();

            // Create an export download record for tracking
            $exportDownload = ExportDownload::create([
                'user_id' => Auth::id(),
                'filename' => $filename,
                'format' => 'csv',
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'database_key' => 'literature',
                'status' => 'processing',
                'started_at' => Carbon::now(),
            ]);

            // Associate with the query log
            $exportDownload->queryLogs()->attach($query_log_id);

            // Process the export directly (no queue needed for small dataset)
            $startTime = microtime(true);
            $directory = 'exports/literature';

            // Make sure the directory exists
            Storage::makeDirectory($directory);

            $path = Storage::path("{$directory}/{$filename}");
            $handle = fopen($path, 'w');

            if (! $handle) {
                throw new \Exception("Unable to open file for writing: {$path}");
            }

            // Write CSV headers - ALL fields from the database
            $headers = [
                'ID',
                'Row ID',
                'Norman SUS ID',
                'Chemical Name',
                'Species',
                'Species (Latin)',
                'Common Name',
                'Title',
                'First Author',
                'Year',
                'DOI',
                'Sex',
                'Diet as Described in Paper',
                'Trophic Level as Described in Paper',
                'Life Stage',
                'Age in Days',
                'Number of Replicates',
                'Type of Monitoring',
                'Active/Passive Sampling',
                'Country',
                'Region/City',
                'Health Status',
                'Habitat Type',
                'Reported Distance to Industry',
                'Last Pesticide Treatment',
                'Pesticide Used in Treatment',
                'Tissue',
                'Matrix',
                'Basis of Measurement',
                'Analytical Method',
                'Storage Temperature (°C)',
                'LOD',
                'LOD Unit',
                'LOQ',
                'LOQ Unit',
                'Pooled',
                'Number of Subsamples',
                'Standard Deviation',
                'Type of Numeric Quantity',
                'Range Min',
                'Range Max',
                'Reported Range Min',
                'Type of Range Max',
                'Concentration Unit',
                'Frequency of Detection',
                'Raw Data Available',
                'Comment',
                'Nest Field if Not Discernable',
                'Chain ID if Paper Has Chain',
                'Sampling Start Day',
                'Sampling Start Month',
                'Sampling Start Year',
                'Sampling End Day',
                'Sampling End Month',
                'Sampling End Year',
                'Imputed Coordinates',
                'Latitude (Decimal)',
                'Longitude (Decimal)',
                'Latitude 1',
                'Latitude 2',
                'Latitude 3',
                'Latitude 4',
                'Latitude 5',
                'Latitude 6',
                'Latitude 7',
                'Latitude 8',
                'Latitude 9',
                'Latitude 10',
                'Longitude 1',
                'Longitude 2',
                'Longitude 3',
                'Longitude 4',
                'Longitude 5',
                'Longitude 6',
                'Longitude 7',
                'Longitude 8',
                'Longitude 9',
                'Longitude 10',
                'Habitat Class',
                'Dietary Preference',
                'Individual ID',
                'Unique Measurement',
                'Concentration Level',
                'Sample ID',
                'Reported Concentration',
                'Frequency (Numeric)',
                'Number of Negative Hits (n_0)',
                'Kingdom',
                'Phylum',
                'Order',
                'Genus',
                'Class (Phylogenetic)',
                'Source Trait',
                'Class (Chemical)',
                'Source Chem',
                'Use Category',
                'Is Transformation Product',
                'Parent',
                'Is Group',
                'Water Content (%)',
                'Concentration (ng/g ww)',
                'LOD (ng/g ww)',
                'LOQ (ng/g ww)',
                'SD (ng/g ww)',
                'Imputed LOD',
                'All Means Without 0',
                'All Means With 0',
                'Created At',
                'Updated At',
                'Export Date',
            ];
            fputcsv($handle, $headers);

            // Build the query from the query log
            $baseQuery = LiteratureTempMain::query();
            $content = json_decode($queryLog->content, true);
            $requestData = $content['request'] ?? [];

            // Process search fields to handle JSON strings properly
            $countrySearch = is_array($requestData['countrySearch'] ?? null)
                ? $requestData['countrySearch']
                : json_decode($requestData['countrySearch'] ?? '[]', true);

            $speciesSearch = is_array($requestData['speciesSearch'] ?? null)
                ? $requestData['speciesSearch']
                : json_decode($requestData['speciesSearch'] ?? '[]', true);

            $classSearch = is_array($requestData['classSearch'] ?? null)
                ? $requestData['classSearch']
                : json_decode($requestData['classSearch'] ?? '[]', true);

            $tissueSearch = is_array($requestData['tissueSearch'] ?? null)
                ? $requestData['tissueSearch']
                : json_decode($requestData['tissueSearch'] ?? '[]', true);

            $matrixSearch = is_array($requestData['matrixSearch'] ?? null)
                ? $requestData['matrixSearch']
                : json_decode($requestData['matrixSearch'] ?? '[]', true);

            $typeOfNumericQuantitySearch = is_array($requestData['typeOfNumericQuantitySearch'] ?? null)
                ? $requestData['typeOfNumericQuantitySearch']
                : json_decode($requestData['typeOfNumericQuantitySearch'] ?? '[]', true);

            $categoriesSearch = is_array($requestData['categoriesSearch'] ?? null)
                ? $requestData['categoriesSearch']
                : json_decode($requestData['categoriesSearch'] ?? '[]', true);

            $fileSearch = is_array($requestData['fileSearch'] ?? null)
                ? $requestData['fileSearch']
                : json_decode($requestData['fileSearch'] ?? '[]', true);

            $projectSearch = is_array($requestData['projectSearch'] ?? null)
                ? $requestData['projectSearch']
                : json_decode($requestData['projectSearch'] ?? '[]', true);

            $substances = is_array($requestData['substances'] ?? null)
                ? $requestData['substances']
                : json_decode($requestData['substances'] ?? '[]', true);

            // Apply the same filters as in the search method
            $baseQuery = $baseQuery
                ->byCountries($countrySearch)
                ->bySubstances($substances)
                ->bySpecies($speciesSearch)
                ->byTypeOfNumericQuantity($typeOfNumericQuantitySearch)
                ->byClasses($classSearch)
                ->byTissues($tissueSearch)
                ->byMatrices($matrixSearch)
                ->byCategories($categoriesSearch)
                ->byFiles($fileSearch)
                ->byProjects($projectSearch);

            // Process records in chunks to manage memory
            $totalExported = 0;
            $exportDate = Carbon::now()->format('Y-m-d H:i:s');

            $baseQuery->with([
                'country',
                'species',
                'substance',
                'tissue',
                'matrix',
                'sex',
                'lifeStage',
                'habitatType',
                'commonName',
                'useCategory',
                'concentrationUnit',
                'typeOfNumericQuantity',
            ])->chunk(500, function ($records) use ($handle, $exportDate, &$totalExported) {
                foreach ($records as $record) {
                    $row = [
                        $record->id,
                        $record->rowid ?? '',
                        $record->substance && $record->substance->code ? 'NS'.$record->substance->code : '',
                        $record->substance->name ?? $record->chemical_name ?? '',
                        $record->species->name ?? '',
                        $record->species->name_latin ?? '',
                        $record->commonName->name ?? '',
                        $record->title ?? '',
                        $record->first_author ?? '',
                        $record->year ?? '',
                        $record->doi ?? '',
                        $record->sex->name ?? '',
                        $record->diet_as_described_in_paper ?? '',
                        $record->trophic_level_as_described_in_paper ?? '',
                        $record->lifeStage->name ?? '',
                        $record->age_in_days ?? '',
                        $record->x_of_replicates ?? '',
                        $record->type_of_monitoring ?? '',
                        $record->active_passive_sampling ?? '',
                        $record->country->name ?? '',
                        $record->region_city ?? '',
                        $record->health_status ?? '',
                        $record->habitatType->name ?? '',
                        $record->reported_distance_to_industry ?? '',
                        $record->last_pesticide_treatment ?? '',
                        $record->pesticide_used_in_treatment ?? '',
                        $record->tissue->name ?? '',
                        $record->matrix->name ?? '',
                        $record->basis_of_measurement ?? '',
                        $record->analytical_method ?? '',
                        $record->storage_temp_c ?? '',
                        $record->lod ?? '',
                        $record->lod_unit ?? '',
                        $record->loq ?? '',
                        $record->loq_unit ?? '',
                        $record->pooled ?? '',
                        $record->x_of_subsamples ?? '',
                        $record->sd ?? '',
                        $record->typeOfNumericQuantity->name ?? '',
                        $record->range_min ?? '',
                        $record->range_max ?? '',
                        $record->reported_range_min ?? '',
                        $record->type_of_range_max ?? '',
                        $record->concentrationUnit->abbreviation ?? $record->concentrationUnit->name ?? '',
                        $record->frequency_of_detection ?? '',
                        $record->raw_data_available ?? '',
                        $record->comment ?? '',
                        $record->nest_field_if_not_dicernable ?? '',
                        $record->chain_id_if_paper_has_chain ?? '',
                        $record->start_of_sampling_day ?? '',
                        $record->start_of_sampling_month ?? '',
                        $record->start_of_sampling_year ?? '',
                        $record->end_of_sampling_day ?? '',
                        $record->end_of_sampling_month ?? '',
                        $record->end_of_sampling_year ?? '',
                        $record->imputed_coordinates ?? '',
                        $record->latitude_decimal ?? '',
                        $record->longitude_decimal ?? '',
                        $record->latitude_1 ?? '',
                        $record->latitude_2 ?? '',
                        $record->latitude_3 ?? '',
                        $record->latitude_4 ?? '',
                        $record->latitude_5 ?? '',
                        $record->latitude_6 ?? '',
                        $record->latitude_7 ?? '',
                        $record->latitude_8 ?? '',
                        $record->latitude_9 ?? '',
                        $record->latitude_10 ?? '',
                        $record->longitude_1 ?? '',
                        $record->longitude_2 ?? '',
                        $record->longitude_3 ?? '',
                        $record->longitude_4 ?? '',
                        $record->longitude_5 ?? '',
                        $record->longitude_6 ?? '',
                        $record->longitude_7 ?? '',
                        $record->longitude_8 ?? '',
                        $record->longitude_9 ?? '',
                        $record->longitude_10 ?? '',
                        $record->habitat_class ?? '',
                        $record->dietary_preference ?? '',
                        $record->individual_id ?? '',
                        $record->unique_measurement ?? '',
                        $record->concentrationlevel ?? '',
                        $record->sample_id ?? '',
                        $record->reported_concentration ?? '',
                        $record->freq_numeric ?? '',
                        $record->n_0 ?? '',
                        $record->kingdom ?? '',
                        $record->phylum ?? '',
                        $record->order ?? '',
                        $record->genus ?? '',
                        $record->class_phyl ?? '',
                        $record->source_trait ?? '',
                        $record->class ?? '',
                        $record->source_chem ?? '',
                        $record->useCategory->name ?? '',
                        $record->is_transformation_product ?? '',
                        $record->parent ?? '',
                        $record->is_group ?? '',
                        $record->water_content ?? '',
                        $record->ww_conc_ng !== null ? number_format($record->ww_conc_ng, 4, '.', '') : '',
                        $record->ww_lod_ng ?? '',
                        $record->ww_loq_ng ?? '',
                        $record->ww_sd_ng ?? '',
                        $record->imputed_lod ?? '',
                        $record->all_means_without_0 ?? '',
                        $record->all_means_with_0 ?? '',
                        $record->created_at ?? '',
                        $record->updated_at ?? '',
                        $exportDate,
                    ];
                    fputcsv($handle, $row);
                    $totalExported++;
                }
            });

            fclose($handle);

            // Get file size and processing time
            $fileSize = Storage::size("{$directory}/{$filename}");
            $formattedFileSize = $this->formatBytes($fileSize);
            $processingTime = round(microtime(true) - $startTime, 2);

            // Update the export download record with completion metrics
            $exportDownload->update([
                'status' => 'completed',
                'record_count' => $totalExported,
                'file_size_bytes' => $fileSize,
                'file_size_formatted' => $formattedFileSize,
                'processing_time_seconds' => $processingTime,
                'completed_at' => Carbon::now(),
            ]);

            Log::info("Literature export complete: {$totalExported} records exported in {$processingTime} seconds. File size: {$formattedFileSize}");

            // Redirect directly to download since processing is complete
            return redirect()->route('literature.csv.download', ['filename' => $filename]);

        } catch (\Exception $e) {
            Log::error('Literature export failed: '.$e->getMessage());

            // Update export download record if it exists
            if (isset($exportDownload)) {
                $exportDownload->update([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                    'completed_at' => Carbon::now(),
                ]);
            }

            session()->flash('error', 'Export failed: '.$e->getMessage());

            return back();
        }
    }

    public function downloadCsv($filename)
    {
        $directory = 'exports/literature';
        $path = Storage::path("{$directory}/{$filename}");

        if (! file_exists($path)) {
            return response()->json([
                'error' => 'File not found',
                'message' => 'The requested CSV file does not exist.',
            ], 404);
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function show($id)
    {
        $record = LiteratureTempMain::with([
            'country',
            'species',
            'substance',
            'tissue',
            'matrix',
            'sex',
            'lifeStage',
            'habitatType',
            'commonName',
            'useCategory',
            'concentrationUnit',
            'typeOfNumericQuantity',
        ])->findOrFail($id);

        return view('literature.show', [
            'record' => $record,
        ]);
    }

    public function edit($id)
    {
        // Authorization is now handled by the constructor middleware
        // TODO: Implement edit logic once database table is created

        return view('literature.edit', [
            'id' => $id,
        ]);
    }

    public function update(Request $request, $id)
    {
        // Authorization is now handled by the constructor middleware
        // TODO: Implement update logic once database table is created

        session()->flash('success', 'Literature record updated successfully.');

        return redirect()->route('literature.search.show', $id);
    }

    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
