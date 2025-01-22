<?php

namespace App\Http\Controllers\Empodat;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Http\Controllers\Controller;
use Illuminate\Container\Attributes\Database;
use App\Models\Empodat\DataCollectionTemplate;
use App\Models\Empodat\DataCollectionTemplateFile;

class DataCollectionTemplateFileController extends Controller
{
  /**
  * Display a listing of the resource.
  */
  public function index()
  {
    //
    $dctitems = DataCollectionTemplate::with('files')->orderBy('id', 'desc')->get();
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
    return view('empodat.dctitems.edit', [
      'edit' => false
    ]);
  }
  
  /**
  * Store a newly created resource in storage.
  */
  public function store(Request $request)
  {
    //
    $validation = [
      'name' => 'required',
      'description' => 'required'
    ];
    
    $request->validate($validation);
    
    $dctitem = New DataCollectionTemplate();
    $dctitem->name = $request->name;
    $dctitem->description = $request->description;
    $dctitem->database_entity_id = DatabaseEntity::where('code', 'empodat')->first()->id;
    try {
      $dctitem->save();
      return redirect()->route('dctitems.index')->with('success', 'Data Collection Template created successfully');
    } catch (\Exception $e) {
      return redirect()->route('dctitems.index')->with('error', 'Data Collection Template could not be created');
    }
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
      'dctitem' => DataCollectionTemplate::find($id),
      'edit' => true
    ]);
  }
  
  /**
  * Update the specified resource in storage.
  */
  public function update(Request $request, string $id)
  {
    //
    $validation = [
      'name' => 'required',
      'description' => 'required'
    ];
    
    $request->validate($validation);
    
    $dctitem                = DataCollectionTemplate::find($id);
    $dctitem->name          = $request->name;
    $dctitem->description   = $request->description;
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
  
  public function uploadNewTemplate($dctitem_id)
  {
    return view('empodat.dctitems.uploadNewTemplate', [
      'dctitem' => DataCollectionTemplate::find($dctitem_id)
    ]);
  }
  
  public function storeNewTemplate(Request $request, $dctitem_id)
  {
    
    $dctitem = DataCollectionTemplate::find($dctitem_id);
    
    if($request->hasFile('file')) {            
      // $fileName = $request->file('file')->getClientOriginalName();
      $fileName = 'dct_' . lcfirst(str_replace(' ', '', ucwords($dctitem->name))) .'_'. Carbon::now()->format('Y-m-dTGis') . '.' . $request->file('file')->extension();
      $path = $request->file('file')->storeAs('empodat/data_collection_templates', $fileName);
    } else {
      return redirect()->route('dctitems.index')->with('error', 'Data Collection Template could not be uploaded');
    }
    
    $dctFile = New DataCollectionTemplateFile();
    
    try {
      $dctFile->create([
        'data_collection_template_id'   => $dctitem_id,
        'path'                          => $path,
        'filename'                      => $fileName
      ]);
      return redirect()->route('dctitems.index')->with('success', 'Data Collection Template uploaded successfully');
    } catch (\Exception $e) {
      return redirect()->route('dctitems.index')->with('error', 'Data Collection Template could not be uploaded');
    }
    
    return redirect()->back();
  }
  
  public function downloadTemplate($id){
    $dctFile = DataCollectionTemplateFile::find($id);
    return response()->download(storage_path('app/' . $dctFile->path));
    
  }
  
  public function indexFiles($dctitem_id){
    $dctitem = DataCollectionTemplate::find($dctitem_id);
    $files = DataCollectionTemplateFile::where('data_collection_template_id', $dctitem_id)->orderBy('updated_at', 'desc')->get();
    return view('empodat.dctitems.indexFiles', [
      'files' => $files,
      'dctitem' => $dctitem
    ]);
  }
  
  public function destroyFile($id){
    $dctFile = DataCollectionTemplateFile::find($id);
    $dctFile->delete();
    return redirect()->back();
  }
}
