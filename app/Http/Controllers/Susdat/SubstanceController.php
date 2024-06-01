<?php

namespace App\Http\Controllers\Susdat;

use Illuminate\Http\Request;
use App\Models\Susdat\Categories;
use App\Models\Susdat\Substances;
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
    $substances = Substances::all();
    return view('susdat.index', [
      'substances' => $substances
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
    $categories = Categories::all();
    return view('susdat.filter', [
      'categories' => $categories
    ]);
  }

  public function search(Request $request)
  {
    // $categories = $request->input('category');
    $categories = [1 => 1];
    $substances = Substances::with(['categories' => function(Builder $query) use ($categories) {
      $query->where('susdat_categories.id', 1);
    }])->where('id', '<=', 10)->get();
    // $substances = Substances::with(['categories'])->limit(1)->paginate(1);
    // dd($substances);
    return view('susdat.index', [
      'substances' => $substances,
      'request' => $request->all()
    ]);
  }
}
