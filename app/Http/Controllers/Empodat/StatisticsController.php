<?php

namespace App\Http\Controllers\Empodat;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Empodat\EmpodatMain;
use App\Models\Empodat\EmpodatStation;
use App\Models\Susdat\Substance;
use App\Models\List\Matrix;
use App\Http\Controllers\Controller;

class StatisticsController extends Controller
{
    /**
     * Display statistics overview page
     */
    public function index()
    {
        // Get basic statistics for the overview
        $totalRecords = EmpodatMain::count();
        
        // Get unique substance count
        $substanceCount = EmpodatMain::distinct('substance_id')
            ->whereNotNull('substance_id')
            ->count('substance_id');
        
        // Get unique country count
        $countryCount = EmpodatStation::join('list_countries', 'empodat_stations.country_id', '=', 'list_countries.id')
            ->distinct('list_countries.id')
            ->count('list_countries.id');
        
        // Get unique matrix count
        $matrixCount = EmpodatMain::distinct('matrix_id')
            ->whereNotNull('matrix_id')
            ->count('matrix_id');
        
        // Get year range
        $yearRange = EmpodatMain::selectRaw('MIN(sampling_date_year) as min_year, MAX(sampling_date_year) as max_year')
            ->whereNotNull('sampling_date_year')
            ->first();

        $minYear = $yearRange->min_year ?? date('Y') - 10;
        $maxYear = $yearRange->max_year ?? date('Y');

        return view('empodat.statistics.index', [
            'totalRecords' => $totalRecords,
            'substanceCount' => $substanceCount,
            'countryCount' => $countryCount,
            'matrixCount' => $matrixCount,
            'minYear' => $minYear,
            'maxYear' => $maxYear,
        ]);
    }

