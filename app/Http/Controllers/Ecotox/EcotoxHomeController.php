<?php

namespace App\Http\Controllers\Ecotox;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Ecotox\EcotoxSubstanceDistinct;

class EcotoxHomeController extends Controller
{
    /**
    * Display a listing of the resource.
    */
    public function index()
    {
        //
        return view('ecotox.home.index');
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
    
    public function syncNewSubstances()
    {
        {
            try {
                // Call the static method from the model
                $newCount = EcotoxSubstanceDistinct::syncNewSubstances();
                
                // Return success response
                session()->flash('success', 'Database counts updated successfully');
                return redirect()->back();
            } catch (\Exception $e) {
                // Return error response
                session()->flash('failure', 'Query logging error: ' . $e->getMessage());
                return redirect()->back();
            }
        }
    }
}
