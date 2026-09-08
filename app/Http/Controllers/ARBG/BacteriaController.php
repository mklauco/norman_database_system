<?php

namespace App\Http\Controllers\ARBG;

use App\Http\Controllers\Controller;
use App\Models\ARBG\BacteriaDataSource;
use App\Models\ARBG\BacteriaMain;
use App\Models\ARBG\DataBacterialGroup;
use App\Models\ARBG\DataCountry;
use App\Models\ARBG\DataSampleMatrix;
use App\Models\Backend\ExportDownload;
use App\Models\Backend\QueryLog;
use App\Models\DatabaseEntity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BacteriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource with all metadata.
     */
    public function show(string $id)
    {
        $record = BacteriaMain::with([
            'coordinate.country',
            'sampleMatrix',
            'bacterialGroup',
            'source.typeOfDataSource',
            'source.typeOfMonitoring',
            'concentrationData',
            'grainSizeDistribution',
            'soilTexture',
            'soilType',
            'depthSamplingType',
            'method.analyticalMethodType',
            'method.typeOfSample',
            'method.targetedAnalysis',
            'method.nonTargetedAnalysis',
        ])->findOrFail($id);

        return view('arbg.bacteria.show', [
            'record' => $record,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        //
    }

    public function filter(Request $request)
    {

        // Get distinct countries from bacteria records with full country names
        $countryIds = BacteriaMain::join('arbg_bacteria_coordinates', 'arbg_bacteria_main.coordinate_id', '=', 'arbg_bacteria_coordinates.id')
            ->whereNotNull('arbg_bacteria_coordinates.country_id')
            ->where('arbg_bacteria_coordinates.country_id', '!=', '')
            ->distinct()
            ->pluck('arbg_bacteria_coordinates.country_id');

        $countryList = DataCountry::whereIn('abbreviation', $countryIds)
            ->orderBy('name')
            ->pluck('name', 'abbreviation')
            ->toArray();

        // Get distinct sample matrices
        $sampleMatrixIds = BacteriaMain::whereNotNull('sample_matrix_id')
            ->distinct()
            ->pluck('sample_matrix_id');

        $matrixList = DataSampleMatrix::filterList($sampleMatrixIds);

        // Get distinct sources (organizations)
        $sourceIds = BacteriaMain::whereNotNull('source_id')
            ->distinct()
            ->pluck('source_id');

        $organisationList = BacteriaDataSource::whereIn('id', $sourceIds)
            ->whereNotNull('organisation')
            ->orderBy('organisation')
            ->distinct()
            ->pluck('organisation', 'organisation')->toArray();

        // Get distinct bacterial groups
        $bacterialGroupIds = BacteriaMain::whereNotNull('bacterial_group_id')
            ->distinct()
            ->pluck('bacterial_group_id');

        $bacterialGroupList = DataBacterialGroup::whereIn('id', $bacterialGroupIds)
            ->orderBy('name')
            ->pluck('name', 'id')->toArray();

        return view('arbg.bacteria.filter', [
            'request' => $request,
            'countryList' => $countryList,
            'matrixList' => $matrixList,
            'organisationList' => $organisationList,
            'bacterialGroupList' => $bacterialGroupList,
            // 'yearList' => $yearList
        ]);
    }

    public function search(Request $request)
    {

        // Define the input fields to process
        $searchFields = ['countrySearch', 'matrixSearch', 'organisationSearch', 'bacterialGroupSearch'];

        // Process each field with the same logic
        /*
        See more details at BioassayController.php method search()
        */
        foreach ($searchFields as $field) {
            ${$field} = is_array($request->input($field))
            ? $request->input($field)
            : json_decode($request->input($field), true);
        }

        $resultsObjects = BacteriaMain::with([
            'coordinate',
            'sampleMatrix',
            'bacterialGroup',
            'source',
        ]);

        $searchParameters = [];

        // Filter by country
        if (! empty($countrySearch)) {
            $resultsObjects = $resultsObjects->whereHas('coordinate', function ($query) use ($countrySearch) {
                $query->whereIn('country_id', $countrySearch);
            });
            // dd($countrySearch);
            $searchParameters['countrySearch'] = DataCountry::whereIn('abbreviation', $countrySearch)
                ->pluck('name');
        }

        // Filter by sample matrix
        if (! empty($matrixSearch)) {
            $resultsObjects = $resultsObjects->whereIn('sample_matrix_id', $matrixSearch);
            $searchParameters['matrixSearch'] = array_values(DataSampleMatrix::filterList($matrixSearch));
        }

        // Filter by organisation
        if (! empty($organisationSearch)) {
            $resultsObjects = $resultsObjects->whereHas('source', function ($query) use ($organisationSearch) {
                $query->whereIn('organisation', $organisationSearch);
            });
            $searchParameters['organisationSearch'] = BacteriaDataSource::whereIn('organisation', $organisationSearch)->pluck('organisation');
        }

        // Filter by bacterial group
        if (! empty($bacterialGroupSearch)) {
            $resultsObjects = $resultsObjects->whereIn('bacterial_group_id', $bacterialGroupSearch);
            $searchParameters['bacterialGroupSearch'] = DataBacterialGroup::whereIn('id', $bacterialGroupSearch)->pluck('name');
        }

        // Filter by year

        // Filter by sampling year range
        if (! is_null($request->input('year_from'))) {
            $resultsObjects = $resultsObjects->where('sampling_date_year', '>=', $request->input('year_from'));
            $searchParameters['year_from'] = $request->input('year_from');
        }

        if (! is_null($request->input('year_to'))) {
            $resultsObjects = $resultsObjects->where('sampling_date_year', '<=', $request->input('year_to'));
            $searchParameters['year_to'] = $request->input('year_to');
        }
        $main_request = $request->all();

        $database_key = 'arbg.bacteria';
        $resultsObjectsCount = DatabaseEntity::where('code', $database_key)->first()->number_of_records ?? 0;

        if (! $request->has('page')) {
            $now = now();
            $bindings = $resultsObjects->getBindings();
            $sql = vsprintf(str_replace('?', "'%s'", $resultsObjects->toSql()), $bindings);
            // try to find same SQL query in the QueryLog table with same total_count based on the query_hash
            $actual_count = QueryLog::where('query_hash', hash('sha256', $sql))->where('total_count', $resultsObjectsCount)->value('actual_count');

            try {
                QueryLog::insert([
                    'content' => json_encode(['request' => $main_request, 'bindings' => $bindings]),
                    'query' => $sql,
                    'user_id' => Auth::check() ? Auth::id() : null,
                    'total_count' => $resultsObjectsCount,
                    'actual_count' => is_null($actual_count) ? null : $actual_count,
                    'database_key' => $database_key,
                    'query_hash' => hash('sha256', $sql),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } catch (\Exception $e) {
                session()->flash('error', 'An error occurred while processing your request.');
            }
        }

        if ($request->displayOption == 1) {
            // use simple pagination
            $resultsObjects = $resultsObjects->orderBy('id', 'asc')
                ->simplePaginate(200)
                ->withQueryString();
        } else {
            // use cursor pagination
            $resultsObjects = $resultsObjects->orderBy('id', 'asc')
                ->paginate(200)
                ->withQueryString();
        }

        return view('arbg.bacteria.index', [
            'resultsObjects' => $resultsObjects,
            'resultsObjectsCount' => $resultsObjectsCount,
            'query_log_id' => QueryLog::orderBy('id', 'desc')->first()->id,
            'request' => $request,
            'searchParameters' => $searchParameters,
            // The view reads these back to rebuild the Refine Search link and to
            // pick its layout. They used to reach it only through the merged
            // request, so a URL missing any of them raised an undefined variable.
            'displayOption' => $request->input('displayOption'),
            'countrySearch' => $request->input('countrySearch'),
            'matrixSearch' => $request->input('matrixSearch'),
            'bacterialGroupSearch' => $request->input('bacterialGroupSearch'),
            'organisationSearch' => $request->input('organisationSearch'),
        ], $main_request);
    }

    /**
     * Start direct CSV download for ARBG Bacteria data (no queue needed due to small dataset)
     */
    public function startDownloadJob($query_log_id)
    {
        if (! Auth::check()) {
            session()->flash('error', 'You must be logged in to download the CSV file.');

            return back();
        }

        try {
            // Get the query log record
            $queryLog = QueryLog::findOrFail($query_log_id);

            // Generate filename
            $filename = 'arbg_bacteria_export_uid_'.Auth::id().'_'.now()->format('YmdHis').'.csv';

            // Get request information for logging
            $ip = request()->ip();
            $userAgent = request()->userAgent();

            // Create an export download record for tracking
            $exportDownload = ExportDownload::create([
                'user_id' => Auth::id(),
                'filename' => $filename,
                'format' => 'csv',
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'database_key' => 'arbg.bacteria',
                'status' => 'processing',
                'started_at' => Carbon::now(),
            ]);

            // Associate with the query log
            $exportDownload->queryLogs()->attach($query_log_id);

            // Process the export directly (no queue needed for small dataset)
            $startTime = microtime(true);
            $directory = 'exports/arbg/bacteria';

            // Make sure the directory exists
            Storage::makeDirectory($directory);

            $path = Storage::path("{$directory}/{$filename}");
            $handle = fopen($path, 'w');

            if (! $handle) {
                throw new \Exception("Unable to open file for writing: {$path}");
            }

            // Write CSV headers
            $headers = [
                'ID',
                'Sample Matrix',
                'Sample Matrix Other',
                'Bacterial Group',
                'Bacterial Group Other',
                'Abundance [CFUs/ml]',
                'Sampling Date',
                'Sampling Year',
                'Sampling Month',
                'Sampling Day',
                'Station Name',
                'Country',
                'Latitude',
                'Longitude',
                'Organization',
                'Remark',
                'Export Date',
            ];
            fputcsv($handle, $headers);

            // Build the query from the query log
            $baseQuery = BacteriaMain::with(['coordinate', 'sampleMatrix', 'bacterialGroup', 'source']);
            $content = json_decode($queryLog->content, true);
            $requestData = $content['request'] ?? [];

            // Process search fields the same way as in the search method
            $searchFields = ['countrySearch', 'matrixSearch', 'organisationSearch', 'bacterialGroupSearch'];

            $filters = [];
            foreach ($searchFields as $field) {
                $filters[$field] = is_array($requestData[$field] ?? null)
                    ? $requestData[$field]
                    : json_decode($requestData[$field] ?? '[]', true);
            }

            // Apply the same filters as in the search method
            if (! empty($filters['countrySearch'])) {
                $baseQuery->whereHas('coordinate', function ($query) use ($filters) {
                    $query->whereIn('country_id', $filters['countrySearch']);
                });
            }

            if (! empty($filters['matrixSearch'])) {
                $baseQuery->whereIn('sample_matrix_id', $filters['matrixSearch']);
            }

            if (! empty($filters['organisationSearch'])) {
                $baseQuery->whereHas('source', function ($query) use ($filters) {
                    $query->whereIn('organisation', $filters['organisationSearch']);
                });
            }

            if (! empty($filters['bacterialGroupSearch'])) {
                $baseQuery->whereIn('bacterial_group_id', $filters['bacterialGroupSearch']);
            }

            if (! is_null($requestData['year_from'] ?? null)) {
                $baseQuery->where('sampling_date_year', '>=', $requestData['year_from']);
            }

            if (! is_null($requestData['year_to'] ?? null)) {
                $baseQuery->where('sampling_date_year', '<=', $requestData['year_to']);
            }

            // Process records in chunks to manage memory
            $totalExported = 0;
            $exportDate = Carbon::now()->format('Y-m-d H:i:s');

            $baseQuery->chunk(500, function ($records) use ($handle, $exportDate, &$totalExported) {
                foreach ($records as $record) {
                    $row = [
                        $record->id,
                        $record->sampleMatrix ? $record->sampleMatrix->name : '',
                        $record->sample_matrix_other ?? '',
                        $record->bacterialGroup ? $record->bacterialGroup->name : '',
                        $record->bacterial_group_other ?? '',
                        $record->abundance ?? '',
                        $record->sampling_date ?? '',
                        $record->sampling_date_year ?? '',
                        $record->sampling_date_month ?? '',
                        $record->sampling_date_day ?? '',
                        $record->coordinate ? $record->coordinate->station_name : '',
                        $record->coordinate ? $record->coordinate->country_id : '',
                        $record->coordinate ? $record->coordinate->latitude : '',
                        $record->coordinate ? $record->coordinate->longitude : '',
                        $record->source ? $record->source->organisation : '',
                        $record->remark ?? '',
                        $exportDate,
                    ];
                    fputcsv($handle, $row);
                    $totalExported++;
                }
            });

            fclose($handle);

            // Get file size and processing time
            $fileSize = Storage::size("{$directory}/{$filename}");
            $formattedFileSize = $this->formatBytes($fileSize);
            $processingTime = round(microtime(true) - $startTime, 2);

            // Update the export download record with completion metrics
            $exportDownload->update([
                'status' => 'completed',
                'record_count' => $totalExported,
                'file_size_bytes' => $fileSize,
                'file_size_formatted' => $formattedFileSize,
                'processing_time_seconds' => $processingTime,
                'completed_at' => Carbon::now(),
            ]);

            Log::info("ARBG Bacteria export complete: {$totalExported} records exported in {$processingTime} seconds. File size: {$formattedFileSize}");

            // Redirect directly to download since processing is complete
            return redirect()->route('arbg.bacteria.csv.download', ['filename' => $filename]);

        } catch (\Exception $e) {
            Log::error('ARBG Bacteria export failed: '.$e->getMessage());

            // Update export download record if it exists
            if (isset($exportDownload)) {
                $exportDownload->update([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                    'completed_at' => Carbon::now(),
                ]);
            }

            session()->flash('error', 'Export failed: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Download the generated CSV file
     */
    public function downloadCsv($filename)
    {
        $directory = 'exports/arbg/bacteria';
        $path = Storage::path("{$directory}/{$filename}");

        // Debug logging for file availability
        Log::info("Download request for: {$filename}", [
            'path' => $path,
            'exists' => file_exists($path),
            'directory_contents' => Storage::files($directory),
        ]);

        if (! file_exists($path)) {
            // Try to find similar files for debugging
            $similarFiles = collect(Storage::files($directory))
                ->filter(function ($file) use ($filename) {
                    $fileBasename = basename($file);
                    $requestBasename = basename($filename);

                    // Check if the filename pattern matches (same user and similar timestamp)
                    return str_contains($fileBasename, explode('_', $requestBasename)[4] ?? '') ||
                           str_contains($fileBasename, explode('_', $requestBasename)[3] ?? '');
                })
                ->values();

            Log::warning("File not found: {$filename}", [
                'path' => $path,
                'similar_files' => $similarFiles->toArray(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'error' => 'File not found',
                'message' => 'The requested CSV file does not exist. It may have expired or failed to generate.',
                'similar_files' => $similarFiles->map(function ($file) {
                    return basename($file);
                }),
            ], 404);
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Format bytes to human-readable file size
     */
    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
