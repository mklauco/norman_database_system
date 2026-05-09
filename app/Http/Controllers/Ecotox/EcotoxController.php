<?php

namespace App\Http\Controllers\Ecotox;

use App\Http\Controllers\Controller;
use App\Models\Backend\QueryLog;
use App\Models\DatabaseEntity;
use App\Models\Ecotox\EcotoxComparativeTableConfig;
use App\Models\Ecotox\EcotoxComparativeTableInputValues;
use App\Models\Ecotox\EcotoxFinal;
use App\Models\Ecotox\EcotoxHarmonised;
use App\Models\Ecotox\EcotoxMainFinalChange;
use App\Models\Ecotox\EcotoxOriginal;
use App\Models\Susdat\Substance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EcotoxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return redirect()->route('ecotox.home.index');
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
        // Fetch data from all three Ecotox models for the given ecotox_id
        $ecotoxFinal = EcotoxFinal::with(['substance'])
            ->where('ecotox_id', $id)
            ->first();

        $ecotoxOriginal = EcotoxOriginal::with(['substance'])
            ->where('ecotox_id', $id)
            ->first();

        $ecotoxHarmonised = EcotoxHarmonised::with(['substance'])
            ->where('ecotox_id', $id)
            ->first();

        if (! $ecotoxFinal && ! $ecotoxOriginal && ! $ecotoxHarmonised) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        // Helper function to get value from models
        $getValue = function ($field) use ($ecotoxOriginal, $ecotoxHarmonised, $ecotoxFinal) {
            return [
                'original' => $ecotoxOriginal?->$field ?? 'N/A',
                'harmonised' => $ecotoxHarmonised?->$field ?? 'N/A',
                'final' => $ecotoxFinal?->$field ?? 'N/A',
            ];
        };

        // Generate table data dynamically from EcotoxComparativeTableConfig
        $tableConfigs = EcotoxComparativeTableConfig::orderBy('order')->get();
        $tableData = [];

        foreach ($tableConfigs as $config) {
            $group = $config->group;
            $header = $config->header;
            $columnName = $config->column_name;
            $columnId = $config->column_id;

            // Get input values for this column
            $inputValues = EcotoxComparativeTableInputValues::where('column_name', $columnName)
                ->pluck('input_value')
                ->toArray();

            // Initialize group if it doesn't exist
            if (! isset($tableData[$group])) {
                $tableData[$group] = [];
            }

            // Add the field to the group using header as parameter name, column_name for data retrieval, and column_id for admin display
            $tableData[$group][$header] = [
                'data' => $getValue($columnName),
                'column_id' => $columnId,
                'is_editable' => $config->is_editable ?? false,
                'input_type' => $config->input_type ?? 'text',
                'input_values' => $inputValues,
            ];
        }

        return response()->json([
            'ecotox_id' => $id,
            'substance' => $ecotoxFinal?->substance ?? $ecotoxOriginal?->substance ?? $ecotoxHarmonised?->substance,
            'table_data' => $tableData,
        ]);
    }

    /**
     * Display the form view for the specified resource.
     */
    public function showForm(string $id)
    {
        // Fetch data from all three Ecotox models for the given ecotox_id
        $ecotoxFinal = EcotoxFinal::with(['substance'])
            ->where('ecotox_id', $id)
            ->first();

        $ecotoxOriginal = EcotoxOriginal::with(['substance'])
            ->where('ecotox_id', $id)
            ->first();

        $ecotoxHarmonised = EcotoxHarmonised::with(['substance'])
            ->where('ecotox_id', $id)
            ->first();

        if (! $ecotoxFinal && ! $ecotoxOriginal && ! $ecotoxHarmonised) {
            abort(404, 'Record not found');
        }

        // Helper function to get value from models
        $getValue = function ($field) use ($ecotoxOriginal, $ecotoxHarmonised, $ecotoxFinal) {
            return [
                'original' => $ecotoxOriginal?->$field ?? 'N/A',
                'harmonised' => $ecotoxHarmonised?->$field ?? 'N/A',
                'final' => $ecotoxFinal?->$field ?? 'N/A',
            ];
        };

        // Generate table data dynamically from EcotoxComparativeTableConfig
        $tableConfigs = EcotoxComparativeTableConfig::orderBy('order')->get();
        $tableData = [];
        $tableRows = [];

        foreach ($tableConfigs as $config) {
            $group = $config->group;
            $header = $config->header;
            $columnName = $config->column_name;
            $columnId = $config->column_id;

            // Get input values for this column
            $inputValues = EcotoxComparativeTableInputValues::where('column_name', $columnName)
                ->pluck('input_value')
                ->toArray();

            // Initialize group if it doesn't exist
            if (! isset($tableData[$group])) {
                $tableData[$group] = [];
            }

            // Add the field to the group using header as parameter name, column_name for data retrieval, and column_id for admin_id for admin display
            $tableData[$group][$header] = [
                'data' => $getValue($columnName),
                'column_id' => $columnId,
                'column_name' => $columnName,
                'is_editable' => $config->is_editable ?? false,
                'input_type' => $config->input_type ?? 'text',
                'input_values' => $inputValues,
            ];
        }

        // Build table rows for the view (moved from JavaScript)
        $tableSections = [
            'Source',
            'Reference',
            'Categorisation',
            'Test substance',
            'Biotest',
            'Test Organism',
            'Dosing system',
            'Controls and Study design',
            'Test conditions',
            'Statistical design',
            'Biological effect',
            'Evaluation',
        ];

        $rowId = 0;
        foreach ($tableSections as $sectionName) {
            $sectionData = $tableData[$sectionName] ?? [];

            if (! empty($sectionData)) {
                // Add section header
                $tableRows[] = [
                    'id' => 'header-'.$rowId++,
                    'type' => 'header',
                    'title' => $sectionName.' Information',
                ];

                // Add data rows
                $index = 0;
                foreach ($sectionData as $key => $value) {
                    // Check if this column has been edited
                    $hasChanges = EcotoxMainFinalChange::where('ecotox_id', $id)
                        ->where('column_name', $value['column_name'] ?? '')
                        ->exists();

                    $tableRows[] = [
                        'id' => 'data-'.$rowId++,
                        'type' => 'data',
                        'key' => $key,
                        'sectionName' => $sectionName,
                        'columnId' => $value['column_id'],
                        'columnName' => $value['column_name'] ?? '',
                        'original' => $value['data']['original'] ?? '',
                        'harmonised' => $value['data']['harmonised'] ?? '',
                        'final' => $value['data']['final'] ?? '',
                        'isEditable' => $value['is_editable'] ?? false,
                        'inputType' => $value['input_type'] ?? 'text',
                        'dropdownOptions' => ! empty($value['input_values']) ? $value['input_values'] : ['Yes', 'No', 'Unknown', 'Not applicable'],
                        'isOdd' => $index % 2 !== 0,
                        'hasChanges' => $hasChanges,
                    ];
                    $index++;
                }
            }
        }

        $record = [
            'ecotox_id' => $id,
            'substance' => $ecotoxFinal?->substance ?? $ecotoxOriginal?->substance ?? $ecotoxHarmonised?->substance,
            'table_data' => $tableData,
        ];

        return view('ecotox.data.ecotox-form', [
            'recordId' => $id,
            'record' => $record,
            'tableRows' => $tableRows,
            'isSuperAdmin' => Auth::check() && Auth::user()->hasRole('super_admin'),
        ]);
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
        return view('ecotox.filter', [
            'request' => $request,
        ]);
    }

    public function search(Request $request)
    {
        // dd($request->all());
        // Initialize search parameters array to track what filters were applied
        $searchParameters = [];

        // Start with a base query with necessary relationships
        $resultsObjects = EcotoxFinal::with([
            'substance',
        ])->orderBy('ecotox_id', 'asc');

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

            return redirect()->route('ecotox.data.search.filter');
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
        return view('ecotox.index', [
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
}
