<?php

namespace App\Http\Controllers\Susdat;

use Illuminate\Http\Request;
use App\Models\Susdat\Category;
use App\Models\Susdat\Substance;
use App\Http\Controllers\Controller;
use App\Models\SLE\SuspectListExchangeSource;

class DuplicateController extends Controller
{
  /**
  * Display a listing of the resource.
  */
  public function index()
  {
    //
    // $substancesCount = Substance::count();
    
    // $columns = [
    //   'id',
    //   'code',
    //   'name',
    //   'cas_number',
    //   'smiles',
    //   'stdinchikey',
    //   'dtxid',
    //   'pubchem_cid',
    //   'chemspider_id',
    //   'molecular_formula',
    //   'mass_iso',
    // ];
    
    // $pivot = 'stdinchikey';
    
    // $duplicates = Substance::select($pivot)
    // ->selectRaw('count(*) as count')
    // ->groupBy($pivot)
    // ->havingRaw('count(*) > 1')
    // ->get();
    
    // $substances = Substance::whereIn($pivot, $duplicates->pluck($pivot)->filter())->orderBy($pivot, 'asc')
    // ->paginate(10)->withQueryString();
    
    // $sources = SuspectListExchangeSource::select('id', 'code', 'name')->get()->keyBy('id');
    // $categories = Category::select('id', 'name', 'abbreviation')->get()->keyBy('id');
    
    return view('susdat.duplicates.index', [
      'getPivotableColumns'           => $this->getPivotableColumns(),
    ]);
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

  public function filter(Request $request){
    $pivot_id = $request->input('pivot_id');
    $pivot = $this->getPivotableColumns()[$pivot_id];
    

    $substancesCount = Substance::count();
    
    $columns = $this->getSelectColumns();
    
    $duplicates = Substance::select($pivot)
    ->selectRaw('count(*) as count')
    ->orderby('count', 'desc')
    ->groupBy($pivot)
    ->havingRaw('count(*) > 1')
    ->get();
    
    $substances = Substance::whereIn($pivot, $duplicates->pluck($pivot)->filter())->orderBy($pivot, 'asc')
    ->paginate(10)->withQueryString();
    
    $sources = SuspectListExchangeSource::select('id', 'code', 'name')->get()->keyBy('id');
    $categories = Category::select('id', 'name', 'abbreviation')->get()->keyBy('id');
    
    $filter = [
      'pivot_id' => $pivot_id,
    ];
    return view('susdat.duplicates.index', [
      // 'substances'        => $substances,
      'duplicates'        => $duplicates,
      'substancesCount'   => $substancesCount,
      'columns'           => $columns,
      'getPivotableColumns'           => $this->getPivotableColumns(),
      'categories'        => $categories,
      'sources'           => $sources,
      'pivot'             => $pivot,
      'filter'            => $filter,
    ]);
  }

  public function records(Request $request, string $pivot, string $pivot_value){
    $columns = $this->getSelectColumns();
    $substances = Substance::where($pivot, $pivot_value)->select($columns)->paginate(10)->withQueryString();
    // dd($substances);
    return view('susdat.duplicates.records', [
      'substances'    => $substances,
      'pivot'         => $pivot,
      'pivot_value'   => $pivot_value,
      'columns'       => $columns,
      'substancesCount' => $substances->total(),
    ]);
  }

  private function getPivotableColumns(){
    return [
      'code',
      'name',
      'cas_number',
      'smiles',
      'stdinchikey',
      'dtxid',
      'pubchem_cid',
      'chemspider_id',
    ];
  }

  private function getSelectColumns(){
    return [
      'id',
      'code',
      'name',
      'cas_number',
      'smiles',
      'stdinchikey',
      'dtxid',
      'pubchem_cid',
      'chemspider_id',
      'molecular_formula',
      'mass_iso',
    ];
  }
  
}
