<?php

namespace App\Http\Controllers\Ecotox;

use App\Http\Controllers\Controller;
use App\Models\Backend\QueryLog;
use App\Models\DatabaseEntity;
use App\Models\Ecotox\LowestPNECMain;
use App\Models\Ecotox\PNEC3;
use App\Models\Susdat\Substance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EcotoxQualityController extends Controller
{
    /**
     * Show the initial quality index page.
     */
    public function index(Request $request)
    {
        return view('ecotox.quality.index', [
            'resultsObjects' => collect(),
            'derivationObjects' => collect(),
            'lowestPnecObjects' => collect(),
            'matrixHabitatCounts' => collect(),
            'request' => $request,
            'searchParameters' => [],
            'resultsObjectsCount' => 0,
            'query_log_id' => null,
        ]);
    }

    /**
     * Show the search filter form.
     */
    public function filter(Request $request)
    {
        return view('ecotox.quality.filter', [
            'request' => $request,
        ]);
    }

    /**
     * Process the search and display results.
     */
    public function search(Request $request)
    {
        // Initialize search parameters array to track what filters were applied
        $searchParameters = [];

        // Start with a base query
        $resultsObjects = PNEC3::orderBy('norman_pnec_id', 'asc');

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

            return redirect()->route('ecotox.quality.search.filter');
        }

        // Get the full request data for logging
        $main_request = $request->all();

        // Get total count from database entity
        $database_key = 'ecotox.ecotox_pnec3';
        $resultsObjectsCount = DatabaseEntity::where('code', $database_key)->first()->number_of_records ?? 0;

        // Get matrix_habitat counts for the specific substance
        $substanceId = is_array($substances) ? $substances[0] : $substances;
        $matrixHabitatCounts = PNEC3::where('substance_id', $substanceId)
            ->select('matrix_habitat', DB::raw('count(*) as count'))
            ->groupBy('matrix_habitat')
            ->orderBy('matrix_habitat')
            ->get();

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
                    'user_id' => auth()->check() ? auth()->id() : null,
                    'total_count' => $resultsObjectsCount,
                    'actual_count' => is_null($actual_count) ? $resultsObjects->count() : $actual_count,
                    'database_key' => $database_key,
                    'query_hash' => hash('sha256', $sql),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } catch (\Exception $e) {
                if (Auth::check() && Auth::user()->hasRole('super_admin')) {
                    session()->flash('failure', 'Query logging error: '.$e->getMessage());
                } else {
                    session()->flash('error', 'An error occurred while processing your request.');
                }
            }
        }

        // Apply pagination based on display option
        if ($request->input('displayOption') == 1) {
            // Use simple pagination
            $resultsObjects = $resultsObjects->orderBy('matrix_habitat')
                ->orderBy('pnec_type')
                ->orderBy('value')
                ->simplePaginate(200)
                ->withQueryString();
        } else {
            // Use cursor pagination
            $resultsObjects = $resultsObjects->orderBy('matrix_habitat')
                ->orderBy('pnec_type')
                ->orderBy('value')
                ->paginate(200)
                ->withQueryString();
        }

        // Get derivation data for the same substances
        $derivationObjects = \App\Models\Ecotox\EcotoxDerivation::whereIn('substance_id', $substances)
            ->orderBy('der_order', 'asc')
            ->orderBy('der_date', 'desc')
            ->get();

        // Get LowestPNECMain data for the same substances (using sus_id which maps to Substance->code)
        $substanceCodes = Substance::whereIn('id', $substances)->pluck('code');
        $lowestPnecObjects = LowestPNECMain::with(['substance', 'originSubstance', 'editor'])
            ->whereIn('sus_id', $substanceCodes)
            ->orderBy('lowest_id', 'asc')
            ->get();

        // Get CRED evaluation data for both PNEC and Derivation records
        $pnecEcotoxIds = $resultsObjects->pluck('ecotox_id')->filter()->unique();
        $derivationEcotoxIds = $derivationObjects->pluck('ecotox_id')->filter()->unique();
        $allEcotoxIds = $pnecEcotoxIds->merge($derivationEcotoxIds)->unique();

        $credEvaluations = \App\Models\Ecotox\EcotoxCredEvaluationFinal::whereIn('ecotox_id', $allEcotoxIds)
            ->get()
            ->keyBy('ecotox_id');

        // Add CRED screening scores to PNEC records
        $resultsObjects->getCollection()->transform(function ($pnec) use ($credEvaluations) {
            $pnec->cred_screening_score = 0;
            if ($pnec->ecotox_id && isset($credEvaluations[$pnec->ecotox_id])) {
                $evaluation = $credEvaluations[$pnec->ecotox_id];
                if ($evaluation->cred_final_score_total && $evaluation->cred_final_score_total > 0) {
                    $pnec->cred_screening_score = round(($evaluation->cred_final_score / $evaluation->cred_final_score_total) * 100, 2);
                }
            }

            return $pnec;
        });

        // Add CRED screening scores to Derivation records
        $derivationObjects->transform(function ($derivation) use ($credEvaluations) {
            $derivation->cred_screening_score = 0;
            if ($derivation->ecotox_id && isset($credEvaluations[$derivation->ecotox_id])) {
                $evaluation = $credEvaluations[$derivation->ecotox_id];
                if ($evaluation->cred_final_score_total && $evaluation->cred_final_score_total > 0) {
                    $derivation->cred_screening_score = round(($evaluation->cred_final_score / $evaluation->cred_final_score_total) * 100, 2);
                }
            }

            return $derivation;
        });

        // Return the view with results and metadata
        return view('ecotox.quality.index', [
            'resultsObjects' => $resultsObjects,
            'derivationObjects' => $derivationObjects,
            'lowestPnecObjects' => $lowestPnecObjects,
            'matrixHabitatCounts' => $matrixHabitatCounts,
            'resultsObjectsCount' => $resultsObjectsCount,
            'query_log_id' => QueryLog::orderBy('id', 'desc')->first()->id ?? 0,
            'request' => $request,
            'searchParameters' => $searchParameters,
        ], $main_request);
    }

    /**
     * Show the PNEC form for comparing PNEC2 and PNEC3 data.
     */
    public function showForm(string $id, Request $request)
    {
        // Check role-based access
        if (! Auth::check() || ! (Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('ecotox'))) {
            abort(403, 'Access denied. Only super_admin, admin, and ecotox roles can access this page.');
        }

        // Fetch data from PNEC2 and PNEC3 models for the given norman_pnec_id
        $pnec2 = \App\Models\Ecotox\PNEC2::with(['substance'])
            ->where('norman_pnec_id', $id)
            ->first();

        $pnec3 = \App\Models\Ecotox\PNEC3::with(['substance'])
            ->where('norman_pnec_id', $id)
            ->first();

        if (! $pnec2 && ! $pnec3) {
            abort(404, 'PNEC record not found');
        }

        // Helper function to get value from models
        $getValue = function ($field) use ($pnec2, $pnec3) {
            return [
                'pnec2' => $pnec2?->$field ?? 'N/A',
                'pnec3' => $pnec3?->$field ?? 'N/A',
            ];
        };

        // Define the fields to display in the table
        $tableFields = [
            'Reference' => [
                'study_title' => 'Study Title',
                'authors' => 'Authors',
                'year' => 'Year',
                'bibliographic_source' => 'Bibliographic Source',
                'dossier_available' => 'Dossier Available',
            ],
            'Substance' => [
                'substance_name' => 'Substance Name',
                'cas' => 'CAS Number',
                'purity' => 'Purity',
                'supplier' => 'Supplier',
            ],
            'Test Information' => [
                'test_type' => 'Test Type',
                'acute_or_chronic' => 'Acute or Chronic',
                'matrix_habitat' => 'Matrix Habitat',
                'taxonomic_group' => 'Taxonomic Group',
                'scientific_name' => 'Scientific Name',
            ],
            'Test Conditions' => [
                'duration' => 'Duration',
                'exposure_regime' => 'Exposure Regime',
                'measured_or_nominal' => 'Measured or Nominal',
                'test_item' => 'Test Item',
            ],
            'Results' => [
                'endpoint' => 'Endpoint',
                'effect_measurement' => 'Effect Measurement',
                'value' => 'Value',
                'concentration_specification' => 'Concentration Specification',
            ],
            'Quality' => [
                'reliability_study' => 'Reliability Study',
                'reliability_score' => 'Reliability Score',
                'institution_study' => 'Institution Study',
                'vote' => 'Vote',
            ],
            'Regulatory' => [
                'legal_status' => 'Legal Status',
                'protected_asset' => 'Protected Asset',
                'pnec_type' => 'PNEC Type',
                'regulatory_context' => 'Regulatory Context',
            ],
        ];

        // Build table rows for the view
        $tableRows = [];
        $rowId = 0;

        foreach ($tableFields as $sectionName => $fields) {
            // Add section header
            $tableRows[] = [
                'id' => 'header-'.$rowId++,
                'type' => 'header',
                'title' => $sectionName.' Information',
            ];

            // Add data rows
            $index = 0;
            foreach ($fields as $fieldName => $displayName) {
                $values = $getValue($fieldName);

                $tableRows[] = [
                    'id' => 'data-'.$rowId++,
                    'type' => 'data',
                    'key' => $displayName,
                    'sectionName' => $sectionName,
                    'columnName' => $fieldName,
                    'pnec2' => $values['pnec2'],
                    'pnec3' => $values['pnec3'],
                    'isEditable' => false, // Set to true for fields that should be editable
                    'inputType' => 'text',
                    'dropdownOptions' => ['Yes', 'No', 'Unknown', 'Not applicable'],
                    'isOdd' => $index % 2 !== 0,
                    'hasChanges' => false, // Set to true if there are changes to track
                ];
                $index++;
            }
        }

        // Determine which record to use for primary information
        $primaryRecord = $pnec3 ?? $pnec2;

        $record = [
            'norman_pnec_id' => $id,
            'substance' => $primaryRecord->substance,
            'matrix_habitat' => $primaryRecord->matrix_habitat ?? 'N/A',
            'pnec_type' => $primaryRecord->pnec_type ?? 'N/A',
        ];

        return view('ecotox.quality.pnec-form', [
            'recordId' => $id,
            'record' => $record,
            'tableRows' => $tableRows,
            'isSuperAdmin' => Auth::check() && Auth::user()->hasRole('super_admin'),
            'searchParameters' => $request->all(),
            'returnUrl' => $request->get('returnUrl'),
        ]);
    }

    /**
     * Get changes for a specific PNEC record and column.
     */
    public function getChanges(string $pnecId, string $columnName)
    {
        // Check role-based access
        if (! Auth::check() || ! (Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('ecotox'))) {
            abort(403, 'Access denied');
        }

        // This would need to be implemented based on your change tracking system
        // For now, returning empty array
        return response()->json([]);
    }
}
