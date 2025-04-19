<?php

namespace App\Http\Controllers\Bioassay;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Models\Backend\QueryLog;
use App\Models\Bioassay\FieldStudy;
use App\Http\Controllers\Controller;
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
    
    public function filter(){
        $countryList = FieldStudy::join('bioassay_monitor_sample_data', 'bioassay_field_studies.m_sd_id', '=', 'bioassay_monitor_sample_data.id')
        ->join('monitor_x_country', 'bioassay_monitor_sample_data.m_country_id', '=', 'monitor_x_country.id')
        ->select('monitor_x_country.id', 'monitor_x_country.name')
        ->distinct()
        ->orderBy('monitor_x_country.name')
        ->pluck('monitor_x_country.name', 'monitor_x_country.id')
        ->toArray();
        
        $bioassayNameList = MonitorXBioassayName::orderBy('name')->pluck('name', 'id')->toArray();
        $endpointList = MonitorXEndpoint::orderBy('name')->pluck('name', 'id')->toArray();
        $determinandList = MonitorXMainDeterminand::orderBy('name')->pluck('name', 'id')->toArray();
        
        return view('bioassay.filter', [
            'countryList' => $countryList,
            'bioassayNameList' => $bioassayNameList,
            'endpointList' => $endpointList,
            'determinandList' => $determinandList,
        ]);
    }
    
    
    
    public function search(Request $request){
        
        // Define the input fields to process
        $searchFields = ['countrySearch', 'bioassayNameSearch', 'endpointSearch', 'determinandSearch'];
        // $searchFields = ['countrySearch'];
        
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
            : json_decode($request->input($field), true);
            
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
            $searchParameters['countrySearch'] = $countrySearch;
        }

        if (!empty($bioassayNameSearch)) {
            $resultsObjects = $resultsObjects->whereHas('bioassayName', function($query) use ($bioassayNameSearch) {
                $query->whereIn('id', $bioassayNameSearch);
            });
            $searchParameters['bioassayNameSearch'] = $bioassayNameSearch;
        }
        if (!empty($endpointSearch)) {
            $resultsObjects = $resultsObjects->whereHas('endpoint', function($query) use ($endpointSearch) {
                $query->whereIn('id', $endpointSearch);
            });
            $searchParameters['endpointSearch'] = $endpointSearch;
        }
        if (!empty($determinandSearch)) {
            $resultsObjects = $resultsObjects->whereHas('mainDeterminand', function($query) use ($determinandSearch) {
                $query->whereIn('id', $determinandSearch);
            });
            $searchParameters['determinandSearch'] = $determinandSearch;
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
        
        $database_key = 'bioassay';
        $resultsObjectsCount = DatabaseEntity::where('code', $database_key)->first()->number_of_records ?? 0;
        
        $main_request = [
            'countrySearch'                   => $countrySearch,
            'displayOption'                   => $request->input('displayOption'),
            'year_from'                       => $request->input('year_from'),
            'year_to'                         => $request->input('year_to'),
        ];
        
        // dd($resultsObjects[0], $countrySearch);
        
        return view('bioassay.index', [
            'resultsObjects' => $resultsObjects,
            'resultsObjectsCount' => $resultsObjectsCount,
            'query_log_id' => QueryLog::orderBy('id', 'desc')->first()->id,
            'request' => $request,
            'searchParameters' => $searchParameters,
        ], $main_request);
    }
}
