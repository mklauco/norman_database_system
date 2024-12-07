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
  
  public function filter()
  {
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
    $sourcesList = [];
    foreach($sources as $s){
      $sourcesList[$s->id] = $s->code. ' - ' . $s->name;
    }
    
    $categoriesList = [];
    $categories = Category::select('id', 'name', 'abbreviation')->get()->keyBy('id');
    foreach($categories as $s){
      $categoriesList[$s->id] = $s->name;
    }
    
    $selectList = ['0' => 0, '1' => 1, '2' => 2];
    
    return view('empodat.filter', [
      'countryList' => $countryList,
      'matrixList' => $matrixList,
      'sourcesList' => $sourcesList,
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
    
    
    $empodats = EmpodatMain::query()
    ->leftJoin('susdat_substances', 'empodat_main.substance_id', '=', 'susdat_substances.id')
    ->leftJoin('list_matrices', 'empodat_main.matrix_id', '=', 'list_matrices.id')
    ->leftJoin('empodat_stations', 'empodat_main.station_id', '=', 'empodat_stations.id');
    
    // Apply filters only when necessary
    if (!empty($countrySearch)) {
      $empodats->whereIn('empodat_stations.country_id', $countrySearch);
    }
    
    if (!empty($matrixSearch)) {
      $empodats->whereIn('empodat_main.matrix_id', $matrixSearch);
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
    
    // Add ordering and pagination
    $empodats = $empodats->orderBy('empodat_main.id', 'asc')
    ->simplePaginate(200)
    ->withQueryString();
    
    // $empodatTotal = $empodats->count('empodat_main.id');
    
    $empodatsCount = DatabaseEntity::where('code', 'empodat')->first()->number_of_records;
    
    return view('empodat.index', [
      'empodats' => $empodats,
      'empodatsCount' => $empodatsCount,
      // 'empodatTotal' => $empodatTotal,
    ]);
  }
}
