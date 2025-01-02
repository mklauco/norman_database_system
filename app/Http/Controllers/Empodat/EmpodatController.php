<?php

namespace App\Http\Controllers\Empodat;

use Illuminate\Http\Request;
use App\Models\Susdat\Category;
use App\Models\Empodat\EmpodatMain;
use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Empodat\SearchMatrix;
use App\Models\Empodat\SearchCountries;
use App\Models\SLE\SuspectListExchangeSource;

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
    
    $countries = SearchCountries::with('country')->orderBy('country_id', 'asc')->get();
    dd('test');
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
    $categories = Category::select('id', 'name', 'abbreviation')->get()->keyBy('id');
    foreach($categories as $s){
      $categoriesList[$s->id] = $s->name;
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
    
    
    $empodats = EmpodatMain::query()
    ->leftJoin('susdat_substances', 'empodat_main.substance_id', '=', 'susdat_substances.id')
    ->leftJoin('list_matrices', 'empodat_main.matrix_id', '=', 'list_matrices.id')
    ->leftJoin('empodat_stations', 'empodat_main.station_id', '=', 'empodat_stations.id');
    
    // Apply filters only when necessary
    if (!empty($countrySearch)) {
      $empodats = $empodats->whereIn('empodat_stations.country_id', $countrySearch);
    }
    
    if (!empty($matrixSearch)) {
      $empodats = $empodats->whereIn('empodat_main.matrix_id', $matrixSearch);
    }

    if (!empty($sourceSearch)) {
      $empodats = $empodats->whereIn('empodat_main.data_source_id', $sourceSearch);
    }

    if (!empty($request->input('substances'))) {
      $empodats = $empodats->whereIn('empodat_main.substance_id', $request->input('substances'));
    }

    if (!is_null($request->input('year_from'))) {
      $empodats = $empodats->where('empodat_main.sampling_date_year', '>=', $request->input('year_from'));
    }
    if (!is_null($request->input('year_to'))) {
      $empodats = $empodats->where('empodat_main.sampling_date_year', '<=', $request->input('year_to'));
    }
    
    // Select only the columns you need
    $empodats = $empodats->select(
      'empodat_main.id', // Required for cursorPaginate
      'empodat_main.*',
      'susdat_substances.name as substance_name',
      'list_matrices.name as matrix_name',
      'empodat_stations.name as station_name',
      'empodat_stations.country as country'
    );
    
    
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

    
    // $empodatTotal = $empodats->count('empodat_main.id');
    
    $empodatsCount = DatabaseEntity::where('code', 'empodat')->first()->number_of_records;
    // dd($countrySearch);
    return view('empodat.index', [
      'empodats' => $empodats,
      'empodatsCount' => $empodatsCount,
      'countrySearch' => $countrySearch,
      'matrixSearch' => $matrixSearch,
      'sourceSearch' => $sourceSearch,
      'year_from' => $request->input('year_from'),
      'year_to' => $request->input('year_to'),
      'displayOption' => $request->input('displayOption'),
      // 'empodatTotal' => $empodatTotal,
    ]);
  }
}
