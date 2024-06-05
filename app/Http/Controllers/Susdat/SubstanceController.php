<?php

namespace App\Http\Controllers\Susdat;

use Illuminate\Http\Request;
use App\Models\Susdat\Category;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Database\Eloquent\Builder;

class SubstanceController extends Controller
{
  /**
  * Display a listing of the resource.
  */
  
  public function index()
  {
    //
    $substances = Substance::cursorPaginate(100);
    // $substances = Substance::all();
    return view('susdat.index', [
      'substances' => $substances,
      'substancesCount' => 0,
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
  public function show(int $id)
  {
    //
    // dd(Substance::findOrFail($id));
    return view('susdat.show', [
      'substance' => Substance::findOrFail($id)->toArray()
    ]);
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
    //
    $categories = Category::orderBy('name', 'asc')->get();
    return view('susdat.filter', [
      'categories' => $categories
    ]);
  }
  
  public function search(Request $request)
  {
    $substancesCount = Substance::count();
    $categoriesSearch = $request->input('category');
    
    $columns = [
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

    $subquery = DB::table('susdat_substances')
    ->select($columns)
    ->join('susdat_category_substance', 'susdat_substances.id', '=', 'susdat_category_substance.substance_id')
    ->whereIn('susdat_category_substance.category_id', $categoriesSearch)
    ->groupBy('susdat_substances.id')
    ->orderBy('susdat_substances.id', 'asc');
    
    $substances = DB::table(DB::raw('(' . $subquery->toSql() . ') as t'))
    ->mergeBindings($subquery)
    ->join('susdat_category_substance', 't.id', '=', 'susdat_category_substance.substance_id')
    ->select($columns)
    ->selectRaw(("STRING_AGG(susdat_category_substance.category_id::text, '|' ORDER BY susdat_category_substance.category_id) AS category_ids")
    )
    ->groupBy($columns)
    ->paginate(10)->withQueryString();
    
    return view('susdat.index', [
      'columns' => $columns,
      'substances' => $substances,
      'substancesCount' => $substancesCount,
      'request' => $request->all(),
      'categories' => Category::select('id', 'name')->get()->keyBy('id')->toArray()
    ]);
  }
}


// SELECT suspect_list_exchanges.substance_id, STRING_AGG(suspect_list_exchange_sources.code, ',' ORDER BY source_id) AS source_ids FROM suspect_list_exchanges JOIN suspect_list_exchange_sources ON suspect_list_exchange_sources.id = suspect_list_exchanges.source_id WHERE substance_id IS NOT NULL GROUP BY substance_id