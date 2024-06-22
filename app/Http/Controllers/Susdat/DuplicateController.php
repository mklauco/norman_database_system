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
        $substancesCount = Substance::count();

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

        $pivot = 'stdinchikey';
        
        $duplicates = Substance::select($pivot)
        ->selectRaw('count(*) as count')
        ->groupBy($pivot)
        ->havingRaw('count(*) > 1')
        ->get();
        
        $substances = Substance::whereIn($pivot, $duplicates->pluck($pivot)->filter())->orderBy($pivot, 'asc')
        ->paginate(10)->withQueryString();
        
        $sources = SuspectListExchangeSource::select('id', 'code', 'name')->get()->keyBy('id');
        $categories = Category::select('id', 'name', 'abbreviation')->get()->keyBy('id');
        
        return view('susdat.duplicates.index', [
            'substances'        => $substances,
            'substancesCount'   => $substancesCount,
            'columns'           => $columns,
            'categories'        => $categories,
            'sources'           => $sources,
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
}
