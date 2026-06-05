<?php

declare(strict_types=1);

namespace App\Actions\EmpodatSuspect;

use App\Models\DatabaseEntity;
use App\Models\Statistic;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Computes and persists all Empodat Suspect statistics.
 *
 * The empodat_suspect_main table is LIST-partitioned on is_numeric_concentration
 * (~4M numeric + ~30M non-numeric ≈ 34M rows). The previous implementation ran
 * ~14 separate aggregations against the partitions. This action consolidates the
 * work into 5 queries against the parent table — PostgreSQL's partition planner
 * scans each partition once, and PHP folds the rows into the {total, numeric,
 * non_numeric} shape the existing views expect.
 */
class GenerateStatisticsAction
{
    private const CONFIDENCE_CASE = "CASE
        WHEN ip_max IS NULL                          THEN 'null'
        WHEN ip_max  > 0.75 AND ip_max <= 1.00       THEN '1'
        WHEN ip_max  > 0.60 AND ip_max <= 0.75       THEN '2'
        WHEN ip_max  > 0.50 AND ip_max <= 0.60       THEN '3'
        WHEN ip_max  > 0.20 AND ip_max <= 0.50       THEN '4'
        WHEN ip_max <= 0.20                          THEN '5'
        ELSE 'unknown'
    END";

    private const CONFIDENCE_LABELS = [
        '1' => 'IP_max > 0.75 AND <= 1.00',
        '2' => 'IP_max > 0.60 AND <= 0.75',
        '3' => 'IP_max > 0.50 AND <= 0.60',
        '4' => 'IP_max > 0.20 AND <= 0.50',
        '5' => 'IP_max <= 0.20',
        'unknown' => 'Unknown',
    ];

    /**
     * Execute the full statistics generation pipeline.
     *
     * @return array<string,int> Summary keyed by stat name with row counts produced.
     */
    public function execute(): array
    {
        $entity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $entity) {
            throw new RuntimeException('Empodat Suspect database entity not found.');
        }

        // Allow long-running aggregations on the connection actually used by Eloquent.
        try {
            DB::statement('SET statement_timeout = 1800000'); // 30 min
        } catch (\Throwable $e) {
            // Not all drivers support this — proceed regardless.
        }

        $generatedAt = now();

        $totalsByPartition = $this->aggregateTotalsByPartition();
        $globalDistinctSubstances = $this->countGlobalDistinctSubstances();

        $this->storeConcentrationTypeStat($entity, $totalsByPartition, $generatedAt);
        $this->storeTotalSubstancesStat($entity, $totalsByPartition, $globalDistinctSubstances, $generatedAt);

        $bySampleCode = $this->storeSampleCodeStats($entity, $generatedAt);
        $byCountry = $this->storeCountryStats($entity, $generatedAt);
        $byConfidence = $this->storeConfidenceIntervalStat($entity, $generatedAt);

        $totalRecords = ($totalsByPartition['numeric']['records'] ?? 0)
            + ($totalsByPartition['non_numeric']['records'] ?? 0);

        $entity->update([
            'number_of_records' => $totalRecords,
            'last_update' => $generatedAt,
        ]);

