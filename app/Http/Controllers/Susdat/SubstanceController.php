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
    // $substances = Substance::fullJoin('susdat_category_substance', 'susdat_substances.id', '=', 'susdat_category_substance.substance_id')->whereIn('susdat_category_substance.category_id', $categoriesSearch)
    // ->orderBy('id', 'asc')
    // ->select([
    //   'susdat_substances.id AS id',
    //   'susdat_substances.code',
    //   'susdat_substances.name',
    //   'susdat_substances.cas_number',
    //   'susdat_substances.smiles',
    //   'susdat_substances.stdinchikey',
    //   'susdat_substances.dtxid',
    //   'susdat_substances.pubchem_cid',
    //   'susdat_substances.chemspider_id',
    //   'susdat_substances.chemspider_id',
    //   'susdat_substances.molecular_formula',
    //   'susdat_substances.mass_iso',
    //   ])->selectRaw("STRING_AGG(susdat_category_substance.category_id::text, '|' ORDER BY category_id) AS category_ids")
    //   ->groupBy('susdat_substances.id')
    //   ->paginate(50)
    //   ->withQueryString();

    $substances = DB::select("SELECT
    t.id,
      STRING_AGG(
        susdat_category_substance.category_id :: text,
        '|'
        ORDER BY
          category_id
      ) AS category_ids
    FROM
    (select
      susdat_substances.id as id
    from
      susdat_substances
      inner join susdat_category_substance on susdat_substances.id = susdat_category_substance.substance_id
    where
      susdat_category_substance.category_id in (5)
    group by
      susdat_substances.id
    order by
      id asc) t
    JOIN susdat_category_substance on t.id = susdat_category_substance.substance_id
    group by
      t.id");
    // dd($substances);

    // dd($substances[0]);
    return view('susdat.index', [
      'substances' => $substances,
      'substancesCount' => $substancesCount,
      'request' => $request->all(),
      'categories' => Category::select('id', 'name')->get()->keyBy('id')->toArray()
    ]);
  }
}


// SELECT suspect_list_exchanges.substance_id, STRING_AGG(suspect_list_exchange_sources.code, ',' ORDER BY source_id) AS source_ids FROM suspect_list_exchanges JOIN suspect_list_exchange_sources ON suspect_list_exchange_sources.id = suspect_list_exchanges.source_id WHERE substance_id IS NOT NULL GROUP BY substance_id