<?php

namespace App\Http\Controllers\ARBG;

use App\Actions\RefreshDatabaseEntityCountsAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
        app(RefreshDatabaseEntityCountsAction::class)->handle(['arbg']);

        session()->flash('success', 'Database counts updated successfully');

        return redirect()->back();
    }

    public function countAllBacteria()
    {
        app(RefreshDatabaseEntityCountsAction::class)->handle(['arbg.bacteria']);
        session()->flash('success', 'Database counts updated successfully');

        return redirect()->back();
    }

    public function countAllGene()
    {
        app(RefreshDatabaseEntityCountsAction::class)->handle(['arbg.gene']);
        session()->flash('success', 'Database counts updated successfully');

        return redirect()->back();
    }
}
