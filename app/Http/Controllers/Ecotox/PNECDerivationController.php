<?php

namespace App\Http\Controllers\Ecotox;

use App\Http\Controllers\Controller;
use App\Models\Backend\QueryLog;
use App\Models\DatabaseEntity;
use App\Models\Ecotox\PNEC3;
use App\Models\Susdat\Substance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PNECDerivationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return redirect()->route('ecotox.pnecderivation.search.filter');
    }

    public function filter(Request $request)
    {
        return view('ecotox.pnecderivation.filter', [
            'request' => $request,
        ]);
    }

    public function search(Request $request)
    {
        // Initialize search parameters array to track what filters were applied
        $searchParameters = [];

        // Start with a base query with necessary relationships
        $resultsObjects = PNEC3::with([
            'substance',
        ])->whereIn('use_study', ['y', 'Y', 'yes', 'YES', 'Yes'])->orderBy('id', 'asc');

        // Apply substance filter (this is the primary filter)
        if (! empty($request->input('substances'))) {
            $substances = $request->input('substances');
            // Handle case when substances is a string (JSON)
            if (is_string($substances)) {
                $substances = json_decode($substances, true);
            }
            // Ensure substances is always an array
            if (! is_array($substances)) {
                $substances = [$substances];
            }
            $resultsObjects = $resultsObjects->whereIn('substance_id', $substances);
            $searchParameters['substances'] = Substance::whereIn('id', $substances)->pluck('name');
        } else {
            // If no substances specified, merge empty array to avoid errors
            $request->merge(['substances' => []]);
            // Return early as we require at least one substance
            session()->flash('info', 'Please select at least one substance to search.');

            return redirect()->route('ecotox.pnecderivation.search.filter');
        }

        // Get the full request data for logging
        $main_request = $request->all();

        // Get total count from database entity
        $database_key = 'ecotox.pnec3';
        $resultsObjectsCount = DatabaseEntity::where('code', $database_key)->first()->number_of_records ?? 0;

        // Log the query if this is the first page request
        if (! $request->has('page')) {
            $now = now();
            $bindings = $resultsObjects->getBindings();
            $sql = vsprintf(str_replace('?', "'%s'", $resultsObjects->toSql()), $bindings);

            // Try to find the same SQL query in the QueryLog table
            $actual_count = QueryLog::where('query_hash', hash('sha256', $sql))
                ->where('total_count', $resultsObjectsCount)
                ->value('actual_count');

            try {
                QueryLog::insert([
                    'content' => json_encode(['request' => $main_request, 'bindings' => $bindings]),
                    'query' => $sql,
                    'user_id' => Auth::check() ? Auth::id() : null,
                    'total_count' => $resultsObjectsCount,
                    'actual_count' => is_null($actual_count) ? $resultsObjects->count() : $actual_count,
                    'database_key' => $database_key,
                    'query_hash' => hash('sha256', $sql),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } catch (\Exception $e) {
                if (Auth::check()) {
                    session()->flash('error', 'An error occurred while processing your request.');
                }
            }
        }

        // Apply pagination based on display option
        if ($request->input('displayOption') == 1) {
            // Use simple pagination
            $resultsObjects = $resultsObjects->orderBy('id', 'asc')
                ->simplePaginate(200)
                ->withQueryString();
        } else {
            // Use cursor pagination
            $resultsObjects = $resultsObjects->orderBy('id', 'asc')
                ->paginate(200)
                ->withQueryString();
        }

        // Return the view with results and metadata
        return view('ecotox.pnecderivation.index', [
            'resultsObjects' => $resultsObjects,
            'resultsObjectsCount' => $resultsObjectsCount,
            'query_log_id' => QueryLog::orderBy('id', 'desc')->first()->id ?? 0,
            'request' => $request,
            'searchParameters' => $searchParameters,
        ], $main_request);
    }

    public function countAll()
    {
        DatabaseEntity::where('code', 'ecotox.pnec3')->update([
            'last_update' => PNEC3::max('updated_at'),
            'number_of_records' => PNEC3::count(),
        ]);
        session()->flash('success', 'Database counts updated successfully');

        return redirect()->back();
    }

    /**
     * Save quality votes for PNEC records
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveQualityVotes(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'votes' => 'required|array',
            'votes.*' => 'nullable|in:I,N,1,2,3',
        ]);

        $votes = $validated['votes'];

        // TODO: Once the database is ready, implement the actual saving logic
        // Example implementation (uncomment when database is ready):
        // foreach ($votes as $pnecId => $vote) {
        //     PNEC3::where('id', $pnecId)->update(['quality_vote' => $vote]);
        // }

        // For now, just return success with the votes count
        return response()->json([
            'success' => true,
            'message' => 'Quality votes prepared for saving',
            'count' => count($votes),
            'votes' => $votes,
        ]);
    }
}
