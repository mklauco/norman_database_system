<?php

namespace App\Http\Controllers\EmpodatSuspect;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\EmpodatSuspect\EmpodatSuspectMain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmpodatSuspectHomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->checkModuleAccess();
    }

    /**
     * Check if user has access to the EmpodatSuspect module
     */
    private function checkModuleAccess(): void
    {
        $databaseEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $databaseEntity) {
            abort(403, 'Module not found.');
        }

        // If module is public, allow access to everyone
        if ($databaseEntity->is_public === true) {
            return;
        }

        // Module is private - check if user is logged in
        if (! Auth::check()) {
            abort(403, 'You must be logged in to access this module.');
        }

        $user = Auth::user();

        // Always allow admin and super_admin
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return;
        }

        // Check if user has the specific module role
        if ($user->hasRole('empodat_suspect')) {
            return;
        }

        // User doesn't have permission
        abort(403, 'You do not have permission to access this module.');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('empodat_suspect.home.index');
    }

    /**
     * Update the database counts for EmpodatSuspect module
     *
     * Custom counting logic:
     * - Count rows from empodat_suspect_xlsx_stations_mapping (number of station columns)
     * - Count DISTINCT substance_id from empodat_suspect_main (number of unique substances)
     * - Multiply them together to get the virtual total count
     *
     * This represents the potential maximum number of data points in a fully pivoted view
     * where each substance can have a value for each station column.
     */
    public function countAll()
    {
        try {
            // Count number of station mappings (columns in pivot table)
            $stationMappingsCount = DB::table('empodat_suspect_xlsx_stations_mapping')->count();

            // Count distinct substances in empodat_suspect_main
            $uniqueSubstancesCount = DB::table('empodat_suspect_main')
                ->distinct('substance_id')
                ->count('substance_id');

            // Calculate virtual total: station mappings × unique substances
            // This represents the maximum potential data points in a fully pivoted view
            $virtualTotal = $stationMappingsCount * $uniqueSubstancesCount;

            // Update the database_entities table
            DatabaseEntity::where('code', 'empodat_suspect')->update([
                'last_update' => now(),
                'number_of_records' => $virtualTotal,
            ]);

            session()->flash('success', sprintf(
                'Empodat Suspect database counts updated successfully. Station mappings: %s, Unique substances: %s, Virtual total: %s',
                number_format($stationMappingsCount),
                number_format($uniqueSubstancesCount),
                number_format($virtualTotal)
            ));

            return redirect()->back();

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update database counts: '.$e->getMessage());

            return redirect()->back();
        }
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
        $record = EmpodatSuspectMain::with([
            'substance',
            'station.country',
            'xlsxStationMapping',
            'files',
        ])->findOrFail($id);

        return view('empodat_suspect.show', [
            'record' => $record,
        ]);
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
}