        return [
            'total_records' => $totalRecords,
            'total_substances' => $globalDistinctSubstances,
            'sample_codes' => $bySampleCode,
            'countries' => $byCountry,
            'confidence_levels' => $byConfidence,
        ];
    }

    /**
     * One scan of the partitioned table, returning per-partition record + distinct
     * substance counts.
     *
     * @return array{numeric: array{records:int, substances:int}, non_numeric: array{records:int, substances:int}}
     */
    private function aggregateTotalsByPartition(): array
    {
        $rows = DB::table('empodat_suspect_main')
            ->select(
                'is_numeric_concentration',
                DB::raw('COUNT(*) AS record_count'),
                DB::raw('COUNT(DISTINCT substance_id) FILTER (WHERE substance_id IS NOT NULL) AS substance_count')
            )
            ->groupBy('is_numeric_concentration')
            ->get();

        $out = [
            'numeric' => ['records' => 0, 'substances' => 0],
            'non_numeric' => ['records' => 0, 'substances' => 0],
        ];

        foreach ($rows as $row) {
            $bucket = $row->is_numeric_concentration ? 'numeric' : 'non_numeric';
            $out[$bucket] = [
                'records' => (int) $row->record_count,
                'substances' => (int) $row->substance_count,
            ];
        }

        return $out;
    }

    /**
     * Distinct substance_id across both partitions (a substance may occur in both,
     * so this is NOT the sum of the per-partition counts).
     */
    private function countGlobalDistinctSubstances(): int
    {
        return (int) DB::table('empodat_suspect_main')
            ->whereNotNull('substance_id')
            ->distinct()
            ->count('substance_id');
    }

    private function storeConcentrationTypeStat(DatabaseEntity $entity, array $totals, Carbon $generatedAt): void
    {
        $numeric = $totals['numeric']['records'];
        $nonNumeric = $totals['non_numeric']['records'];
        $total = $numeric + $nonNumeric;

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'empodat_suspect.records_by_concentration_type',
            'meta_data' => [
                'numeric_count' => $numeric,
                'non_numeric_count' => $nonNumeric,
                'total_count' => $total,
                'numeric_percentage' => $total > 0 ? round(($numeric / $total) * 100, 2) : 0,
                'non_numeric_percentage' => $total > 0 ? round(($nonNumeric / $total) * 100, 2) : 0,
                'generated_at' => $generatedAt->toISOString(),
            ],
        ]);
    }

    private function storeTotalSubstancesStat(
        DatabaseEntity $entity,
        array $totals,
        int $globalDistinct,
        Carbon $generatedAt
    ): void {
        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'empodat_suspect.total_substances',
            'meta_data' => [
                'count' => $globalDistinct,
                // Substances that have at least one numeric concentration record.
                'numeric_count' => $totals['numeric']['substances'],
                // Substances that NEVER have a numeric value (N/A only). Derived as
                // total - numeric so the two sub-counts form a clean, non-overlapping
                // partition of `count` (the previous per-partition count overlapped,
                // since a substance can appear in both partitions).
                'non_numeric_count' => max(0, $globalDistinct - $totals['numeric']['substances']),
                'generated_at' => $generatedAt->toISOString(),
            ],
        ]);
    }

    /**
     * Single query for records + substances per (sample_code, partition).
     * Folded into two Statistic rows: substances_by_sample_code, records_by_sample_code.
     */
    private function storeSampleCodeStats(DatabaseEntity $entity, Carbon $generatedAt): int
    {
        $rows = DB::table('empodat_suspect_main as esm')
            ->join('empodat_suspect_xlsx_stations_mapping as mapping', 'esm.xlsx_station_mapping_id', '=', 'mapping.id')
            ->select(
                'mapping.xlsx_name as sample_code',
                'esm.is_numeric_concentration',
                DB::raw('COUNT(*) AS record_count'),
                DB::raw('COUNT(DISTINCT esm.substance_id) FILTER (WHERE esm.substance_id IS NOT NULL) AS substance_count')
            )
            ->whereNotNull('mapping.xlsx_name')
            ->groupBy('mapping.xlsx_name', 'esm.is_numeric_concentration')
            ->get();

        [$substances, $records] = $this->foldByKey(
            $rows,
            fn ($row) => $row->sample_code,
            fn ($row) => (int) $row->substance_count,
            fn ($row) => (int) $row->record_count,
        );

        ksort($substances);
        ksort($records);

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'empodat_suspect.substances_by_sample_code',
            'meta_data' => [
                'data' => $substances,
                'generated_at' => $generatedAt->toISOString(),
                'total_sample_codes' => count($substances),
            ],
        ]);

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'empodat_suspect.records_by_sample_code',
            'meta_data' => [
                'data' => $records,
                'generated_at' => $generatedAt->toISOString(),
                'total_sample_codes' => count($records),
            ],
        ]);

        return count($records);
    }

    /**
     * Single query for records + substances per (country, partition).
     * Folded into substances_by_country + records_by_country Statistic rows.
     */
    private function storeCountryStats(DatabaseEntity $entity, Carbon $generatedAt): int
    {
        $rows = DB::table('empodat_suspect_main as esm')
            ->join('empodat_stations as es', 'esm.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                'esm.is_numeric_concentration',
                DB::raw('COUNT(*) AS record_count'),
                DB::raw('COUNT(DISTINCT esm.substance_id) FILTER (WHERE esm.substance_id IS NOT NULL) AS substance_count')
            )
            ->whereNotNull('es.country_id')
            ->groupBy('lc.name', 'lc.code', 'esm.is_numeric_concentration')
            ->get();

        $substances = [];
        $records = [];
        $codes = [];

        foreach ($rows as $row) {
            $name = $row->country_name;
            $bucket = $row->is_numeric_concentration ? 'numeric' : 'non_numeric';

            $codes[$name] = $row->country_code;

            $substances[$name] ??= ['total' => 0, 'numeric' => 0, 'non_numeric' => 0];
            $substances[$name][$bucket] += (int) $row->substance_count;
            $substances[$name]['total'] += (int) $row->substance_count;

            $records[$name] ??= ['total' => 0, 'numeric' => 0, 'non_numeric' => 0];
            $records[$name][$bucket] += (int) $row->record_count;
            $records[$name]['total'] += (int) $row->record_count;
        }

        $substancesPayload = [];
        $recordsPayload = [];
        foreach ($substances as $name => $counts) {
            $substancesPayload[$name] = [
                'code' => $codes[$name] ?? '',
                'count' => $counts['total'],
                'numeric' => $counts['numeric'],
                'non_numeric' => $counts['non_numeric'],
            ];
        }
        foreach ($records as $name => $counts) {
            $recordsPayload[$name] = [
                'code' => $codes[$name] ?? '',
                'count' => $counts['total'],
                'numeric' => $counts['numeric'],
                'non_numeric' => $counts['non_numeric'],
            ];
        }

        ksort($substancesPayload);
        ksort($recordsPayload);

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'empodat_suspect.substances_by_country',
            'meta_data' => [
                'data' => $substancesPayload,
                'generated_at' => $generatedAt->toISOString(),
                'total_countries' => count($substancesPayload),
            ],
        ]);

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'empodat_suspect.records_by_country',
            'meta_data' => [
                'data' => $recordsPayload,
                'generated_at' => $generatedAt->toISOString(),
                'total_countries' => count($recordsPayload),
            ],
        ]);

        return count($recordsPayload);
    }

    /**
     * Single query for confidence-interval breakdown across both partitions.
     * NULL ip_max is bucketed via the CASE itself (label 'null'), so we avoid
     * the two extra "count where null" queries the old controller had.
     */
    private function storeConfidenceIntervalStat(DatabaseEntity $entity, Carbon $generatedAt): int
    {
        $caseExpr = self::CONFIDENCE_CASE;

        $rows = DB::table('empodat_suspect_main')
            ->select(
                DB::raw("{$caseExpr} AS confidence_level"),
                'is_numeric_concentration',
                DB::raw('COUNT(*) AS record_count')
            )
            ->groupBy(DB::raw("{$caseExpr}, is_numeric_concentration"))
            ->get();

        $buckets = [];
        $totals = ['ip_max' => 0, 'null' => 0];
        $totalsByBucket = [
            'ip_max' => ['numeric' => 0, 'non_numeric' => 0],
            'null' => ['numeric' => 0, 'non_numeric' => 0],
        ];

        foreach ($rows as $row) {
            $level = (string) $row->confidence_level;
            $bucket = $row->is_numeric_concentration ? 'numeric' : 'non_numeric';
            $count = (int) $row->record_count;

            $buckets[$level] ??= ['numeric' => 0, 'non_numeric' => 0];
            $buckets[$level][$bucket] += $count;

            if ($level === 'null') {
                $totals['null'] += $count;
                $totalsByBucket['null'][$bucket] += $count;
            } else {
                $totals['ip_max'] += $count;
                $totalsByBucket['ip_max'][$bucket] += $count;
            }
        }

        $data = [];
        foreach (self::CONFIDENCE_LABELS as $level => $label) {
            if (! isset($buckets[$level])) {
                continue;
            }
            $numeric = $buckets[$level]['numeric'];
            $nonNumeric = $buckets[$level]['non_numeric'];
            $data[$label] = [
                'level' => $level,
                'count' => $numeric + $nonNumeric,
                'numeric' => $numeric,
                'non_numeric' => $nonNumeric,
            ];
        }

        if ($totals['null'] > 0) {
            $data['No IP_max value'] = [
                'level' => 'null',
                'count' => $totals['null'],
                'numeric' => $totalsByBucket['null']['numeric'],
                'non_numeric' => $totalsByBucket['null']['non_numeric'],
            ];
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'empodat_suspect.records_by_confidence_interval',
            'meta_data' => [
                'data' => $data,
                'generated_at' => $generatedAt->toISOString(),
                'total_with_ip_max' => $totals['ip_max'],
                'total_with_ip_max_numeric' => $totalsByBucket['ip_max']['numeric'],
                'total_with_ip_max_non_numeric' => $totalsByBucket['ip_max']['non_numeric'],
                'total_without_ip_max' => $totals['null'],
                'total_without_ip_max_numeric' => $totalsByBucket['null']['numeric'],
                'total_without_ip_max_non_numeric' => $totalsByBucket['null']['non_numeric'],
            ],
        ]);

        return count($data);
    }

    /**
     * Helper that folds raw per-(key,partition) rows into two parallel arrays
     * keyed by the dimension, each value shaped as {total, numeric, non_numeric}.
     *
     * @param  iterable<object>  $rows
     * @return array{0: array<string,array{total:int,numeric:int,non_numeric:int}>, 1: array<string,array{total:int,numeric:int,non_numeric:int}>}
     */
    private function foldByKey(iterable $rows, callable $keyFn, callable $substanceFn, callable $recordFn): array
    {
        $substances = [];
        $records = [];

        foreach ($rows as $row) {
            $key = $keyFn($row);
            $bucket = $row->is_numeric_concentration ? 'numeric' : 'non_numeric';

            $sCount = $substanceFn($row);
            $rCount = $recordFn($row);

            $substances[$key] ??= ['total' => 0, 'numeric' => 0, 'non_numeric' => 0];
            $substances[$key][$bucket] += $sCount;
            $substances[$key]['total'] += $sCount;

            $records[$key] ??= ['total' => 0, 'numeric' => 0, 'non_numeric' => 0];
            $records[$key][$bucket] += $rCount;
            $records[$key]['total'] += $rCount;
        }

        return [$substances, $records];
    }
}
