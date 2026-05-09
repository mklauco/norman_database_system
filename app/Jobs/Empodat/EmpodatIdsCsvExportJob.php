<?php

namespace App\Jobs\Empodat;

use App\Jobs\AbstractCsvExportJob;
use App\Mail\Empodat\IdsCsvExportReady;
use App\Models\Backend\QueryLog;
use App\Models\Empodat\EmpodatMain;

class EmpodatIdsCsvExportJob extends AbstractCsvExportJob
{
    /**
     * The filename for the CSV export
     */
    protected $filename;

    /**
     * Optimized batch sizes for efficient processing of IDs only
     */
    protected $initialBatchSize = 10000;

    protected $maxBatchSize = 50000;

    /**
     * Timeout for large datasets
     */
    protected $maxExecutionTime = 3600;

    /**
     * Job timeout
     */
    public $timeout = 7200;

    /**
     * Get the database key for this module
     */
    protected function getDatabaseKey(): string
    {
        return 'empodat';
    }

    /**
     * Get the storage directory for exports
     */
    protected function getStorageDirectory(): string
    {
        return 'exports/empodat';
    }

    /**
     * Get CSV headers
     */
    protected function getHeaders(): array
    {
        return ['ID'];
    }

    /**
     * Get the mail class for notifications
     */
    protected function getMailClass(): string
    {
        return IdsCsvExportReady::class;
    }

    /**
     * Build the base query for this module
     */
    protected function buildBaseQuery()
    {
        return EmpodatMain::query();
    }

    /**
     * Apply filters from the query log to the base query using optimized JOINs
     */
    protected function applyQueryFilters($baseQuery, QueryLog $queryLog)
    {
        $content = json_decode($queryLog->content, true);

        if (! is_array($content)) {
            return $baseQuery;
        }

        $request = $content['request'] ?? [];

        if (! is_array($request)) {
            return $baseQuery;
        }

        // Handle ID range filters (most common case)
        if (! empty($request['id_from']) || ! empty($request['id_to'])) {
            if (! empty($request['id_from']) && ! empty($request['id_to'])) {
                $baseQuery->whereBetween('empodat_main.id', [$request['id_from'], $request['id_to']]);
            } elseif (! empty($request['id_from'])) {
                $baseQuery->where('empodat_main.id', '>=', $request['id_from']);
            } elseif (! empty($request['id_to'])) {
                $baseQuery->where('empodat_main.id', '<=', $request['id_to']);
            }
        }

        // Use optimized scope methods with JOINs
        if (! empty($request['countrySearch']) && is_array($request['countrySearch'])) {
            $baseQuery->byCountries($request['countrySearch']);
        }

        if (! empty($request['matrixSearch']) && is_array($request['matrixSearch'])) {
            $baseQuery->byMatrices($request['matrixSearch']);
        }

        if (! empty($request['substances']) && is_array($request['substances'])) {
            $baseQuery->bySubstances($request['substances']);
        }

        if (! empty($request['normanRelevantOnly']) && $request['normanRelevantOnly']) {
            $baseQuery->normanRelevant();
        }

        if (! empty($request['concentrationIndicatorSearch']) && is_array($request['concentrationIndicatorSearch'])) {
            $baseQuery->byConcentrationIndicators($request['concentrationIndicatorSearch']);
        }

        if (! empty($request['year_from']) || ! empty($request['year_to'])) {
            $baseQuery->byYearRange($request['year_from'], $request['year_to']);
        }

        if (! empty($request['categoriesSearch']) && is_array($request['categoriesSearch'])) {
            $baseQuery->byCategories($request['categoriesSearch']);
        }

        if (! empty($request['sourceSearch']) && is_array($request['sourceSearch'])) {
            $baseQuery->bySources($request['sourceSearch']);
        }

        if ((! empty($request['typeDataSourcesSearch']) && is_array($request['typeDataSourcesSearch'])) ||
            (! empty($request['dataSourceLaboratorySearch']) && is_array($request['dataSourceLaboratorySearch'])) ||
            (! empty($request['dataSourceOrganisationSearch']) && is_array($request['dataSourceOrganisationSearch']))) {
            $baseQuery->byDataSourceFilters(
                $request['typeDataSourcesSearch'] ?? [],
                $request['dataSourceLaboratorySearch'] ?? [],
                $request['dataSourceOrganisationSearch'] ?? []
            );
        }

        if (! empty($request['analyticalMethodSearch']) && is_array($request['analyticalMethodSearch'])) {
            $baseQuery->byAnalyticalMethods($request['analyticalMethodSearch']);
        }

        if (! empty($request['qualityAnalyticalMethodsSearch']) && is_array($request['qualityAnalyticalMethodsSearch'])) {
            $ratings = \App\Models\List\QualityEmpodatAnalyticalMethods::whereIn('id', $request['qualityAnalyticalMethodsSearch'])->get();
            $baseQuery->byQualityRatings($ratings);
        }

        if (! empty($request['fileSearch']) && is_array($request['fileSearch'])) {
            $baseQuery->byFiles($request['fileSearch']);
        }

        return $baseQuery;
    }

    /**
     * Override ID extraction for maximum efficiency - we already have IDs
     */
    protected function extractIds(QueryLog $queryLog)
    {
        $baseQuery = $this->buildBaseQuery();
        $filteredQuery = $this->applyQueryFilters($baseQuery, $queryLog);

        // Explicitly select empodat_main.id to avoid ambiguity
        $ids = $filteredQuery->select('empodat_main.id')
            ->orderBy('empodat_main.id')
            ->pluck('empodat_main.id')
            ->toArray();

        foreach ($ids as $id) {
            yield $id;
        }
    }

    /**
     * Override chunked ID extraction
     */
    protected function extractIdsChunked(QueryLog $queryLog)
    {
        $baseQuery = $this->buildBaseQuery();
        $filteredQuery = $this->applyQueryFilters($baseQuery, $queryLog);

        $ids = $filteredQuery->select('empodat_main.id')
            ->orderBy('empodat_main.id')
            ->pluck('empodat_main.id')
            ->toArray();

        foreach ($ids as $id) {
            yield $id;
        }
    }

    /**
     * Get records for a batch of IDs - super efficient for IDs only
     */
    protected function getRecordsBatch(array $idBatch)
    {
        $orderedIds = array_values($idBatch);
        sort($orderedIds);

        // For IDs only, we can just use the IDs directly without any JOINs
        return collect($orderedIds)->map(function ($id) {
            return (object) ['id' => $id];
        });
    }

    /**
     * Format a single record for CSV output - just the ID
     */
    protected function formatRecord($record, string $exportDate): array
    {
        return [$record->id];
    }
}
