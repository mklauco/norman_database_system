<?php

namespace App\Http\Controllers\Empodat;

use App\Http\Controllers\Controller;
use App\Jobs\Empodat\GenerateEmpodatStatisticsJob;
use App\Models\Empodat\EmpodatMain;
use App\Models\List\Matrix;
use App\Models\Susdat\Substance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * Get statistics key with scope suffix
     *
     * @param  string  $baseKey  Base key like 'country_year', 'matrix', etc.
     * @param  string  $scope  'public' or 'all'
     */
    private function getStatisticsKey(string $baseKey, string $scope = 'public'): string
    {
        return $scope === 'public' ? $baseKey : $baseKey.'_all';
    }

    /**
     * Display statistics overview page
     */
    public function index(Request $request)
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();
        $allStats = [];

        // Determine which scope to display
        $scope = $request->input('scope', 'public');

        // Only admins can view 'all' scope
        $canViewAll = Auth::check() && (
            Auth::user()->hasRole('super_admin') ||
            Auth::user()->hasRole('admin') ||
            Auth::user()->hasRole('empodat')
        );

        if ($scope === 'all' && ! $canViewAll) {
            $scope = 'public';
        }

        $totalRecords = 0;

        if ($empodatEntity) {
            // Get the base keys we're interested in
            $baseKeys = ['country_year', 'matrix', 'substance', 'quality'];

            foreach ($baseKeys as $baseKey) {
                $key = $this->getStatisticsKey($baseKey, $scope);

                $latestStat = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
                    ->where('key', $key)
                    ->latest('created_at')
                    ->first();

                if ($latestStat) {
                    // Store under the base key for template compatibility
                    $allStats[$baseKey] = $latestStat->meta_data;

                    // Use stored total_records from any available statistic
                    if ($totalRecords === 0 && isset($latestStat->meta_data['total_records'])) {
                        $totalRecords = $latestStat->meta_data['total_records'];
                    }
                }
            }
        }

        return view('empodat.statistics.index', [
            'empodatEntity' => $empodatEntity,
            'allStats' => $allStats,
            'totalRecords' => $totalRecords,
            'scope' => $scope,
            'canViewAll' => $canViewAll,
        ]);
    }

    /**
     * Queue one run that regenerates every statistic in both scopes.
     */
    public function generateStatistics(): RedirectResponse
    {
        abort_unless(Auth::user()?->hasRole('super_admin'), 403);

        GenerateEmpodatStatisticsJob::dispatch();

        return back()->with('success', 'Statistics generation queued. It takes a few minutes; refresh this page afterwards.');
    }

    /**
     * Display country year statistics from stored JSON data
     */
    public function viewCountryStats(Request $request)
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Determine which scope to display
        $scope = $request->input('scope', 'public');

        // Only admins can view 'all' scope
        $canViewAll = Auth::check() && (
            Auth::user()->hasRole('super_admin') ||
            Auth::user()->hasRole('admin') ||
            Auth::user()->hasRole('empodat')
        );

        if ($scope === 'all' && ! $canViewAll) {
            $scope = 'public';
        }

        $key = $this->getStatisticsKey('country_year', $scope);

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', $key)
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat.statistics.countryYear', [
                'countryStats' => [],
                'totalRecords' => 0,
                'minYear' => date('Y') - 10,
                'maxYear' => date('Y'),
                'displayMinYear' => date('Y') - 10,
                'displayMaxYear' => date('Y'),
                'message' => 'No country statistics data available. Please generate the statistics first.',
                'statistics' => null,
                'scope' => $scope,
                'canViewAll' => $canViewAll,
            ]);
        }

        $countryData = $statisticsRecord->meta_data;
        $countryStats = $countryData['data'];
        $dbMinYear = $countryData['year_range']['min_year'];
        $dbMaxYear = $countryData['year_range']['max_year'];

        // Get display year range from request or use defaults
        $displayMinYear = $request->input('min_year', date('Y') - 10);
        $displayMaxYear = $request->input('max_year', date('Y'));

        // Validate year range
        $displayMinYear = max($displayMinYear, $dbMinYear);
        $displayMaxYear = min($displayMaxYear, $dbMaxYear);

        if ($displayMinYear > $displayMaxYear) {
            $displayMinYear = $displayMaxYear;
        }

        // Filter country stats by display year range
        $filteredCountryStats = [];
        foreach ($countryStats as $country => $yearData) {
            foreach ($yearData as $year => $count) {
                if ($year >= $displayMinYear && $year <= $displayMaxYear) {
                    $filteredCountryStats[$country][$year] = $count;
                }
            }
        }

        // Use stored total_records from generation time
        $totalRecords = $countryData['total_records'] ?? 0;

        return view('empodat.statistics.countryYear', [
            'countryStats' => $filteredCountryStats,
            'totalRecords' => $totalRecords,
            'minYear' => $dbMinYear,
            'maxYear' => $dbMaxYear,
            'displayMinYear' => $displayMinYear,
            'displayMaxYear' => $displayMaxYear,
            'generatedAt' => $countryData['generated_at'] ?? null,
            'statistics' => $statisticsRecord,
            'scope' => $scope,
            'canViewAll' => $canViewAll,
        ]);
    }

    /**
     * Display matrix statistics
     */
    public function matrix(Request $request)
    {
        return $this->viewMatrixStats($request);
    }

    /**
     * Display matrix statistics from stored JSON data
     */
    public function viewMatrixStats(?Request $request = null)
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Determine which scope to display
        $scope = $request ? $request->input('scope', 'public') : 'public';

        // Only admins can view 'all' scope
        $canViewAll = Auth::check() && (
            Auth::user()->hasRole('super_admin') ||
            Auth::user()->hasRole('admin') ||
            Auth::user()->hasRole('empodat')
        );

        if ($scope === 'all' && ! $canViewAll) {
            $scope = 'public';
        }

        $key = $this->getStatisticsKey('matrix', $scope);

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', $key)
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat.statistics.matrix', [
                'matrixStats' => [],
                'totalRecords' => 0,
                'message' => 'No matrix statistics data available. Please generate the statistics first.',
                'statistics' => null,
                'scope' => $scope,
                'canViewAll' => $canViewAll,
            ]);
        }

        $matrixData = $statisticsRecord->meta_data;
        $matrixStats = $matrixData['data'];

        // Use stored total_records from generation time
        $totalRecords = $matrixData['total_records'] ?? 0;

        return view('empodat.statistics.matrix', [
            'matrixStats' => $matrixStats,
            'totalRecords' => $totalRecords,
            'generatedAt' => $matrixData['generated_at'] ?? null,
            'totalMatrices' => $matrixData['total_matrices'] ?? 0,
            'statistics' => $statisticsRecord,
            'scope' => $scope,
            'canViewAll' => $canViewAll,
        ]);
    }

    /**
     * Download matrix statistics as CSV
     */
    private function downloadMatrixCsv()
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return response()->json(['error' => 'Empodat database entity not found.'], 404);
        }

        // Get statistics record
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)->first();

        if (! $statisticsRecord || ! isset($statisticsRecord->meta_data['matrix_statistics'])) {
            return response()->json(['error' => 'No matrix statistics available.'], 404);
        }

        $matrixData = $statisticsRecord->meta_data['matrix_statistics'];
        $matrixStats = $matrixData['data'];

        $csvData = [];
        $csvData[] = ['Title', 'Subtitle', 'Type', 'Matrix Name', 'Hierarchy Path', 'Record Count'];

        foreach ($matrixStats as $stat) {
            $csvData[] = [
                $stat['title'] ?? '',
                $stat['subtitle'] ?? '',
                $stat['type'] ?? '',
                $stat['matrix_name'],
                $stat['hierarchy_path'],
                $stat['record_count'],
            ];
        }

        $filename = 'empodat_matrix_statistics_'.date('Y-m-d').'.csv';

        return $this->generateCsvResponse($csvData, $filename);
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
            case 'substance':
                return $this->downloadSubstanceCsv();
            case 'country':
                return $this->downloadCountryCsv();
            case 'method':
                return $this->downloadMethodCsv();
            case 'quality':
                return $this->downloadQualityCsv();
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

        $filename = 'empodat_country_year_statistics_'.$displayMinYear.'_to_'.$displayMaxYear.'_'.date('Y-m-d').'.csv';

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
                $stat->record_count,
            ];
        }

        $filename = 'empodat_country_statistics_'.date('Y-m-d').'.csv';

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
                $stat->record_count,
            ];
        }

        $filename = 'empodat_method_statistics_'.date('Y-m-d').'.csv';

        return $this->generateCsvResponse($csvData, $filename);
    }

    /**
     * Generate CSV response
     */
    private function generateCsvResponse($csvData, $filename)
    {
        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= implode(',', array_map(function ($field) {
                return '"'.str_replace('"', '""', $field).'"';
            }, $row))."\n";
        }

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    /**
     * Display substance statistics
     */
    public function substance(Request $request)
    {
        return $this->viewSubstanceStats($request);
    }

    /**
     * Display substance statistics from stored JSON data
     */
    public function viewSubstanceStats(?Request $request = null)
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Determine which scope to display
        $scope = $request ? $request->input('scope', 'public') : 'public';

        // Only admins can view 'all' scope
        $canViewAll = Auth::check() && (
            Auth::user()->hasRole('super_admin') ||
            Auth::user()->hasRole('admin') ||
            Auth::user()->hasRole('empodat')
        );

        if ($scope === 'all' && ! $canViewAll) {
            $scope = 'public';
        }

        $key = $this->getStatisticsKey('substance', $scope);

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', $key)
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat.statistics.substance', [
                'substanceStats' => [],
                'totalRecords' => 0,
                'message' => 'No substance statistics data available. Please generate the statistics first.',
                'statistics' => null,
                'scope' => $scope,
                'canViewAll' => $canViewAll,
            ]);
        }

        $substanceData = $statisticsRecord->meta_data;
        $substanceStats = $substanceData['data'];

        // Use stored total_records from generation time
        $totalRecords = $substanceData['total_records'] ?? 0;

        return view('empodat.statistics.substance', [
            'substanceStats' => $substanceStats,
            'totalRecords' => $totalRecords,
            'generatedAt' => $substanceData['generated_at'] ?? null,
            'totalSubstances' => $substanceData['total_substances'] ?? 0,
            'statistics' => $statisticsRecord,
            'scope' => $scope,
            'canViewAll' => $canViewAll,
        ]);
    }

    /**
     * Download substance statistics as CSV
     */
    private function downloadSubstanceCsv()
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return response()->json(['error' => 'Empodat database entity not found.'], 404);
        }

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', 'substance')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return response()->json(['error' => 'No substance statistics available.'], 404);
        }

        $substanceData = $statisticsRecord->meta_data;
        $substanceStats = $substanceData['data'];

        $csvData = [];
        $csvData[] = ['Substance Prefix', 'Substance Name', 'Record Count'];

        foreach ($substanceStats as $stat) {
            $csvData[] = [
                $stat['substance_prefix'],
                $stat['substance_name'],
                $stat['record_count'],
            ];
        }

        $filename = 'empodat_substance_statistics_'.date('Y-m-d').'.csv';

        return $this->generateCsvResponse($csvData, $filename);
    }

    /**
     * Display quality statistics
     */
    public function quality(Request $request)
    {
        return $this->viewQualityStats($request);
    }

    /**
     * Display quality statistics from stored JSON data
     */
    public function viewQualityStats(?Request $request = null)
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Determine which scope to display
        $scope = $request ? $request->input('scope', 'public') : 'public';

        // Only admins can view 'all' scope
        $canViewAll = Auth::check() && (
            Auth::user()->hasRole('super_admin') ||
            Auth::user()->hasRole('admin') ||
            Auth::user()->hasRole('empodat')
        );

        if ($scope === 'all' && ! $canViewAll) {
            $scope = 'public';
        }

        $key = $this->getStatisticsKey('quality', $scope);

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', $key)
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat.statistics.quality', [
                'qualityStats' => [],
                'totalRecords' => 0,
                'message' => 'No quality statistics data available. Please generate the statistics first.',
                'statistics' => null,
                'scope' => $scope,
                'canViewAll' => $canViewAll,
            ]);
        }

        $qualityData = $statisticsRecord->meta_data;
        $qualityStats = $qualityData['data'];

        // Use stored total_records from generation time
        $totalRecords = $qualityData['total_records'] ?? 0;

        return view('empodat.statistics.quality', [
            'qualityStats' => $qualityStats,
            'totalRecords' => $totalRecords,
            'generatedAt' => $qualityData['generated_at'] ?? null,
            'totalCategories' => $qualityData['total_categories'] ?? 0,
            'statistics' => $statisticsRecord,
            'scope' => $scope,
            'canViewAll' => $canViewAll,
        ]);
    }

    /**
     * Download quality statistics as CSV
     */
    private function downloadQualityCsv()
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (! $empodatEntity) {
            return response()->json(['error' => 'Empodat database entity not found.'], 404);
        }

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', 'quality')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return response()->json(['error' => 'No quality statistics available.'], 404);
        }

        $qualityData = $statisticsRecord->meta_data;
        $qualityStats = $qualityData['data'];

        $csvData = [];
        $csvData[] = ['Quality Category', 'Rating Range', 'Min Rating', 'Max Rating', 'Record Count'];

        foreach ($qualityStats as $stat) {
            $csvData[] = [
                $stat['category_name'],
                $stat['rating_range'],
                $stat['min_rating'] ?? 'N/A',
                $stat['max_rating'] ?? 'N/A',
                $stat['record_count'],
            ];
        }

        $filename = 'empodat_quality_statistics_'.date('Y-m-d').'.csv';

        return $this->generateCsvResponse($csvData, $filename);
    }
}
