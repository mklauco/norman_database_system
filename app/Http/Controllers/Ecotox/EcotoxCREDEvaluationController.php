<?php

namespace App\Http\Controllers\Ecotox;

use App\Http\Controllers\Controller;
use App\Models\Backend\QueryLog;
use App\Models\DatabaseEntity;
use App\Models\Ecotox\EcotoxCredQuestion;
use App\Models\Ecotox\EcotoxFinal;
use App\Models\Ecotox\EcotoxMainFinalChange;
use App\Models\Susdat\Substance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EcotoxCREDEvaluationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return redirect()->route('ecotox.credevaluation.home.index');
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
        return view('ecotox.credevaluation.filter', [
            'request' => $request,
        ]);
    }

    public function search(Request $request)
    {
        // Initialize search parameters array to track what filters were applied
        $searchParameters = [];

        // Start with a base query with necessary relationships
        $resultsObjects = EcotoxFinal::with([
            'substance',
        ])->whereIn('use_study', ['y', 'Y', 'yes', 'YES', 'Yes'])->orderBy('ecotox_id', 'asc');

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

            return redirect()->route('ecotox.credevaluation.search.filter');
        }

        // Get the full request data for logging
        $main_request = $request->all();

        // Get total count from database entity
        $database_key = 'ecotox.ecotox';
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

        // Get CRED evaluation data for the records
        $ecotoxIds = $resultsObjects->pluck('ecotox_id')->filter()->unique();

        $credEvaluations = \App\Models\Ecotox\EcotoxCredEvaluationFinal::whereIn('ecotox_id', $ecotoxIds)
            ->get()
            ->keyBy('ecotox_id');

        // Add CRED screening scores to records
        $resultsObjects->getCollection()->transform(function ($record) use ($credEvaluations) {
            $record->cred_screening_score = 0;
            if ($record->ecotox_id && isset($credEvaluations[$record->ecotox_id])) {
                $evaluation = $credEvaluations[$record->ecotox_id];
                if ($evaluation->cred_final_score_total && $evaluation->cred_final_score_total > 0) {
                    $record->cred_screening_score = round(($evaluation->cred_final_score / $evaluation->cred_final_score_total) * 100, 2);
                }
            }

            return $record;
        });

        // Return the view with results and metadata
        return view('ecotox.credevaluation.index', [
            'resultsObjects' => $resultsObjects,
            'resultsObjectsCount' => $resultsObjectsCount,
            'query_log_id' => QueryLog::orderBy('id', 'desc')->first()->id ?? 0,
            'request' => $request,
            'searchParameters' => $searchParameters,
        ], $main_request);
    }

    public function countAll()
    {
        DatabaseEntity::where('code', 'ecotox.ecotox')->update([
            'last_update' => EcotoxFinal::max('updated_at'),
            'number_of_records' => EcotoxFinal::count(),
        ]);
        session()->flash('success', 'Database counts updated successfully');

        return redirect()->back();
    }

    /**
     * Get data for CRED evaluation modal
     */
    public function getModalData($recordId)
    {
        try {
            $record = EcotoxFinal::with(['substance'])
                ->where('ecotox_id', $recordId)
                ->first();

            if (! $record) {
                return response()->json(['error' => 'Record not found'], 404);
            }

            // Fetch CRED questions with their sub-questions
            $credQuestions = EcotoxCredQuestion::with(['subQuestions' => function ($query) {
                $query->orderBy('sort_order');
            }])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get();

            // Debug logging
            Log::info('CRED Questions Query Result', [
                'total_questions' => $credQuestions->count(),
                'questions' => $credQuestions->toArray(),
            ]);

            $credQuestions = $credQuestions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question_number' => $question->question_number,
                    'question_text' => $question->question_text,
                    'max_score' => $question->max_score,
                    'screening_score' => $question->screening_score,
                    'sort_order' => $question->sort_order,
                    'sub_questions' => $question->subQuestions->map(function ($subQuestion) {
                        return [
                            'id' => $subQuestion->id,
                            'question_letter' => $subQuestion->question_letter,
                            'question_text' => $subQuestion->question_text,
                            'max_score' => $subQuestion->max_score,
                            'screening_score' => $subQuestion->screening_score,
                            'sort_order' => $subQuestion->sort_order,
                        ];
                    }),
                ];
            });

            return response()->json([
                'record' => $record,
                'credQuestions' => $credQuestions,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getModalData', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Failed to fetch record data: '.$e->getMessage()], 500);
        }
    }

    /**
     * Get evaluation history for a record
     */
    public function getEvaluationHistory($recordId)
    {
        try {
            // This would typically come from a CredEvaluation model
            // For now, returning empty array as placeholder
            $history = [];

            return response()->json($history);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch evaluation history'], 500);
        }
    }

    /**
     * Save CRED evaluation
     */
    public function saveEvaluation(Request $request)
    {
        try {
            $request->validate([
                'record_id' => 'required|string',
                'reliability_score' => 'required|string',
                'use_of_study' => 'required|string',
                'comments' => 'nullable|string',
                'evaluation_date' => 'required|date',
            ]);

            // This would typically save to a CredEvaluation model
            // For now, just returning success as placeholder
            $evaluationData = $request->all();
            $evaluationData['evaluated_by'] = Auth::id();
            $evaluationData['evaluated_at'] = now();

            // TODO: Implement actual saving logic
            // CredEvaluation::create($evaluationData);

            return response()->json([
                'success' => true,
                'message' => 'Evaluation saved successfully',
                'data' => $evaluationData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save evaluation: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the CRED evaluation form with questions table
     */
    public function showForm($recordId = null, ?Request $request = null)
    {
        try {
            // Fetch CRED questions with their sub-questions and parameters
            $credQuestions = EcotoxCredQuestion::with([
                'subQuestions' => function ($query) {
                    $query->orderBy('sort_order');
                },
                'subQuestions.parameters.ecotoxConfig' => function ($query) {
                    $query->orderBy('order');
                },
                'subQuestions.parameters.ecotoxConfig.inputValues' => function ($query) {
                    $query->orderBy('input_value');
                },
            ])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get();

            // If no questions found, show error
            if ($credQuestions->isEmpty()) {
                Log::warning('No CRED questions found in database');

                return redirect()->back()->with('warning', 'No CRED questions are available in the database. Please ensure the questions have been seeded.');
            }

            // Fetch record information if recordId is provided
            $record = null;
            $substances = [];
            $returnUrl = null;
            $parameterValues = [];

            if ($recordId) {
                $record = EcotoxFinal::with(['substance'])
                    ->where('ecotox_id', $recordId)
                    ->first();

                if (! $record) {
                    return redirect()->back()->with('error', 'Record not found.');
                }

                // Extract parameter values from EcotoxFinal using column_name from parameters
                foreach ($credQuestions as $question) {
                    foreach ($question->subQuestions as $subQuestion) {
                        foreach ($subQuestion->parameters as $parameter) {
                            if ($parameter->ecotoxConfig && $parameter->ecotoxConfig->column_name) {
                                $columnName = $parameter->ecotoxConfig->column_name;
                                $parameterValues[$parameter->id] = $record->$columnName ?? null;

                                // Check if this parameter has been changed
                                $hasChanges = EcotoxMainFinalChange::where('ecotox_id', $recordId)
                                    ->where('column_name', $columnName)
                                    ->exists();

                                // Store both value and change status
                                $parameterValues[$parameter->id] = [
                                    'value' => $record->$columnName ?? null,
                                    'hasChanges' => $hasChanges,
                                    'columnName' => $columnName,
                                ];

                                // Log the extraction for debugging
                                Log::info('Parameter value extraction', [
                                    'parameter_id' => $parameter->id,
                                    'column_name' => $columnName,
                                    'value' => $record->$columnName ?? null,
                                    'parameter_label' => $parameter->parameter_label,
                                    'hasChanges' => $hasChanges,
                                ]);
                            }
                        }
                    }
                }

                // Get substances from query parameters if available
                if ($request && $request->has('substances')) {
                    $substanceIds = json_decode($request->substances, true);
                    if (is_array($substanceIds)) {
                        $substances = Substance::whereIn('id', $substanceIds)->get();
                    }
                }

                // Get return URL if available
                $returnUrl = $request ? $request->get('returnUrl') : null;
            }

            return view('ecotox.credevaluation.cred-form', [
                'credQuestions' => $credQuestions,
                'recordId' => $recordId,
                'record' => $record,
                'substances' => $substances,
                'returnUrl' => $returnUrl,
                'parameterValues' => $parameterValues,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in showForm', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'record_id' => $recordId,
            ]);

            // Return to previous page with error message
            return redirect()->back()->with('error', 'Failed to load CRED evaluation form: '.$e->getMessage());
        }
    }

    /**
     * Get changes for a specific ecotox_id and column_name.
     */
    public function getChanges(string $ecotoxId, string $columnName)
    {
        $changes = EcotoxMainFinalChange::with(['user'])
            ->where('ecotox_id', $ecotoxId)
            ->where('column_name', $columnName)
            ->orderBy('change_date', 'desc')
            ->get()
            ->map(function ($change) {
                return [
                    'id' => $change->id,
                    'change_date' => $change->change_date ? $change->change_date->format('Y-m-d H:i:s') : 'N/A',
                    'user_name' => $change->user ? $change->user->getFormattedNameAttribute() : 'Unknown',
                    'change_old' => $change->change_old,
                    'change_new' => $change->change_new,
                    'change_type' => $change->change_type,
                ];
            });

        return response()->json($changes);
    }

    /**
     * Show the CRED evaluation form without requiring a record ID (for testing/demo)
     */
    public function showDemoForm(Request $request)
    {
        return $this->showForm(null, $request);
    }
}
