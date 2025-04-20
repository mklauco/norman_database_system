<?php

namespace App\Http\Controllers\Bioassay;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Backend\QueryLog;
use App\Models\Bioassay\FieldStudy;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Bioassay\MonitorXCountry;
use App\Models\Bioassay\MonitorXEndpoint;
use App\Models\Bioassay\MonitorXBioassayName;
use App\Models\Bioassay\MonitorXMainDeterminand;

class BioassayController extends Controller
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
        $countryList = FieldStudy::join('bioassay_monitor_sample_data', 'bioassay_field_studies.m_sd_id', '=', 'bioassay_monitor_sample_data.id')
        ->join('monitor_x_country', 'bioassay_monitor_sample_data.m_country_id', '=', 'monitor_x_country.id')
        ->select('monitor_x_country.id', 'monitor_x_country.name')
        ->distinct()
        ->orderBy('monitor_x_country.name')
        ->pluck('monitor_x_country.name', 'monitor_x_country.id')
        ->toArray();
        
        $bioassayNameList = MonitorXBioassayName::orderBy('name')->pluck('name', 'id')->toArray();
        $endpointList     = MonitorXEndpoint::orderBy('name')->pluck('name', 'id')->toArray();
        $determinandList  = MonitorXMainDeterminand::orderBy('name')->pluck('name', 'id')->toArray();
        
        return view('bioassay.filter', [
            'request' => $request,
            'countryList'      => $countryList,
            'bioassayNameList' => $bioassayNameList,
            'endpointList'     => $endpointList,
            'determinandList'  => $determinandList,
        ]);
    }
    
    
    
    public function search(Request $request){
        
        // Define the input fields to process
        $searchFields = ['countrySearch', 'bioassayNameSearch', 'endpointSearch', 'determinandSearch'];
        
        // Process each field with the same logic
        /* 
        ORIGINAL CODE
        if(is_array($request->input('countrySearch'))){
        $countrySearch = $request->input('countrySearch');
        } else{
        $countrySearch = json_decode($request->input('countrySearch'));
        }
        END ORIGINAL CODE
        */
        foreach ($searchFields as $field) {
            ${$field} = is_array($request->input($field))
            ? $request->input($field) 
            :  json_decode($request->input($field), true);
            
            // Ensure we have an array even if json_decode returns null
            // if (!is_array(${$field})) {
            //     ${$field} = 'a';
            // }
        }
        
        // dd($request->all(), ${$field});
        $resultsObjects = FieldStudy::with(['sampleData.country', 'sampleData.dataSource', 'bioassayName', 'endpoint', 'mainDeterminand']);
        
        $searchParameters = [];
        if (!empty($countrySearch)) {
            $resultsObjects = $resultsObjects->whereHas('sampleData.country', function($query) use ($countrySearch) {
                $query->whereIn('id', $countrySearch);
            });
            $searchParameters['countrySearch'] = MonitorXCountry::whereIn('id', $countrySearch)->pluck('name');
        }
        
        if (!empty($bioassayNameSearch)) {
            $resultsObjects = $resultsObjects->whereHas('bioassayName', function($query) use ($bioassayNameSearch) {
                $query->whereIn('id', $bioassayNameSearch);
            });
            $searchParameters['bioassayNameSearch'] = MonitorXBioassayName::whereIn('id',$bioassayNameSearch)->pluck('name');
        }
        if (!empty($endpointSearch)) {
            $resultsObjects = $resultsObjects->whereHas('endpoint', function($query) use ($endpointSearch) {
                $query->whereIn('id', $endpointSearch);
            });
            $searchParameters['endpointSearch'] = MonitorXEndpoint::whereIn('id', $endpointSearch)->pluck('name');
        }
        if (!empty($determinandSearch)) {
            $resultsObjects = $resultsObjects->whereHas('mainDeterminand', function($query) use ($determinandSearch) {
                $query->whereIn('id', $determinandSearch);
            });
            $searchParameters['determinandSearch'] = MonitorXMainDeterminand::whereIn('id', $determinandSearch)->pluck('name');
        }
        
        if (!is_null($request->input('year_from'))) {
            $resultsObjects                = $resultsObjects->where('date_performed_year', '>=', $request->input('year_from'));
            $searchParameters['year_from'] = $request->input('year_from');
        }
        if (!is_null($request->input('year_to'))) {
            $resultsObjects              = $resultsObjects->where('date_performed_year', '<=', $request->input('year_to'));
            $searchParameters['year_to'] = $request->input('year_to');
        }
        
        $main_request = [
            'countrySearch'      => $countrySearch,
            'bioassayNameSearch' => $bioassayNameSearch,
            'endpointSearch'     => $endpointSearch,
            'determinandSearch'  => $determinandSearch,
            'displayOption'      => $request->input('displayOption'),
            'year_from'          => $request->input('year_from'),
            'year_to'            => $request->input('year_to'),
        ];
        
        $database_key        = 'bioassay';
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
            $resultsObjects = $resultsObjects->orderBy('bioassay_field_studies.id', 'asc')
            ->simplePaginate(200)
            ->withQueryString();
        } else {
            // use cursor pagination
            $resultsObjects = $resultsObjects->orderBy('bioassay_field_studies.id', 'asc')
            ->paginate(200)
            ->withQueryString();
        }

    
        
        // dd($resultsObjects[0], $countrySearch);
        // dd($searchParameters);
        return view('bioassay.index', [
            'resultsObjects'      => $resultsObjects,
            'resultsObjectsCount' => $resultsObjectsCount,
            'query_log_id'        => QueryLog::orderBy('id', 'desc')->first()->id,
            'request'             => $request,
            'searchParameters'    => $searchParameters,
        ], $main_request);
    }
}
