<?php

namespace App\Http\Controllers\Empodat;

use App\Models\List\Iso;
use App\Models\List\Matrix;
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
    ->with('concetrationIndicator') 
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
  
  public function startDownloadJob($query_log_id){
    
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
  
  public function downloadCsv($filename){
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
    foreach($countries as $s){
      $countryList[$s->country_id] = $s->country->name.' - '.$s->country->code;
    }
    
    $matrices = SearchMatrix::with('matrix')->orderBy('matrix_id', 'asc')->get();
    $matrixList = [];
    foreach($matrices as $s){
      $matrixList[$s->matrix_id] = $s->matrix->name;
    }
    
    $sources = SuspectListExchangeSource::select('id', 'code', 'name')->get()->keyBy('id');
    $sourceList = [];
    foreach($sources as $s){
      $sourceList[$s->id] = $s->code. ' - ' . $s->name;
    }
    
    $categoriesList = [];
    $categories = Category::orderBy('name', 'asc')->select('id', 'name', 'abbreviation')->get()->keyBy('id');
    foreach($categories as $s){
      $categoriesList[$s->id] = $s->name;
    }
    
    $typeDataSourcesList = [];
    $typeSources = TypeDataSource::all();
    foreach($typeSources as $s){
      $typeDataSourcesList[$s->id] = $s->name;
    }
    
    $concentrationIndicatorList = [];
    $concentrationIndicator = ConcentrationIndicator::all();
    foreach($concentrationIndicator as $s){
      $concentrationIndicatorList[$s->id] = $s->name;
    }
    
    $analyticalMethodsList = [];
    $analyticalMethods = AnalyticalMethod::all();
    foreach($analyticalMethods as $s){
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
  
  public function search(Request $request){
    if(is_array($request->input('countrySearch'))){
      $countrySearch = $request->input('countrySearch');
    } else{
      $countrySearch = json_decode($request->input('countrySearch'));
    }
    
    if(is_array($request->input('matrixSearch'))){
      $matrixSearch = $request->input('matrixSearch');
    } else{
      $matrixSearch = json_decode($request->input('matrixSearch'));
    }
    
    if(is_array($request->input('sourceSearch'))){
      $sourceSearch = $request->input('sourceSearch');
    } else{
      $sourceSearch = json_decode($request->input('sourceSearch'));
    }
    
    if( is_null($request->input('analyticalMethodSearch')) ){
      $analyticalMethodSearch = [];
    } else {
      $analyticalMethodSearch = json_decode($request->input('analyticalMethodSearch'));
    }
    
    if(is_array($request->input('sourceSearch'))){
      $sourceSearch = $request->input('sourceSearch');
    } else{
      $sourceSearch = json_decode($request->input('sourceSearch'));
    }

    if(is_array($request->input('categoriesSearch'))){
      $categoriesSearch = $request->input('categoriesSearch');
    } else{
      $categoriesSearch = json_decode($request->input('categoriesSearch'));
    }
    
    if( is_array($request->input('typeDataSourcesSearch')) ){
      $typeDataSourcesSearch = [];
    } else {
      $typeDataSourcesSearch = json_decode($request->input('typeDataSourcesSearch'));
    }
    
    if( is_array($request->input('concentrationIndicatorSearch')) ){
      $concentrationIndicatorSearch = [];
    } else {
      $concentrationIndicatorSearch = json_decode($request->input('concentrationIndicatorSearch'));
    }
    
    if( is_array($request->input('qualityAnalyticalMethodsSearch')) ){
      $qualityAnalyticalMethodsSearch = [];
    } else {
      $qualityAnalyticalMethodsSearch = json_decode($request->input('qualityAnalyticalMethodsSearch'));
    }
    
    if( is_array($request->input('dataSourceLaboratorySearch')) ){
      $dataSourceLaboratorySearch = [];
    } else {
      $dataSourceLaboratorySearch = json_decode($request->input('dataSourceLaboratorySearch'));
    }
    
    if( is_array($request->input('dataSourceOrganisationSearch')) ){
      $dataSourceOrganisationSearch = [];
    } else {
      $dataSourceOrganisationSearch = json_decode($request->input('dataSourceOrganisationSearch'));
    }
    
    $use_tables = [
      'empodat_data_sources' => false,
    ];
    
    $empodats = EmpodatMain::with('concetrationIndicator')
    ->leftjoin('susdat_substances', 'empodat_main.substance_id', '=', 'susdat_substances.id')
    ->leftJoin('list_matrices', 'empodat_main.matrix_id', '=', 'list_matrices.id')
    ->leftJoin('empodat_stations', 'empodat_main.station_id', '=', 'empodat_stations.id')
    ->leftJoin('list_countries', 'empodat_stations.country_id', '=', 'list_countries.id')
    ->where('susdat_substances.relevant_to_norman', 1);
    // ->where('empodat_main.id', 10779391);
    
    $searchParameters = [];
    // Apply filters only when necessary
    if (!empty($countrySearch)) {
      $empodats = $empodats->whereIn('empodat_stations.country_id', $countrySearch);
      $searchParameters['countrySearch'] = Country::whereIn('id', $countrySearch)->pluck('name');
    }
    
    if (!empty($matrixSearch)) {
      $empodats = $empodats->whereIn('empodat_main.matrix_id', $matrixSearch);
      $searchParameters['matrixSearch'] = Matrix::whereIn('id', $matrixSearch)->pluck('name');
    }
    
    if (!empty($request->input('substances'))) {
      $empodats = $empodats->whereIn('empodat_main.substance_id', $request->input('substances'));
      $searchParameters['substances'] = Substance::whereIn('id', $request->input('substances'))->pluck('name');
    }
    
    if (!empty($typeDataSourcesSearch)) {
      $use_tables['empodat_data_sources'] = true;
      $empodats = $empodats->join('empodat_data_sources', 'empodat_data_sources.id', '=', 'empodat_main.data_source_id');
      $empodats = $empodats->whereIn('empodat_data_sources.type_data_source_id', $typeDataSourcesSearch);
      $searchParameters['typeDataSourcesSearch'] = TypeDataSource::whereIn('id', $typeDataSourcesSearch)->pluck('name');
    }
    
    if (!empty($analyticalMethodSearch)) {
      $empodats = $empodats->join('empodat_analytical_methods', 'empodat_analytical_methods.id', '=', 'empodat_main.method_id');
      $empodats = $empodats->whereIn('empodat_analytical_methods.analytical_method_id', $analyticalMethodSearch);
      $searchParameters['analyticalMethodSearch'] = AnalyticalMethod::whereIn('id', $analyticalMethodSearch)->pluck('name');
    }
    
    //substance category
    
    if (!empty($categoriesSearch)) {
      $empodats = $empodats->join('susdat_category_substance', 'susdat_category_substance.substance_id', '=', 'empodat_main.substance_id');
      $empodats = $empodats->whereIn('susdat_category_substance.category_id', $categoriesSearch);
      $searchParameters['categoriesSearch'] = Category::whereIn('id', $categoriesSearch)->pluck('name');
    }
    
    if (!empty($concentrationIndicatorSearch)) {
      $empodats = $empodats->whereIn('empodat_main.concentration_indicator_id', $concentrationIndicatorSearch);
      $searchParameters['concentrationIndicatorSearch'] = ConcentrationIndicator::whereIn('id', $concentrationIndicatorSearch)->pluck('name');
    }    
    
    if (!empty($sourceSearch)) {
      $empodats = $empodats->join('susdat_source_substance', 'susdat_source_substance.substance_id', '=', 'empodat_main.substance_id');
      $empodats = $empodats->whereIn('susdat_source_substance.source_id', $sourceSearch);
      $searchParameters['sourceSearch'] = SuspectListExchangeSource::whereIn('id', $sourceSearch)->pluck('code');
    }    
    
    if (!empty($qualityAnalyticalMethodsSearch)) {
      $ratings = QualityEmpodatAnalyticalMethods::whereIn('id', $qualityAnalyticalMethodsSearch)->get();
      $searchParameters['ratings'] = $ratings;
      $empodats = $empodats->join('empodat_analytical_methods', 'empodat_analytical_methods.id', '=', 'empodat_main.method_id');
      $empodats = $empodats->where(function($query) use ($ratings) {
        foreach ($ratings as $rating) {
          $query->orWhere(function($query) use ($rating) {
            $query->where('empodat_analytical_methods.rating', '>=', $rating->min_rating)
            ->where('empodat_analytical_methods.rating', '<', $rating->max_rating);
          });
        }
      });
    }
    
    if (!is_null($request->input('year_from'))) {
      $empodats = $empodats->where('empodat_main.sampling_date_year', '>=', $request->input('year_from'));
      $searchParameters['year_from'] = $request->input('year_from');
    }
    if (!is_null($request->input('year_to'))) {
      $empodats = $empodats->where('empodat_main.sampling_date_year', '<=', $request->input('year_to'));
      $searchParameters['year_to'] = $request->input('year_to');
    }
    
    
    if (!empty($dataSourceLaboratorySearch)) {
      if($use_tables['empodat_data_sources'] == false){
        $empodats = $empodats->join('empodat_data_sources', 'empodat_data_sources.id', '=', 'empodat_main.data_source_id');
        $use_tables['empodat_data_sources'] = true;
      }
      $empodats = $empodats->whereIn('empodat_data_sources.laboratory1_id', $dataSourceLaboratorySearch);
      $searchParameters['dataSourceLaboratorySearch'] = DataSourceLaboratory::whereIn('id', $dataSourceLaboratorySearch)->pluck('name');
    }   
    
    if (!empty($dataSourceOrganisationSearch)) {
      if($use_tables['empodat_data_sources'] == false){
        $empodats = $empodats->join('empodat_data_sources', 'empodat_data_sources.id', '=', 'empodat_main.data_source_id');
        $use_tables['empodat_data_sources'] = true;
      }      
      $empodats = $empodats->whereIn('empodat_data_sources.organisation_id', $dataSourceOrganisationSearch);
      $searchParameters['dataSourceOrganisationSearch'] = DataSourceOrganisation::whereIn('id', $dataSourceOrganisationSearch)->pluck('name');
    }   
    
    // Select only the columns you need
    $empodats = $empodats->select(
      'empodat_main.id', // Required for cursorPaginate
      'empodat_main.*',
      'susdat_substances.name as substance_name',
      'list_matrices.name as matrix_name',
      'empodat_stations.name as station_name',
      'susdat_substances.id AS substance_id',
      'list_matrices.unit AS concentration_unit',
      'list_countries.name AS country_name',
      'list_countries.code AS country_code',
    );
    
    
    $main_request = [
      'countrySearch'                   => $countrySearch,
      'matrixSearch'                    => $matrixSearch,
      'sourceSearch'                    => $sourceSearch,
      'analyticalMethodSearch'          => $analyticalMethodSearch,
      'year_from'                       => $request->input('year_from'),
      'year_to'                         => $request->input('year_to'),
      'displayOption'                   => $request->input('displayOption'),
      'substances'                      => $request->input('substances'),
      'categoriesSearch'                => $request->input('categoriesSearch'),
      'typeDataSourcesSearch'           => $typeDataSourcesSearch,
      'concentrationIndicatorSearch'    => $concentrationIndicatorSearch,
      'dataSourceLaboratorySearch'      => $dataSourceLaboratorySearch,
      'dataSourceOrganisationSearch'    => $dataSourceOrganisationSearch,
      'qualityAnalyticalMethodsSearch'  => $qualityAnalyticalMethodsSearch,
    ];
    // dd($request);
    $database_key = 'empodat';
    $empodatsCount = DatabaseEntity::where('code', $database_key)->first()->number_of_records;
    if(!$request->has('page')){
      $now = now();
      $bindings = $empodats->getBindings();
      $sql = vsprintf(str_replace('?', "'%s'", $empodats->toSql()), $bindings);
      // try to find same SQL query in the QueryLog table with same total_count based on the query_hash
      $actual_count = QueryLog::where('query_hash', hash('sha256', $sql))->where('total_count', $empodatsCount)->value('actual_count');
      
      try {
        QueryLog::insert([
          'content' => json_encode(['request' => $main_request, 'bindings' => $bindings]),
          'query' => $sql,
          'user_id' => auth()->check() ? auth()->id() : null,
          'total_count' => $empodatsCount,
          'actual_count' => is_null($actual_count) ? null : $actual_count,
          'database_key' => $database_key,
          'query_hash' => hash('sha256', $sql),
          'created_at' => $now,
          'updated_at' => $now,
        ]);
      } catch (\Exception $e) {
        // dd($e, hash('sha256', $sql));
        session()->flash('error', 'An error occurred while processing your request.');
      }
      
      
    }
    
    if ($request->displayOptiondisplayOption == 1) {
      // use simple pagination
      $empodats = $empodats->orderBy('empodat_main.id', 'asc')
      ->simplePaginate(200)
      ->withQueryString();
    } else {
      // use cursor pagination
      $empodats = $empodats->orderBy('empodat_main.id', 'asc')
      ->paginate(200)
      ->withQueryString();
    }
    // dd($searchParameters, $categoriesSearch);
    return view('empodat.index', [
      'empodats' => $empodats,
      'empodatsCount' => $empodatsCount,
      'query_log_id' => QueryLog::orderBy('id', 'desc')->first()->id,
      'searchParameters' => $searchParameters,
    ], $main_request);
  }

  public function getSearchParameters(){
    $p = [];
    return $p;
  }

  public function fieldMapAnalyticalMethods(){
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

  public function fieldMapEmpodatDataSources(){
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

