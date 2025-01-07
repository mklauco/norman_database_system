<?php

namespace App\Http\Controllers\Empodat;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Susdat\Category;
use App\Models\Backend\QueryLog;
use App\Models\Empodat\EmpodatMain;
use App\Models\List\TypeDataSource;
use App\Http\Controllers\Controller;
use App\Models\Empodat\SearchMatrix;
use App\Models\List\AnalyticalMethods;
use App\Models\Empodat\SearchCountries;
use App\Models\List\DataSourceLaboratory;
use App\Models\List\ConcentrationIndicator;
use App\Models\List\DataSourceOrganisation;
use App\Models\SLE\SuspectListExchangeSource;
use App\Models\List\QualityEmpodatAnalyticalMethods;

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
    $analyticalMethods = AnalyticalMethods::all();
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
    ->join('susdat_substances', 'empodat_main.substance_id', '=', 'susdat_substances.id')
    ->leftJoin('list_matrices', 'empodat_main.matrix_id', '=', 'list_matrices.id')
    ->leftJoin('empodat_stations', 'empodat_main.station_id', '=', 'empodat_stations.id')
    ->leftJoin('list_countries', 'empodat_stations.country_id', '=', 'list_countries.id');
    
    // Apply filters only when necessary
    if (!empty($countrySearch)) {
      $empodats = $empodats->whereIn('empodat_stations.country_id', $countrySearch);
    }
    
    if (!empty($matrixSearch)) {
      $empodats = $empodats->whereIn('empodat_main.matrix_id', $matrixSearch);
    }
    
    if (!empty($request->input('substances'))) {
      $empodats = $empodats->whereIn('empodat_main.substance_id', $request->input('substances'));
    }
    
    if (!empty($typeDataSourcesSearch)) {
      $use_tables['empodat_data_sources'] = true;
      $empodats = $empodats->join('empodat_data_sources', 'empodat_data_sources.id', '=', 'empodat_main.data_source_id');
      $empodats = $empodats->whereIn('empodat_data_sources.type_data_source_id', $typeDataSourcesSearch);
    }
    
    if (!empty($analyticalMethodSearch)) {
      $empodats = $empodats->join('empodat_analytical_methods', 'empodat_analytical_methods.id', '=', 'empodat_main.method_id');
      $empodats = $empodats->whereIn('empodat_analytical_methods.analytical_method_id', $analyticalMethodSearch);
    }
    
    //substance category
    
    if (!empty($categoriesSearch)) {
      $empodats = $empodats->join('susdat_category_substance', 'susdat_category_substance.substance_id', '=', 'empodat_main.substance_id');
      $empodats = $empodats->whereIn('susdat_category_substance.category_id', $categoriesSearch);
    }
    
    if (!empty($concentrationIndicatorSearch)) {
      $empodats = $empodats->whereIn('empodat_main.concentration_indicator_id', $concentrationIndicatorSearch);
    }    
    
    if (!empty($sourceSearch)) {
      $empodats = $empodats->join('susdat_source_substance', 'susdat_source_substance.substance_id', '=', 'empodat_main.substance_id');
      $empodats = $empodats->whereIn('susdat_source_substance.source_id', $sourceSearch);
    }    
    
    if (!empty($qualityAnalyticalMethodsSearch)) {
      $ratings = QualityEmpodatAnalyticalMethods::whereIn('id', $qualityAnalyticalMethodsSearch)->get();
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
    }
    if (!is_null($request->input('year_to'))) {
      $empodats = $empodats->where('empodat_main.sampling_date_year', '<=', $request->input('year_to'));
    }
    
    
    if (!empty($dataSourceLaboratorySearch)) {
      if($use_tables['empodat_data_sources'] == false){
        $empodats = $empodats->join('empodat_data_sources', 'empodat_data_sources.id', '=', 'empodat_main.data_source_id');
        $use_tables['empodat_data_sources'] = true;
      }
      $empodats = $empodats->whereIn('empodat_data_sources.laboratory1_id', $dataSourceLaboratorySearch);
    }   
    
    if (!empty($dataSourceOrganisationSearch)) {
      if($use_tables['empodat_data_sources'] == false){
        $empodats = $empodats->join('empodat_data_sources', 'empodat_data_sources.id', '=', 'empodat_main.data_source_id');
        $use_tables['empodat_data_sources'] = true;
      }      
      $empodats = $empodats->whereIn('empodat_data_sources.organisation_id', $dataSourceOrganisationSearch);
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

    if(!$request->has('page')){
      $now = now();
      $bindings = $empodats->getBindings();
      $sql = vsprintf(str_replace('?', "'%s'", $empodats->toSql()), $bindings);
      
      QueryLog::insert([
        'content' => json_encode(['request' => $main_request, 'bindings' => $bindings]),
        'query' => $sql,
        'user_id' => auth()->check() ? auth()->id() : null,
        'created_at' => $now,
        'updated_at' => $now,
      ]);
      // dd($request->all());
    }

    if ($request->displayOption == 1) {
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
    // dd($empodats[0]);
    
    // $empodatTotal = $empodats->count('empodat_main.id');
    // dd($categoriesSearch);
    $empodatsCount = DatabaseEntity::where('code', 'empodat')->first()->number_of_records;
    // dd($countrySearch);
    return view('empodat.index', [
      'empodats' => $empodats,
      'empodatsCount' => $empodatsCount,
      'query_log_id' => QueryLog::orderBy('id', 'desc')->first()->id,
      // search filters
    ], $main_request);
  }
}
