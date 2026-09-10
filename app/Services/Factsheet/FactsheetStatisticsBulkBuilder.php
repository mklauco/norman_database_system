<?php

declare(strict_types=1);

namespace App\Services\Factsheet;

use App\Models\Factsheet\FactsheetStatistic;
use Illuminate\Support\Facades\DB;

/**
 * Computes the factsheet statistics payload for every substance at once (#26).
 *
 * `FactsheetStatisticsController` computes the same payload one substance at a
 * time, which is right for the "generate for this substance" button but wrong
 * for a full rebuild: 7 592 substances × six queries each is roughly 2.5 hours
 * of index scans over `empodat_main`.
 *
 * This runs one query per statistic, grouped by `substance_id`, so the table is
 * scanned six times in total rather than 45 000 times. Measured on the
 * development database: 22s for country-year, 5s for country, 4s for matrix,
 * 37s for the surface-water occurrence block — about a minute and a half for
 * everything, against 2.5 hours.
 *
 * The grouped results are small enough to hold: under a million rows across all
 * statistics, the largest being ~474 000 country-year combinations.
 *
 * The payload shape is identical to the per-substance path, and
 * `FactsheetStatisticsBulkBuilderTest` asserts that by diffing the two.
 */
class FactsheetStatisticsBulkBuilder
{
    /** @see FactsheetStatisticsController::MEASURED_VALUE_INDICATOR */
    private const MEASURED_VALUE_INDICATOR = 1;

    private const RECENT_WINDOW_YEARS = 6;

    /** Substance payloads written per database round trip. */
    private const WRITE_CHUNK = 200;

    private int $latestSamplingYear;

    /** @var list<int> */
    private array $surfaceMatrixIds;

    /**
     * Build and persist statistics for every substance present in EMPODAT.
     *
     * @param  callable(int, int): void|null  $progress  called with (done, total)
     * @return int number of substances written
     */
    public function rebuildAll(?callable $progress = null): int
    {
        $this->latestSamplingYear = (int) (DB::table('empodat_main')->max('sampling_date_year') ?: now()->year);
        $this->surfaceMatrixIds = DB::table('list_matrices')
            ->where('empodat_matrix_link', 'empodat_matrix_water_surface')
            ->pluck('id')->map(fn ($id) => (int) $id)->all();

        $countryYear = $this->countryYearStats();
        $matrix = $this->matrixStats();
        $country = $this->countryStats();
        $quality = $this->qualityStats();
        $yearRange = $this->yearRangeStats();
        $occurrence = $this->surfaceWaterStats();
        $totals = $this->totalRecords();

        $substanceIds = array_keys($totals);
        sort($substanceIds);

        $now = now()->toISOString();
        $written = 0;
        $buffer = [];

        foreach ($substanceIds as $id) {
            $buffer[$id] = [
                'country_year' => $countryYear[$id] ?? ['data' => [], 'year_range' => ['min_year' => null, 'max_year' => null], 'total_countries' => 0],
                'matrix' => $matrix[$id] ?? ['data' => [], 'total_matrices' => 0, 'total_records' => 0],
                'country' => $country[$id] ?? ['data' => [], 'total_countries' => 0],
                'quality' => $quality[$id] ?? ['data' => [], 'total_categories' => 0, 'total_records' => 0],
                'year_range' => $yearRange[$id] ?? [
                    'data' => [], 'min_year' => date('Y'), 'max_year' => date('Y'), 'total_years' => 0,
                ],
                'surface_water_occurrence' => $occurrence[$id] ?? ['available' => false, 'reason' => 'No surface water data'],
                'generated_at' => $now,
                'total_records' => $totals[$id],
            ];

            if (count($buffer) >= self::WRITE_CHUNK) {
                $written += $this->flush($buffer);
                $buffer = [];
                $progress && $progress($written, count($substanceIds));
            }
        }

        $written += $this->flush($buffer);
        $progress && $progress($written, count($substanceIds));

        return $written;
    }

