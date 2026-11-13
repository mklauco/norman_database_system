<?php

declare(strict_types=1);

namespace App\Actions\Empodat;

use App\Models\DatabaseEntity;
use App\Models\Statistic;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Computes and stores every EMPODAT statistic in one run.
 *
 * A record is public when its file is neither protected nor deleted. The admin
 * scope ("all") also includes protected files. Records in deleted files, and
 * records without a file, are in neither scope.
 *
 * Each aggregation groups empodat_main by file_id once and PHP sorts the
 * per-file counts into the two scopes, so the table is scanned four times in
 * total. All eight rows (four keys in two scopes) share one generated_at and
 * one total_records per scope, so every figure on the statistics pages comes
 * from the same count.
 */
class GenerateEmpodatStatisticsAction
{
    public const SCOPE_PUBLIC = 'public';

    public const SCOPE_ALL = 'all';

    private const SCOPES = [self::SCOPE_PUBLIC, self::SCOPE_ALL];

    /**
     * @return array<string, int|string>
     */
    public function execute(): array
    {
        $entity = DatabaseEntity::where('code', 'empodat')->first();

        if (! $entity) {
            throw new RuntimeException('Empodat database entity not found.');
        }

        $startedAt = microtime(true);
        $generatedAt = now();

        DB::statement('SET statement_timeout = 1800000');

        try {
            $fileScopes = $this->fileScopes();

            $matrixCounts = $this->fold(
                DB::table('empodat_main')
                    ->select('file_id', 'matrix_id')
                    ->selectRaw('COUNT(*) AS record_count')
                    ->groupBy('file_id', 'matrix_id')
                    ->cursor(),
                $fileScopes,
                fn (object $row): string => (string) $row->matrix_id,
            );

            $substanceCounts = $this->fold(
                DB::table('empodat_main')
                    ->select('file_id', 'substance_id')
                    ->selectRaw('COUNT(*) AS record_count')
                    ->groupBy('file_id', 'substance_id')
                    ->cursor(),
                $fileScopes,
                fn (object $row): string => (string) $row->substance_id,
            );

            $countryYearCounts = $this->fold(
                DB::table('empodat_main as em')
                    ->join('empodat_stations as es', 'es.id', '=', 'em.station_id')
                    ->whereNotNull('es.country_id')
                    ->whereNotNull('em.sampling_date_year')
                    ->select('em.file_id', 'es.country_id', 'em.sampling_date_year')
                    ->selectRaw('COUNT(*) AS record_count')
                    ->groupBy('em.file_id', 'es.country_id', 'em.sampling_date_year')
                    ->cursor(),
                $fileScopes,
                fn (object $row): string => $row->country_id.'|'.$row->sampling_date_year,
            );

            $ratingCounts = $this->fold(
                DB::table('empodat_main as em')
                    ->leftJoin('empodat_analytical_methods as eam', 'eam.id', '=', 'em.method_id')
                    ->select('em.file_id', 'eam.rating')
                    ->selectRaw('COUNT(*) AS record_count')
                    ->groupBy('em.file_id', 'eam.rating')
                    ->cursor(),
                $fileScopes,
                fn (object $row): string => (string) $row->rating,
            );
        } finally {
            DB::statement('RESET statement_timeout');
        }

        $rows = [];
        $summary = [];

        foreach (self::SCOPES as $scope) {
            $totalRecords = array_sum($matrixCounts[$scope]);
            $base = [
                'generated_at' => $generatedAt->toISOString(),
                'total_records' => $totalRecords,
                'scope' => $scope,
            ];

            $countryYear = $this->buildCountryYear($countryYearCounts[$scope]);
            $matrix = $this->buildMatrix($matrixCounts[$scope]);
            $substance = $this->buildSubstance($substanceCounts[$scope]);
            $quality = $this->buildQuality($ratingCounts[$scope]);

            $rows[$this->key('country_year', $scope)] = $countryYear + $base;
            $rows[$this->key('matrix', $scope)] = $matrix + $base;
            $rows[$this->key('substance', $scope)] = $substance + $base;
            $rows[$this->key('quality', $scope)] = $quality + $base;

            $summary["{$scope}_records"] = $totalRecords;
            $summary["{$scope}_countries"] = $countryYear['total_countries'];
            $summary["{$scope}_matrices"] = $matrix['total_matrices'];
            $summary["{$scope}_substances"] = $substance['total_substances'];
        }

        DB::transaction(function () use ($rows, $entity, $generatedAt): void {
            foreach ($rows as $key => $metaData) {
                $statistic = new Statistic([
                    'database_entity_id' => $entity->id,
                    'key' => $key,
                    'meta_data' => $metaData,
                ]);
                $statistic->created_at = $generatedAt;
                $statistic->updated_at = $generatedAt;
                $statistic->save();
            }
        });

        $summary['generated_at'] = $generatedAt->toISOString();
        $summary['duration_seconds'] = (int) round(microtime(true) - $startedAt);

        return $summary;
    }

    /**
     * Scopes each file's records belong to, keyed by file id.
     *
     * @return array<int, list<string>>
     */
    private function fileScopes(): array
    {
        $scopes = [];

        foreach (DB::table('files')->select('id', 'is_protected', 'is_deleted')->cursor() as $file) {
            if ((bool) $file->is_deleted) {
                $scopes[(int) $file->id] = [];
            } elseif ((bool) $file->is_protected) {
                $scopes[(int) $file->id] = [self::SCOPE_ALL];
            } else {
                $scopes[(int) $file->id] = [self::SCOPE_PUBLIC, self::SCOPE_ALL];
            }
        }

        return $scopes;
    }

