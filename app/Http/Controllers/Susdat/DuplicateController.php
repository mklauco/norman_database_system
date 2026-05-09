<?php

namespace App\Http\Controllers\Susdat;

use App\Http\Controllers\Controller;
use App\Models\Susdat\Substance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'pivot_id' => 'required|string|in:'.implode(',', $this->getPivotableColumns()),
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
        if (! in_array($pivot, $this->getPivotableColumns())) {
            abort(404, 'Invalid pivot column');
        }

        // Get substances, excluding already merged ones
        $substances = Substance::where($pivot, $pivot_value)
            ->where(function ($query) {
                $query->whereNull('canonical_id')
                    ->orWhere('status', 'active');
            })
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
     * Show merge history for substances
     */
    public function mergeHistory(Request $request)
    {
        $query = Substance::with(['canonical', 'mergedBy'])
            ->whereNotNull('canonical_id')
            ->where('status', 'merged');

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('merged_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('merged_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('merged_by')) {
            $query->where('merged_by', $request->input('merged_by'));
        }

        $mergedSubstances = $query->orderBy('merged_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $users = User::orderBy('first_name')->get();

        return view('susdat.duplicates.merge-history', [
            'mergedSubstances' => $mergedSubstances,
            'users' => $users,
            'filters' => $request->only(['date_from', 'date_to', 'merged_by']),
        ]);
    }

    /**
     * Handle duplicate resolution using canonical reference system
     */
    public function handleDuplicates(Request $request)
    {
        $request->validate([
            'canonical_id' => 'required|integer|exists:susdat_substances,id',
            'merge_reason' => 'required|string|max:500',
            'duplicateChoice' => 'required|array',
            'duplicateChoice.*' => 'integer|exists:susdat_substances,id',
        ]);

        // Additional validation: ensure canonical_id is not in duplicateChoice
        if (in_array($request->input('canonical_id'), $request->input('duplicateChoice'))) {
            session()->flash('error', 'The active substance cannot be marked as deprecated.');

            return redirect()->back()->withErrors(['canonical_id' => 'Active substance cannot be deprecated.']);
        }

        $mergedCount = $this->executeMerge(
            $request->input('canonical_id'),
            $request->input('duplicateChoice'),
            $request->input('merge_reason')
        );

        $message = "Successfully merged {$mergedCount} duplicate(s) into canonical record.";
        session()->flash('success', $message);

        return redirect()->back();
    }

    /**
     * Execute merge operation using canonical reference system
     */
    private function executeMerge($canonicalId, $duplicateIds, $mergeReason)
    {
        $canonical = Substance::findOrFail($canonicalId);

        // Ensure canonical substance is active
        if ($canonical->status !== 'active') {
            throw new \InvalidArgumentException('Canonical substance must be active');
        }

        $mergedCount = 0;
        $userId = auth()->id();

        foreach ($duplicateIds as $duplicateId) {
            if ($duplicateId == $canonicalId) {
                continue; // Skip if trying to merge canonical with itself
            }

            $duplicate = Substance::findOrFail($duplicateId);

            // Update duplicate to point to canonical
            $duplicate->update([
                'canonical_id' => $canonicalId,
                'status' => 'merged',
                'merged_at' => now(),
                'merged_by' => $userId,
                'merge_reason' => $mergeReason,
            ]);

            $mergedCount++;
        }

        return $mergedCount;
    }

    /**
     * Get impact analysis for substances (how many other tables reference each)
     */
    private function getSubstanceImpact($substanceId)
    {
        $impact = [];

        // Check various related tables - you can expand this based on your actual relationships
        $tables = [
            'categories' => 'susdat_category_substance_joins',
            'sources' => 'susdat_source_substance_joins',
            // Add more tables as needed
        ];

        foreach ($tables as $tableName => $table) {
            try {
                $count = DB::table($table)
                    ->where('substance_id', $substanceId)
                    ->count();
                $impact[$tableName] = $count;
            } catch (\Exception $e) {
                $impact[$tableName] = 0;
            }
        }

        return $impact;
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
     * Restore a merged substance
     */
    public function restore(Request $request, string $id)
    {
        $mergedSubstance = Substance::where('id', $id)
            ->where('status', 'merged')
            ->whereNotNull('canonical_id')
            ->firstOrFail();

        // Check if user has permission to restore
        if (! auth()->check()) {
            abort(403, 'You must be logged in to restore substances.');
        }

        try {
            DB::beginTransaction();

            // Restore the substance to active status
            $mergedSubstance->update([
                'canonical_id' => null,
                'status' => 'active',
                'merged_at' => null,
                'merged_by' => null,
                'merge_reason' => null,
            ]);

            // Log the restore action (using session flash for now)
            session()->flash('info', 'Restore action logged for substance ID: '.$id);

            DB::commit();

            session()->flash('success', 'Substance has been successfully restored and is now active again.');

            return redirect()->route('duplicates.mergeHistory');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to restore substance. Please try again.');

            return redirect()->back();
        }
    }

    /**
     * Unused resource methods
     */
    public function create()
    { /* Not implemented */
    }

    public function store(Request $request)
    { /* Not implemented */
    }

    public function show(string $id)
    { /* Not implemented */
    }

    public function edit(string $id)
    { /* Not implemented */
    }

    public function update(Request $request, string $id)
    { /* Not implemented */
    }

    public function destroy(string $id)
    { /* Not implemented */
    }
}
