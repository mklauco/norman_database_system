<?php

namespace App\Http\Controllers\Ecotox;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ecotox\LowestPNEC;
use App\Models\Ecotox\LowestPNECMain;
use App\Models\Ecotox\PNEC3;
use App\Models\Susdat\Substance;
use App\Models\DatabaseEntity;
use App\Models\Backend\QueryLog;
use Illuminate\Support\Facades\Auth;

class LowestPNECController extends Controller
{
    /**
     * Display a listing of the LowestPNEC resources.
     */
    public function index()
    {
        $lowestPnecs = LowestPNEC::with('substance')
            ->orderBy('id')
            ->paginate(50);
            
        return view('ecotox.lowestpnec.index', [
            'lowestPnecs' => $lowestPnecs,
            'displayOption' => 0,
        ]);
    }

    /**
     * Show the form for filtering LowestPNEC records.
     */
    public function filter(Request $request)
    {
        // Fetch the substance list for filtering
        $substanceList = Substance::whereIn('id', function($query) {
                $query->select('substance_id')
                    ->from('ecotox_lowest_pnec')
                    ->whereNotNull('substance_id');
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
            
        return view('ecotox.lowestpnec.filter', [
            'request' => $request,
            'substanceList' => $substanceList,
        ]);
    }

    /**
     * Search for LowestPNEC records based on filter criteria.
     */
    public function search(Request $request)
    {
        // Define the input fields to process
        $searchFields = ['substance_search', 'pnec_type', 'exp_pred'];
        
        // Process each field with the same logic
        foreach ($searchFields as $field) {
            ${$field} = is_array($request->input($field))
                ? $request->input($field) 
                : json_decode($request->input($field), true);
        }
        
        $searchParameters = [];
        
        $resultsObjects = LowestPNEC::with('substance');
        
        // Apply substance filter
        if (!empty($substance_search)) {
            $resultsObjects = $resultsObjects->whereIn('substance_id', $substance_search);
            $searchParameters['substance_search'] = Substance::whereIn('id', $substance_search)->pluck('name');
        }
        
        // Apply PNEC type filter (1-8 for different PNEC types)
        if (!empty($pnec_type)) {
            $resultsObjects = $resultsObjects->where(function($query) use ($pnec_type) {
                foreach ($pnec_type as $type) {
                    $query->orWhereNotNull('lowest_pnec_value_' . $type);
                }
            });
            $searchParameters['pnec_type'] = $pnec_type;
        }
        
        // Apply exp_pred filter
        if (!empty($exp_pred)) {
            $resultsObjects = $resultsObjects->whereIn('lowest_exp_pred', $exp_pred);
            $searchParameters['exp_pred'] = $exp_pred;
        }
        
        // Apply PNEC value range filter
        if ($request->has('pnec_min') && $request->pnec_min !== null) {
            $resultsObjects = $resultsObjects->where(function($query) use ($request) {
                for ($i = 1; $i <= 8; $i++) {
                    $query->orWhere('lowest_pnec_value_' . $i, '>=', $request->pnec_min);
                }
            });
            $searchParameters['pnec_min'] = $request->pnec_min;
        }
        
        if ($request->has('pnec_max') && $request->pnec_max !== null) {
            $resultsObjects = $resultsObjects->where(function($query) use ($request) {
                for ($i = 1; $i <= 8; $i++) {
                    $query->orWhere('lowest_pnec_value_' . $i, '<=', $request->pnec_max);
                }
            });
            $searchParameters['pnec_max'] = $request->pnec_max;
        }
        
        $main_request = $request->all();
        
        $database_key = 'ecotox';
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
        
        return view('ecotox.lowestpnec.index', [
            'lowestPnecs'        => $resultsObjects,
            'resultsObjectsCount' => $resultsObjectsCount,
            'query_log_id'        => QueryLog::orderBy('id', 'desc')->first()->id ?? null,
            'request'             => $request,
            'searchParameters'    => $searchParameters,
            'displayOption'       => $request->displayOption,
        ], $main_request);
    }

    /**
     * Display the specified resource as JSON.
     */
    public function show($id)
    {
        $lowestPnec = LowestPNEC::with('substance')->findOrFail($id);
        
        // Find the related LowestPNECMain record if exists
        $lowestPnecMain = LowestPNECMain::with(['substance', 'editor'])
            ->where('sus_id', $lowestPnec->sus_id)
            ->first();
            
        // Prepare the response data
        $responseData = $lowestPnec->toArray();
        
        // Add the main record data if available
        if ($lowestPnecMain) {
            $responseData['main_record'] = $lowestPnecMain->toArray();
            
            // If editor info is available, include it
            if ($lowestPnecMain->editor) {
                $responseData['editor'] = [
                    'id' => $lowestPnecMain->editor->id,
                    'name' => $lowestPnecMain->editor->name,
                ];
            }
            
            // Look up PNEC3 record if we have the base_id
            if ($lowestPnecMain->lowest_base_id) {
                $pnec3 = PNEC3::where('norman_pnec_id', $lowestPnecMain->lowest_base_id)->first();
                if ($pnec3) {
                    $responseData['pnec3'] = $pnec3->toArray();
                }
            }
        }
        
        return response()->json($responseData);
    }
}