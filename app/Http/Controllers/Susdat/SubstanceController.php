<?php

namespace App\Http\Controllers\Susdat;

use Illuminate\Http\Request;
use App\Models\Susdat\Category;
use App\Models\Susdat\Substance;
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
    $substances = Substance::paginate(50);
    return view('susdat.index', [
      'substances' => $substances,
      'substancesCount' => $substances->total(),
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
    $categories = Category::all();
    return view('susdat.filter', [
      'categories' => $categories
    ]);
  }

  public function search(Request $request)
  {
    $substancesCount = Substance::count();
    $categoriesSearch = $request->input('category');
    $substances = Substance::wherehas('categories', function(Builder $query) use ($categoriesSearch) {
      $query->whereIn('susdat_categories.id', $categoriesSearch);
    })->paginate(50);

    // dd($substances);
    return view('susdat.index', [
      'substances' => $substances,
      'substancesCount' => $substancesCount,
      'request' => $request->all(),
      'categories' => Category::select('id', 'name')->get()->keyBy('id')->toArray()
    ]);
  }
}
