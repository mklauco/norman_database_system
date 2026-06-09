<?php

namespace App\Http\Controllers\EmpodatSuspect;

use App\Http\Controllers\Controller;
use App\Jobs\EmpodatSuspect\EmpodatSuspectCsvExportJob;
use App\Models\Backend\ExportDownload;
use App\Models\Backend\QueryLog;
use App\Models\DatabaseEntity;
use App\Models\Empodat\SearchCountries;
use App\Models\Empodat\SearchMatrix;
use App\Models\EmpodatSuspect\EmpodatSuspectDataSource;
use App\Models\EmpodatSuspect\EmpodatSuspectMain;
use App\Models\List\AnalyticalMethod;
use App\Models\List\ConcentrationIndicator;
use App\Models\List\Country;
use App\Models\List\DataSourceLaboratory;
use App\Models\List\DataSourceOrganisation;
use App\Models\List\Matrix;
use App\Models\List\QualityEmpodatAnalyticalMethods;
use App\Models\List\TypeDataSource;
use App\Models\SLE\SuspectListExchangeSource;
use App\Models\Susdat\Category;
use App\Models\Susdat\Substance;
use App\Services\EmpodatSuspect\MatrixMetadataLabeller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmpodatSuspectController extends Controller
{
    /**
     * Maximum number of records allowed for download
     */
    private const MAX_DOWNLOAD_RECORDS = 700000;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->checkModuleAccess();
    }

    /**
     * Check if user has access to the EmpodatSuspect module
     */
    private function checkModuleAccess(): void
    {
        $databaseEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

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
        if ($user->hasRole('empodat_suspect')) {
            return;
        }

        // User doesn't have permission
        abort(403, 'You do not have permission to access this module.');
    }

    /**
     * Show the filter form for Empodat Suspect search
     * Mimics the Empodat filter functionality
     */
    public function filter(Request $request)
    {
        // Get countries that have empodat_suspect data (via the materialized view)
        $countries = SearchCountries::with('country')
            ->whereIn('country_id', function ($query) {
                $query->select('country_id')
                    ->from('empodat_suspect_station_filters')
                    ->distinct();
            })
            ->orderBy('country_id', 'asc')
            ->get();

        $countryList = [];
        foreach ($countries as $s) {
            $countryList[$s->country_id] = $s->country->name;
        }

        // Get matrices that have empodat_suspect data (via the materialized view)
        $matrices = SearchMatrix::with('matrix')
            ->whereIn('matrix_id', function ($query) {
                $query->select('matrix_id')
                    ->from('empodat_suspect_station_filters')
                    ->distinct();
            })
            ->orderBy('matrix_id', 'asc')
            ->get();

        $matrixList = [];
        foreach ($matrices as $s) {
            $matrixList[$s->matrix_id] = $s->matrix->name;
        }

        // Get SLE sources
        $sources = SuspectListExchangeSource::select('id', 'code', 'name')->get()->keyBy('id');
        $sourceList = [];
        foreach ($sources as $s) {
            $code = preg_replace('/[^a-zA-Z0-9]/', '', $s->code);
            $name = preg_replace('/[^a-zA-Z0-9]/', '', $s->name);
            $sourceList[$s->id] = $code.' - '.$name;
        }

        // Get categories
        $categories = Category::orderBy('name', 'asc')
            ->select('id', 'name', 'abbreviation')
            ->get()
            ->keyBy('id');

        // Get type data sources
        $typeDataSourcesList = [];
        $typeSources = TypeDataSource::all();
        foreach ($typeSources as $s) {
            $typeDataSourcesList[$s->id] = $s->name;
        }

        // Get concentration indicators
        $concentrationIndicatorList = [];
        $concentrationIndicator = ConcentrationIndicator::all();
        foreach ($concentrationIndicator as $s) {
            $concentrationIndicatorList[$s->id] = $s->name;
        }

        // Get analytical methods
        $analyticalMethodsList = [];
        $analyticalMethods = AnalyticalMethod::all();
        foreach ($analyticalMethods as $s) {
            $analyticalMethodsList[$s->id] = $s->name;
        }

        // Get quality analytical methods
        $qualityAnalyticalMethodsList = [];
        $qualityAnalyticalMethods = QualityEmpodatAnalyticalMethods::all();
        foreach ($qualityAnalyticalMethods as $method) {
            $qualityAnalyticalMethodsList[$method->id] = $method->name;
        }

        // Get data source laboratories
        $dataSourceLaboratoryList = [];
        $dataSourceLaboratories = DataSourceLaboratory::all();
        foreach ($dataSourceLaboratories as $laboratory) {
            $dataSourceLaboratoryList[$laboratory->id] = $laboratory->name;
        }

        // Get data source organisations
        $dataSourceOrganisationList = [];
        $dataSourceOrganisations = DataSourceOrganisation::all();
        foreach ($dataSourceOrganisations as $organisation) {
            $dataSourceOrganisationList[$organisation->id] = $organisation->name;
        }

        // Get files for empodat_suspect (database_entity_id = 18)
        // Only for admin, super_admin, and empodat_suspect roles
        $fileList = [];
        $showFileFilter = false;

        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasRole(['admin', 'super_admin', 'empodat_suspect'])) {
                $showFileFilter = true;
                $files = \App\Models\Backend\File::where('database_entity_id', 18)
                    ->orderBy('id', 'asc')
                    ->get();
                foreach ($files as $file) {
                    $fileList[$file->id] = $file->name;
                }
            }
        }

        // Confidence level options
        $confidenceLevelList = [
            1 => 'IP_max > 0.75 AND <= 1.00',
            2 => 'IP_max > 0.60 AND <= 0.75',
            3 => 'IP_max > 0.50 AND <= 0.60',
            4 => 'IP_max > 0.20 AND <= 0.50',
            5 => 'IP_max <= 0.20',
        ];

        return view('empodat_suspect.filter', [
            'request' => $request,
            'countryList' => $countryList,
            'matrixList' => $matrixList,
            'sourceList' => $sourceList,
            'categories' => $categories,
            'analyticalMethodsList' => $analyticalMethodsList,
            'concentrationIndicatorList' => $concentrationIndicatorList,
            'dataSourceLaboratoryList' => $dataSourceLaboratoryList,
            'typeDataSourcesList' => $typeDataSourcesList,
            'qualityAnalyticalMethodsList' => $qualityAnalyticalMethodsList,
            'dataSourceOrganisationList' => $dataSourceOrganisationList,
            'fileList' => $fileList,
            'showFileFilter' => $showFileFilter,
            'confidenceLevelList' => $confidenceLevelList,
        ]);
    }

    /**
     * Search Empodat Suspect data using the materialized view
     * Mimics the Empodat search functionality
     */
    public function search(Request $request)
    {
        try {
            // Set database timeout
            try {
                DB::statement('SET statement_timeout = 300000'); // 5 minutes timeout
            } catch (\Exception $timeoutError) {
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
                'fileSearch' => [],
                'confidenceLevelSearch' => [],
                'year_from' => null,
                'year_to' => null,
            ];

            // Process all search inputs
            $searchInputs = $this->processSearchInput($request, $searchFields);

            // STEP 1: Determine if we should use the materialized view
            // The materialized view contains station/country/matrix/year combinations
            // for fast filtering by geography and ecosystem.
            $hasStationFilters = ! empty($searchInputs['countrySearch'])
                || ! empty($searchInputs['matrixSearch'])
                || ! empty($searchInputs['year_from'])
                || ! empty($searchInputs['year_to']);

            $empodatSuspects = EmpodatSuspectMain::query()
                ->select('empodat_suspect_main.*')
                ->where('empodat_suspect_main.is_numeric_concentration', true)
                ->whereNotNull('empodat_suspect_main.station_id')
                ->whereNotNull('empodat_suspect_main.substance_id')
                ->selectSub(function ($query) {
                    $query->select('sampling_date_year')
                        ->from('empodat_suspect_station_filters')
                        ->whereColumn('empodat_suspect_station_filters.station_id', 'empodat_suspect_main.station_id')
                        ->limit(1);
                }, 'sampling_year');

            // Only use materialized view if there are station-level filters
            if ($hasStationFilters) {
                $stationFiltersQuery = DB::table('empodat_suspect_station_filters');

                // Apply filters to the materialized view
                if (! empty($searchInputs['countrySearch'])) {
                    $stationFiltersQuery->whereIn('country_id', $searchInputs['countrySearch']);
                }

                if (! empty($searchInputs['matrixSearch'])) {
                    $stationFiltersQuery->whereIn('matrix_id', $searchInputs['matrixSearch']);
                }

                if (! empty($searchInputs['year_from'])) {
                    $stationFiltersQuery->where('sampling_date_year', '>=', $searchInputs['year_from']);
                }

                if (! empty($searchInputs['year_to'])) {
                    $stationFiltersQuery->where('sampling_date_year', '<=', $searchInputs['year_to']);
                }

                // Get distinct station_ids from the filtered materialized view
                $filteredStationIds = $stationFiltersQuery->distinct()->pluck('station_id');

                // Filter empodat_suspect_main by station IDs
                $empodatSuspects->whereIn('station_id', $filteredStationIds);
            }

            // Apply additional filters specific to empodat_suspect_main
            if (! empty($request->input('substances'))) {
                $empodatSuspects->whereIn('substance_id', $request->input('substances'));
            }

            // Apply file filter (only for authorized users)
            if (! empty($searchInputs['fileSearch'])) {
                $empodatSuspects->whereIn('file_id', $searchInputs['fileSearch']);
            }

            // Apply category filter (via substance relationship)
            if (! empty($searchInputs['categoriesSearch'])) {
                $empodatSuspects->whereHas('substance.categories', function ($q) use ($searchInputs) {
                    $q->whereIn('susdat_categories.id', $searchInputs['categoriesSearch']);
                });
            }

            // Apply SLE source filter (via substance relationship)
            if (! empty($searchInputs['sourceSearch'])) {
                $empodatSuspects->whereHas('substance', function ($q) use ($searchInputs) {
                    $q->whereHas('sources', function ($sourceQuery) use ($searchInputs) {
                        $sourceQuery->whereIn('sle_sources.id', $searchInputs['sourceSearch']);
                    });
                });
            }

            // Apply confidence level filter (ip_max range) - supports multiple selections
            if (! empty($searchInputs['confidenceLevelSearch'])) {
                $empodatSuspects->where(function ($query) use ($searchInputs) {
                    foreach ($searchInputs['confidenceLevelSearch'] as $index => $level) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $query->$method(function ($q) use ($level) {
                            switch ($level) {
                                case '1': // IP_max > 0.75 AND <= 1.00
                                    $q->where('ip_max', '>', 0.75)->where('ip_max', '<=', 1.00);
                                    break;
                                case '2': // IP_max > 0.60 AND <= 0.75
                                    $q->where('ip_max', '>', 0.60)->where('ip_max', '<=', 0.75);
                                    break;
                                case '3': // IP_max > 0.50 AND <= 0.60
                                    $q->where('ip_max', '>', 0.50)->where('ip_max', '<=', 0.60);
                                    break;
                                case '4': // IP_max > 0.20 AND <= 0.50
                                    $q->where('ip_max', '>', 0.20)->where('ip_max', '<=', 0.50);
                                    break;
                                case '5': // IP_max <= 0.20
                                    $q->where('ip_max', '<=', 0.20);
                                    break;
                            }
                        });
                    }
                });
            }

            // Build search parameters for display
            $searchParameters = $this->buildSearchParameters($searchInputs, $request);

            // Prepare request data for logging
            $mainRequest = $this->prepareRequestData($request, $searchInputs);

            // Check if any filters are applied (for download restriction)
            $hasFilters = $this->checkIfFiltersApplied($searchInputs, $request);

            // Log query if not paginated request
            $queryLogId = $this->logQuery($empodatSuspects, $mainRequest, $request);

            // Apply user-selected sorting (after logging so the query hash stays
            // stable across sort changes). Relationship-backed sorts are only
            // allowed once filters have narrowed the result set.
            $this->applySorting($empodatSuspects, $request, $hasFilters);

            // Apply pagination
            $empodatSuspects = $this->applyPagination($empodatSuspects, $request);

            // Eager load relationships
            $empodatSuspects->load([
                'substance',
                'station.country',
            ]);

            // Get total count
            $empodatSuspectsCount = $this->getDatabaseEntityCount('empodat_suspect');

            // Get actual record count for download limit check
            $actualRecordCount = null;
            if ($request->input('displayOption') != 1) {
                $actualRecordCount = $empodatSuspects->total();
            }

            return view('empodat_suspect.index', array_merge([
                'empodatSuspects' => $empodatSuspects,
                'empodatSuspectsCount' => $empodatSuspectsCount,
                'query_log_id' => $queryLogId,
                'searchParameters' => $searchParameters,
                'hasFilters' => $hasFilters,
                'actualRecordCount' => $actualRecordCount,
                'maxDownloadRecords' => self::MAX_DOWNLOAD_RECORDS,
            ], $mainRequest));

        } catch (\Exception $e) {
            Log::error('Empodat Suspect search failed: '.$e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('empodat_suspect.search.filter')
                ->with('error', 'Search failed due to a database error. Please try again with fewer filters or contact support.');
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
                // For simple string values (like year), use the value directly
                // Only try JSON decoding for fields that might contain JSON arrays
                if (str_ends_with($field, 'Search') || str_ends_with($field, '[]')) {
                    $decoded = json_decode($value, true);
                    $processed[$field] = $decoded ?? $defaultValue;
                } else {
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

        // File parameters (only for authorized users)
        if (! empty($searchInputs['fileSearch'])) {
            $searchParameters['fileSearch'] = \App\Models\Backend\File::whereIn('id', $searchInputs['fileSearch'])->pluck('name');
        }

        // Confidence level parameter
        if (! empty($searchInputs['confidenceLevelSearch'])) {
            $confidenceLevels = [
                '1' => 'IP_max > 0.75 AND <= 1.00',
                '2' => 'IP_max > 0.60 AND <= 0.75',
                '3' => 'IP_max > 0.50 AND <= 0.60',
                '4' => 'IP_max > 0.20 AND <= 0.50',
                '5' => 'IP_max <= 0.20',
            ];
            $selectedLevels = [];
            foreach ($searchInputs['confidenceLevelSearch'] as $level) {
                if (isset($confidenceLevels[$level])) {
                    $selectedLevels[] = $confidenceLevels[$level];
                }
            }
            $searchParameters['confidenceLevelSearch'] = collect($selectedLevels);
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

        $databaseKey = 'empodat_suspect';
        $empodatSuspectsCount = $this->getDatabaseEntityCount($databaseKey);
        $now = now();
        $bindings = $query->getBindings();
        $sql = vsprintf(str_replace('?', "'%s'", $query->toSql()), $bindings);
        $queryHash = hash('sha256', $sql);

        // Check for existing query with same hash
        $actualCount = QueryLog::where('query_hash', $queryHash)
            ->where('total_count', $empodatSuspectsCount)
            ->value('actual_count');

        try {
            QueryLog::insert([
                'content' => json_encode(['request' => $mainRequest, 'bindings' => $bindings]),
                'query' => $sql,
                'user_id' => Auth::id(),
                'total_count' => $empodatSuspectsCount,
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
     * Apply pagination based on display option.
     *
     * Ordering is applied separately by applySorting() before this runs.
     */
    private function applyPagination($query, Request $request)
    {
        if ($request->input('displayOption') == 1) {
            return $query->simplePaginate(200)->withQueryString();
        }

        return $query->paginate(200)->withQueryString();
    }

    /**
     * Columns that sort against the partitioned empodat_suspect_main table
     * directly (no join). These are always available, even on unfiltered
     * searches.
     *
     * @var array<string, string>
     */
    private const DIRECT_SORT_COLUMNS = [
        'id' => 'empodat_suspect_main.id',
        'concentration' => 'empodat_suspect_main.concentration',
        'units' => 'empodat_suspect_main.units',
        'ip_max' => 'empodat_suspect_main.ip_max',
        'hrms' => 'empodat_suspect_main.based_on_hrms_library',
    ];

    /**
     * Columns that require a join or a correlated sub-select to sort. Sorting
     * these over the full (unfiltered) result set scans millions of rows, so
     * they are only honoured once the user has applied at least one filter.
     *
     * @var array<string, string>
     */
    private const RELATED_SORT_COLUMNS = [
        'substance' => 'susdat_substances.name',
        'country' => 'list_countries.name',
        'sample_code' => 'empodat_stations.short_sample_code',
        'station' => 'empodat_stations.name',
        'year' => 'sampling_year',
    ];

    /**
     * Apply a whitelisted ORDER BY to the results query.
     *
     * Only columns in the two maps above may be sorted — this prevents SQL
     * injection and keeps sorts to columns we can serve. Relationship-backed
     * sorts are gated behind $hasFilters so an unfiltered search can never
     * trigger a multi-million-row join sort.
     */
    private function applySorting($query, Request $request, bool $hasFilters): void
    {
        $sort = (string) $request->input('sort', 'id');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (array_key_exists($sort, self::DIRECT_SORT_COLUMNS)) {
            $query->orderBy(self::DIRECT_SORT_COLUMNS[$sort], $direction);
        } elseif ($hasFilters && array_key_exists($sort, self::RELATED_SORT_COLUMNS)) {
            $this->joinForSort($query, $sort);

            if ($sort === 'year') {
                // Order by the correlated sub-select alias added in search().
                $query->orderByRaw('sampling_year '.$direction.' nulls last');
            } else {
                $query->orderBy(self::RELATED_SORT_COLUMNS[$sort], $direction);
            }
        } else {
            // Unknown column, or a related column requested without filters:
            // fall back to the default stable order and stop.
            $query->orderBy('empodat_suspect_main.id', 'asc');

            return;
        }

        // Deterministic tiebreaker so pagination is stable for low-cardinality
        // sorts (units, hrms, year, ...).
        if ($sort !== 'id') {
            $query->orderBy('empodat_suspect_main.id', 'asc');
        }
    }

    /**
     * Add the join(s) a relationship-backed sort column needs. `year` needs no
     * join (it uses the correlated sub-select aliased in search()).
     */
    private function joinForSort($query, string $sort): void
    {
        match ($sort) {
            'substance' => $query->leftJoin('susdat_substances', 'susdat_substances.id', '=', 'empodat_suspect_main.substance_id'),
            'sample_code', 'station' => $query->leftJoin('empodat_stations', 'empodat_stations.id', '=', 'empodat_suspect_main.station_id'),
            'country' => $query
                ->leftJoin('empodat_stations', 'empodat_stations.id', '=', 'empodat_suspect_main.station_id')
                ->leftJoin('list_countries', 'list_countries.id', '=', 'empodat_stations.country_id'),
            default => null,
        };
    }

    /**
     * Get database entity record count
     */
    private function getDatabaseEntityCount(string $databaseKey): int
    {
        return DatabaseEntity::where('code', $databaseKey)->value('number_of_records') ?? 0;
    }

    /**
     * Check if any filters are applied (used during search to pass to view)
     */
    private function checkIfFiltersApplied(array $searchInputs, Request $request): bool
    {
        // List of filter fields to check
        $filterFields = [
            'countrySearch',
            'matrixSearch',
            'sourceSearch',
            'analyticalMethodSearch',
            'categoriesSearch',
            'typeDataSourcesSearch',
            'concentrationIndicatorSearch',
            'qualityAnalyticalMethodsSearch',
            'dataSourceLaboratorySearch',
            'dataSourceOrganisationSearch',
            'fileSearch',
            'confidenceLevelSearch',
        ];

        // Check array-based filters from searchInputs
        foreach ($filterFields as $field) {
            if (isset($searchInputs[$field]) && is_array($searchInputs[$field]) && count($searchInputs[$field]) > 0) {
                return true;
            }
        }

        // Check substances from request
        if (! empty($request->input('substances'))) {
            return true;
        }

        // Check year filters
        if (! empty($searchInputs['year_from']) || ! empty($searchInputs['year_to'])) {
            return true;
        }

        return false;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $record = EmpodatSuspectMain::with([
            'substance',
            'station.country',
            'xlsxStationMapping',
            'file',
        ])->findOrFail($id);

        // Get data source information via file_id
        $dataSource = null;
        if ($record->file_id) {
            $dataSource = EmpodatSuspectDataSource::with([
                'sourceData',
                'monitoringType',
                'organisation.country',
                'laboratory.country',
            ])->where('file_id', $record->file_id)->first();
        }

        // Get matrix metadata from pre-computed MVs
        // NOTE: We pick only the FIRST matching record per matrix type
        // because one station can have many empodat_main records
        $matrixMetadata = [];
        $stationId = $record->station_id;
        $labeller = new MatrixMetadataLabeller;

        if ($stationId) {
            // Check each matrix MV for data
            $matrixTypes = [
                'biota' => 'empodat_suspect_matrix_biota',
                'sediments' => 'empodat_suspect_matrix_sediments',
                'water_surface' => 'empodat_suspect_matrix_water_surface',
                'water_ground' => 'empodat_suspect_matrix_water_ground',
                'water_waste' => 'empodat_suspect_matrix_water_waste',
                'suspended_matter' => 'empodat_suspect_matrix_suspended_matter',
                'soil' => 'empodat_suspect_matrix_soil',
                'air' => 'empodat_suspect_matrix_air',
                'sewage_sludge' => 'empodat_suspect_matrix_sewage_sludge',
            ];

            foreach ($matrixTypes as $type => $tableName) {
                try {
                    $data = DB::table($tableName)
                        ->where('station_id', $stationId)
                        ->first();

                    if ($data) {
                        $matrixMetadata[$type] = $labeller->label($type, $data);
                    }
                } catch (\Exception $e) {
                    // MV might not exist yet, skip silently
                }
            }
        }

        return view('empodat_suspect.show', [
            'record' => $record,
            'matrixMetadata' => $matrixMetadata,
            'dataSource' => $dataSource,
        ]);
    }

    /**
     * Start CSV download job (dispatches to queue for background processing)
     *
     * Uses the queue-based EmpodatSuspectCsvExportJob which:
     * - Processes large exports in the background
     * - Includes all matrix metadata (biota, sediments, water, soil, air, etc.)
     * - Sends email notification when complete
     * - Tracks progress in the ExportDownload table
     */
    public function startDownloadJob($query_log_id)
    {
        if (! Auth::check()) {
            session()->flash('error', 'You must be logged in to download the CSV file.');

            return back();
        }

        // Get the query log entry
        $queryLog = QueryLog::find($query_log_id);
        if (! $queryLog) {
            session()->flash('error', 'Query log not found. Please perform a new search.');

            return back();
        }

        // Check if any filters were applied
        if (! $this->hasFiltersApplied($queryLog)) {
            session()->flash('error', 'Download is not available for unfiltered data. Please apply at least one search criterion (country, matrix, substance, year, etc.) to download the data.');

            return back();
        }

        // Check record count limit
        $recordCount = $this->getQueryRecordCount($queryLog);
        if ($recordCount > self::MAX_DOWNLOAD_RECORDS) {
            $formattedCount = number_format($recordCount, 0, '.', ' ');
            $formattedLimit = number_format(self::MAX_DOWNLOAD_RECORDS, 0, '.', ' ');
            session()->flash('error', "The number of records ({$formattedCount}) exceeds the maximum download limit of {$formattedLimit}. Please use the API for large data exports or contact the administrator.");

            return back();
        }

        // Generate filename at dispatch time to avoid timing issues
        $filename = 'empodat_suspect_export_uid_'.Auth::id().'_'.now()->format('YmdHis').'.csv';

        // Dispatch the job to the queue
        $user = Auth::user();
        EmpodatSuspectCsvExportJob::dispatch($query_log_id, $user, $filename);

        session()->flash('success', 'The CSV file is being generated with all matrix metadata. You will receive an email once it is ready for download, or check the "My downloads" page for the status.');

        return back();
    }

    /**
     * Check if any filters were applied to the query
     */
    private function hasFiltersApplied(QueryLog $queryLog): bool
    {
        $content = json_decode($queryLog->content, true);

        if (! $content || ! isset($content['request'])) {
            return false;
        }

        $request = $content['request'];

        // List of filter fields to check
        $filterFields = [
            'countrySearch',
            'matrixSearch',
            'sourceSearch',
            'analyticalMethodSearch',
            'categoriesSearch',
            'typeDataSourcesSearch',
            'concentrationIndicatorSearch',
            'qualityAnalyticalMethodsSearch',
            'dataSourceLaboratorySearch',
            'dataSourceOrganisationSearch',
            'fileSearch',
            'confidenceLevelSearch',
            'substances',
        ];

        // Check array-based filters
        foreach ($filterFields as $field) {
            if (isset($request[$field]) && is_array($request[$field]) && count($request[$field]) > 0) {
                return true;
            }
        }

        // Check year filters
        if (! empty($request['year_from']) || ! empty($request['year_to'])) {
            return true;
        }

        return false;
    }

    /**
     * Get the record count for the query
     */
    private function getQueryRecordCount(QueryLog $queryLog): int
    {
        // First try to use the cached actual_count
        if ($queryLog->actual_count !== null) {
            return (int) $queryLog->actual_count;
        }

        // If not cached, we need to execute a count query
        // Re-build the query from the logged content
        $content = json_decode($queryLog->content, true);

        if (! $content || ! isset($content['request'])) {
            // If we can't parse the content, return the total count as a fallback
            return $queryLog->total_count ?? 0;
        }

        $request = $content['request'];
        $searchInputs = $request;

        // Build query similar to search method
        $query = EmpodatSuspectMain::query()
            ->where('is_numeric_concentration', true)
            ->whereNotNull('station_id')
            ->whereNotNull('substance_id');

        $hasStationFilters = ! empty($searchInputs['countrySearch'])
            || ! empty($searchInputs['matrixSearch'])
            || ! empty($searchInputs['year_from'])
            || ! empty($searchInputs['year_to']);

        if ($hasStationFilters) {
            $stationFiltersQuery = DB::table('empodat_suspect_station_filters');

            if (! empty($searchInputs['countrySearch'])) {
                $stationFiltersQuery->whereIn('country_id', $searchInputs['countrySearch']);
            }

            if (! empty($searchInputs['matrixSearch'])) {
                $stationFiltersQuery->whereIn('matrix_id', $searchInputs['matrixSearch']);
            }

            if (! empty($searchInputs['year_from'])) {
                $stationFiltersQuery->where('sampling_date_year', '>=', $searchInputs['year_from']);
            }

            if (! empty($searchInputs['year_to'])) {
                $stationFiltersQuery->where('sampling_date_year', '<=', $searchInputs['year_to']);
            }

            $filteredStationIds = $stationFiltersQuery->distinct()->pluck('station_id');
            $query->whereIn('station_id', $filteredStationIds);
        }

        if (! empty($searchInputs['substances'])) {
            $query->whereIn('substance_id', $searchInputs['substances']);
        }

        if (! empty($searchInputs['fileSearch'])) {
            $query->whereIn('file_id', $searchInputs['fileSearch']);
        }

        if (! empty($searchInputs['categoriesSearch'])) {
            $query->whereHas('substance.categories', function ($q) use ($searchInputs) {
                $q->whereIn('susdat_categories.id', $searchInputs['categoriesSearch']);
            });
        }

        if (! empty($searchInputs['sourceSearch'])) {
            $query->whereHas('substance', function ($q) use ($searchInputs) {
                $q->whereHas('sources', function ($sourceQuery) use ($searchInputs) {
                    $sourceQuery->whereIn('sle_sources.id', $searchInputs['sourceSearch']);
                });
            });
        }

        if (! empty($searchInputs['confidenceLevelSearch'])) {
            $query->where(function ($q) use ($searchInputs) {
                foreach ($searchInputs['confidenceLevelSearch'] as $index => $level) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $q->$method(function ($subQ) use ($level) {
                        switch ($level) {
                            case '1':
                                $subQ->where('ip_max', '>', 0.75)->where('ip_max', '<=', 1.00);
                                break;
                            case '2':
                                $subQ->where('ip_max', '>', 0.60)->where('ip_max', '<=', 0.75);
                                break;
                            case '3':
                                $subQ->where('ip_max', '>', 0.50)->where('ip_max', '<=', 0.60);
                                break;
                            case '4':
                                $subQ->where('ip_max', '>', 0.20)->where('ip_max', '<=', 0.50);
                                break;
                            case '5':
                                $subQ->where('ip_max', '<=', 0.20);
                                break;
                        }
                    });
                }
            });
        }

        $count = $query->count();

        // Cache the count for future use
        $queryLog->update(['actual_count' => $count]);

        return $count;
    }

    /**
     * Download CSV file
     */
    public function downloadCsv($filename)
    {
        $directory = 'exports/empodat_suspect';
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

    /**
     * Format bytes to human-readable string
     */
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
