<?php

namespace App\Http\Controllers\Susdat;

use Exception;
use Illuminate\Http\Request;
use App\Models\Susdat\Category;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\SLE\SuspectListExchange;
use App\Models\SLE\SuspectListExchangeSource;
use Illuminate\Contracts\Database\Eloquent\Builder;

class SubstanceController extends Controller
{

  public function audits(Substance $substance)
  {
    $audits = $substance->audits()
      ->with('user')
      ->orderBy('created_at', 'desc')
      ->paginate(20);

    return view('susdat.audits', [
      'substance' => $substance,
      'audits' => $audits,
    ]);
  }

  public function withAudits()
  {
    $substances = Substance::whereHas('audits')
      ->withCount('audits')
      ->orderBy('audits_count', 'desc')
      ->paginate(50);

    return view('susdat.with-audits', [
      'substances' => $substances,
    ]);
  }

  /**
   * Display a listing of the resource.
   */

  public function index()
  {
    //
    // $substances = Substance::cursorPaginate(100);

    return redirect()->route('substances.search');
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
  public function show($id)
  {
    //
    // dd(Substance::findOrFail($id));
    return view('susdat.show', [
      'substance' => Substance::findOrFail($id)
    ]);
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(string $id)
  {
    //
    $substance = Substance::findOrFail($id);
    $editables = $this->getEditableColumns();
    // dd($substance);
    $categories = Category::orderBy('name', 'asc')->get();
    $sources = SuspectListExchangeSource::orderBy('id', 'asc')->get();
    $sourceList = [];
    foreach ($sources as $s) {
      $sourceList[$s->id] = $s->code . ' - ' . $s->name;
    }
    return view('susdat.edit', [
      'substance' => $substance,
      'categories' => $categories,
      'sources' => $sources,
      'sourceList' => $sourceList,
      'editables' => $editables
    ]);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    //

    $substance = Substance::findOrFail($id);
    $editables = $this->getEditableColumns();

    foreach ($editables as $key) {
      if ($request->has($key)) {
        if (substr($key, 0, 8) == 'metadata') {
          $substance->$key = json_encode($request->input($key));
        } else {
          $substance->$key = $request->input($key);
        }
      }
    }


    try {
      $s = $substance->save();
      session()->flash('success', 'Substance updated successfully');
      return redirect()->route('substances.show', ['substance' => $id]);
    } catch (Exception $e) {
      session()->flash('failure', 'An error occurred while updating the substance. Please contact the administrator.' . $e->getMessage());
      return redirect()->route('substances.edit', ['substance' => $id])->with('error', $e->getMessage());
    }
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
    $sources = SuspectListExchangeSource::orderBy('id', 'asc')->get();
    $sourceList = [];
    foreach ($sources as $s) {
      $sourceList[$s->id] = $s->code . ' - ' . $s->name;
    }

    return view('susdat.filter', [
      'categories' => $categories,
      'sources' => $sources,
      'sourceList' => $sourceList
    ]);
  }

  public function search(Request $request)
  {

    $substancesCount = Substance::count();

    // get all categories and sources by id
    $allCategories = Category::all()->pluck('id')->toArray();
    $allSources = SuspectListExchangeSource::all()->pluck('id')->toArray();

    if (is_array($request->input('categoriesSearch'))) {
      $categoriesSearch = $request->input('categoriesSearch');
    } else {
      $categoriesSearch = json_decode($request->input('categoriesSearch'));
    }

    if (is_array($request->input('sourcesSearch'))) {
      $sourcesSearch = $request->input('sourcesSearch');
    } else {
      $sourcesSearch = json_decode($request->input('sourcesSearch'));
    }

    if (is_array($request->input('substancesSearch'))) {
      $substancesSearch = $request->input('substancesSearch');
    } else {
      $substancesSearch = json_decode($request->input('substancesSearch'));
    }
    // dd($substancesSearch);

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
      'deleted_at',
    ];

    $subquery = DB::table('susdat_substances')
      ->select($columns)
      ->leftJoin('susdat_category_substance', 'susdat_substances.id', '=', 'susdat_category_substance.substance_id')
      ->leftJoin('susdat_source_substance', 'susdat_substances.id', '=', 'susdat_source_substance.substance_id');
    if ($request->input('searchCategory') == 1) {
      $subquery = $subquery->whereIn('susdat_category_substance.category_id', $categoriesSearch);
      $subquery = $subquery->whereIn('susdat_source_substance.source_id', $allSources);
      $sourcesSearch = $allSources;
    } elseif ($request->input('searchSource') == 1) {
      $subquery = $subquery->whereIn('susdat_category_substance.category_id', $allCategories);
      $subquery = $subquery->whereIn('susdat_source_substance.source_id', $sourcesSearch);
      $categoriesSearch = $allCategories;
    } elseif ($request->input('searchSubstance') == 1) {
      $subquery = $subquery->whereIn('susdat_substances.id', $substancesSearch);
      // 
      $categoriesSearch = $allCategories;
      $sourcesSearch = $allSources;
    } else {
      // $subquery = $subquery->whereIn('susdat_category_substance.category_id', $allCategories);
      // $subquery = $subquery->whereIn('susdat_source_substance.source_id', $allSources);
    }

    $subquery = $subquery->groupBy('susdat_substances.id')->orderBy('susdat_substances.id', 'asc');

    // dd($substancesSearch, $subquery->toSql(), $subquery->get());
    $substances = DB::table(DB::raw('(' . $subquery->toSql() . ') as t'))
      ->mergeBindings($subquery)
      ->leftJoin('susdat_category_substance', 't.id', '=', 'susdat_category_substance.substance_id')
      ->select($columns)
      ->selectRaw(("STRING_AGG(susdat_category_substance.category_id::text, '|' ORDER BY susdat_category_substance.category_id) AS category_ids"))
      ->groupBy($columns);

    // dd($substancesSearch, $subquery->toSql(), $substances->get());

    if (!is_null($request->input('order_by_column')) && !is_null($request->input('order_by_direction'))) {
      $substances = $substances->orderBy('t.' . $columns[$request->input('order_by_column')], $this->orderByList($request->input('order_by_direction')));
    } else {
      $substances = $substances->orderBy('t.id', 'desc');
    }

    $substances = $substances->paginate(30)->withQueryString();


    $sourceIds = Substance::leftJoin('susdat_source_substance', 'susdat_source_substance.substance_id', '=', 'susdat_substances.id')
      ->whereIn('susdat_source_substance.substance_id', $substances->pluck('id'))
      ->select([
        'susdat_source_substance.substance_id AS id',
      ])
      ->selectRaw(("STRING_AGG(susdat_source_substance.source_id::text, '|' ORDER BY susdat_source_substance.source_id) AS source_ids"))
      ->groupBy('substance_id')
      ->get()->keyBy('id')->toArray();

    $filter['order_by_direction'] = $this->orderByList($request->input('order_by_direction')) ?? null;
    $filter['order_by_column'] = $columns[$request->input('order_by_column')] ?? null;


    // prepare list for multiple selects
    $sources = SuspectListExchangeSource::select('id', 'code', 'name')->get()->keyBy('id');
    $categories = Category::select('id', 'name', 'abbreviation')->get()->keyBy('id');
    $sourcesList = [];
    $categoriesList = [];
    foreach ($sources as $s) {
      $sourceList[$s->id] = $s->code . ' - ' . $s->name;
    }

    foreach ($categories as $s) {
      $categoriesList[$s->id] = $s->name;
    }

    return view('susdat.index', [
      'columns' => $this->getViewColumns(),
      'substances' => $substances,
      'substancesCount' => $substancesCount,
      'request' => $request,
      'sourceIds' => $sourceIds,
      'activeCategoryids' => $categoriesSearch,
      'activeSourceids' => $sourcesSearch,
      'sources' => $sources,
      'sourceList' => $sourceList,
      'categories' => $categories,
      'categoriesList' => $categoriesList,
      'orderByDirection' => $this->orderByList(),
      'filter' => $filter,
    ]);
  }

  protected function getEditableColumns()
  {
    return [
      'code',
      'name',
      'name_dashboard',
      'name_chemspider',
      'name_iupac',
      'cas_number',
      'smiles',
      'smiles_dashboard',
      'stdinchi',
      'stdinchikey',
      'pubchem_cid',
      'chemspider_id',
      'dtxid',
      'molecular_formula',
      'mass_iso',
      'metadata_synonyms',
      'metadata_cas',
      'metadata_ms_ready',
      'metadata_general',
    ];
  }

  private function getSelectColumns()
  {
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

  private function getViewColumns()
  {
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
  
  
  // SELECT suspect_list_exchanges.substance_id, STRING_AGG(suspect_list_exchange_sources.code, ',' ORDER BY source_id) AS source_ids FROM suspect_list_exchanges JOIN suspect_list_exchange_sources ON suspect_list_exchange_sources.id = suspect_list_exchanges.source_id WHERE substance_id IS NOT NULL GROUP BY substance_id