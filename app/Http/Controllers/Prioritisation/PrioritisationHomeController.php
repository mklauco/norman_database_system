<?php

namespace App\Http\Controllers\Prioritisation;

use App\Actions\RefreshDatabaseEntityCountsAction;
use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Prioritisation\ModellingDanube;
use App\Models\Prioritisation\ModellingScarce;
use App\Models\Prioritisation\MonitoringDanube;
use App\Models\Prioritisation\MonitoringScarce;

class PrioritisationHomeController extends Controller
{
    //
    public function index()
    {
        return view('prioritisation.home.index');
    }

    public function countAll()
    {
        app(RefreshDatabaseEntityCountsAction::class)->handle(['prioritisation']);

        // Count total records from all prioritisation tables
        $totalRecords =
            MonitoringDanube::count() +
            MonitoringScarce::count() +
            ModellingDanube::count() +
            ModellingScarce::count();

        // Update the database entity record
        DatabaseEntity::where('code', 'prioritisation')->update([
            'last_update' => $latestUpdateTime,
            'number_of_records' => $totalRecords,
        ]);

        session()->flash('success', 'Database counts updated successfully');

        return redirect()->back();
    }
}
