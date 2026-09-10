<?php

namespace App\Http\Controllers\Factsheet;

use App\Http\Controllers\Controller;
use App\Models\Ecotox\LowestPNEC;
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
     * `list_concentration_indicators` id for "Individual Value" — a real
     * measurement. Ids 2 and 3 are "Less than LoD" and "Less than LoQ", so
     * this is what "analysis above LoQ" means on the factsheet.
     */
    private const MEASURED_VALUE_INDICATOR = 1;

    /**
     * Length of the factsheet's "recent data" window, in years, inclusive of
     * its final year. Legacy describes it as "the last 6 years".
     */
    private const RECENT_WINDOW_YEARS = 6;

    /**
     * Memoised newest sampling year — see latestSamplingYear().
     */
    private ?int $latestSamplingYear = null;

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
     * Generate comprehensive statistics for a specific substance.
     *
     * Public so `factsheets:generate-statistics` can drive it in bulk; the
     * "Populate all" button only ever created placeholder rows.
     */
    public function generateStatisticsForSubstance($substanceId)
    {
        // Generate all statistics categories
        $countryYearStats = $this->generateCountryYearStats($substanceId);
        $matrixStats = $this->generateMatrixStats($substanceId);
        $countryStats = $this->generateCountryStats($substanceId);
        $qualityStats = $this->generateQualityStats($substanceId);
        $yearRangeStats = $this->generateYearRangeStats($substanceId);
        $surfaceWaterOccurrence = $this->generateSurfaceWaterOccurrenceStats($substanceId);

        // Combine all statistics
        $allStats = [
            'country_year' => $countryYearStats,
            'matrix' => $matrixStats,
            'country' => $countryStats,
            'quality' => $qualityStats,
            'year_range' => $yearRangeStats,
            'surface_water_occurrence' => $surfaceWaterOccurrence,
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
    /**
     * Generate the surface-water occurrence statistics behind the factsheet's
     * "Environmental occurrence (all data)" and "Potential risk of exceedance"
     * sections (#26).
     *
     * The legacy factsheet read these from precomputed tables in its own
     * `factsheets` database (`environmental_occurence_all_data_*`), which were
     * never migrated — and are stale besides: for Myclobutanil legacy reports
     * 54 analyses where EMPODAT now holds 112 968, because the snapshot
     * predates the 2016-2020 import. The numbers are therefore computed from
     * `empodat_main` directly; only the table layout and the scoring bands are
     * taken from legacy.
     *
     * "Above LoQ" means a real measurement, i.e.
     * `concentration_indicator_id = 1` ("Individual Value"); ids 2 and 3 are
     * "Less than LoD" and "Less than LoQ". Concentration aggregates are taken
     * over measured values only, so rows recording a non-detect do not drag
     * the median and percentile down.
     *
     * @return array<string, mixed>
     */
    private function generateSurfaceWaterOccurrenceStats($substanceId): array
    {
        $matrixIds = DB::table('list_matrices')
            ->where('empodat_matrix_link', 'empodat_matrix_water_surface')
            ->pluck('id')
            ->all();

        if ($matrixIds === []) {
            return ['available' => false, 'reason' => 'No surface-water matrices configured'];
        }

        $toYear = $this->latestSamplingYear();
        $fromYear = $toYear - self::RECENT_WINDOW_YEARS + 1;

        $counts = $this->surfaceWaterCounts($substanceId, $matrixIds, $fromYear, $toYear);

        return [
            'available' => true,
            'unit' => Matrix::whereIn('id', $matrixIds)->value('unit'),
            'all_data' => $this->surfaceWaterBlock($counts, 'all'),
            'recent_data' => $this->surfaceWaterBlock($counts, 'recent') + [
                'from_year' => $fromYear,
                'to_year' => $toYear,
            ],
            'concentrations' => $this->surfaceWaterConcentrations($substanceId, $matrixIds, $fromYear, $toYear),
            'exceedance' => $this->surfaceWaterExceedance($substanceId, $matrixIds),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Figures behind the "Potential risk of exceedance of lowest PNEC"
     * section: how often measured surface-water concentrations exceed the
     * freshwater PNEC, and by how much.
     *
     *   Frequency of exceedance — measurements above the PNEC, as a percentage
     *                             of all measurements
     *   Extent of exceedance    — MEC95 divided by the PNEC; above 1 the 95th
     *                             percentile of what is actually measured sits
     *                             above the no-effect concentration
     *
     * The frequency of exceedance is scored the same way as the frequency of
     * quantification — the plain ratio. Legacy confirms it: for Ibuprofen it
     * prints 45.87 % against a score of 0.46.
     *
     * The extent of exceedance carries no score here. Legacy scores it (17.62
     * against 0.25), so a band clearly exists, but one observation is not
     * enough to recover it and the band is recorded nowhere we hold. Guessing
     * would put an unagreed number on the page.
     *
     * @param  list<int>  $matrixIds
     * @return array<string, mixed>
     */
    private function surfaceWaterExceedance($substanceId, array $matrixIds): array
    {
        $substance = Substance::find($substanceId);

        // `ecotox_lowest_pnec` is keyed by the numeric part of the SusDat code.
        $pnec = $substance === null ? null : LowestPNEC::where('sus_id', (int) $substance->code)
            ->value('lowest_pnec_value_1');

        $pnec = ($pnec === null || (float) $pnec <= 0) ? null : (float) $pnec;

        if ($pnec === null) {
            return [
                'pnec_freshwater' => null,
                'reason' => 'No freshwater PNEC available for this substance',
            ];
        }

        $row = DB::table('empodat_main as em')
            ->where('em.substance_id', $substanceId)
            ->whereIn('em.matrix_id', $matrixIds)
            ->where('em.concentration_indicator_id', self::MEASURED_VALUE_INDICATOR)
            ->selectRaw('
                count(*) as measured,
                count(*) filter (where em.concentration_value > ?) as exceeding,
                percentile_cont(0.95) within group (order by em.concentration_value) as mec95
            ', [$pnec])
            ->first();

        $measured = (int) $row->measured;
        $mec95 = $row->mec95 === null ? null : (float) $row->mec95;
        $exceeding = (int) $row->exceeding;
        $frequency = $measured > 0 ? $exceeding / $measured : null;

        return [
            'pnec_freshwater' => $pnec,
            'measured' => $measured,
            'exceeding' => $exceeding,
            'mec95' => $mec95,
            'frequency_of_exceedance' => $frequency === null ? null : round($frequency * 100, 2),
            'extent_of_exceedance' => $mec95 === null ? null : round($mec95 / $pnec, 3),
            'scores' => [
                'frequency_of_exceedance' => $frequency === null ? null : round($frequency, 2),
                // Band unknown — see the note above.
                'extent_of_exceedance' => null,
            ],
        ];
    }

    /**
     * The newest sampling year present in EMPODAT, which anchors the "recent
     * data" window.
     *
     * Deliberately NOT the calendar year: sampling data lags, and anchoring on
     * "now" silently drops the newest full year of measurements. In September
     * 2026 the newest sampling year was 2025, while 2026 held no rows at all —
     * a calendar anchor produced a 2021-2026 window that excluded 2020 and its
     * 12.5 million surface-water records.
     *
     * Deliberately global rather than per substance: a substance nobody has
     * measured lately should show an empty recent-data table, not have its
     * window quietly slid back to whenever it was last sampled.
     *
     * Memoised because `populateAll()` runs this for every substance.
     */
    private function latestSamplingYear(): int
    {
        return $this->latestSamplingYear ??= (int) (
            DB::table('empodat_main')->max('sampling_date_year') ?: now()->year
        );
    }

    /**
     * Every count both occurrence tables need, in a single pass.
     *
     * Six figures × two time windows would be twelve separate aggregate
     * queries over `empodat_main`; PostgreSQL's aggregate FILTER clause gets
     * them from one scan instead. That matters — `empodat_main` is past 100
     * million rows and heading for 200 million, and these run for every
     * substance.
     *
     * @param  list<int>  $matrixIds
     */
    private function surfaceWaterCounts($substanceId, array $matrixIds, int $fromYear, int $toYear): object
    {
        $measured = 'em.concentration_indicator_id = '.self::MEASURED_VALUE_INDICATOR;
        $recent = 'em.sampling_date_year between '.$fromYear.' and '.$toYear;

        return DB::table('empodat_main as em')
            ->join('empodat_stations as es', 'em.station_id', '=', 'es.id')
            ->where('em.substance_id', $substanceId)
            ->whereIn('em.matrix_id', $matrixIds)
            ->selectRaw("
                count(*)                                                        as all_analyses,
                count(*) filter (where $measured)                               as all_analyses_above,
                count(distinct es.country_id)                                   as all_countries,
                count(distinct es.country_id) filter (where $measured)          as all_countries_above,
                count(distinct em.station_id)                                   as all_stations,
                count(distinct em.station_id) filter (where $measured)          as all_stations_above,
                count(*) filter (where $recent)                                 as recent_analyses,
                count(*) filter (where $recent and $measured)                   as recent_analyses_above,
                count(distinct es.country_id) filter (where $recent)            as recent_countries,
                count(distinct es.country_id) filter (where $recent and $measured) as recent_countries_above,
                count(distinct em.station_id) filter (where $recent)            as recent_stations,
                count(distinct em.station_id) filter (where $recent and $measured) as recent_stations_above
            ")
            ->first();
    }

    /**
     * Shape one "Occurrence data" table from the combined counts, adding the
     * frequency of quantification and the prioritisation scores.
     *
     * @return array<string, mixed>
     */
    private function surfaceWaterBlock(object $counts, string $window): array
    {
        $countries = (int) $counts->{$window.'_countries'};
        $countriesAbove = (int) $counts->{$window.'_countries_above'};
        $stations = (int) $counts->{$window.'_stations'};
        $stationsAbove = (int) $counts->{$window.'_stations_above'};
        $analyses = (int) $counts->{$window.'_analyses'};
        $analysesAbove = (int) $counts->{$window.'_analyses_above'};

        $frequency = $analyses > 0 ? $analysesAbove / $analyses : null;

        return [
            'countries' => $countries,
            'countries_above_loq' => $countriesAbove,
            'stations' => $stations,
            'stations_above_loq' => $stationsAbove,
            'analyses' => $analyses,
            'analyses_above_loq' => $analysesAbove,
            'frequency_of_quantification' => $frequency === null ? null : round($frequency * 100, 2),
            // Scores follow the NORMAN prioritisation bands. Only the
            // ">LoQ" counts and the frequency carry a score; the plain counts
            // render as "n.a.", as they do in legacy.
            'scores' => [
                'countries_above_loq' => $this->scoreBand($countriesAbove, [10 => 1.0, 5 => 0.5, 2 => 0.2, 1 => 0.1]),
                'stations_above_loq' => $this->scoreBand($stationsAbove, [1000 => 1.0, 100 => 0.5, 10 => 0.2, 1 => 0.1]),
                'frequency' => $frequency === null ? null : round($frequency, 2),
            ],
        ];
    }

    /**
     * LOQmin, median, max and MEC95 per surface-water matrix.
     *
     * MEC95 is the 95th percentile of measured concentrations — computed by
     * PostgreSQL's `percentile_cont`, so it needs no application-side sort.
     *
     * @param  list<int>  $matrixIds
     * @return list<array<string, mixed>>
     */
    private function surfaceWaterConcentrations($substanceId, array $matrixIds, int $fromYear, int $toYear): array
    {
        $rows = DB::table('empodat_main as em')
            ->join('list_matrices as lm', 'em.matrix_id', '=', 'lm.id')
            ->leftJoin('empodat_analytical_methods as eam', 'eam.id', '=', 'em.method_id')
            ->select(
                'lm.id as matrix_id',
                'lm.name as matrix_name',
                // A LoQ of exactly zero means "not reported" rather than a
                // real limit, and would otherwise win every min().
                DB::raw('min(nullif(eam.loq, 0)) as loq_min'),
                DB::raw('percentile_cont(0.5) within group (order by em.concentration_value) as median'),
                DB::raw('max(em.concentration_value) as max'),
                DB::raw('percentile_cont(0.95) within group (order by em.concentration_value) as mec95_all'),
                DB::raw('percentile_cont(0.95) within group (order by em.concentration_value)
                         filter (where em.sampling_date_year between '.$fromYear.' and '.$toYear.') as mec95_recent'),
                DB::raw('count(*) as measured_count')
            )
            ->where('em.substance_id', $substanceId)
            ->whereIn('em.matrix_id', $matrixIds)
            ->where('em.concentration_indicator_id', self::MEASURED_VALUE_INDICATOR)
            ->groupBy('lm.id', 'lm.name')
            ->orderBy('lm.name')
            ->get();

        return $rows->map(fn ($r) => [
            'matrix_id' => $r->matrix_id,
            'matrix_name' => $r->matrix_name,
            'loq_min' => $r->loq_min === null ? null : (float) $r->loq_min,
            'median' => $r->median === null ? null : (float) $r->median,
            'max' => $r->max === null ? null : (float) $r->max,
            'mec95_all' => $r->mec95_all === null ? null : (float) $r->mec95_all,
            'mec95_recent' => $r->mec95_recent === null ? null : (float) $r->mec95_recent,
            'measured_count' => (int) $r->measured_count,
        ])->all();
    }

    /**
     * Map a count onto a prioritisation score band. `$bands` is
     * threshold => score, highest threshold first. Returns null below the
     * lowest band, which the factsheet renders as an empty cell.
     *
     * @param  array<int, float>  $bands
     */
    private function scoreBand(int $value, array $bands): ?float
    {
        foreach ($bands as $threshold => $score) {
            if ($value >= $threshold) {
                return $score;
            }
        }

        return null;
    }

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
