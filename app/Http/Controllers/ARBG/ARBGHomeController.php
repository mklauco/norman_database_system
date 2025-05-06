<?php

namespace App\Http\Controllers\ARBG;

use Illuminate\Http\Request;
use App\Models\ARBG\GeneMain;
use App\Models\DatabaseEntity;
use App\Models\ARBG\BacteriaMain;
use App\Http\Controllers\Controller;

class ARBGHomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return view('arbg.home.index');
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

    public function countAll()
    {
        $bacteriaCount = BacteriaMain::count();
        $geneCount = GeneMain::count();
        $totalCount = $bacteriaCount + $geneCount;
        
        // Get the latest update timestamp from either bacteria or genes
        $latestBacteriaUpdate = BacteriaMain::max('updated_at');
        $latestGeneUpdate = GeneMain::max('updated_at');
        $lastUpdate = $latestBacteriaUpdate > $latestGeneUpdate ? $latestBacteriaUpdate : $latestGeneUpdate;
        
        DatabaseEntity::where('code', 'arbg')->update([
            'last_update' => $lastUpdate,
            'number_of_records' => $totalCount
        ]);
        
        session()->flash('success', 'Database counts updated successfully');
        return redirect()->back();
    }

    public function countAllBacteria()
    {
        DatabaseEntity::where('code', 'arbg.bacteria')->update([
            'last_update' => BacteriaMain::max('updated_at'),
            'number_of_records' => BacteriaMain::count()
        ]);
        session()->flash('success', 'Database counts updated successfully');
        return redirect()->back();
    }

    public function countAllGene()
    {
        DatabaseEntity::where('code', 'arbg.gene')->update([
            'last_update' => GeneMain::max('updated_at'),
            'number_of_records' => GeneMain::count()
        ]);
        session()->flash('success', 'Database counts updated successfully');
        return redirect()->back();
    }

}
