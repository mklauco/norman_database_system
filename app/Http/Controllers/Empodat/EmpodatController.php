<?php

namespace App\Http\Controllers\Empodat;

use Illuminate\Http\Request;
use App\Models\Susdat\Category;
use App\Http\Controllers\Controller;
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
      $countryList[$s->id] = $s->country->name.' - '.$s->country->code;
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
      'ecosystemSearch' => $countryList,
      'sourcesList' => $sourcesList,
      'categoriesList' => $categoriesList,
      'categories' => $categories,
      'selectList' => $selectList,
      'getEqualitySigns' => $this->getEqualitySigns(),
    ]);
  }
}
