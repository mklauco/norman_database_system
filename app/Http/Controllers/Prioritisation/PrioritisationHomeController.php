<?php

namespace App\Http\Controllers\Prioritisation;

use Illuminate\Http\Request;
use App\Models\DatabaseEntity;
use App\Http\Controllers\Controller;
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
        // Get the latest update timestamp from any of the four tables
        $latestUpdateTime = max([
            MonitoringDanube::max('updated_at') ?? '1970-01-01',
            MonitoringScarce::max('updated_at') ?? '1970-01-01',
            ModellingDanube::max('updated_at') ?? '1970-01-01',
            ModellingScarce::max('updated_at') ?? '1970-01-01'
        ]);
        
        // Count total records from all prioritisation tables
        $totalRecords = 
            MonitoringDanube::count() +
            MonitoringScarce::count() +
            ModellingDanube::count() +
            ModellingScarce::count();
        
        // Update the database entity record
        DatabaseEntity::where('code', 'prioritisation')->update([
            'last_update' => $latestUpdateTime,
            'number_of_records' => $totalRecords
        ]);
        
        session()->flash('success', 'Database counts updated successfully');
        return redirect()->back();
    }
}