    /**
     * @param  array<int, array<string, mixed>>  $buffer
     */
    private function flush(array $buffer): int
    {
        if ($buffer === []) {
            return 0;
        }

        $now = now();
        $rows = [];

        foreach ($buffer as $substanceId => $payload) {
            $rows[] = [
                'substance_id' => $substanceId,
                'meta_data' => json_encode($payload),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // `substance_id` carries no unique index, so an upsert has no conflict
        // target to use. Deleting the batch and reinserting it costs one extra
        // round trip and keeps the write atomic within the transaction.
        DB::transaction(function () use ($rows) {
            $ids = array_column($rows, 'substance_id');
            FactsheetStatistic::whereIn('substance_id', $ids)->delete();
            FactsheetStatistic::insert($rows);
        });

        return count($rows);
    }

    /**
     * @return array<int, int>
     */
    private function totalRecords(): array
    {
        $out = [];

        // Joined to `susdat_substances` rather than filtered on NOT NULL:
        // `empodat_main.substance_id` carries no foreign key, and 151 of the
        // 7 592 distinct ids point at substances that do not exist. They cannot
        // have a factsheet — there is no substance record to render one for —
        // and `factsheet_substance_statistics` DOES have the foreign key, so
        // writing them fails outright.
        DB::table('empodat_main as em')
            ->join('susdat_substances as ss', 'ss.id', '=', 'em.substance_id')
            ->selectRaw('em.substance_id, count(*) as c')
            ->groupBy('em.substance_id')
            ->orderBy('em.substance_id')
            ->cursor()
            ->each(function ($r) use (&$out) {
                $out[(int) $r->substance_id] = (int) $r->c;
            });

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function countryYearStats(): array
    {
        $perSubstance = [];

        DB::table('empodat_main as em')
            ->join('empodat_stations as es', 'em.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->selectRaw('em.substance_id, lc.name as country_name, em.sampling_date_year as year, count(*) as c')
            ->whereNotNull('em.substance_id')
            ->whereNotNull('em.sampling_date_year')
            ->groupBy('em.substance_id', 'lc.name', 'em.sampling_date_year')
            ->orderBy('em.substance_id')
            ->orderBy('lc.name')
            ->orderBy('em.sampling_date_year')
            ->cursor()
            ->each(function ($r) use (&$perSubstance) {
                $id = (int) $r->substance_id;
                $perSubstance[$id]['data'][$r->country_name][$r->year] = (int) $r->c;
                $year = (int) $r->year;
                $perSubstance[$id]['min'] = min($perSubstance[$id]['min'] ?? $year, $year);
                $perSubstance[$id]['max'] = max($perSubstance[$id]['max'] ?? $year, $year);
            });

        $out = [];

        foreach ($perSubstance as $id => $acc) {
            $out[$id] = [
                'data' => $acc['data'],
                'year_range' => ['min_year' => $acc['min'], 'max_year' => $acc['max']],
                'total_countries' => count($acc['data']),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function matrixStats(): array
    {
        $out = [];

        DB::table('empodat_main as em')
            ->join('list_matrices as lm', 'em.matrix_id', '=', 'lm.id')
            ->selectRaw('em.substance_id, lm.id as matrix_id, lm.name as matrix_name, lm.title, lm.subtitle, lm.type, count(*) as c')
            ->whereNotNull('em.substance_id')
            ->groupBy('em.substance_id', 'lm.id', 'lm.name', 'lm.title', 'lm.subtitle', 'lm.type')
            ->orderBy('em.substance_id')
            ->cursor()
            ->each(function ($r) use (&$out) {
                $hierarchy = array_values(array_filter([$r->title, $r->subtitle, $r->type]));

                $out[(int) $r->substance_id]['data'][] = [
                    'matrix_id' => $r->matrix_id,
                    'matrix_name' => $r->matrix_name,
                    'title' => $r->title,
                    'subtitle' => $r->subtitle,
                    'type' => $r->type,
                    'hierarchy_path' => implode(' → ', $hierarchy),
                    'hierarchy_level' => count($hierarchy),
                    'record_count' => (int) $r->c,
                ];
            });

        foreach ($out as $id => $acc) {
            $data = $acc['data'];
            usort($data, fn ($a, $b) => strcmp($a['hierarchy_path'], $b['hierarchy_path']));

            $out[$id] = [
                'data' => $data,
                'total_matrices' => count($data),
                'total_records' => array_sum(array_column($data, 'record_count')),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function countryStats(): array
    {
        $out = [];

        DB::table('empodat_main as em')
            ->join('empodat_stations as es', 'em.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->selectRaw('em.substance_id, lc.name as country_name, lc.code as country_code, count(*) as record_count')
            ->whereNotNull('em.substance_id')
            ->groupBy('em.substance_id', 'lc.name', 'lc.code', 'lc.id')
            ->orderBy('em.substance_id')
            ->orderByDesc('record_count')
            // Deterministic tiebreak; see the matching note on the
            // per-substance query in FactsheetStatisticsController.
            ->orderBy('lc.name')
            ->cursor()
            ->each(function ($r) use (&$out) {
                $out[(int) $r->substance_id][] = [
                    'country_name' => $r->country_name,
                    'country_code' => $r->country_code,
                    'record_count' => (int) $r->record_count,
                ];
            });

        return array_map(
            fn (array $rows) => ['data' => $rows, 'total_countries' => count($rows)],
            $out
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function qualityStats(): array
    {
        $categories = DB::table('list_quality_empodat_analytical_methods')
            ->orderByDesc('min_rating')
            ->get();

        $counts = [];

        // One pass keyed by substance and rating; the ratings are bucketed in
        // PHP rather than with a query per category per substance.
        DB::table('empodat_main as em')
            ->leftJoin('empodat_analytical_methods as eam', 'eam.id', '=', 'em.method_id')
            ->selectRaw('em.substance_id, eam.rating, count(*) as c')
            ->whereNotNull('em.substance_id')
            ->groupBy('em.substance_id', 'eam.rating')
            ->orderBy('em.substance_id')
            ->cursor()
            ->each(function ($r) use (&$counts) {
                $counts[(int) $r->substance_id][$r->rating === null ? 'null' : (string) $r->rating] = (int) $r->c;
            });

        $out = [];

        foreach ($counts as $substanceId => $byRating) {
            $stats = [];
            $total = 0;

            foreach ($categories as $category) {
                $count = 0;

                foreach ($byRating as $rating => $c) {
                    if ($rating === 'null') {
                        continue;
                    }
                    if ((float) $rating >= $category->min_rating && (float) $rating < $category->max_rating) {
                        $count += $c;
                    }
                }

                if ($count > 0) {
                    $stats[] = [
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                        'min_rating' => $category->min_rating,
                        'max_rating' => $category->max_rating,
                        'rating_range' => $category->min_rating.'-'.($category->max_rating - 1),
                        'record_count' => $count,
                    ];
                    $total += $count;
                }
            }

            if (($byRating['null'] ?? 0) > 0) {
                $stats[] = [
                    'category_id' => null,
                    'category_name' => 'No quality rating available',
                    'min_rating' => null,
                    'max_rating' => null,
                    'rating_range' => 'N/A',
                    'record_count' => $byRating['null'],
                ];
                $total += $byRating['null'];
            }

            $out[$substanceId] = [
                'data' => $stats,
                'total_categories' => count($stats),
                'total_records' => $total,
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function yearRangeStats(): array
    {
        $out = [];

        DB::table('empodat_main')
            ->selectRaw('substance_id, sampling_date_year, count(*) as record_count')
            ->whereNotNull('substance_id')
            ->whereNotNull('sampling_date_year')
            ->groupBy('substance_id', 'sampling_date_year')
            ->orderBy('substance_id')
            ->orderBy('sampling_date_year')
            ->cursor()
            ->each(function ($r) use (&$out) {
                $out[(int) $r->substance_id][] = [
                    'sampling_date_year' => $r->sampling_date_year,
                    'record_count' => (int) $r->record_count,
                ];
            });

        return array_map(
            fn (array $rows) => [
                'data' => $rows,
                // Rows arrive ordered by year, so the ends are the range.
                'min_year' => $rows[0]['sampling_date_year'],
                'max_year' => $rows[count($rows) - 1]['sampling_date_year'],
                'total_years' => count($rows),
            ],
            $out
        );
    }

    /**
     * Surface-water occurrence, exceedance and concentration figures for every
     * substance. Mirrors
     * `FactsheetStatisticsController::generateSurfaceWaterOccurrenceStats()`.
     *
     * @return array<int, array<string, mixed>>
     */
    private function surfaceWaterStats(): array
    {
        if ($this->surfaceMatrixIds === []) {
            return [];
        }

        $toYear = $this->latestSamplingYear;
        $fromYear = $toYear - self::RECENT_WINDOW_YEARS + 1;
        $unit = DB::table('list_matrices')->whereIn('id', $this->surfaceMatrixIds)->value('unit');

        $measured = 'em.concentration_indicator_id = '.self::MEASURED_VALUE_INDICATOR;
        $recent = 'em.sampling_date_year between '.$fromYear.' and '.$toYear;

        $counts = [];

        DB::table('empodat_main as em')
            ->join('empodat_stations as es', 'em.station_id', '=', 'es.id')
            ->whereNotNull('em.substance_id')
            ->whereIn('em.matrix_id', $this->surfaceMatrixIds)
            ->groupBy('em.substance_id')
            ->orderBy('em.substance_id')
            ->selectRaw("
                em.substance_id,
                count(*) as all_analyses,
                count(*) filter (where $measured) as all_analyses_above,
                count(distinct es.country_id) as all_countries,
                count(distinct es.country_id) filter (where $measured) as all_countries_above,
                count(distinct em.station_id) as all_stations,
                count(distinct em.station_id) filter (where $measured) as all_stations_above,
                count(*) filter (where $recent) as recent_analyses,
                count(*) filter (where $recent and $measured) as recent_analyses_above,
                count(distinct es.country_id) filter (where $recent) as recent_countries,
                count(distinct es.country_id) filter (where $recent and $measured) as recent_countries_above,
                count(distinct em.station_id) filter (where $recent) as recent_stations,
                count(distinct em.station_id) filter (where $recent and $measured) as recent_stations_above
            ")
            ->cursor()
            ->each(function ($r) use (&$counts) {
                $counts[(int) $r->substance_id] = $r;
            });

        $concentrations = $this->surfaceWaterConcentrations($fromYear, $toYear);
        $exceedance = $this->surfaceWaterExceedance();

        $out = [];

        foreach ($counts as $substanceId => $row) {
            $out[$substanceId] = [
                'available' => true,
                'unit' => $unit,
                'all_data' => $this->block($row, 'all'),
                'recent_data' => $this->block($row, 'recent') + [
                    'from_year' => $fromYear,
                    'to_year' => $toYear,
                ],
                'concentrations' => $concentrations[$substanceId] ?? [],
                'exceedance' => $exceedance[$substanceId] ?? [
                    'pnec_freshwater' => null,
                    'reason' => 'No freshwater PNEC available for this substance',
                ],
                'generated_at' => now()->toISOString(),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function block(object $c, string $window): array
    {
        $countries = (int) $c->{$window.'_countries'};
        $countriesAbove = (int) $c->{$window.'_countries_above'};
        $stations = (int) $c->{$window.'_stations'};
        $stationsAbove = (int) $c->{$window.'_stations_above'};
        $analyses = (int) $c->{$window.'_analyses'};
        $analysesAbove = (int) $c->{$window.'_analyses_above'};

        $frequency = $analyses > 0 ? $analysesAbove / $analyses : null;

        return [
            'countries' => $countries,
            'countries_above_loq' => $countriesAbove,
            'stations' => $stations,
            'stations_above_loq' => $stationsAbove,
            'analyses' => $analyses,
            'analyses_above_loq' => $analysesAbove,
            'frequency_of_quantification' => $frequency === null ? null : round($frequency * 100, 2),
            'scores' => [
                'countries_above_loq' => $this->scoreBand($countriesAbove, [10 => 1.0, 5 => 0.5, 2 => 0.2, 1 => 0.1]),
                'stations_above_loq' => $this->scoreBand($stationsAbove, [1000 => 1.0, 100 => 0.5, 10 => 0.2, 1 => 0.1]),
                'frequency' => $frequency === null ? null : round($frequency, 2),
            ],
        ];
    }

    /**
     * @return array<int, list<array<string, mixed>>>
     */
    private function surfaceWaterConcentrations(int $fromYear, int $toYear): array
    {
        $out = [];

        DB::table('empodat_main as em')
            ->join('list_matrices as lm', 'em.matrix_id', '=', 'lm.id')
            ->leftJoin('empodat_analytical_methods as eam', 'eam.id', '=', 'em.method_id')
            ->whereNotNull('em.substance_id')
            ->whereIn('em.matrix_id', $this->surfaceMatrixIds)
            ->where('em.concentration_indicator_id', self::MEASURED_VALUE_INDICATOR)
            ->groupBy('em.substance_id', 'lm.id', 'lm.name')
            ->orderBy('em.substance_id')
            ->orderBy('lm.name')
            ->selectRaw('
                em.substance_id,
                lm.id as matrix_id,
                lm.name as matrix_name,
                min(nullif(eam.loq, 0)) as loq_min,
                percentile_cont(0.5) within group (order by em.concentration_value) as median,
                max(em.concentration_value) as max,
                percentile_cont(0.95) within group (order by em.concentration_value) as mec95_all,
                percentile_cont(0.95) within group (order by em.concentration_value)
                    filter (where em.sampling_date_year between '.$fromYear.' and '.$toYear.') as mec95_recent,
                count(*) as measured_count
            ')
            ->cursor()
            ->each(function ($r) use (&$out) {
                $out[(int) $r->substance_id][] = [
                    'matrix_id' => $r->matrix_id,
                    'matrix_name' => $r->matrix_name,
                    'loq_min' => $r->loq_min === null ? null : (float) $r->loq_min,
                    'median' => $r->median === null ? null : (float) $r->median,
                    'max' => $r->max === null ? null : (float) $r->max,
                    'mec95_all' => $r->mec95_all === null ? null : (float) $r->mec95_all,
                    'mec95_recent' => $r->mec95_recent === null ? null : (float) $r->mec95_recent,
                    'measured_count' => (int) $r->measured_count,
                ];
            });

        return $out;
    }

    /**
     * Exceedance of the freshwater PNEC, per substance.
     *
     * The PNEC lives in `ecotox_lowest_pnec`, keyed by the numeric part of the
     * SusDat code, so it is joined through `susdat_substances.code` rather than
     * by id.
     *
     * @return array<int, array<string, mixed>>
     */
    private function surfaceWaterExceedance(): array
    {
        $out = [];

        DB::table('empodat_main as em')
            ->join('susdat_substances as ss', 'ss.id', '=', 'em.substance_id')
            ->join('ecotox_lowest_pnec as p', 'p.sus_id', '=', DB::raw('ss.code::integer'))
            ->whereIn('em.matrix_id', $this->surfaceMatrixIds)
            ->where('em.concentration_indicator_id', self::MEASURED_VALUE_INDICATOR)
            ->whereNotNull('p.lowest_pnec_value_1')
            ->where('p.lowest_pnec_value_1', '>', 0)
            ->groupBy('em.substance_id', 'p.lowest_pnec_value_1')
            ->orderBy('em.substance_id')
            ->selectRaw('
                em.substance_id,
                p.lowest_pnec_value_1 as pnec,
                count(*) as measured,
                count(*) filter (where em.concentration_value > p.lowest_pnec_value_1) as exceeding,
                percentile_cont(0.95) within group (order by em.concentration_value) as mec95
            ')
            ->cursor()
            ->each(function ($r) use (&$out) {
                $pnec = (float) $r->pnec;
                $measured = (int) $r->measured;
                $exceeding = (int) $r->exceeding;
                $mec95 = $r->mec95 === null ? null : (float) $r->mec95;
                $frequency = $measured > 0 ? $exceeding / $measured : null;

                $out[(int) $r->substance_id] = [
                    'pnec_freshwater' => $pnec,
                    'measured' => $measured,
                    'exceeding' => $exceeding,
                    'mec95' => $mec95,
                    'frequency_of_exceedance' => $frequency === null ? null : round($frequency * 100, 2),
                    'extent_of_exceedance' => $mec95 === null ? null : round($mec95 / $pnec, 3),
                    'scores' => [
                        'frequency_of_exceedance' => $frequency === null ? null : round($frequency, 2),
                        'extent_of_exceedance' => null,
                    ],
                ];
            });

        return $out;
    }

    /**
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
}
