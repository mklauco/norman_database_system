<?php

declare(strict_types=1);

namespace App\Jobs\EmpodatSuspect;

use App\Jobs\AbstractCsvExportJob;
use App\Mail\EmpodatSuspect\CsvExportReady;
use App\Models\Backend\QueryLog;
use App\Models\DatabaseEntity;
use App\Models\EmpodatSuspect\EmpodatSuspectMain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmpodatSuspectCsvExportJob extends AbstractCsvExportJob
{
    /**
     * Optimized batch sizes for efficient processing
     */
    protected $initialBatchSize = 500;

    protected $maxBatchSize = 2000;

    /**
     * Cache for matrix metadata keyed by station_id to avoid re-fetching across batches
     */
    protected array $matrixCache = [];

    /**
     * Extended timeout for large datasets
     */
    protected $maxExecutionTime = 3600; // 1 hour

    /**
     * Job timeout
     */
    public $timeout = 7200; // 2 hours

    /**
     * Matrix types and their corresponding MV tables
     */
    protected array $matrixTypes = [
        'biota' => 'empodat_suspect_matrix_biota',
        'sediments' => 'empodat_suspect_matrix_sediments',
        'water_surface' => 'empodat_suspect_matrix_water_surface',
        'water_ground' => 'empodat_suspect_matrix_water_ground',
        'water_waste' => 'empodat_suspect_matrix_water_waste',
        'suspended_matter' => 'empodat_suspect_matrix_suspended_matter',
        'soil' => 'empodat_suspect_matrix_soil',
        'air' => 'empodat_suspect_matrix_air',
        'sewage_sludge' => 'empodat_suspect_matrix_sewage_sludge',
    ];

    /**
     * Get the database key for this module
     */
    protected function getDatabaseKey(): string
    {
        return 'empodat_suspect';
    }

    /**
     * Get the storage directory for exports
     */
    protected function getStorageDirectory(): string
    {
        return 'exports/empodat_suspect';
    }

    /**
     * Get CSV headers - includes all matrix metadata fields
     */
    protected function getHeaders(): array
    {
        return [
            // empodat_suspect_main fields
            'ID',
            'Norman SUS ID',
            'Substance Name',
            'Concentration',
            'Units',
            'IP',
            'IP Max',
            'Based on HRMS Library',
            'Station ID',
            'Station Name',
            'Sample Code',
            'Country Name',
            'Country Code',
            'Station Latitude',
            'Station Longitude',
            'File ID',

            // Prioritisation data (from empodat_main via MV)
            'Matrix ID',
            'Sampling Date Year',
            'Max IP Max',

            // Matrix type indicator
            'Matrix Type',

            // Biota fields
            'Biota Name',
            'Biota Basin Name',
            'Biota KM',
            'Biota Species',
            'Biota Species Name',
            'Biota Species Alive',
            'Biota Size',
            'Biota Length',
            'Biota Weight',
            'Biota Sex',
            'Biota Age',
            'Biota Agegroup',
            'Biota Number Organisms',
            'Biota Water Content',
            'Biota Dry Wet',
            'Biota Fat Content',
            'Biota Nutrition Condition',
            'Biota No Pooled Individuals',

            // Sediments fields
            'Sediments Name',
            'Sediments Basin Name',
            'Sediments KM',
            'Sediments Depth (m)',
            'Sediments Carbon',
            'Sediments Total Carbon',

            // Water Surface fields
            'Water Surface Name',
            'Water Surface Basin Name',
            'Water Surface KM',
            'Water Surface Depth (m)',
            'Water Surface Salinity Min',
            'Water Surface Salinity Mean',
            'Water Surface Salinity Max',
            'Water Surface SPM',
            'Water Surface pH',
            'Water Surface Temperature',
            'Water Surface Conductivity',
            'Water Surface DOC',
            'Water Surface Carbon',
            'Water Surface Hardness',

            // Water Ground fields
            'Water Ground Name',
            'Water Ground Basin Name',
            'Water Ground Depth (m)',
            'Water Ground Carbon',
            'Water Ground pH',
            'Water Ground Temperature',
            'Water Ground SPM Conc',
            'Water Ground Conductivity',
            'Water Ground DOC',
            'Water Ground Hardness',

            // Water Waste fields
            'Water Waste Name',
            'Water Waste Basin Name',
            'Water Waste pH',
            'Water Waste Temperature',
            'Water Waste Carbon',
            'Water Waste Type Industry',
            'Water Waste Capacity',
            'Water Waste Flow',

            // Suspended Matter fields
            'Suspended Matter Name',
            'Suspended Matter Basin Name',
            'Suspended Matter KM',
            'Suspended Matter Depth (m)',
            'Suspended Matter SPM',
            'Suspended Matter Carbon',
            'Suspended Matter Distance',

            // Soil fields
            'Soil Basin Name',
            'Soil Depth (m)',
            'Soil pH',
            'Soil Carbon',
            'Soil Wider Area',
            'Soil Dry Wet',
            'Soil Type',
            'Soil Texture',
            'Soil Bulk Density',
            'Soil Organic Carbon Content',

            // Air fields
            'Air Temperature',
            'Air Height Level',
            'Air Barometric Pressure',
            'Air Humidity',
            'Air Wider Area',
            'Air Sea Level',
            'Air Wind Speed',
            'Air Wind Direction',
            'Air Flow Rate',
            'Air Ground Level',

            // Sewage Sludge fields
            'Sewage Sludge Basin Name',
            'Sewage Sludge Depth (m)',
            'Sewage Sludge pH',
            'Sewage Sludge Temperature',
            'Sewage Sludge Carbon',
            'Sewage Sludge Type Industry',
            'Sewage Sludge Capacity',
            'Sewage Sludge SRT',
            'Sewage Sludge Reactor',

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
        return EmpodatSuspectMain::query()
            ->whereNotNull('station_id')
            ->whereNotNull('substance_id');
    }

    /**
     * Apply filters from the query log to the base query
     */
    protected function applyQueryFilters($baseQuery, QueryLog $queryLog)
    {
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

        // Apply station-level filters via materialized view
        $hasStationFilters = ! empty($request['countrySearch'])
            || ! empty($request['matrixSearch'])
            || ! empty($request['year_from'])
            || ! empty($request['year_to']);

        if ($hasStationFilters) {
            $stationFiltersQuery = DB::table('empodat_suspect_station_filters');

            if (! empty($request['countrySearch']) && is_array($request['countrySearch'])) {
                $stationFiltersQuery->whereIn('country_id', $request['countrySearch']);
            }

            if (! empty($request['matrixSearch']) && is_array($request['matrixSearch'])) {
                $stationFiltersQuery->whereIn('matrix_id', $request['matrixSearch']);
            }

            if (! empty($request['year_from'])) {
                $stationFiltersQuery->where('sampling_date_year', '>=', $request['year_from']);
            }

            if (! empty($request['year_to'])) {
                $stationFiltersQuery->where('sampling_date_year', '<=', $request['year_to']);
            }

            $filteredStationIds = $stationFiltersQuery->distinct()->pluck('station_id');
            $baseQuery->whereIn('empodat_suspect_main.station_id', $filteredStationIds);
        }

        // Apply substance filter
        if (! empty($request['substances']) && is_array($request['substances'])) {
            $baseQuery->whereIn('empodat_suspect_main.substance_id', $request['substances']);
        }

        // Apply file filter
        if (! empty($request['fileSearch']) && is_array($request['fileSearch'])) {
            $baseQuery->whereIn('empodat_suspect_main.file_id', $request['fileSearch']);
        }

        // Apply category filter (via substance relationship)
        if (! empty($request['categoriesSearch']) && is_array($request['categoriesSearch'])) {
            $baseQuery->whereHas('substance.categories', function ($q) use ($request) {
                $q->whereIn('susdat_categories.id', $request['categoriesSearch']);
            });
        }

        // Apply SLE source filter (via substance relationship)
        if (! empty($request['sourceSearch']) && is_array($request['sourceSearch'])) {
            $baseQuery->whereHas('substance', function ($q) use ($request) {
                $q->whereHas('sources', function ($sourceQuery) use ($request) {
                    $sourceQuery->whereIn('sle_sources.id', $request['sourceSearch']);
                });
            });
        }

        return $baseQuery;
    }

    /**
     * Get records for a batch of IDs with all necessary relationships and matrix metadata
     */
    protected function getRecordsBatch(array $idBatch)
    {
        $orderedIds = array_values($idBatch);
        sort($orderedIds);

        // Get the base records with relationships
        $records = EmpodatSuspectMain::with([
            'substance',
            'station.country',
        ])
            ->whereIn('id', $orderedIds)
            ->orderBy('id')
            ->get();

        // Get station IDs for matrix metadata lookup
        $stationIds = $records->pluck('station_id')->unique()->filter()->values()->toArray();

        // Pre-fetch all matrix metadata for these stations
        $matrixData = $this->fetchMatrixMetadataForStations($stationIds);

        // Pre-fetch prioritisation data by record IDs (efficient single query)
        $prioritisationData = $this->fetchPrioritisationDataForIds($orderedIds);

        // Attach matrix data and prioritisation data to records
        foreach ($records as $record) {
            $record->matrixMetadata = $matrixData[$record->station_id] ?? [];
            $record->prioritisationData = $prioritisationData[$record->id] ?? null;
        }

        return $records;
    }

    /**
     * Fetch prioritisation data for a batch of empodat_suspect_main IDs
     * Returns matrix, sampling_date_year, max_ip_max keyed by suspect record ID
     */
    protected function fetchPrioritisationDataForIds(array $ids): array
    {
        $result = [];

        if (empty($ids)) {
            return $result;
        }

        try {
            $data = DB::table('empodat_suspect_prioritisation')
                ->select('id', 'matrix', 'sampling_date_y', 'max_ip_max', 'station_name')
                ->whereIn('id', $ids)
                ->get();

            foreach ($data as $row) {
                $result[$row->id] = $row;
            }
        } catch (\Exception $e) {
            // MV might not exist, log and continue without prioritisation data
            Log::warning('Prioritisation MV not available: '.$e->getMessage());
        }

        return $result;
    }

    /**
     * Fetch matrix metadata for a set of station IDs, using cross-batch cache
     */
    protected function fetchMatrixMetadataForStations(array $stationIds): array
    {
        if (empty($stationIds)) {
            return [];
        }

        // Only query for station IDs not already cached
        $uncachedIds = array_values(array_diff($stationIds, array_keys($this->matrixCache)));

        if (! empty($uncachedIds)) {
            // Initialize cache entries for uncached stations
            foreach ($uncachedIds as $id) {
                $this->matrixCache[$id] = [];
            }

            foreach ($this->matrixTypes as $type => $tableName) {
                try {
                    $data = DB::table($tableName)
                        ->whereIn('station_id', $uncachedIds)
                        ->get()
                        ->groupBy('station_id');

                    foreach ($data as $stationId => $rows) {
                        $this->matrixCache[$stationId][$type] = $rows->first();
                    }
                } catch (\Exception $e) {
                    Log::debug("Matrix MV {$tableName} not available: ".$e->getMessage());
                }
            }
        }

        // Return only the requested station IDs from cache
        $result = [];
        foreach ($stationIds as $id) {
            $result[$id] = $this->matrixCache[$id] ?? [];
        }

        return $result;
    }

    /**
     * Initialize message content for email notification
     * Override to use the correct route for EmpodatSuspect downloads
     *
     * @return array<string, mixed>
     */
    protected function initializeMessageContent(string $filename): array
    {
        return [
            'user' => $this->user->name ?? $this->user->email,
            'filename' => $filename,
            'download_link' => route('empodat_suspect.csv.download', ['filename' => $filename]),
            'my_downloads_link' => route('export_downloads.index', ['user_id' => $this->user->id]),
            'is_public' => $this->moduleIsPublic(),
            'total_records' => 0,
            'processing_time' => 0,
            'file_size' => '0 KB',
            'export_failed' => false,
        ];
    }

    /**
     * Whether the EmpoDat Suspect module is currently public.
     *
     * While the module is non-public, downloads require an authenticated
     * session, so the notification email must direct the user to log in and
     * fetch the prepared file from "My Downloads" rather than offering a raw
     * download link. This reads the same flag the access controller enforces,
     * so the email reverts automatically once the module is made public.
     */
    protected function moduleIsPublic(): bool
    {
        return (bool) DatabaseEntity::where('code', $this->getDatabaseKey())->value('is_public');
    }

    /**
     * Format a single record for CSV output with all matrix metadata
     */
    protected function formatRecord($record, string $exportDate): array
    {
        // Get country information safely
        $countryName = '';
        $countryCode = '';
        $stationLatitude = '';
        $stationLongitude = '';
        $stationName = '';
        $sampleCode = '';

        if ($record->station) {
            $stationName = $record->station->name ?? '';
            $sampleCode = $record->station->short_sample_code ?? '';
            $stationLatitude = $record->station->latitude ?? '';
            $stationLongitude = $record->station->longitude ?? '';

            if ($record->station->country_id) {
                $country = $record->station->getRelation('country');
                if ($country) {
                    $countryName = $country->name ?? '';
                    $countryCode = $country->code ?? '';
                }
            }
        }

        // Get prioritisation data (from empodat_main via MV)
        $prioritisation = $record->prioritisationData;
        $matrixId = $prioritisation->matrix ?? '';
        $samplingDateYear = $prioritisation->sampling_date_y ?? '';
        $maxIpMax = $prioritisation->max_ip_max ?? '';

        // Determine which matrix type has data
        $matrixType = '';
        $matrixData = $record->matrixMetadata ?? [];

        // Get matrix-specific fields
        $biota = $matrixData['biota'] ?? null;
        $sediments = $matrixData['sediments'] ?? null;
        $waterSurface = $matrixData['water_surface'] ?? null;
        $waterGround = $matrixData['water_ground'] ?? null;
        $waterWaste = $matrixData['water_waste'] ?? null;
        $suspendedMatter = $matrixData['suspended_matter'] ?? null;
        $soil = $matrixData['soil'] ?? null;
        $air = $matrixData['air'] ?? null;
        $sewageSludge = $matrixData['sewage_sludge'] ?? null;

        // Determine primary matrix type
        if ($biota) {
            $matrixType = 'Biota';
        } elseif ($sediments) {
            $matrixType = 'Sediments';
        } elseif ($waterSurface) {
            $matrixType = 'Water (Surface)';
        } elseif ($waterGround) {
            $matrixType = 'Water (Ground)';
        } elseif ($waterWaste) {
            $matrixType = 'Water (Waste)';
        } elseif ($suspendedMatter) {
            $matrixType = 'Suspended Matter';
        } elseif ($soil) {
            $matrixType = 'Soil';
        } elseif ($air) {
            $matrixType = 'Air';
        } elseif ($sewageSludge) {
            $matrixType = 'Sewage Sludge';
        }

        return [
            // empodat_suspect_main fields
            $record->id,
            $record->substance && $record->substance->code ? 'NS'.$record->substance->code : '',
            $record->substance->name ?? '',
            $record->concentration ?? '',
            $record->units ?? '',
            $record->ip ?? '',
            $record->ip_max ?? '',
            $record->based_on_hrms_library ? 'TRUE' : 'FALSE',
            $record->station_id ?? '',
            $stationName,
            $sampleCode,
            $countryName,
            $countryCode,
            $stationLatitude,
            $stationLongitude,
            $record->file_id ?? '',

            // Prioritisation data (from empodat_main via MV)
            $matrixId,
            $samplingDateYear,
            $maxIpMax,

            // Matrix type
            $matrixType,

            // Biota fields
            $biota->name ?? '',
            $biota->basin_name ?? '',
            $biota->km ?? '',
            $biota->species ?? '',
            $biota->species_name ?? '',
            $biota->species_alive ?? '',
            $biota->biota_size ?? '',
            $biota->biota_length ?? '',
            $biota->biota_weight ?? '',
            $biota->biota_sex ?? '',
            $biota->biota_age ?? '',
            $biota->agegroup ?? '',
            $biota->number_organisms ?? '',
            $biota->water_content ?? '',
            $biota->dry_wet ?? '',
            $biota->fat_content ?? '',
            $biota->nutrition_condition ?? '',
            $biota->no_pooled_individuals ?? '',

            // Sediments fields
            $sediments->name ?? '',
            $sediments->basin_name ?? '',
            $sediments->km ?? '',
            $sediments->depth_m ?? '',
            $sediments->carbon ?? '',
            $sediments->total_carbon ?? '',

            // Water Surface fields
            $waterSurface->name ?? '',
            $waterSurface->basin_name ?? '',
            $waterSurface->km ?? '',
            $waterSurface->depth_m ?? '',
            $waterSurface->salinity_min ?? '',
            $waterSurface->salinity_mean ?? '',
            $waterSurface->salinity_max ?? '',
            $waterSurface->spm ?? '',
            $waterSurface->ph ?? '',
            $waterSurface->temperature ?? '',
            $waterSurface->conductivity ?? '',
            $waterSurface->doc ?? '',
            $waterSurface->carbon ?? '',
            $waterSurface->hardness ?? '',

            // Water Ground fields
            $waterGround->name ?? '',
            $waterGround->basin_name ?? '',
            $waterGround->depth_m ?? '',
            $waterGround->carbon ?? '',
            $waterGround->ph ?? '',
            $waterGround->temperature ?? '',
            $waterGround->spm_conc ?? '',
            $waterGround->conductivity ?? '',
            $waterGround->doc ?? '',
            $waterGround->hardness ?? '',

            // Water Waste fields
            $waterWaste->name ?? '',
            $waterWaste->basin_name ?? '',
            $waterWaste->ph ?? '',
            $waterWaste->temperature ?? '',
            $waterWaste->carbon ?? '',
            $waterWaste->type_industry ?? '',
            $waterWaste->capacity ?? '',
            $waterWaste->flow ?? '',

            // Suspended Matter fields
            $suspendedMatter->name ?? '',
            $suspendedMatter->basin_name ?? '',
            $suspendedMatter->km ?? '',
            $suspendedMatter->depth_m ?? '',
            $suspendedMatter->spm ?? '',
            $suspendedMatter->carbon ?? '',
            $suspendedMatter->distance ?? '',

            // Soil fields
            $soil->basin_name ?? '',
            $soil->depth_m ?? '',
            $soil->ph ?? '',
            $soil->carbon ?? '',
            $soil->wider_area ?? '',
            $soil->dry_wet ?? '',
            $soil->soil_type ?? '',
            $soil->soil_texture ?? '',
            $soil->bulk_density ?? '',
            $soil->organic_carbon_content ?? '',

            // Air fields
            $air->temperature ?? '',
            $air->height_level ?? '',
            $air->barometric_pressure ?? '',
            $air->humidity ?? '',
            $air->wider_area ?? '',
            $air->sea_level ?? '',
            $air->wind_speed ?? '',
            $air->wind_direction ?? '',
            $air->flow_rate ?? '',
            $air->ground_level ?? '',

            // Sewage Sludge fields
            $sewageSludge->basin_name ?? '',
            $sewageSludge->depth_m ?? '',
            $sewageSludge->ph ?? '',
            $sewageSludge->temperature ?? '',
            $sewageSludge->carbon ?? '',
            $sewageSludge->type_industry ?? '',
            $sewageSludge->capacity ?? '',
            $sewageSludge->srt ?? '',
            $sewageSludge->reactor ?? '',

            $exportDate,
        ];
    }
}