    /**
     * Adds per-file counts into per-scope counts.
     *
     * @param  iterable<object>  $rows  each with file_id and record_count
     * @param  array<int, list<string>>  $fileScopes
     * @param  callable(object): string  $keyOf
     * @return array<string, array<string, int>>
     */
    private function fold(iterable $rows, array $fileScopes, callable $keyOf): array
    {
        $result = array_fill_keys(self::SCOPES, []);

        foreach ($rows as $row) {
            $scopes = $row->file_id === null ? [] : ($fileScopes[(int) $row->file_id] ?? []);

            if ($scopes === []) {
                continue;
            }

            $key = $keyOf($row);

            foreach ($scopes as $scope) {
                $result[$scope][$key] = ($result[$scope][$key] ?? 0) + (int) $row->record_count;
            }
        }

        return $result;
    }

    /**
     * @param  array<string, int>  $counts  keyed by "country_id|year"
     * @return array{data: array<string, array<int, int>>, year_range: array{min_year: int, max_year: int}, total_countries: int}
     */
    private function buildCountryYear(array $counts): array
    {
        $countries = DB::table('list_countries')->pluck('name', 'id');
        $data = [];
        $years = [];

        foreach ($counts as $key => $count) {
            [$countryId, $year] = explode('|', (string) $key);
            $name = $countries[(int) $countryId] ?? null;

            if ($name === null) {
                continue;
            }

            $data[$name][(int) $year] = ($data[$name][(int) $year] ?? 0) + $count;
            $years[] = (int) $year;
        }

        ksort($data);

        foreach ($data as &$yearData) {
            ksort($yearData);
        }
        unset($yearData);

        return [
            'data' => $data,
            'year_range' => [
                'min_year' => $years === [] ? (int) date('Y') - 10 : min($years),
                'max_year' => $years === [] ? (int) date('Y') : max($years),
            ],
            'total_countries' => count($data),
        ];
    }

    /**
     * @param  array<string, int>  $counts  keyed by matrix id
     * @return array{data: list<array<string, mixed>>, total_matrices: int}
     */
    private function buildMatrix(array $counts): array
    {
        $matrices = DB::table('list_matrices')
            ->select('id', 'name', 'title', 'subtitle', 'type')
            ->get()
            ->keyBy('id');

        $data = [];

        foreach ($counts as $matrixId => $count) {
            $matrix = $matrices[(int) $matrixId] ?? null;

            if ($matrix === null) {
                continue;
            }

            $hierarchy = array_values(array_filter([$matrix->title, $matrix->subtitle, $matrix->type]));

            $data[] = [
                'matrix_id' => $matrix->id,
                'matrix_name' => $matrix->name,
                'title' => $matrix->title,
                'subtitle' => $matrix->subtitle,
                'type' => $matrix->type,
                'hierarchy_path' => implode(' → ', $hierarchy),
                'hierarchy_level' => count($hierarchy),
                'record_count' => $count,
            ];
        }

        usort($data, fn (array $a, array $b): int => strcmp($a['hierarchy_path'], $b['hierarchy_path']));

        return [
            'data' => $data,
            'total_matrices' => count($data),
        ];
    }

    /**
     * @param  array<string, int>  $counts  keyed by substance id
     * @return array{data: list<array<string, mixed>>, total_substances: int}
     */
    private function buildSubstance(array $counts): array
    {
        $ids = array_values(array_filter(array_map('intval', array_keys($counts))));
        $substances = [];

        foreach (array_chunk($ids, 5000) as $chunk) {
            foreach (DB::table('susdat_substances')->whereIn('id', $chunk)->select('id', 'code', 'name')->get() as $substance) {
                $substances[(int) $substance->id] = $substance;
            }
        }

        $data = [];

        foreach ($counts as $substanceId => $count) {
            $substance = $substances[(int) $substanceId] ?? null;

            if ($substance === null) {
                continue;
            }

            $data[] = [
                'substance_id' => $substance->id,
                'substance_prefix' => 'NS'.$substance->code,
                'substance_name' => $substance->name,
                'substance_code' => $substance->code,
                'record_count' => $count,
            ];
        }

        usort($data, fn (array $a, array $b): int => $b['record_count'] <=> $a['record_count']);

        return [
            'data' => $data,
            'total_substances' => count($data),
        ];
    }

    /**
     * @param  array<string, int>  $counts  keyed by method rating ('' when there is no rating)
     * @return array{data: list<array<string, mixed>>, total_categories: int}
     */
    private function buildQuality(array $counts): array
    {
        $categories = DB::table('list_quality_empodat_analytical_methods')
            ->orderBy('min_rating', 'desc')
            ->get();

        $data = [];

        foreach ($categories as $category) {
            $recordCount = 0;

            foreach ($counts as $rating => $count) {
                if ($rating !== '' && (float) $rating >= $category->min_rating && (float) $rating < $category->max_rating) {
                    $recordCount += $count;
                }
            }

            $data[] = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'min_rating' => $category->min_rating,
                'max_rating' => $category->max_rating,
                'rating_range' => $category->min_rating.'-'.($category->max_rating - 1),
                'record_count' => $recordCount,
            ];
        }

        $noRatingCount = $counts[''] ?? 0;

        if ($noRatingCount > 0) {
            $data[] = [
                'category_id' => null,
                'category_name' => 'No quality rating available',
                'min_rating' => null,
                'max_rating' => null,
                'rating_range' => 'N/A',
                'record_count' => $noRatingCount,
            ];
        }

        return [
            'data' => $data,
            'total_categories' => count($data),
        ];
    }

    private function key(string $baseKey, string $scope): string
    {
        return $scope === self::SCOPE_PUBLIC ? $baseKey : $baseKey.'_all';
    }
}
