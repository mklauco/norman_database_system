<?php

namespace App\Http\Controllers\Empodat;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Empodat\DCTItem;
use App\Http\Controllers\Controller;

class DCTItemController extends Controller
{
    /**
    * Display a listing of the resource.
    */
    public function index()
    {
        //
        $dctitems = DCTItem::all();
        return view('empodat.dctitems.index', [
            'dctitems' => $dctitems
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
        return view('empodat.dctitems.edit', [
            'dctitem' => DCTItem::find($id)
        ]);
    }
    
    /**
    * Update the specified resource in storage.
    */
    public function update(Request $request, string $id)
    {
        //
        if($request->hasFile('file')) {            
            // $fileName = $request->file('file')->getClientOriginalName();
            $fileName = 'dct_' . str_replace(' ', '', ucwords($request->name)) .'_'. Carbon::now()->format('Y-m-d') . '.' . $request->file('file')->extension();
            $path = $request->file('file')->storeAs('empodat/data_collection_templates', $fileName);
        }
        $dctitem = DCTItem::find($id);
        $dctitem->name = $request->name;
        $dctitem->file_path = $path;
        try {
            $dctitem->save();
            return redirect()->route('dctitems.index')->with('success', 'Data Collection Template updated successfully');
        } catch (\Exception $e) {
            return redirect()->route('dctitems.index')->with('error', 'Data Collection Template could not be updated');
        }
    }
    
    /**
    * Remove the specified resource from storage.
    */
    public function destroy(string $id)
    {
        //
    }
}
