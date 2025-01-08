<?php

namespace App\Http\Controllers\Empodat;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Empodat\EmpodatMain;
use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Susdat\Substance;

class UniqueSearchController extends Controller
{
    //
    public function countries(){
        
        /* ORIGINAL SQL QUERY
        WITH relevant_stations AS (
        SELECT DISTINCT station_id
        FROM empodat_main
        )
        SELECT DISTINCT lc.id
        FROM relevant_stations rs
        JOIN empodat_stations es ON rs.station_id = es.id
        JOIN list_countries lc ON es.country_id = lc.id;
        */
        
        // Step 1: Subquery to get relevant stations
        $relevantStations = DB::table('empodat_main')
        ->distinct()
        ->select('station_id');
        
        // Step 2: Main query using the subquery
        $distinctCountryIds = DB::table('empodat_stations as es')
        ->joinSub($relevantStations, 'rs', function ($join) {
            $join->on('rs.station_id', '=', 'es.id');
        })
        ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
        ->distinct()
        ->select('lc.id')
        ->get();
        
        $p = [];
        foreach ($distinctCountryIds as $countryId) {
            $p[] = ([
                'country_id' => $countryId->id
            ]);
        }
        DB::table('empodat_search_countries')->truncate();
        DB::table('empodat_search_countries')->insert($p);
        
        session()->flash('success', 'Countries updated successfully');
        return redirect()->back();
    }
    
    public function matrices(){
        // Step 1: Select distinct rows based on matrix_id
        $distinctMatrices = DB::table('empodat_main')
        ->select('matrix_id')
        ->distinct()
        ->get();
        
        // Step 2: Process the distinct matrices if needed
        $p = [];
        foreach ($distinctMatrices as $matrix) {
            $p[] = ([
                'matrix_id' => $matrix->matrix_id
            ]);
        }
        
        // Example: Insert distinct matrices into another table
        DB::table('empodat_search_matrices')->truncate();
        DB::table('empodat_search_matrices')->insert($p);
        
        session()->flash('success', 'Matrices updated successfully');
        return redirect()->back();   
    }

    public function updateDatabaseEntitiesCounts()
    {
        DatabaseEntity::where('code', 'empodat')->update([
            // 'last_update' => EmpodatMain::max('updated_at'),
            'number_of_records' => EmpodatMain::where('empodat_main.substance_id', '<', 400000)->count()
        ]);
        DatabaseEntity::where('code', 'susdat')->update([
            'last_update' => Substance::max('updated_at'),
            'number_of_records' => Substance::count()
        ]);
        session()->flash('success', 'Database counts updated successfully');
        return redirect()->back();   
    }
}
