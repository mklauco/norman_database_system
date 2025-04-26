<?php

namespace App\Http\Controllers\Indoor;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Indoor\IndoorMain;
use App\Http\Controllers\Controller;

class IndoorHomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return view('indoor.home.index');
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
        DatabaseEntity::where('code', 'indoor')->update([
            'last_update' => IndoorMain::max('updated_at'),
            'number_of_records' => IndoorMain::count()
        ]);
        session()->flash('success', 'Database counts updated successfully');
        return redirect()->back();
    }
}
