<?php

namespace App\Http\Controllers\Factsheet;

use App\Http\Controllers\Controller;
use App\Models\Empodat\EmpodatMain;
use App\Models\Factsheet\FactsheetStatistic;
use App\Models\List\Matrix;
use App\Models\Susdat\Substance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FactsheetStatisticsController extends Controller
{
    /**
     * Populate factsheet statistics records for all unique substances in EmpodatMain with NULL meta_data
     */
    public function populateAll()
    {
        try {
            // Get all unique substance IDs from EmpodatMain
            $uniqueSubstanceIds = EmpodatMain::distinct()
                ->whereNotNull('substance_id')
                ->pluck('substance_id')
                ->toArray();

            if (empty($uniqueSubstanceIds)) {
                return back()->with('error', 'No substances found to process.');
            }

            // Get substance IDs that already have factsheet statistics records
            $existingSubstanceIds = FactsheetStatistic::whereIn('substance_id', $uniqueSubstanceIds)
                ->pluck('substance_id')
                ->toArray();

            // Filter out substances that already have records
            $newSubstanceIds = array_diff($uniqueSubstanceIds, $existingSubstanceIds);

            if (empty($newSubstanceIds)) {
                return back()->with('info', 'All substances already have factsheet statistic records.');
            }

            // Create records with NULL meta_data for new substances only
            $records = [];
            $now = now();

            foreach ($newSubstanceIds as $substanceId) {
                $records[] = [
                    'substance_id' => $substanceId,
                    'meta_data' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Insert new records
            FactsheetStatistic::insert($records);

            $processed = count($newSubstanceIds);

            return back()->with('success', "Successfully created {$processed} factsheet statistic records with NULL meta_data.");

        } catch (\Exception $e) {
            Log::error('Failed to populate factsheet statistics: '.$e->getMessage());

            return back()->with('error', 'Failed to populate statistics records. Check logs for details.');
        }
    }

    /**
     * Generate statistics for a specific substance
     */
    public function generateForSubstance(Request $request)
    {
        $substanceId = $request->input('substance_id');

        if (! $substanceId) {
            return back()->with('error', 'Substance ID is required.');
        }

        // Verify substance exists
        $substance = Substance::find($substanceId);
        if (! $substance) {
            return back()->with('error', 'Substance not found.');
        }

        try {
            $this->generateStatisticsForSubstance($substanceId);

            return back()->with('success', 'Statistics generated successfully for '.$substance->name);
        } catch (\Exception $e) {
            Log::error("Failed to generate statistics for substance {$substance->name} (ID: {$substanceId}): ".$e->getMessage());

            return back()->with('error', 'Failed to generate statistics. Please try again.');
        }
    }

    /**
     * Generate comprehensive statistics for a specific substance
     */
    private function generateStatisticsForSubstance($substanceId)
    {
        // Generate all statistics categories
        $countryYearStats = $this->generateCountryYearStats($substanceId);
        $matrixStats = $this->generateMatrixStats($substanceId);
        $countryStats = $this->generateCountryStats($substanceId);
        $qualityStats = $this->generateQualityStats($substanceId);
        $yearRangeStats = $this->generateYearRangeStats($substanceId);

        // Combine all statistics
        $allStats = [
            'country_year' => $countryYearStats,
            'matrix' => $matrixStats,
            'country' => $countryStats,
            'quality' => $qualityStats,
            'year_range' => $yearRangeStats,
            'generated_at' => now()->toISOString(),
            'total_records' => EmpodatMain::where('substance_id', $substanceId)->count(),
        ];

        // Store or update statistics
        FactsheetStatistic::updateOrCreate(
            ['substance_id' => $substanceId],
            ['meta_data' => $allStats]
        );
    }

    /**
     * Generate country year statistics for a substance
     */
    private function generateCountryYearStats($substanceId)
    {
        // Get the year range for this substance
        $yearRange = EmpodatMain::where('substance_id', $substanceId)
            ->selectRaw('MIN(sampling_date_year) as min_year, MAX(sampling_date_year) as max_year')
            ->whereNotNull('sampling_date_year')
            ->first();

        $dbMinYear = $yearRange->min_year ?? date('Y');
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
            ->where('em.substance_id', $substanceId)
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

        return [
            'data' => $countryStats,
            'year_range' => [
                'min_year' => $dbMinYear,
                'max_year' => $dbMaxYear,
            ],
            'total_countries' => count($countryStats),
        ];
    }

    /**
     * Generate matrix statistics for a substance
     */
    private function generateMatrixStats($substanceId)
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
            ->where('em.substance_id', $substanceId)
            ->groupBy('lm.title', 'lm.subtitle', 'lm.type', 'lm.name', 'lm.id')
            ->orderBy('lm.title')
            ->orderBy('lm.subtitle')
            ->orderBy('lm.type')
            ->get();

        // Transform data into hierarchical structure
        $matrixStats = [];
        $totalRecords = 0;

        foreach ($statistics as $stat) {
            // Build the full hierarchy path
            $hierarchy = [];
            if ($stat->title) {
                $hierarchy[] = $stat->title;
            }
            if ($stat->subtitle) {
                $hierarchy[] = $stat->subtitle;
            }
            if ($stat->type) {
                $hierarchy[] = $stat->type;
            }

            $fullPath = implode(' → ', $hierarchy);
            $level = count($hierarchy);

            $matrixStats[] = [
                'matrix_id' => $stat->matrix_id,
                'matrix_name' => $stat->matrix_name,
                'title' => $stat->title,
                'subtitle' => $stat->subtitle,
                'type' => $stat->type,
                'hierarchy_path' => $fullPath,
                'hierarchy_level' => $level,
                'record_count' => $stat->record_count,
            ];
            $totalRecords += $stat->record_count;
        }

        // Sort by hierarchy path for better organization
        usort($matrixStats, function ($a, $b) {
            return strcmp($a['hierarchy_path'], $b['hierarchy_path']);
        });

        return [
            'data' => $matrixStats,
            'total_matrices' => count($matrixStats),
            'total_records' => $totalRecords,
        ];
    }

    /**
     * Generate country statistics for a substance
     */
    private function generateCountryStats($substanceId)
    {
        $countryStats = DB::table('empodat_main as em')
            ->join('empodat_stations as es', 'em.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->where('em.substance_id', $substanceId)
            ->groupBy('lc.name', 'lc.code', 'lc.id')
            ->orderBy('record_count', 'desc')
            ->get();

        return [
            'data' => $countryStats->toArray(),
            'total_countries' => $countryStats->count(),
        ];
    }

    /**
     * Generate quality statistics for a substance
     */
    private function generateQualityStats($substanceId)
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
                ->where('em.substance_id', $substanceId)
                ->where('eam.rating', '>=', $category->min_rating)
                ->where('eam.rating', '<', $category->max_rating)
                ->count();

            if ($recordCount > 0) {
                $qualityStats[] = [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'min_rating' => $category->min_rating,
                    'max_rating' => $category->max_rating,
                    'rating_range' => $category->min_rating.'-'.($category->max_rating - 1),
                    'record_count' => $recordCount,
                ];
                $totalRecords += $recordCount;
            }
        }

        // Get records with no rating (NULL or no analytical method)
        $noRatingCount = DB::table('empodat_main as em')
            ->leftJoin('empodat_analytical_methods as eam', 'eam.id', '=', 'em.method_id')
            ->where('em.substance_id', $substanceId)
            ->where(function ($query) {
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
                'record_count' => $noRatingCount,
            ];
            $totalRecords += $noRatingCount;
        }

        return [
            'data' => $qualityStats,
            'total_categories' => count($qualityStats),
            'total_records' => $totalRecords,
        ];
    }

    /**
     * Generate year range statistics for a substance
     */
    private function generateYearRangeStats($substanceId)
    {
        $yearStats = DB::table('empodat_main as em')
            ->select(
                'em.sampling_date_year',
                DB::raw('COUNT(*) as record_count')
            )
            ->where('em.substance_id', $substanceId)
            ->whereNotNull('em.sampling_date_year')
            ->groupBy('em.sampling_date_year')
            ->orderBy('em.sampling_date_year')
            ->get();

        $yearRange = EmpodatMain::where('substance_id', $substanceId)
            ->selectRaw('MIN(sampling_date_year) as min_year, MAX(sampling_date_year) as max_year')
            ->whereNotNull('sampling_date_year')
            ->first();

        return [
            'data' => $yearStats->toArray(),
            'min_year' => $yearRange->min_year ?? date('Y'),
            'max_year' => $yearRange->max_year ?? date('Y'),
            'total_years' => $yearStats->count(),
        ];
    }

    /**
     * Check if statistics exist for a substance
     */
    public function hasStatistics($substanceId)
    {
        return FactsheetStatistic::where('substance_id', $substanceId)->exists();
    }

    /**
     * Get statistics for a substance
     */
    public function getStatistics($substanceId)
    {
        return FactsheetStatistic::where('substance_id', $substanceId)->first();
    }

    /**
     * Display raw JSON metadata for a substance with substance information
     */
    public function showRawJson($substanceId)
    {
        // Get the substance
        $substance = Substance::find($substanceId);
        if (! $substance) {
            return response()->json(['error' => 'Substance not found'], 404);
        }

        // Get the statistics record
        $statisticsRecord = FactsheetStatistic::where('substance_id', $substanceId)->first();
        if (! $statisticsRecord) {
            return response()->json(['error' => 'No statistics found for this substance'], 404);
        }

        // Prepare the output with substance information at the beginning
        $output = [
            'substance_name' => $substance->name,
            'substance_prefixed_code' => $substance->prefixed_code,
            'substance_id' => $substanceId,
            'statistics_data' => $statisticsRecord->meta_data,
        ];

        return response()->json($output, 200, [], JSON_PRETTY_PRINT);
    }
}
