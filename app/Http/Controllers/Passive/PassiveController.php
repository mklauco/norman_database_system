<?php

namespace App\Http\Controllers\Passive;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Backend\QueryLog;
use App\Models\Susdat\Substance;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Passive\PassiveDataMatrix;
use App\Models\Passive\PassiveDataCountry;
use App\Models\Passive\PassiveSamplingMain;

class PassiveController extends Controller
{
    /**
    * Display a listing of the resource.
    */
    public function index()
    {
        //
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
    
    public function filter(Request $request){
        $countryIds = PassiveSamplingMain::distinct('country_id')
        ->whereNotNull('country_id')
        ->pluck('country_id')
        ->toArray();
        
        // Get the country names in alphabetical order
        $countryList = PassiveDataCountry::whereIn('abbreviation', $countryIds)
        ->orderBy('name')
        ->pluck('name', 'abbreviation')
        ->toArray();
        
        // Get matrices that are actually used in the main table
        $matrixIds = PassiveSamplingMain::distinct('matrix_id')
        ->whereNotNull('matrix_id')
        ->pluck('matrix_id')
        ->toArray();
        
        // Get the matrix names in alphabetical order
        $matrixList = PassiveDataMatrix::whereIn('id', $matrixIds)
        ->orderBy('name')
        ->pluck('name', 'id')
        ->toArray();
        
        return view('passive.filter', [
            'request'                 => $request,
            'countryList'             => $countryList,
            // 'environmentTypeList'     => $environmentTypeList,
            // 'environmentCategoryList' => $environmentCategoryList,
            'matrixList'              => $matrixList,
        ]);
    }
    
    public function search(Request $request){
        
        
        // Define the input fields to process
        $searchFields = ['countrySearch', 'matrixSearch' ];
        
        // Process each field with the same logic
        /* 
        See more details at BioassayController.php method search()
        */
        foreach ($searchFields as $field) {
            ${$field} = is_array($request->input($field))
            ? $request->input($field) 
            :  json_decode($request->input($field), true);
        }
        $searchParameters = [];
        
        
        
        $resultsObjects = PassiveSamplingMain::with([
            'country', 
        ]);
        
        // Apply country filter
        if (!empty($countrySearch)) {
            $resultsObjects = $resultsObjects->whereIn('country_id', $countrySearch); // TOTO JE ZLE !
            $searchParameters['countrySearch'] = PassiveDataCountry::whereIn('abbreviation', $countrySearch)->pluck('name');
        }
        
        // Apply matrix filter
        if (!empty($matrixSearch)) {
            $resultsObjects = $resultsObjects->whereIn('matrix_id', $matrixSearch);
            $searchParameters['matrixSearch'] = PassiveDataMatrix::whereIn('id', $matrixSearch)->pluck('name');
        }
        
        if (!empty($request->input('substances'))) {
            $resultsObjects = $resultsObjects->whereIn('passive_sampling_main.substance_id', $request->input('substances'));
            $searchParameters['substances'] = Substance::whereIn('id', $request->input('substances'))->pluck('name');
        } else {
            // TASK fix this:
            $request->merge(['substances' => []]);
        }
        
        $main_request = $request->all();
        
        $database_key        = 'passive';
        $resultsObjectsCount = DatabaseEntity::where('code', $database_key)->first()->number_of_records ?? 0;
        
        if(!$request->has('page')){
            $now = now();
            $bindings = $resultsObjects->getBindings();
            $sql = vsprintf(str_replace('?', "'%s'", $resultsObjects->toSql()), $bindings);
            // try to find same SQL query in the QueryLog table with same total_count based on the query_hash
            $actual_count = QueryLog::where('query_hash', hash('sha256', $sql))->where('total_count', $resultsObjectsCount)->value('actual_count');
            
            try {
                QueryLog::insert([
                    'content'      => json_encode(['request' => $main_request, 'bindings' => $bindings]),
                    'query'        => $sql,
                    'user_id'      => auth()->check() ? auth()->id() : null,
                    'total_count'  => $resultsObjectsCount,
                    'actual_count' => is_null($actual_count) ? null : $actual_count,
                    'database_key' => $database_key,
                    'query_hash'   => hash('sha256', $sql),
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            } catch (\Exception $e) {
                if (Auth::check() && Auth::user()->hasRole('super_admin')) {
                    session()->flash('failure', 'Query logging error: ' . $e->getMessage());
                } else {
                    session()->flash('error', 'An error occurred while processing your request.');
                }
            }
        }
        
        if ($request->displayOption == 1) {
            // use simple pagination
            $resultsObjects = $resultsObjects->orderBy('id', 'asc')
            ->simplePaginate(200)
            ->withQueryString();
        } else {
            // use cursor pagination
            $resultsObjects = $resultsObjects->orderBy('id', 'asc')
            ->paginate(200)
            ->withQueryString();
        }
        
        // dd($main_request);
        
        return view('passive.index', [
            'resultsObjects'      => $resultsObjects,
            'resultsObjectsCount' => $resultsObjectsCount,
            'query_log_id'        => QueryLog::orderBy('id', 'desc')->first()->id,
            'request'             => $request,
            'searchParameters'    => $searchParameters,
        ], $main_request);
        
    }
}
