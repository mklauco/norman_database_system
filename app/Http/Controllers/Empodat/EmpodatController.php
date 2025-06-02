<?php

namespace App\Http\Controllers\Empodat;

use App\Models\List\Iso;
use App\Models\List\Matrix;
use App\Models\Backend\File;
use App\Models\List\Country;
use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\List\Authority;
use App\Models\List\FieldBlank;
use App\Models\Susdat\Category;
use App\Models\Backend\QueryLog;
use App\Models\Susdat\Substance;
use App\Models\List\ControlChart;
use App\Models\List\GivenAnalyte;
use App\Models\Empodat\EmpodatMain;
use App\Models\List\CoverageFactor;
use App\Models\List\SamplingMethod;
use App\Models\List\TypeDataSource;
use App\Models\List\TypeMonitoring;
use App\Http\Controllers\Controller;
use App\Jobs\Empodat\DownloadCsvJob;
use App\Models\Empodat\SearchMatrix;
use App\Models\List\ValidatedMethod;
use App\Models\List\AnalyticalMethod;
use App\Models\List\DataAccesibility;
use App\Models\List\InternalStandard;
use App\Models\List\CorrectedRecovery;
use App\Models\Empodat\SearchCountries;
use App\Models\List\StandardisedMethod;
use App\Models\List\SummaryPerformance;
use Illuminate\Support\Facades\Storage;
use App\Models\List\DataSourceLaboratory;
use App\Models\List\LaboratoryParticipate;
use App\Models\List\ConcentrationIndicator;
use App\Models\List\DataSourceOrganisation;
use App\Models\List\SamplePreparationMethod;
use App\Models\List\SamplingCollectionDevice;
use App\Models\SLE\SuspectListExchangeSource;
use App\Models\List\QualityEmpodatAnalyticalMethods;
use App\Models\List\AnalyticalMethod as AnalyticalMethodList;

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

      // Eager load relationships (as needed)
      ->with('concentrationIndicator')
      ->with('station')
      ->with('analyticalMethod')
      ->with('dataSource')
      // ->with('substance') 

      // Joins
      ->leftJoin('susdat_substances', 'empodat_main.substance_id', '=', 'susdat_substances.id')
      // ->leftJoin('list_matrices', 'empodat_main.matrix_id', '=', 'list_matrices.id')
      // ->leftJoin('empodat_stations', 'empodat_main.station_id', '=', 'empodat_stations.id')
      // ->leftJoin('list_countries', 'empodat_stations.country_id', '=', 'list_countries.id')
      // ->join('empodat_data_sources', 'empodat_data_sources.id', '=', 'empodat_main.data_source_id')
      // ->join('empodat_analytical_methods', 'empodat_analytical_methods.id', '=', 'empodat_main.method_id')
      // ->join('susdat_category_substance', 'susdat_category_substance.substance_id', '=', 'empodat_main.substance_id')
      // ->join('susdat_source_substance', 'susdat_source_substance.substance_id', '=', 'empodat_main.substance_id')

      // Finally, constrain it to a single empodat_main.id
      ->where('empodat_main.id', $id)

      // Execute
      ->first();  // or ->get(), depending on whether you expect one record or multiple

    // ==============================
    // REMAP ANALYTICAL METHOD FIELDS
    // ==============================

    // 2) Build a lookup table for each model: [id => name]
    $fieldsMap = $this->fieldMapAnalyticalMethods();
    $lookups = [];
    foreach ($fieldsMap as $field => $meta) {
      $lookups[$field] = $meta['model']::query()->pluck('name', 'id');
    }

    // 3) Loop through each field in $empodat->analyticalMethod
    foreach ($fieldsMap as $field => $meta) {
      // 3a) Extract the field value (the *_id)
      $id = data_get($empodat->analyticalMethod, $field);

      // 3b) If it's not null (or 0, if your DB defaults that way) and we find a match
      if (!empty($id) && isset($lookups[$field][$id])) {
        // 3c) Write the "name" to the desired attribute
        data_set($empodat->analyticalMethod, $meta['targetAttribute'], $lookups[$field][$id]);
        // 3d) Optionally set the original *_id field to null
        data_set($empodat->analyticalMethod, $field, null);
      }
    }

    // ==============================
    // END REMAP ANALYTICAL METHOD FIELDS
    // ==============================

    // ==============================
    // REMAP SOURCES  FIELDS
    // ==============================

    $fieldsMap = $this->fieldMapEmpodatDataSources();
    $lookups = [];
    foreach ($fieldsMap as $field => $meta) {
      $lookups[$field] = $meta['model']::query()->pluck('name', 'id');
    }

    // 3) Loop through each field in $empodat->analyticalMethod
    foreach ($fieldsMap as $field => $meta) {
      // 3a) Extract the field value (the *_id)
      $id = data_get($empodat->dataSource, $field);

      // 3b) If it's not null (or 0, if your DB defaults that way) and we find a match
      if (!empty($id) && isset($lookups[$field][$id])) {
        // 3c) Write the "name" to the desired attribute
        data_set($empodat->dataSource, $meta['targetAttribute'], $lookups[$field][$id]);
        // 3d) Optionally set the original *_id field to null
        data_set($empodat->dataSource, $field, null);
      }
    }

    // ==============================
    // END SOURCES FIELDS
    // ==============================

    // dd($empodat->dataSource);
    return response()->json($empodat);
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

    if (!auth()->check()) {
      session()->flash('error', 'You must be logged in to download the CSV file.');
      return back();
    }
    // $q = QueryLog::find($query_log_id);
    // dd($query_log_id, $q->query);
    // Dispatch the job to the queue
    $user = auth()->user();
    // dd($user->email);
    DownloadCsvJob::dispatch($query_log_id, $user);

    session()->flash('success', 'The CSV file is being generated. You will receive an email once it is ready for download.');
    return back();
  }

  public function downloadCsv($filename)
  {
    $directory = 'exports/empodat';
    $path = Storage::path("{$directory}/{$filename}");
    // $path = storage_path("app/exports/empodat/.$filename");

    if (!file_exists($path)) {
      abort(404);
    }

    return response()->download($path, $filename, [
      'Content-Type' => 'text/csv',
    ]);
  }

  public function filter(Request $request)
  {
    // dd($request->all());
    $countries = SearchCountries::with('country')->orderBy('country_id', 'asc')->get();

    $countryList = [];
    foreach ($countries as $s) {
      $countryList[$s->country_id] = $s->country->name . ' - ' . $s->country->code;
    }

    $matrices = SearchMatrix::with('matrix')->orderBy('matrix_id', 'asc')->get();
    $matrixList = [];
    foreach ($matrices as $s) {
      $matrixList[$s->matrix_id] = $s->matrix->name;
    }

    $sources = SuspectListExchangeSource::select('id', 'code', 'name')->get()->keyBy('id');
    $sourceList = [];
    foreach ($sources as $s) {
      $sourceList[$s->id] = $s->code . ' - ' . $s->name;
    }

    $categoriesList = [];
    $categories = Category::orderBy('name', 'asc')->select('id', 'name', 'abbreviation')->get()->keyBy('id');
    foreach ($categories as $s) {
      $categoriesList[$s->id] = $s->name;
    }

    $typeDataSourcesList = [];
    $typeSources = TypeDataSource::all();
    foreach ($typeSources as $s) {
      $typeDataSourcesList[$s->id] = $s->name;
    }

    $concentrationIndicatorList = [];
    $concentrationIndicator = ConcentrationIndicator::all();
    foreach ($concentrationIndicator as $s) {
      $concentrationIndicatorList[$s->id] = $s->name;
    }

    $analyticalMethodsList = [];
    $analyticalMethods = AnalyticalMethod::all();
    foreach ($analyticalMethods as $s) {
      $analyticalMethodsList[$s->id] = $s->name;
    }

    $qualityAnalyticalMethodsList = [];
    $qualityAnalyticalMethods = QualityEmpodatAnalyticalMethods::all();
    foreach ($qualityAnalyticalMethods as $method) {
      $qualityAnalyticalMethodsList[$method->id] = $method->name;
    }


    $dataSourceLaboratoryList = [];
    $dataSourceLaboratories = DataSourceLaboratory::all();
    foreach ($dataSourceLaboratories as $laboratory) {
      $dataSourceLaboratoryList[$laboratory->id] = $laboratory->name;
    }

    $dataSourceOrganisationList = [];
    $dataSourceOrganisations = DataSourceOrganisation::all();
    foreach ($dataSourceOrganisations as $organisation) {
      $dataSourceOrganisationList[$organisation->id] = $organisation->name;
    }


    $selectList = ['0' => 0, '1' => 1, '2' => 2];


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
    ];
    
    // Process all search inputs
    $searchInputs = $this->processSearchInput($request, $searchFields);
    // dd($searchInputs);
    
    // Build query using model scopes
    $empodats = EmpodatMain::withSearchRelations()
        ->normanRelevant()
        ->byCountries($searchInputs['countrySearch'])
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
    
    // Handle quality ratings separately as it needs the ratings collection
    if (!empty($searchInputs['qualityAnalyticalMethodsSearch'])) {
        $ratings = QualityEmpodatAnalyticalMethods::whereIn('id', $searchInputs['qualityAnalyticalMethodsSearch'])->get();
        $empodats = $empodats->byQualityRatings($ratings);
    }
    
    // Build search parameters for display
    $searchParameters = $this->buildSearchParameters($searchInputs, $request);
    
    // Prepare request data for logging
    $mainRequest = $this->prepareRequestData($request, $searchInputs);
    
    // Log query if not paginated request
    $queryLogId = $this->logQuery($empodats, $mainRequest, $request);
    
    // Apply pagination
    $empodats = $this->applyPagination($empodats, $request);
    
    // Get total count
    $empodatsCount = $this->getDatabaseEntityCount('empodat');
    
    return view('empodat.index', [
        'empodats' => $empodats,
        'empodatsCount' => $empodatsCount,
        'query_log_id' => $queryLogId,
        'searchParameters' => $searchParameters,
    ], $mainRequest);
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
            $decoded = json_decode($value, true);
            $processed[$field] = $decoded ?? $defaultValue;
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
    if (!empty($searchInputs['countrySearch'])) {
        $searchParameters['countrySearch'] = Country::whereIn('id', $searchInputs['countrySearch'])->pluck('name');
    }
    
    // Matrix parameters
    if (!empty($searchInputs['matrixSearch'])) {
        $searchParameters['matrixSearch'] = Matrix::whereIn('id', $searchInputs['matrixSearch'])->pluck('name');
    }
    
    // Substance parameters
    if (!empty($request->input('substances'))) {
        $searchParameters['substances'] = Substance::whereIn('id', $request->input('substances'))->pluck('name');
    }
    
    // Data source parameters
    if (!empty($searchInputs['typeDataSourcesSearch'])) {
        $searchParameters['typeDataSourcesSearch'] = TypeDataSource::whereIn('id', $searchInputs['typeDataSourcesSearch'])->pluck('name');
    }
    
    if (!empty($searchInputs['dataSourceLaboratorySearch'])) {
        $searchParameters['dataSourceLaboratorySearch'] = DataSourceLaboratory::whereIn('id', $searchInputs['dataSourceLaboratorySearch'])->pluck('name');
    }
    
    if (!empty($searchInputs['dataSourceOrganisationSearch'])) {
        $searchParameters['dataSourceOrganisationSearch'] = DataSourceOrganisation::whereIn('id', $searchInputs['dataSourceOrganisationSearch'])->pluck('name');
    }
    
    // Analytical method parameters
    if (!empty($searchInputs['analyticalMethodSearch'])) {
        $searchParameters['analyticalMethodSearch'] = AnalyticalMethod::whereIn('id', $searchInputs['analyticalMethodSearch'])->pluck('name');
    }
    
    // Category parameters
    if (!empty($searchInputs['categoriesSearch'])) {
        $searchParameters['categoriesSearch'] = Category::whereIn('id', $searchInputs['categoriesSearch'])->pluck('name');
    }
    
    // Concentration indicator parameters
    if (!empty($searchInputs['concentrationIndicatorSearch'])) {
        $searchParameters['concentrationIndicatorSearch'] = ConcentrationIndicator::whereIn('id', $searchInputs['concentrationIndicatorSearch'])->pluck('name');
    }
    
    // Source parameters
    if (!empty($searchInputs['sourceSearch'])) {
        $searchParameters['sourceSearch'] = SuspectListExchangeSource::whereIn('id', $searchInputs['sourceSearch'])->pluck('code');
    }
    
    // Quality parameters
    if (!empty($searchInputs['qualityAnalyticalMethodsSearch'])) {
        $searchParameters['ratings'] = QualityEmpodatAnalyticalMethods::whereIn('id', $searchInputs['qualityAnalyticalMethodsSearch'])->get();
    }
    
    // Year parameters
    if (!is_null($request->input('year_from'))) {
        $searchParameters['year_from'] = $request->input('year_from');
    }
    
    if (!is_null($request->input('year_to'))) {
        $searchParameters['year_to'] = $request->input('year_to');
    }
    
    // File parameters (by ID only)
    if (!empty($searchInputs['fileSearch'])) {
        // Ensure it's an array
        $fileIds = is_array($searchInputs['fileSearch']) ? $searchInputs['fileSearch'] : [$searchInputs['fileSearch']];
        
        $searchParameters['fileSearch'] = File::whereIn('id', $fileIds)
            ->pluck('original_name')
            ->map(function($name) {
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
    return array_merge($searchInputs, [
        'year_from' => $request->input('year_from'),
        'year_to' => $request->input('year_to'),
        'displayOption' => $request->input('displayOption'),
        'substances' => $request->input('substances'),
    ]);
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
    $sql = vsprintf(str_replace('?', "'%s'", $query->toSql()), $bindings);
    $queryHash = hash('sha256', $sql);
    
    // Check for existing query with same hash
    $actualCount = QueryLog::where('query_hash', $queryHash)
                           ->where('total_count', $empodatsCount)
                           ->value('actual_count');
    
    try {
        QueryLog::insert([
            'content' => json_encode(['request' => $mainRequest, 'bindings' => $bindings]),
            'query' => $sql,
            'user_id' => auth()->id(),
            'total_count' => $empodatsCount,
            'actual_count' => $actualCount,
            'database_key' => $databaseKey,
            'query_hash' => $queryHash,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        return QueryLog::orderBy('id', 'desc')->first()->id;
        
    } catch (\Exception $e) {
        \Log::error('Query logging failed: ' . $e->getMessage(), [
            'query_hash' => $queryHash,
            'user_id' => auth()->id()
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
    
    return number_format($bytes / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
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
            round(($recordsWithFiles / $empodatRecords->count()) * 100, 2) : 0
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
      'coverage_factor_id'            => [
        'model' => CoverageFactor::class,
        'targetAttribute' => 'coverage_factor_name'
      ],
      'sample_preparation_method_id'  => [
        'model' => SamplePreparationMethod::class,
        'targetAttribute' => 'sample_preparation_method_name'
      ],
      'analytical_method_id'          => [
        'model' => AnalyticalMethodList::class,
        'targetAttribute' => 'analytical_method'
      ],
      'standardised_method_id'        => [
        'model' => StandardisedMethod::class,
        'targetAttribute' => 'standardised_method_name'
      ],
      'validated_method_id'           => [
        'model' => ValidatedMethod::class,
        'targetAttribute' => 'validated_method_name'
      ],
      'corrected_recovery_id'         => [
        'model' => CorrectedRecovery::class,
        'targetAttribute' => 'corrected_recovery_name'
      ],
      'field_blank_id'                => [
        'model' => FieldBlank::class,
        'targetAttribute' => 'field_blank_name'
      ],
      'iso_id'                        => [
        'model' => Iso::class,
        'targetAttribute' => 'iso_name'
      ],
      'given_analyte_id'              => [
        'model' => GivenAnalyte::class,
        'targetAttribute' => 'given_analyte_name'
      ],
      'laboratory_participate_id'     => [
        'model' => LaboratoryParticipate::class,
        'targetAttribute' => 'laboratory_participate_name'
      ],
      'summary_performance_id'        => [
        'model' => SummaryPerformance::class,
        'targetAttribute' => 'summary_performance_name'
      ],
      'control_charts_id'             => [
        'model' => ControlChart::class,
        'targetAttribute' => 'control_charts_name'
      ],
      'internal_standards_id'         => [
        'model' => InternalStandard::class,
        'targetAttribute' => 'internal_standards_name'
      ],
      'authority_id'                  => [
        'model' => Authority::class,
        'targetAttribute' => 'authority_name'
      ],
      'sampling_method_id'            => [
        'model' => SamplingMethod::class,
        'targetAttribute' => 'sampling_method_name'
      ],
      'sampling_collection_device_id' => [
        'model' => SamplingCollectionDevice::class,
        'targetAttribute' => 'sampling_collection_device_name'
      ],
    ];
  }

  public function fieldMapEmpodatDataSources()
  {
    // 1) Map each *_id field to its model & target attribute name:
    return [
      'type_data_source_id'            => [
        'model' => TypeDataSource::class,
        'targetAttribute' => 'type_data_source_name'
      ],
      'type_monitoring_id'            => [
        'model' => TypeMonitoring::class,
        'targetAttribute' => 'type_monitoring_name'
      ],
      'data_accessibility_id'            => [
        'model' => DataAccesibility::class,
        'targetAttribute' => 'data_accessibility_name'
      ],
      'organisation_id'            => [
        'model' => DataSourceLaboratory::class,
        'targetAttribute' => 'laboratory_name'
      ],
      'laboratory1_id'            => [
        'model' => DataSourceLaboratory::class,
        'targetAttribute' => 'laboratory_name'
      ],
      'laboratory2_id'            => [
        'model' => DataSourceLaboratory::class,
        'targetAttribute' => 'laboratory_name_2'
      ],
    ];
  }
}
