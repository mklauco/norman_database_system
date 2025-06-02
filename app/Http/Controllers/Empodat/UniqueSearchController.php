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
    public function countries()
    {

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

    public function matrices()
    {
        // Step 1: Use JOIN to get only valid matrix_ids efficiently
        $validMatrices = DB::table('empodat_main as em')
            ->join('list_matrices as lm', 'em.matrix_id', '=', 'lm.id')
            ->select('em.matrix_id')
            ->distinct()
            ->whereNotNull('em.matrix_id')
            ->where('em.matrix_id', '>', 0)
            ->get();

        // Step 2: Prepare data for insertion
        $insertData = $validMatrices->map(function ($matrix) {
            return ['matrix_id' => $matrix->matrix_id];
        })->toArray();

        // Step 3: Clear and insert in one transaction for safety
        DB::transaction(function () use ($insertData) {
            DB::table('empodat_search_matrices')->truncate();

            if (!empty($insertData)) {
                // Insert in chunks to avoid memory issues with very large datasets
                collect($insertData)->chunk(1000)->each(function ($chunk) {
                    DB::table('empodat_search_matrices')->insert($chunk->toArray());
                });
            }
        });

        $insertedCount = count($insertData);

        if ($insertedCount > 0) {
            session()->flash('success', "Matrices updated successfully. {$insertedCount} valid matrices inserted.");
        } else {
            session()->flash('warning', 'No valid matrices found to insert.');
        }

        return redirect()->back();
    }

    public function updateDatabaseEntitiesCounts()
    {
        DatabaseEntity::where('code', 'empodat')->update([
            // 'last_update' => EmpodatMain::max('updated_at'),
            'number_of_records' => EmpodatMain::leftjoin('susdat_substances', 'empodat_main.substance_id', '=', 'susdat_substances.id')->where('susdat_substances.relevant_to_norman', 1)->count()
        ]);
        DatabaseEntity::where('code', 'susdat')->update([
            'last_update' => Substance::max('updated_at'),
            'number_of_records' => Substance::count()
        ]);
        session()->flash('success', 'Database counts updated successfully');
        return redirect()->back();
    }
}
