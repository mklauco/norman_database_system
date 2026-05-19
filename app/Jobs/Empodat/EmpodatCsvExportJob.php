<?php

declare(strict_types=1);

namespace App\Jobs\Empodat;

use App\Jobs\AbstractCsvExportJob;
use App\Mail\Empodat\CsvExportReady;
use App\Models\Backend\ExportDownload;
use App\Models\Backend\QueryLog;
use App\Models\Empodat\EmpodatMain;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmpodatCsvExportJob extends AbstractCsvExportJob
{
    /**
     * The filename for the CSV export
     */
    protected $filename;

    /**
     * Optimized batch sizes for efficient processing of 95M rows
     */
    protected $initialBatchSize = 1000;

    protected $maxBatchSize = 5000;

    /**
     * Extended timeout for large datasets (95M rows)
     */
    protected $maxExecutionTime = 7200; // 2 hours for very large exports

    /**
     * Job timeout
     */
    public $timeout = 10800; // 3 hours

    /**
     * Threshold for using PostgreSQL COPY (rows count)
     * Above this, use COPY TO STDOUT for memory efficiency
     */
    protected int $copyThreshold = 100000;

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
        return [
            // empodat_main fields
            'ID',
            'Station ID',
            'Station Name',
            'Country ID',
            'Country',
            'Country Code',
            'File ID',
            'Matrix ID',
            'Matrix',
            'Concentration Unit',
            'Substance ID',
            'Substance',
            'CAS Number',
            'Sampling Year',
            'Concentration Indicator ID',
            'Concentration Value',
            'Method ID',
            'Data Source ID',
            'Latitude',
            'Longitude',

            // empodat_minor fields
            'DPC ID',
            'Altitude',
            'Matrix Other',
            'Compound',
            'DCOD ID',
            'Unit Extra',
            'Tier',
            'Sampling Technique',
            'Sampling Date',
            'Sampling Date T',
            'Sampling Date1 Y',
            'Sampling Date1 M',
            'Sampling Date1 D',
            'Sampling Date1 T',
            'Sampling Date1',
            'Analysis Date Y',
            'Analysis Date M',
            'Analysis Date D',
            'Sampling Duration Day',
            'Sampling Duration Hour',
            'Description',
            'Remark',
            'Remark Add',
            'Show Date',
            'DTOD ID',
            'DTOD Other',
            'Agg Uncertainty',
            'DMM ID',
            'Agg Max',
            'Agg Min',
            'Agg Number',
            'Agg Deviation',
            'DTL ID',
            'DTL Other',
            'DST ID',
            'DST Other',
            'DTOS ID',
            'DPLU ID',
            'No Export',
            'List ID',

            'Export Date',
        ];
    }

    /**
     * Get the mail class for notifications
     */
    protected function getMailClass(): string
    {
        return CsvExportReady::class;
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
     *
     * This method reconstructs the query filters using the same optimized approach
     * as the main search functionality
     */
    protected function applyQueryFilters($baseQuery, QueryLog $queryLog)
    {
        // Parse the content JSON to get the original request parameters
        $content = json_decode($queryLog->content, true);

        if (! is_array($content)) {
            Log::warning("Invalid query log content for ID {$queryLog->id}");

            return $baseQuery;
        }

        $request = $content['request'] ?? [];

        if (! is_array($request)) {
            Log::warning("Invalid request data in query log ID {$queryLog->id}");

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

        // Use optimized scope methods with JOINs instead of whereHas
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
            // Get the quality ratings collection like in the main search
            $ratings = \App\Models\List\QualityEmpodatAnalyticalMethods::whereIn('id', $request['qualityAnalyticalMethodsSearch'])->get();
            $baseQuery->byQualityRatings($ratings);
        }

        if (! empty($request['fileSearch']) && is_array($request['fileSearch'])) {
            $baseQuery->byFiles($request['fileSearch']);
        }

        return $baseQuery;
    }

    /**
     * Get records for a batch of IDs with all necessary relationships
     * Optimized to avoid JOIN conflicts with filtered queries
     */
    protected function getRecordsBatch(array $idBatch)
    {
        $orderedIds = array_values($idBatch);
        sort($orderedIds);

        // Use a separate optimized query for data retrieval to avoid JOIN conflicts
        // This approach ensures we get all the data we need without interfering with filtering JOINs
        return DB::table('empodat_main')
            ->select(
                // empodat_main fields
                'empodat_main.id',
                'empodat_main.station_id',
                'empodat_stations.name as station_name',
                'empodat_main.country_id',
                'list_countries.name as country_name',
                'list_countries.code as country_code',
                'empodat_main.file_id',
                'empodat_main.matrix_id',
                'list_matrices.name as matrix_name',
                'list_matrices.unit as concentration_unit',
                'empodat_main.substance_id',
                'susdat_substances.name as substance_name',
                'susdat_substances.cas_number',
                'empodat_main.sampling_date_year',
                'empodat_main.concentration_indicator_id',
                'empodat_main.concentration_value',
                'empodat_main.method_id',
                'empodat_main.data_source_id',
                'empodat_stations.latitude',
                'empodat_stations.longitude',

                // empodat_minor fields
                'empodat_minor.dpc_id',
                'empodat_minor.altitude',
                'empodat_minor.matrix_other',
                'empodat_minor.compound',
                'empodat_minor.dcod_id',
                'empodat_minor.unit_extra',
                'empodat_minor.tier',
                'empodat_minor.sampling_technique',
                'empodat_minor.sampling_date',
                'empodat_minor.sampling_date_t',
                'empodat_minor.sampling_date1_y',
                'empodat_minor.sampling_date1_m',
                'empodat_minor.sampling_date1_d',
                'empodat_minor.sampling_date1_t',
                'empodat_minor.sampling_date1',
                'empodat_minor.analysis_date_y',
                'empodat_minor.analysis_date_m',
                'empodat_minor.analysis_date_d',
                'empodat_minor.sampling_duration_day',
                'empodat_minor.sampling_duration_hour',
                'empodat_minor.description',
                'empodat_minor.remark',
                'empodat_minor.remark_add',
                'empodat_minor.show_date',
                'empodat_minor.dtod_id',
                'empodat_minor.dtod_other',
                'empodat_minor.agg_uncertainty',
                'empodat_minor.dmm_id',
                'empodat_minor.agg_max',
                'empodat_minor.agg_min',
                'empodat_minor.agg_number',
                'empodat_minor.agg_deviation',
                'empodat_minor.dtl_id',
                'empodat_minor.dtl_other',
                'empodat_minor.dst_id',
                'empodat_minor.dst_other',
                'empodat_minor.dtos_id',
                'empodat_minor.dplu_id',
                'empodat_minor.noexport',
                'empodat_minor.list_id'
            )
            ->leftJoin('empodat_minor', 'empodat_main.id', '=', 'empodat_minor.id')
            ->leftJoin('empodat_stations', 'empodat_main.station_id', '=', 'empodat_stations.id')
            ->leftJoin('list_countries', 'empodat_main.country_id', '=', 'list_countries.id')
            ->leftJoin('list_matrices', 'empodat_main.matrix_id', '=', 'list_matrices.id')
            ->leftJoin('susdat_substances', 'empodat_main.substance_id', '=', 'susdat_substances.id')
            ->whereIn('empodat_main.id', $orderedIds)
            ->orderBy('empodat_main.id')
            ->cursor(); // Use cursor for memory efficiency with larger datasets
    }

    /**
     * Format a single record for CSV output
     */
    protected function formatRecord($record, string $exportDate): array
    {
        return [
            // empodat_main fields
            $record->id ?? '',
            $record->station_id ?? '',
            $record->station_name ?? '',
            $record->country_id ?? '',
            $record->country_name ?? '',
            $record->country_code ?? '',
            $record->file_id ?? '',
            $record->matrix_id ?? '',
            $record->matrix_name ?? '',
            $record->concentration_unit ?? '',
            $record->substance_id ?? '',
            $record->substance_name ?? '',
            $record->cas_number ?? '',
            $record->sampling_date_year ?? '',
            $record->concentration_indicator_id ?? '',
            $record->concentration_value ?? '',
            $record->method_id ?? '',
            $record->data_source_id ?? '',
            $record->latitude ?? '',
            $record->longitude ?? '',

            // empodat_minor fields
            $record->dpc_id ?? '',
            $record->altitude ?? '',
            $record->matrix_other ?? '',
            $record->compound ?? '',
            $record->dcod_id ?? '',
            $record->unit_extra ?? '',
            $record->tier ?? '',
            $record->sampling_technique ?? '',
            $record->sampling_date ?? '',
            $record->sampling_date_t ?? '',
            $record->sampling_date1_y ?? '',
            $record->sampling_date1_m ?? '',
            $record->sampling_date1_d ?? '',
            $record->sampling_date1_t ?? '',
            $record->sampling_date1 ?? '',
            $record->analysis_date_y ?? '',
            $record->analysis_date_m ?? '',
            $record->analysis_date_d ?? '',
            $record->sampling_duration_day ?? '',
            $record->sampling_duration_hour ?? '',
            $record->description ?? '',
            $record->remark ?? '',
            $record->remark_add ?? '',
            $record->show_date ?? '',
            $record->dtod_id ?? '',
            $record->dtod_other ?? '',
            $record->agg_uncertainty ?? '',
            $record->dmm_id ?? '',
            $record->agg_max ?? '',
            $record->agg_min ?? '',
            $record->agg_number ?? '',
            $record->agg_deviation ?? '',
            $record->dtl_id ?? '',
            $record->dtl_other ?? '',
            $record->dst_id ?? '',
            $record->dst_other ?? '',
            $record->dtos_id ?? '',
            $record->dplu_id ?? '',
            $record->noexport ?? '',
            $record->list_id ?? '',

            $exportDate,
        ];
    }

    /**
     * Export large datasets via a PostgreSQL server-side cursor.
     * Streams rows over the existing PDO connection — no subprocess,
     * no shell escaping, errors surface as PDOException for Sentry.
     *
     * @return int Number of records exported
     */
    public function exportWithPostgresCopy(QueryLog $queryLog, string $filePath): int
    {
        $exportDate = Carbon::now()->format('Y-m-d H:i:s');
        $selectSql = $this->buildCopyQuery($queryLog, $exportDate);

        Log::info('Starting PDO server-side cursor export', [
            'query_log_id' => $queryLog->id,
            'file_path' => $filePath,
        ]);

        $rowCount = $this->streamSelectToCsv($selectSql, $this->getHeaders(), $filePath);

        Log::info('PDO server-side cursor export completed', [
            'query_log_id' => $queryLog->id,
            'rows_exported' => $rowCount,
        ]);

        return $rowCount;
    }

    /**
     * Build the SQL query for PostgreSQL COPY export.
     *
     * Wraps the already-filtered SELECT stored on the QueryLog as a subquery
     * and joins the lookup tables on top. This preserves every JOIN and
     * predicate from the original query (including those injected by scopes
     * such as byUserPermissions, byCategories, byCountries, ...) without the
     * export job needing to know about them — fixes the "missing FROM-clause
     * entry for table" failures and keeps the COPY path correct as new
     * scopes are added.
     *
     * The stored SQL is logged before pagination, so it has no LIMIT and no
     * ORDER BY — safe to use as a subquery. The outer ORDER BY filtered.id
     * is required for stable streaming through the server-side cursor.
     */
    protected function buildCopyQuery(QueryLog $queryLog, string $exportDate): string
    {
        $storedSql = $queryLog->query;

        if (empty($storedSql)) {
            throw new \RuntimeException(
                "Cannot build COPY query: query log {$queryLog->id} has no stored SQL"
            );
        }

        $exportDateLiteral = str_replace("'", "''", $exportDate);

        $selectColumns = "
            filtered.id,
            filtered.station_id,
            empodat_stations.name as station_name,
            filtered.country_id,
            list_countries.name as country_name,
            list_countries.code as country_code,
            filtered.file_id,
            filtered.matrix_id,
            list_matrices.name as matrix_name,
            list_matrices.unit as concentration_unit,
            filtered.substance_id,
            susdat_substances.name as substance_name,
            susdat_substances.cas_number,
            filtered.sampling_date_year,
            filtered.concentration_indicator_id,
            filtered.concentration_value,
            filtered.method_id,
            filtered.data_source_id,
            empodat_stations.latitude,
            empodat_stations.longitude,
            empodat_minor.dpc_id,
            empodat_minor.altitude,
            empodat_minor.matrix_other,
            empodat_minor.compound,
            empodat_minor.dcod_id,
            empodat_minor.unit_extra,
            empodat_minor.tier,
            empodat_minor.sampling_technique,
            empodat_minor.sampling_date,
            empodat_minor.sampling_date_t,
            empodat_minor.sampling_date1_y,
            empodat_minor.sampling_date1_m,
            empodat_minor.sampling_date1_d,
            empodat_minor.sampling_date1_t,
            empodat_minor.sampling_date1,
            empodat_minor.analysis_date_y,
            empodat_minor.analysis_date_m,
            empodat_minor.analysis_date_d,
            empodat_minor.sampling_duration_day,
            empodat_minor.sampling_duration_hour,
            empodat_minor.description,
            empodat_minor.remark,
            empodat_minor.remark_add,
            empodat_minor.show_date,
            empodat_minor.dtod_id,
            empodat_minor.dtod_other,
            empodat_minor.agg_uncertainty,
            empodat_minor.dmm_id,
            empodat_minor.agg_max,
            empodat_minor.agg_min,
            empodat_minor.agg_number,
            empodat_minor.agg_deviation,
            empodat_minor.dtl_id,
            empodat_minor.dtl_other,
            empodat_minor.dst_id,
            empodat_minor.dst_other,
            empodat_minor.dtos_id,
            empodat_minor.dplu_id,
            empodat_minor.noexport,
            empodat_minor.list_id,
            '{$exportDateLiteral}'::text as export_date
        ";

        $joins = "
            FROM ({$storedSql}) AS filtered
            LEFT JOIN empodat_minor    ON filtered.id           = empodat_minor.id
            LEFT JOIN empodat_stations ON filtered.station_id   = empodat_stations.id
            LEFT JOIN list_countries   ON filtered.country_id   = list_countries.id
            LEFT JOIN list_matrices    ON filtered.matrix_id    = list_matrices.id
            LEFT JOIN susdat_substances ON filtered.substance_id = susdat_substances.id
        ";

        return "SELECT {$selectColumns} {$joins} ORDER BY filtered.id";
    }

    /**
     * Stream a SELECT to a CSV file using a PostgreSQL server-side cursor.
     * Writes UTF-8 BOM, headers, then fetches rows in fixed-size batches
     * so PHP memory stays bounded regardless of result-set size.
     *
     * On error: closes cursor, rolls back, removes partial file, rethrows.
     *
     * @param  array<int, string>  $headers
     * @return int Number of data rows written (excludes header)
     */
    protected function streamSelectToCsv(string $selectSql, array $headers, string $filePath): int
    {
        $pdo = DB::connection('pgsql')->getPdo();
        $cursorName = 'empodat_export_cursor_'.bin2hex(random_bytes(8));
        $fetchSize = 10000;

        $handle = null;
        $cursorOpen = false;
        $inTransaction = false;

        try {
            $handle = fopen($filePath, 'wb');
            if ($handle === false) {
                throw new \RuntimeException("Cannot open file for writing: {$filePath}");
            }

            // UTF-8 BOM for Excel compatibility, then header row
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            $pdo->beginTransaction();
            $inTransaction = true;

            $pdo->exec("DECLARE {$cursorName} NO SCROLL CURSOR FOR {$selectSql}");
            $cursorOpen = true;

            $totalRows = 0;

            while (true) {
                $stmt = $pdo->query("FETCH FORWARD {$fetchSize} FROM {$cursorName}");
                $rows = $stmt->fetchAll(\PDO::FETCH_NUM);
                $stmt->closeCursor();

                if (empty($rows)) {
                    break;
                }

                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }

                $totalRows += count($rows);

                unset($rows, $stmt);
            }

            $pdo->exec("CLOSE {$cursorName}");
            $cursorOpen = false;

            $pdo->commit();
            $inTransaction = false;

            if (! fclose($handle)) {
                $handle = null;
                throw new \RuntimeException("Failed to close output file: {$filePath}");
            }
            $handle = null;

            return $totalRows;
        } catch (\Throwable $e) {
            if ($handle !== null) {
                @fclose($handle);
            }

            if ($cursorOpen) {
                try {
                    $pdo->exec("CLOSE {$cursorName}");
                } catch (\Throwable $closeEx) {
                    Log::warning('Failed to CLOSE cursor on error path: '.$closeEx->getMessage());
                }
            }

            if ($inTransaction) {
                try {
                    $pdo->rollBack();
                } catch (\Throwable $rollbackEx) {
                    Log::warning('Failed to rollback transaction on error path: '.$rollbackEx->getMessage());
                }
            }

            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            throw $e;
        }
    }

    /**
     * Check if this export should use PostgreSQL COPY.
     *
     * The COPY path wraps QueryLog::query as a subquery, so it requires that
     * stored SQL to exist. If it doesn't, fall back to the parent per-ID path
     * (extractIds → getRecordsBatch), which can rebuild the filter from the
     * logged request payload.
     */
    public function shouldUseCopyExport(QueryLog $queryLog): bool
    {
        $rowCount = $queryLog->actual_count ?? 0;

        return $rowCount >= $this->copyThreshold && ! empty($queryLog->query);
    }

    /**
     * Override the handle method to use COPY for large exports
     */
    public function handle(): void
    {
        // Get the query log to check row count
        $queryLog = QueryLog::find($this->queryLogId);

        if ($queryLog && $this->shouldUseCopyExport($queryLog)) {
            $this->handleWithCopy();
        } else {
            // Use the parent's default implementation for smaller exports
            parent::handle();
        }
    }

    /**
     * Handle export using PostgreSQL COPY
     */
    protected function handleWithCopy(): void
    {
        // Disable debugbar and query log to prevent memory issues
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::disableQueryLog();

        // Set execution parameters
        ini_set('memory_limit', '512M'); // Much lower memory needed for COPY
        set_time_limit($this->maxExecutionTime);

        $filename = $this->generateFilename();
        $messageContent = $this->initializeMessageContent($filename);

        // Get request information
        $request = request();
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        // Create an export download record
        $exportDownload = ExportDownload::create([
            'user_id' => $this->user->id,
            'filename' => $filename,
            'format' => 'csv',
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'database_key' => $this->getDatabaseKey(),
            'status' => 'processing',
            'message' => 'Using PostgreSQL COPY for large dataset export',
            'started_at' => Carbon::now(),
        ]);

        // Associate with the query log
        $exportDownload->queryLogs()->attach($this->queryLogId);

        try {
            $startTime = microtime(true);
            $directory = $this->getStorageDirectory();

            // Make sure the directory exists
            Storage::makeDirectory($directory);

            $filePath = Storage::path("{$directory}/{$filename}");

            // Get the query log
            $queryLog = QueryLog::findOrFail($this->queryLogId);

            Log::info("Starting COPY export for {$this->getDatabaseKey()}", [
                'query_log_id' => $queryLog->id,
                'estimated_rows' => $queryLog->actual_count,
            ]);

            // Execute the PostgreSQL COPY export
            $totalExported = $this->exportWithPostgresCopy($queryLog, $filePath);

            // Get file size and processing time
            $fileSize = filesize($filePath);
            $formattedFileSize = $this->formatBytes($fileSize);
            $processingTime = round(microtime(true) - $startTime, 2);

            // Update message content
            $messageContent['total_records'] = $totalExported;
            $messageContent['processing_time'] = $processingTime;
            $messageContent['file_size'] = $formattedFileSize;

            Log::info("{$this->getDatabaseKey()} COPY export complete", [
                'records' => $totalExported,
                'time' => $processingTime,
                'size' => $formattedFileSize,
            ]);

            // Update the export download record with completion metrics
            $exportDownload->update([
                'status' => 'completed',
                'record_count' => $totalExported,
                'file_size_bytes' => $fileSize,
                'file_size_formatted' => $formattedFileSize,
                'processing_time_seconds' => $processingTime,
                'completed_at' => Carbon::now(),
            ]);

        } catch (\Exception $e) {
            Log::error("{$this->getDatabaseKey()} COPY export failed: ".$e->getMessage());

            $messageContent['export_failed'] = true;
            $messageContent['error'] = $e->getMessage();

            $exportDownload->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'completed_at' => Carbon::now(),
                'processing_time_seconds' => isset($startTime)
                    ? round(microtime(true) - $startTime, 2)
                    : null,
            ]);
        }

        // Send notification email
        $this->sendNotificationEmail($messageContent);
    }
}
