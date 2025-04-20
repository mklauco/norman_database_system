<?php

namespace App\Http\Controllers\Bioassay;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Bioassay\FieldStudy;
use App\Http\Controllers\Controller;

class BioassayHomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return view('bioassay.home.index');

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

    /**ß
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function countAll()
    {
        DatabaseEntity::where('code', 'bioassay')->update([
            'last_update' => FieldStudy::max('updated_at'),
            'number_of_records' => FieldStudy::count()
        ]);
        session()->flash('success', 'Database counts updated successfully');
        return redirect()->back();
    }
}
