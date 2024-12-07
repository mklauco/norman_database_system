<?php

namespace App\Http\Controllers\Empodat;

use Illuminate\Http\Request;
use App\Models\Susdat\Category;
use App\Models\Empodat\EmpodatMain;
use App\Http\Controllers\Controller;
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


    // dd($countrySearch);
    // $empodats = EmpodatMain::where('empodat_main.id', 150000006)->get();
    $empodats = EmpodatMain::
    leftjoin('susdat_substances', 'empodat_main.substance_id', '=', 'susdat_substances.id')->
    leftjoin('list_matrices', 'empodat_main.matrix_id', '=', 'list_matrices.id')->
    leftjoin('empodat_stations', 'empodat_main.station_id', '=', 'empodat_stations.id');
    
    
    if(!empty($countrySearch)){
      $empodats = $empodats->whereIn('empodat_stations.country_id', $countrySearch);
    }
    // dd($matrixSearch);
    if(!empty($matrixSearch)){
      $empodats = $empodats->whereIn('empodat_main.matrix_id', $matrixSearch);
    }

    $empodats = $empodats->
    // where('empodat_main.id', 150000006)->
    select('empodat_main.*', 'susdat_substances.name as substance_name', 'list_matrices.name as matrix_name', 'empodat_stations.name AS station_name', 'empodat_stations.country AS country');
    
    
    $empodats = $empodats->orderby('id', 'asc')->paginate(200)->withQueryString();
    // dd($empodats);
    $empodatsCount = EmpodatMain::count();
  
    return view('empodat.index', [
      'empodats' => $empodats,
      'empodatsCount' => $empodatsCount,
    ]);
  }
}
