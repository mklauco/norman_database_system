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
    // Get empodat database entity
    $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();
    $allStats = [];
    
    if ($empodatEntity) {
        // Dynamically get all unique statistic keys for this entity
        $statisticKeys = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->distinct()
            ->pluck('key')
            ->toArray();
        
        foreach ($statisticKeys as $key) {
            $latestStat = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
                ->where('key', $key)
                ->latest('created_at')
                ->first();
            
            if ($latestStat) {
                $allStats[$key] = $latestStat->meta_data;
            }
        }
    }
    
    $totalRecords = $empodatEntity->number_of_records ?? 0;

    return view('empodat.statistics.index', [
        'empodatEntity' => $empodatEntity,
        'allStats' => $allStats,
        'totalRecords' => $totalRecords,
    ]);
}

  /**
   * Display country year statistics
   */
  /**
   * Generate and store country statistics data
   */
  /**
   * Generate and store country statistics data
   */
  public function generateCountryStats()
  {
    // Get the full range of years from the database
    $yearRange = EmpodatMain::selectRaw('MIN(sampling_date_year) as min_year, MAX(sampling_date_year) as max_year')
      ->whereNotNull('sampling_date_year')
      ->first();

    $dbMinYear = $yearRange->min_year ?? date('Y') - 10;
    $dbMaxYear = $yearRange->max_year ?? date('Y');

    // Get countries with their statistics for all years
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
      ->groupBy('lc.name', 'lc.code', 'em.sampling_date_year')
      ->orderBy('lc.name')
      ->orderBy('em.sampling_date_year')
      ->get();

    // Transform data into a structure suitable for storage
    $countryStats = [];
    foreach ($statistics as $stat) {
      $countryStats[$stat->country_name][$stat->sampling_date_year] = $stat->record_count;
    }

    // Get empodat database entity
    $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

    if (!$empodatEntity) {
      return back()->with('error', 'Empodat database entity not found.');
    }

    // Create new statistics record (no more updateOrCreate for historical tracking)
    $statisticsData = [
      'data'       => $countryStats,
      'year_range' => [
        'min_year' => $dbMinYear,
        'max_year' => $dbMaxYear
      ],
      'generated_at'    => now()->toISOString(),
      'total_countries' => count($countryStats)
    ];

    \App\Models\Statistic::create([
      'database_entity_id' => $empodatEntity->id,
      'key'                => 'country_year',
      'meta_data'          => $statisticsData
    ]);

    return back()->with('success', 'Country statistics generated and stored successfully.');
  }

  /**
   * Display country year statistics from stored JSON data
   */
  public function viewCountryStats(Request $request)
  {
    // Get empodat database entity
    $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

    if (!$empodatEntity) {
      return back()->with('error', 'Empodat database entity not found.');
    }

    // Get latest statistics record by key
    $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
      ->where('key', 'country_year')
      ->latest('created_at')
      ->first();

    if (!$statisticsRecord) {
      return view('empodat.statistics.countryYear', [
        'countryStats'   => [],
        'totalRecords'   => 0,
        'minYear'        => date('Y') - 10,
        'maxYear'        => date('Y'),
        'displayMinYear' => date('Y') - 10,
        'displayMaxYear' => date('Y'),
        'message'        => 'No country statistics data available. Please generate the statistics first.',
        'statistics'     => null,
      ]);
    }

    $countryData  = $statisticsRecord->meta_data;
    $countryStats = $countryData['data'];
    $dbMinYear    = $countryData['year_range']['min_year'];
    $dbMaxYear    = $countryData['year_range']['max_year'];

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

    // Get total records count for context
    $totalRecords = EmpodatMain::count();

    return view('empodat.statistics.countryYear', [
      'countryStats'   => $filteredCountryStats,
      'totalRecords'   => $totalRecords,
      'minYear'        => $dbMinYear,
      'maxYear'        => $dbMaxYear,
      'displayMinYear' => $displayMinYear,
      'displayMaxYear' => $displayMaxYear,
      'generatedAt'    => $countryData['generated_at'] ?? null,
      'statistics'     => $statisticsRecord,
    ]);
  }

  /**
   * Display matrix statistics
   */
  public function matrix()
  {
    return $this->viewMatrixStats();
  }

  /**
   * Generate and store matrix statistics data
   */
  public function generateMatrixStats()
  {
    // Get matrix statistics with hierarchical structure
    $statistics = DB::table('empodat_main as em')
      ->join('list_matrices as lm', 'em.matrix_id', '=', 'lm.id')
      ->select(
        'lm.title',
        'lm.subtitle',
        'lm.type',
        'lm.name as matrix_name',
        'lm.id as matrix_id',
        DB::raw('COUNT(*) as record_count')
      )
      ->groupBy('lm.title', 'lm.subtitle', 'lm.type', 'lm.name', 'lm.id')
      ->orderBy('lm.title')
      ->orderBy('lm.subtitle')
      ->orderBy('lm.type')
      ->get();

    // Transform data into hierarchical structure
    $matrixStats  = [];
    $totalRecords = 0;

    foreach ($statistics as $stat) {
      // Build the full hierarchy path
      $hierarchy                     = [];
      if ($stat->title) $hierarchy[]    = $stat->title;
      if ($stat->subtitle) $hierarchy[] = $stat->subtitle;
      if ($stat->type) $hierarchy[]     = $stat->type;

      $fullPath = implode(' → ', $hierarchy);
      $level    = count($hierarchy);

      $matrixStats[] = [
        'matrix_id'       => $stat->matrix_id,
        'matrix_name'     => $stat->matrix_name,
        'title'           => $stat->title,
        'subtitle'        => $stat->subtitle,
        'type'            => $stat->type,
        'hierarchy_path'  => $fullPath,
        'hierarchy_level' => $level,
        'record_count'    => $stat->record_count
      ];
      $totalRecords += $stat->record_count;
    }

    // Sort by hierarchy path for better organization
    usort($matrixStats, function ($a, $b) {
      return strcmp($a['hierarchy_path'], $b['hierarchy_path']);
    });

    // Get empodat database entity
    $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

    if (!$empodatEntity) {
      return back()->with('error', 'Empodat database entity not found.');
    }

    // Create new statistics record with key 'matrix'
    $statisticsData = [
      'data'           => $matrixStats,
      'generated_at'   => now()->toISOString(),
      'total_matrices' => count($matrixStats),
      'total_records'  => $totalRecords
    ];

    \App\Models\Statistic::create([
      'database_entity_id' => $empodatEntity->id,
      'key'                => 'matrix',
      'meta_data'          => $statisticsData
    ]);

    return back()->with('success', 'Matrix statistics generated and stored successfully.');
  }

  /**
   * Display matrix statistics from stored JSON data
   */
  public function viewMatrixStats()
  {
    // Get empodat database entity
    $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

    if (!$empodatEntity) {
      return back()->with('error', 'Empodat database entity not found.');
    }

    // Get latest statistics record by key
    $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
      ->where('key', 'matrix')
      ->latest('created_at')
      ->first();

    if (!$statisticsRecord) {
      return view('empodat.statistics.matrix', [
        'matrixStats'  => [],
        'totalRecords' => EmpodatMain::count(),
        'message'      => 'No matrix statistics data available. Please generate the statistics first.',
        'statistics'   => null,
      ]);
    }

    $matrixData  = $statisticsRecord->meta_data;
    $matrixStats = $matrixData['data'];

    // Get total records count for context
    $totalRecords = EmpodatMain::count();

    return view('empodat.statistics.matrix', [
      'matrixStats'   => $matrixStats,
      'totalRecords'  => $totalRecords,
      'generatedAt'   => $matrixData['generated_at'] ?? null,
      'totalMatrices' => $matrixData['total_matrices'] ?? 0,
      'statistics'    => $statisticsRecord,
    ]);
  }

  /**
   * Download matrix statistics as CSV
   */
  private function downloadMatrixCsv()
  {
    // Get empodat database entity
    $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

    if (!$empodatEntity) {
      return response()->json(['error' => 'Empodat database entity not found.'], 404);
    }

    // Get statistics record
    $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)->first();

    if (!$statisticsRecord || !isset($statisticsRecord->meta_data['matrix_statistics'])) {
      return response()->json(['error' => 'No matrix statistics available.'], 404);
    }

    $matrixData  = $statisticsRecord->meta_data['matrix_statistics'];
    $matrixStats = $matrixData['data'];

    $csvData   = [];
    $csvData[] = ['Title', 'Subtitle', 'Type', 'Matrix Name', 'Hierarchy Path', 'Record Count'];

    foreach ($matrixStats as $stat) {
      $csvData[] = [
        $stat['title'] ?? '',
        $stat['subtitle'] ?? '',
        $stat['type'] ?? '',
        $stat['matrix_name'],
        $stat['hierarchy_path'],
        $stat['record_count']
      ];
    }

    $filename = 'empodat_matrix_statistics_' . date('Y-m-d') . '.csv';

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

    $filename = 'empodat_country_year_statistics_' . $displayMinYear . '_to_' . $displayMaxYear . '_' . date('Y-m-d') . '.csv';

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

    $csvData   = [];
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

    $csvData   = [];
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
      $csvContent .= implode(',', array_map(function ($field) {
        return '"' . str_replace('"', '""', $field) . '"';
      }, $row)) . "\n";
    }

    return response($csvContent)
      ->header('Content-Type', 'text/csv')
      ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
  }

  /**
     * Display substance statistics
     */
    public function substance()
    {
        return $this->viewSubstanceStats();
    }

    /**
     * Generate and store substance statistics data
     */
    public function generateSubstanceStats()
    {
        // Get substance statistics
        $statistics = DB::table('empodat_main as em')
            ->join('susdat_substances as ss', 'em.substance_id', '=', 'ss.id')
            ->select(
                'ss.id as substance_id',
                'ss.code as substance_code',
                'ss.name as substance_name',
                DB::raw('COUNT(*) as record_count')
            )
            ->groupBy('ss.id', 'ss.code', 'ss.name')
            ->orderBy('record_count', 'desc')
            ->get();

        // Transform data into a structure suitable for storage
        $substanceStats = [];
        $totalRecords = 0;

        foreach ($statistics as $stat) {
            // Create substance prefix (NS + code)
            $substancePrefix = 'NS' . $stat->substance_code;

            $substanceStats[] = [
                'substance_id' => $stat->substance_id,
                'substance_prefix' => $substancePrefix,
                'substance_name' => $stat->substance_name,
                'substance_code' => $stat->substance_code,
                'record_count' => $stat->record_count
            ];
            $totalRecords += $stat->record_count;
        }

        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (!$empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Create new statistics record with key 'substance'
        $statisticsData = [
            'data' => $substanceStats,
            'generated_at' => now()->toISOString(),
            'total_substances' => count($substanceStats),
            'total_records' => $totalRecords
        ];

        \App\Models\Statistic::create([
            'database_entity_id' => $empodatEntity->id,
            'key' => 'substance',
            'meta_data' => $statisticsData
        ]);

        return back()->with('success', 'Substance statistics generated and stored successfully.');
    }

    /**
     * Display substance statistics from stored JSON data
     */
    public function viewSubstanceStats()
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (!$empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', 'substance')
            ->latest('created_at')
            ->first();

        if (!$statisticsRecord) {
            return view('empodat.statistics.substance', [
                'substanceStats' => [],
                'totalRecords' => EmpodatMain::count(),
                'message' => 'No substance statistics data available. Please generate the statistics first.',
                'statistics' => null,
            ]);
        }

        $substanceData = $statisticsRecord->meta_data;
        $substanceStats = $substanceData['data'];

        // Get total records count for context
        $totalRecords = EmpodatMain::count();

        return view('empodat.statistics.substance', [
            'substanceStats' => $substanceStats,
            'totalRecords' => $totalRecords,
            'generatedAt' => $substanceData['generated_at'] ?? null,
            'totalSubstances' => $substanceData['total_substances'] ?? 0,
            'statistics' => $statisticsRecord,
        ]);
    }

    /**
     * Download substance statistics as CSV
     */
    private function downloadSubstanceCsv()
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (!$empodatEntity) {
            return response()->json(['error' => 'Empodat database entity not found.'], 404);
        }

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', 'substance')
            ->latest('created_at')
            ->first();

        if (!$statisticsRecord) {
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
                $stat['record_count']
            ];
        }

        $filename = 'empodat_substance_statistics_' . date('Y-m-d') . '.csv';

        return $this->generateCsvResponse($csvData, $filename);
    }

    /**
     * Display quality statistics
     */
    public function quality()
    {
        return $this->viewQualityStats();
    }

    /**
     * Generate and store quality statistics data
     */
    public function generateQualityStats()
    {
        // Get all quality categories
        $qualityCategories = DB::table('list_quality_empodat_analytical_methods')
            ->orderBy('min_rating', 'desc')
            ->get();

        $qualityStats = [];
        $totalRecords = 0;

        foreach ($qualityCategories as $category) {
            // Count records for each quality category
            $recordCount = DB::table('empodat_main as em')
                ->join('empodat_analytical_methods as eam', 'eam.id', '=', 'em.method_id')
                ->where('eam.rating', '>=', $category->min_rating)
                ->where('eam.rating', '<', $category->max_rating)
                ->count();

            $qualityStats[] = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'min_rating' => $category->min_rating,
                'max_rating' => $category->max_rating,
                'rating_range' => $category->min_rating . '-' . ($category->max_rating - 1),
                'record_count' => $recordCount
            ];

            $totalRecords += $recordCount;
        }

        // Get records with no rating (NULL or no analytical method)
        $noRatingCount = DB::table('empodat_main as em')
            ->leftJoin('empodat_analytical_methods as eam', 'eam.id', '=', 'em.method_id')
            ->where(function($query) {
                $query->whereNull('eam.rating')
                      ->orWhereNull('em.method_id');
            })
            ->count();

        if ($noRatingCount > 0) {
            $qualityStats[] = [
                'category_id' => null,
                'category_name' => 'No quality rating available',
                'min_rating' => null,
                'max_rating' => null,
                'rating_range' => 'N/A',
                'record_count' => $noRatingCount
            ];
            $totalRecords += $noRatingCount;
        }

        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (!$empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Create new statistics record with key 'quality'
        $statisticsData = [
            'data' => $qualityStats,
            'generated_at' => now()->toISOString(),
            'total_categories' => count($qualityStats),
            'total_records' => $totalRecords
        ];

        \App\Models\Statistic::create([
            'database_entity_id' => $empodatEntity->id,
            'key' => 'quality',
            'meta_data' => $statisticsData
        ]);

        return back()->with('success', 'Quality statistics generated and stored successfully.');
    }

    /**
     * Display quality statistics from stored JSON data
     */
    public function viewQualityStats()
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (!$empodatEntity) {
            return back()->with('error', 'Empodat database entity not found.');
        }

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', 'quality')
            ->latest('created_at')
            ->first();

        if (!$statisticsRecord) {
            return view('empodat.statistics.quality', [
                'qualityStats' => [],
                'totalRecords' => EmpodatMain::count(),
                'message' => 'No quality statistics data available. Please generate the statistics first.',
                'statistics' => null,
            ]);
        }

        $qualityData = $statisticsRecord->meta_data;
        $qualityStats = $qualityData['data'];

        // Get total records count for context
        $totalRecords = EmpodatMain::count();

        return view('empodat.statistics.quality', [
            'qualityStats' => $qualityStats,
            'totalRecords' => $totalRecords,
            'generatedAt' => $qualityData['generated_at'] ?? null,
            'totalCategories' => $qualityData['total_categories'] ?? 0,
            'statistics' => $statisticsRecord,
        ]);
    }

    /**
     * Download quality statistics as CSV
     */
    private function downloadQualityCsv()
    {
        // Get empodat database entity
        $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();

        if (!$empodatEntity) {
            return response()->json(['error' => 'Empodat database entity not found.'], 404);
        }

        // Get latest statistics record by key
        $statisticsRecord = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
            ->where('key', 'quality')
            ->latest('created_at')
            ->first();

        if (!$statisticsRecord) {
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
                $stat['record_count']
            ];
        }

        $filename = 'empodat_quality_statistics_' . date('Y-m-d') . '.csv';

        return $this->generateCsvResponse($csvData, $filename);
    }
}