    /**
     * Display country year statistics
     */
    public function countryYear(Request $request)
    {
        // Get the full range of years from the database
        $yearRange = EmpodatMain::selectRaw('MIN(sampling_date_year) as min_year, MAX(sampling_date_year) as max_year')
            ->whereNotNull('sampling_date_year')
            ->first();

        $dbMinYear = $yearRange->min_year ?? date('Y') - 10;
        $dbMaxYear = $yearRange->max_year ?? date('Y');

        // Get display year range from request or use defaults
        $displayMinYear = $request->input('min_year', date('Y') - 10);
        $displayMaxYear = $request->input('max_year', date('Y'));

        // Validate year range
        $displayMinYear = max($displayMinYear, $dbMinYear);
        $displayMaxYear = min($displayMaxYear, $dbMaxYear);
        
        if ($displayMinYear > $displayMaxYear) {
            $displayMinYear = $displayMaxYear;
        }

        // Get countries with their statistics for the selected year range
        $statistics = DB::table('empodat_main as em')
            ->join('empodat_stations as es', 'em.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                'em.sampling_date_year',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('em.sampling_date_year')
            ->whereBetween('em.sampling_date_year', [$displayMinYear, $displayMaxYear])
            ->groupBy('lc.name', 'lc.code', 'em.sampling_date_year')
            ->orderBy('lc.name')
            ->orderBy('em.sampling_date_year')
            ->get();

        // Transform data into a structure suitable for the view
        $countryStats = [];
        foreach ($statistics as $stat) {
            $countryStats[$stat->country_name][$stat->sampling_date_year] = $stat->record_count;
        }

        // Get total records count for context
        $totalRecords = EmpodatMain::count();

        return view('empodat.statistics.countryYear', [
            'countryStats' => $countryStats,
            'totalRecords' => $totalRecords,
            'minYear' => $dbMinYear,
            'maxYear' => $dbMaxYear,
            'displayMinYear' => $displayMinYear,
            'displayMaxYear' => $displayMaxYear,
        ]);
    }

    /**
     * Display matrix statistics
     */
    public function matrix()
    {
        $matrixStats = DB::table('empodat_main as em')
            ->join('list_matrices as lm', 'em.matrix_id', '=', 'lm.id')
            ->select(
                'lm.name as matrix_name',
                'lm.code as matrix_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->groupBy('lm.name', 'lm.code', 'lm.id')
            ->orderBy('record_count', 'desc')
            ->get();

        $totalRecords = EmpodatMain::count();

        return view('empodat.statistics.matrix', [
            'matrixStats' => $matrixStats,
            'totalRecords' => $totalRecords,
        ]);
    }

    /**
     * Display sub-matrix statistics
     */
    public function subMatrix()
    {
        //
    }

    /**
     * Display country statistics
     */
    public function country()
    {
        $countryStats = DB::table('empodat_main as em')
            ->join('empodat_stations as es', 'em.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->groupBy('lc.name', 'lc.code', 'lc.id')
            ->orderBy('record_count', 'desc')
            ->get();

        $totalRecords = EmpodatMain::count();

        return view('empodat.statistics.country', [
            'countryStats' => $countryStats,
            'totalRecords' => $totalRecords,
        ]);
    }

    /**
     * Display method statistics
     */
    public function method()
    {
        $methodStats = DB::table('empodat_main as em')
            ->join('empodat_analytical_methods as eam', 'em.method_id', '=', 'eam.id')
            ->select(
                'eam.name as method_name',
                'eam.code as method_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->groupBy('eam.name', 'eam.code', 'eam.id')
            ->orderBy('record_count', 'desc')
            ->get();

        $totalRecords = EmpodatMain::count();

        return view('empodat.statistics.method', [
            'methodStats' => $methodStats,
            'totalRecords' => $totalRecords,
        ]);
    }

    /**
     * Display QA/QC category statistics
     */
    public function qaqc()
    {
        //
    }

    /**
     * Download statistics as CSV
     */
    public function downloadCsv(Request $request)
    {
        $type = $request->input('type', 'countryYear');
        
        switch ($type) {
            case 'countryYear':
                return $this->downloadCountryYearCsv($request);
            case 'matrix':
                return $this->downloadMatrixCsv();
            case 'country':
                return $this->downloadCountryCsv();
            case 'method':
                return $this->downloadMethodCsv();
            default:
                return $this->downloadCountryYearCsv($request);
        }
    }

    /**
     * Download country year statistics as CSV
     */
    private function downloadCountryYearCsv(Request $request)
    {
        // Get the same data as the countryYear method
        $yearRange = EmpodatMain::selectRaw('MIN(sampling_date_year) as min_year, MAX(sampling_date_year) as max_year')
            ->whereNotNull('sampling_date_year')
            ->first();

        $dbMinYear = $yearRange->min_year ?? date('Y') - 10;
        $dbMaxYear = $yearRange->max_year ?? date('Y');

        $displayMinYear = $request->input('min_year', date('Y') - 10);
        $displayMaxYear = $request->input('max_year', date('Y'));

        $displayMinYear = max($displayMinYear, $dbMinYear);
        $displayMaxYear = min($displayMaxYear, $dbMaxYear);
        
        if ($displayMinYear > $displayMaxYear) {
            $displayMinYear = $displayMaxYear;
        }

        $displayYears = range($displayMinYear, $displayMaxYear);

        $statistics = DB::table('empodat_main as em')
            ->join('empodat_stations as es', 'em.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                'em.sampling_date_year',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('em.sampling_date_year')
            ->whereBetween('em.sampling_date_year', [$displayMinYear, $displayMaxYear])
            ->groupBy('lc.name', 'lc.code', 'em.sampling_date_year')
            ->orderBy('lc.name')
            ->orderBy('em.sampling_date_year')
            ->get();

        $countryStats = [];
        foreach ($statistics as $stat) {
            $countryStats[$stat->country_name][$stat->sampling_date_year] = $stat->record_count;
        }

        // Generate CSV content
        $csvData = [];
        
        // Header row
        $header = ['Country'];
        foreach ($displayYears as $year) {
            $header[] = $year;
        }
        $csvData[] = $header;

        // Data rows
        foreach ($countryStats as $country => $yearData) {
            $row = [$country];
            foreach ($displayYears as $year) {
                $row[] = isset($yearData[$year]) ? $yearData[$year] : '';
            }
            $csvData[] = $row;
        }

        $filename = 'empodat_country_year_statistics_' . $displayMinYear . '_to_' . $displayMaxYear . '_' . date('Y-m-d') . '.csv';
        
        return $this->generateCsvResponse($csvData, $filename);
    }

    /**
     * Download matrix statistics as CSV
     */
    private function downloadMatrixCsv()
    {
        $matrixStats = DB::table('empodat_main as em')
            ->join('list_matrices as lm', 'em.matrix_id', '=', 'lm.id')
            ->select(
                'lm.name as matrix_name',
                'lm.code as matrix_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->groupBy('lm.name', 'lm.code', 'lm.id')
            ->orderBy('record_count', 'desc')
            ->get();

        $csvData = [];
        $csvData[] = ['Matrix Name', 'Matrix Code', 'Record Count'];

        foreach ($matrixStats as $stat) {
            $csvData[] = [
                $stat->matrix_name,
                $stat->matrix_code,
                $stat->record_count
            ];
        }

        $filename = 'empodat_matrix_statistics_' . date('Y-m-d') . '.csv';
        
        return $this->generateCsvResponse($csvData, $filename);
    }

    /**
     * Download country statistics as CSV
     */
    private function downloadCountryCsv()
    {
        $countryStats = DB::table('empodat_main as em')
            ->join('empodat_stations as es', 'em.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->groupBy('lc.name', 'lc.code', 'lc.id')
            ->orderBy('record_count', 'desc')
            ->get();

        $csvData = [];
        $csvData[] = ['Country Name', 'Country Code', 'Record Count'];

        foreach ($countryStats as $stat) {
            $csvData[] = [
                $stat->country_name,
                $stat->country_code,
                $stat->record_count
            ];
        }

        $filename = 'empodat_country_statistics_' . date('Y-m-d') . '.csv';
        
        return $this->generateCsvResponse($csvData, $filename);
    }

    /**
     * Download method statistics as CSV
     */
    private function downloadMethodCsv()
    {
        $methodStats = DB::table('empodat_main as em')
            ->join('empodat_analytical_methods as eam', 'em.method_id', '=', 'eam.id')
            ->select(
                'eam.name as method_name',
                'eam.code as method_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->groupBy('eam.name', 'eam.code', 'eam.id')
            ->orderBy('record_count', 'desc')
            ->get();

        $csvData = [];
        $csvData[] = ['Method Name', 'Method Code', 'Record Count'];

        foreach ($methodStats as $stat) {
            $csvData[] = [
                $stat->method_name,
                $stat->method_code,
                $stat->record_count
            ];
        }

        $filename = 'empodat_method_statistics_' . date('Y-m-d') . '.csv';
        
        return $this->generateCsvResponse($csvData, $filename);
    }

    /**
     * Generate CSV response
     */
    private function generateCsvResponse($csvData, $filename)
    {
        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}