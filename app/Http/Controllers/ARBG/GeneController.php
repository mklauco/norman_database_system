<?php

namespace App\Http\Controllers\ARBG;

use Illuminate\Http\Request;
use App\Models\ARBG\GeneMain;
use App\Models\DatabaseEntity;
use App\Models\Backend\QueryLog;
use App\Models\ARBG\GeneDataSource;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\ARBG\DataSampleMatrix;
use App\Models\ARBG\GeneCoordinate;

class GeneController extends Controller
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
    
    public function filter(Request $request)
    {
        // Get distinct countries from gene records
        $countryIds = GeneMain::join('arbg_gene_coordinates', 'arbg_gene_main.coordinate_id', '=', 'arbg_gene_coordinates.id')
        ->whereNotNull('arbg_gene_coordinates.country_id')
        ->where('arbg_gene_coordinates.country_id', '!=', '')
        ->distinct()
        ->pluck('arbg_gene_coordinates.country_id');
        
        $countryList = GeneCoordinate::whereIn('country_id', $countryIds)
        ->orderBy('country_id')
        ->pluck('country_id', 'country_id')
        ->toArray();
        
        // Get distinct sample matrices using the relationship
        $matrixIds = GeneMain::whereNotNull('sample_matrix_id')
        ->distinct()
        ->pluck('sample_matrix_id');
        
        $matrixList = DataSampleMatrix::whereIn('id', $matrixIds)
        ->orderBy('name')
        ->pluck('name', 'id')
        ->toArray();
        
        // Get distinct organizations using the relationship
        $sourceIds = GeneMain::whereNotNull('source_id')
        ->distinct()
        ->pluck('source_id');
        
        $organisationList = GeneDataSource::whereIn('id', $sourceIds)
        ->whereNotNull('organisation')
        ->orderBy('organisation')
        ->pluck('organisation', 'organisation')
        ->toArray();
        
        // Get distinct gene names
        $geneNameList = GeneMain::whereNotNull('gene_name')
        ->distinct()
        ->orderBy('gene_name')
        ->pluck('gene_name')
        ->toArray();
        
        // Get all sampling years
        $yearList = GeneMain::whereNotNull('sampling_date_year')
        ->where('sampling_date_year', '>', 0)
        ->distinct()
        ->orderBy('sampling_date_year', 'desc')
        ->pluck('sampling_date_year')
        ->toArray();
        
        return view('arbg.gene.filter', [
            'request' => $request,
            'countryList' => $countryList,
            'matrixList' => $matrixList,
            'organisationList' => $organisationList,
            'geneNameList' => $geneNameList,
            'yearList' => $yearList
        ]);
    }
    
    public function search(Request $request){
        
        // Define the input fields to process
        $searchFields = ['countrySearch', 'matrixSearch', 'organisationSearch', 'geneNameSearch'];
        
        // Process each field with the same logic
        foreach ($searchFields as $field) {
            ${$field} = is_array($request->input($field))
            ? $request->input($field) 
            : json_decode($request->input($field), true);
        }
        
        $resultsObjects = GeneMain::with([
            'coordinate', 
            'sampleMatrix',
            'source'
        ]);
        
        $searchParameters = [];
        
        // Filter by country
        if (!empty($countrySearch)) {
            $resultsObjects = $resultsObjects->whereHas('coordinate', function($query) use ($countrySearch) {
                $query->whereIn('country_id', $countrySearch);
            });
            $searchParameters['countrySearch'] = GeneCoordinate::whereIn('country_id', $countrySearch)
            ->distinct()->pluck('country_id');
        }
        
        // Filter by sample matrix
        if (!empty($matrixSearch)) {
            $resultsObjects = $resultsObjects->whereIn('sample_matrix_id', $matrixSearch);
            $searchParameters['matrixSearch'] = DataSampleMatrix::whereIn('id', $matrixSearch)
            ->pluck('name');
        }
        
        // Filter by organisation
        if (!empty($organisationSearch)) {
            $resultsObjects = $resultsObjects->whereHas('source', function($query) use ($organisationSearch) {
                $query->whereIn('organisation', $organisationSearch);
            });
            $searchParameters['organisationSearch'] = GeneDataSource::whereIn('id', $organisationSearch)
            ->pluck('organisation');
        }
        
        // Filter by gene name
        if (!empty($geneNameSearch)) {
            $resultsObjects = $resultsObjects->whereIn('gene_name', $geneNameSearch);
            $searchParameters['geneNameSearch'] = $geneNameSearch;
        }
        
        // Filter by sampling year range
        if (!is_null($request->input('year_from'))) {
            $resultsObjects = $resultsObjects->where('sampling_date_year', '>=', $request->input('year_from'));
            $searchParameters['year_from'] = $request->input('year_from');
        }
        
        if (!is_null($request->input('year_to'))) {
            $resultsObjects = $resultsObjects->where('sampling_date_year', '<=', $request->input('year_to'));
            $searchParameters['year_to'] = $request->input('year_to');
        }
        
        $main_request = $request->all();
        
        $database_key        = 'arbg.gene';
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
        
        return view('arbg.gene.index', [
            'resultsObjects'      => $resultsObjects,
            'resultsObjectsCount' => $resultsObjectsCount,
            'query_log_id'        => QueryLog::orderBy('id', 'desc')->first()->id,
            'request'             => $request,
            'searchParameters'    => $searchParameters,
        ], $main_request);
    }
}
