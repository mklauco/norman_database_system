<?php

namespace App\Http\Controllers\SLE;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Http\Controllers\Controller;
use App\Models\SLE\SuspectListExchangeSource;

class SuspectListExchangeHomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $sleSources = SuspectListExchangeSource::orderBy('order', 'asc')->where('show', 1)->get();
        return view('sle.home.index', [
            'sleSources' => $sleSources,
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

    public function countAll()
    {
        DatabaseEntity::where('code', 'sle')->update([
            'last_update' => SuspectListExchangeSource::max('updated_at'),
            'number_of_records' => SuspectListExchangeSource::count()
        ]);
        session()->flash('success', 'Database counts updated successfully');
        return redirect()->back();
    }
}
