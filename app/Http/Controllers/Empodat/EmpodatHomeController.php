<?php

namespace App\Http\Controllers\Empodat;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Backend\Template;
use App\Http\Controllers\Controller;

class EmpodatHomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return view('empodat.codhome.index');
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

    public function specificIndex($code)
    {
        // Find the database entity by code
        $databaseEntity = DatabaseEntity::where('code', $code)->firstOrFail();
        
        // Get active templates for this database entity
        $templates = Template::with(['databaseEntity', 'creator'])
            ->where('database_entity_id', $databaseEntity->id)
            ->where('is_active', true)
            ->orderBy('valid_from', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('backend.templates.specific_index', compact('templates', 'databaseEntity'));
    }
}
