<?php

namespace App\Http\Controllers\Susdat;

use Illuminate\Http\Request;
use App\Models\Susdat\Category;
use App\Models\Susdat\Substance;
use App\Http\Controllers\Controller;
use App\Models\SLE\SuspectListExchangeSource;

class DuplicateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('susdat.duplicates.index', [
            'getPivotableColumns' => $this->getPivotableColumns(),
            'columnLabels' => $this->getColumnLabels(),
        ]);
    }

    /**
     * Filter duplicates by selected column
     */
    public function filter(Request $request)
    {
        $request->validate([
            'pivot_id' => 'required|string|in:' . implode(',', $this->getPivotableColumns())
        ]);

        $pivot_id = $request->input('pivot_id');
        $pivot = $pivot_id; // Use the actual column name

        $duplicates = Substance::select($pivot)
            ->selectRaw('count(*) as count')
            ->whereNotNull($pivot)
            ->where($pivot, '!=', '')
            ->groupBy($pivot)
            ->havingRaw('count(*) > 1')
            ->orderBy('count', 'desc')
            ->get();

        return view('susdat.duplicates.index', [
            'duplicates' => $duplicates,
            'pivot' => $pivot,
            'pivot_id' => $pivot_id,
            'getPivotableColumns' => $this->getPivotableColumns(),
            'columnLabels' => $this->getColumnLabels(),
            'totalDuplicateGroups' => $duplicates->count(),
            'totalDuplicateRecords' => $duplicates->sum('count'),
        ]);
    }

    /**
     * Show records for a specific duplicate group
     */
    public function records(Request $request, string $pivot, string $pivot_value)
    {
        // Validate pivot is allowed
        if (!in_array($pivot, $this->getPivotableColumns())) {
            abort(404, 'Invalid pivot column');
        }

        $substances = Substance::where($pivot, $pivot_value)
            ->orderBy('id')
            ->withTrashed()
            ->paginate(10)
            ->withQueryString();

        return view('susdat.duplicates.records', [
            'substances' => $substances,
            'pivot' => $pivot,
            'pivot_value' => $pivot_value,
            'columns' => $this->getSelectColumns(),
            'substancesCount' => $substances->total(),
            'dtxsIds' => $substances->pluck('dtxid')->filter()->toArray(),
            'pubchemIds' => $substances->pluck('pubchem_cid')->filter()->unique()->toArray(),
        ]);
    }

    /**
     * Handle duplicate resolution (delete/restore)
     */
    public function handleDuplicates(Request $request)
    {
        $deletedCount = 0;
        $restoredCount = 0;

        // Handle deletions
        if ($request->filled('duplicateChoice')) {
            foreach ($request->input('duplicateChoice') as $id => $choice) {
                if ($choice == 0) {
                    $substance = Substance::find($id);
                    if ($substance) {
                        $substance->delete();
                        $deletedCount++;
                    }
                }
            }
        }

        // Handle restorations
        if ($request->filled('duplicateRestore')) {
            foreach ($request->input('duplicateRestore') as $id => $choice) {
                if ($choice == 1) {
                    $substance = Substance::withTrashed()->find($id);
                    if ($substance) {
                        $substance->restore();
                        $restoredCount++;
                    }
                }
            }
        }

        $message = '';
        if ($deletedCount > 0) {
            $message .= "Deleted {$deletedCount} duplicate(s). ";
        }
        if ($restoredCount > 0) {
            $message .= "Restored {$restoredCount} record(s). ";
        }

        if ($message) {
            session()->flash('success', trim($message));
        } else {
            session()->flash('info', 'No changes were made.');
        }

        return redirect()->back();
    }

    /**
     * Get pivotable column names (actual database columns)
     */
    private function getPivotableColumns()
    {
        return [
            'code',
            'name',
            'cas_number',
            'smiles',
            'stdinchikey',
            'dtxid',
            'pubchem_cid',
            'chemspider_id',
        ];
    }

    /**
     * Get human-readable labels for columns
     */
    private function getColumnLabels()
    {
        return [
            'code' => 'Code',
            'name' => 'Name',
            'cas_number' => 'CAS Number',
            'smiles' => 'SMILES',
            'stdinchikey' => 'InChI Key',
            'dtxid' => 'DTX ID',
            'pubchem_cid' => 'PubChem CID',
            'chemspider_id' => 'ChemSpider ID',
        ];
    }

    /**
     * Get columns for display in records view
     */
    private function getSelectColumns()
    {
        return [
            'id',
            'code',
            'name',
            'cas_number',
            'smiles',
            'stdinchikey',
            'dtxid',
            'pubchem_cid',
            'chemspider_id',
            'molecular_formula',
            'mass_iso',
        ];
    }

    /**
     * Unused resource methods
     */
    public function create() { /* Not implemented */ }
    public function store(Request $request) { /* Not implemented */ }
    public function show(string $id) { /* Not implemented */ }
    public function edit(string $id) { /* Not implemented */ }
    public function update(Request $request, string $id) { /* Not implemented */ }
    public function destroy(string $id) { /* Not implemented */ }
}