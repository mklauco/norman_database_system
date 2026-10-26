<?php

namespace App\Http\Controllers\Indoor;

use App\Http\Controllers\Controller;
use App\Models\Backend\ExportDownload;
use App\Models\Backend\QueryLog;
use App\Models\DatabaseEntity;
use App\Models\Indoor\IndoorDataCountry;
use App\Models\Indoor\IndoorDataDcoe;
use App\Models\Indoor\IndoorDataDic;
use App\Models\Indoor\IndoorDataDtoe;
use App\Models\Indoor\IndoorDataMatrix;
use App\Models\Indoor\IndoorMain;
use App\Models\SLE\SuspectListExchangeSource;
use App\Models\Susdat\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IndoorController extends Controller
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
        $record = IndoorMain::with([
            'countryRecord',
            'countryOtherRecord',
            'matrix',
            'environmentType',
            'environmentCategory',
            'substance',
            'purposeCode',
            'observationType',
            'collectionCode',
            'analyticalMethod.coverageFactor',
            'analyticalMethod.samplingMethod1',
            'analyticalMethod.samplingMethod2',
            'analyticalMethod.samplePreparationMethod',
            'analyticalMethod.analyticalMethod',
            'analyticalMethod.standardisedMethod',
            'dataSource.typeOfDataSource',
        ])->findOrFail($id);

        return view('indoor.show', [
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
    public function destroy(string $id)
    {
        //
    }

    public function filter(Request $request)
    {
        $countryIds = IndoorMain::distinct('country')
            ->whereNotNull('country')
            ->pluck('country')
            ->toArray();

        $countryList = IndoorDataCountry::whereIn('abbreviation', $countryIds)
            ->orderBy('name')
            ->pluck('name', 'abbreviation')
            ->toArray();
        $environmentTypeList = IndoorDataDtoe::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $environmentCategoryList = IndoorDataDcoe::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $matrixList = IndoorDataMatrix::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $concentrationIndicatorList = IndoorDataDic::orderBy('id')
            ->pluck('name', 'id')
            ->toArray();

        // SLE Sources
        $sources = SuspectListExchangeSource::where('show', 1)
            ->whereNotNull('order')
            ->orderBy('order', 'asc')
            ->get();
        $sourceList = [];
        foreach ($sources as $s) {
            $code = preg_replace('/[^a-zA-Z0-9]/', '', $s->code);
            $name = preg_replace('/[^a-zA-Z0-9]/', '', $s->name);
            $sourceList[$s->id] = $code.' - '.$name;
        }
        asort($sourceList);

        // Categories
        $categories = Category::select('id', 'name', 'abbreviation')
            ->get()
            ->map(function ($cat) {
                $cat->name_abbreviation = $cat->name.($cat->abbreviation ? ' ('.$cat->abbreviation.')' : '');

                return $cat;
            })
            ->keyBy('id');

        return view('indoor.filter', [
            'request' => $request,
            'countryList' => $countryList,
            'environmentTypeList' => $environmentTypeList,
            'environmentCategoryList' => $environmentCategoryList,
            'matrixList' => $matrixList,
            'concentrationIndicatorList' => $concentrationIndicatorList,
            'sourceList' => $sourceList,
            'categories' => $categories,
        ]);
    }

    public function search(Request $request)
    {

        // Define the input fields to process
        $searchFields = ['countrySearch', 'matrixSearch', 'environmentTypeSearch', 'environmentCategorySearch', 'concentrationIndicatorSearch'];

        // Process each field with the same logic
        /*
        See more details at BioassayController.php method search()
        */
        foreach ($searchFields as $field) {
            $value = is_array($request->input($field))
            ? $request->input($field)
            : json_decode((string) $request->input($field), true);

            ${$field} = is_array($value) ? array_values(array_filter($value, 'is_scalar')) : [];
        }

        // Process substances separately (comes from Livewire component)
        $substances = is_array($request->input('substances'))
            ? $request->input('substances')
            : json_decode($request->input('substances'), true);

        // Process sourceSearch
        $sourceSearch = is_array($request->input('sourceSearch'))
            ? $request->input('sourceSearch')
            : json_decode($request->input('sourceSearch'), true);

        // Process categoriesSearch (checkboxes)
        $categoriesSearch = $request->input('categoriesSearch', []);

        $searchParameters = [];

        $resultsObjects = IndoorMain::with([
            'countryRecord',
            'matrix',
            'environmentType',
            'environmentCategory',
            'substance',
            'collectionCode',
        ]);

        // Apply country filter
        if (! empty($countrySearch)) {
            $resultsObjects = $resultsObjects->whereIn('country', $countrySearch); // TOTO JE ZLE !
            $searchParameters['countrySearch'] = IndoorDataCountry::whereIn('abbreviation', $countrySearch)->pluck('name');
        }

        // Apply matrix filter
        if (! empty($matrixSearch)) {
            $resultsObjects = $resultsObjects->whereIn('matrix_id', $matrixSearch);
            $searchParameters['matrixSearch'] = IndoorDataMatrix::whereIn('id', $matrixSearch)->pluck('name');
        }

        // Apply environment type filter
        if (! empty($environmentTypeSearch)) {
            $resultsObjects = $resultsObjects->whereIn('dtoe_id', $environmentTypeSearch);
            $searchParameters['environmentTypeSearch'] = IndoorDataDtoe::whereIn('id', $environmentTypeSearch)->pluck('name');
        }

        // Apply environment category filter
        if (! empty($environmentCategorySearch)) {
            $resultsObjects = $resultsObjects->whereIn('dcoe_id', $environmentCategorySearch);
            $searchParameters['environmentCategorySearch'] = IndoorDataDcoe::whereIn('id', $environmentCategorySearch)->pluck('name');
        }

        // Apply substance filter
        if (! empty($substances)) {
            $resultsObjects = $resultsObjects->whereIn('substance_id', $substances);
            $searchParameters['substances'] = \App\Models\Susdat\Substance::whereIn('id', $substances)->pluck('name');
        }

        // Apply concentration indicator filter
        if (! empty($concentrationIndicatorSearch)) {
            $resultsObjects = $resultsObjects->whereIn('dic_id', $concentrationIndicatorSearch);
            $searchParameters['concentrationIndicatorSearch'] = IndoorDataDic::whereIn('id', $concentrationIndicatorSearch)->pluck('name');
        }

        // Apply SLE source filter (via substance relationship)
        if (! empty($sourceSearch)) {
            $resultsObjects = $resultsObjects->bySources($sourceSearch);
            $searchParameters['sourceSearch'] = SuspectListExchangeSource::whereIn('id', $sourceSearch)->pluck('code');
        }

        // Apply category filter (via substance relationship)
        if (! empty($categoriesSearch)) {
            $resultsObjects = $resultsObjects->byCategories($categoriesSearch);
            $searchParameters['categoriesSearch'] = Category::whereIn('id', $categoriesSearch)->pluck('name');
        }

        $main_request = $request->all();

        $database_key = 'indoor';
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
                    'user_id' => auth()->check() ? auth()->id() : null,
                    'total_count' => $resultsObjectsCount,
                    'actual_count' => is_null($actual_count) ? null : $actual_count,
                    'database_key' => $database_key,
                    'query_hash' => hash('sha256', $sql),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } catch (\Exception $e) {
                if (Auth::check() && Auth::user()->hasRole('super_admin')) {
                    session()->flash('failure', 'Query logging error: '.$e->getMessage());
                } else {
                    session()->flash('error', 'An error occurred while processing your request.');
                }
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

        return view('indoor.index', [
            'resultsObjects' => $resultsObjects,
            'resultsObjectsCount' => $resultsObjectsCount,
            'query_log_id' => QueryLog::orderBy('id', 'desc')->first()->id,
            'request' => $request,
            'searchParameters' => $searchParameters,
        ], $main_request);

    }

    /**
     * Start direct CSV download for Indoor data
     */
    public function startDownloadJob($query_log_id)
    {
        if (! Auth::check()) {
            session()->flash('error', 'You must be logged in to download the CSV file.');

            return back();
        }

        // Disable DebugBar to prevent memory exhaustion during large exports
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        try {
            // Get the query log record
            $queryLog = QueryLog::findOrFail($query_log_id);

            // Generate filename
            $filename = 'indoor_export_uid_'.Auth::id().'_'.now()->format('YmdHis').'.csv';

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
                'database_key' => 'indoor',
                'status' => 'processing',
                'started_at' => Carbon::now(),
            ]);

            // Associate with the query log
            $exportDownload->queryLogs()->attach($query_log_id);

            // Process the export directly
            $startTime = microtime(true);
            $directory = 'exports/indoor';

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
                'Country',
                'Station Name',
                'Sample Code',
                'Matrix',
                'Environment Type',
                'Environment Category',
                'Concentration Value',
                'Concentration Unit',
                'Sampling Date',
                'Latitude',
                'Longitude',
                'Remark',
            ];
            fputcsv($handle, $headers);

            // Build optimized query with joins instead of eager loading
            $content = json_decode($queryLog->content, true);
            $requestData = $content['request'] ?? [];

            // Process search fields
            $searchFields = ['countrySearch', 'matrixSearch', 'environmentTypeSearch', 'environmentCategorySearch', 'concentrationIndicatorSearch'];
            $filters = [];
            foreach ($searchFields as $field) {
                $filters[$field] = is_array($requestData[$field] ?? null)
                    ? $requestData[$field]
                    : json_decode($requestData[$field] ?? '[]', true);
            }

            // Process substances separately
            $filters['substances'] = is_array($requestData['substances'] ?? null)
                ? $requestData['substances']
                : json_decode($requestData['substances'] ?? '[]', true);

            // Process sourceSearch
            $filters['sourceSearch'] = is_array($requestData['sourceSearch'] ?? null)
                ? $requestData['sourceSearch']
                : json_decode($requestData['sourceSearch'] ?? '[]', true);

            // Process categoriesSearch
            $filters['categoriesSearch'] = $requestData['categoriesSearch'] ?? [];

            // Use raw query with joins for better performance
            $baseQuery = DB::table('indoor_main as im')
                ->leftJoin('indoor_data_country as idc', 'im.country', '=', 'idc.abbreviation')
                ->leftJoin('indoor_data_matrix as idm', 'im.matrix_id', '=', 'idm.id')
                ->leftJoin('indoor_data_dtoe as idt', 'im.dtoe_id', '=', 'idt.id')
                ->leftJoin('indoor_data_dcoe as idcat', 'im.dcoe_id', '=', 'idcat.id')
                ->select([
                    'im.id',
                    'idc.name as country_name',
                    'im.country as country_code',
                    'im.station_name',
                    'im.sample_code',
                    'idm.name as matrix_name',
                    'idt.name as environment_type_name',
                    'idcat.name as environment_category_name',
                    'im.concentration_value',
                    'im.concentration_unit',
                    'im.sampling_date_y',
                    'im.sampling_date_m',
                    'im.sampling_date_d',
                    'im.latitude_decimal',
                    'im.longitude_decimal',
                    'im.remark',
                ]);

            // Apply filters
            if (! empty($filters['countrySearch'])) {
                $baseQuery->whereIn('im.country', $filters['countrySearch']);
            }

            if (! empty($filters['matrixSearch'])) {
                $baseQuery->whereIn('im.matrix_id', $filters['matrixSearch']);
            }

            if (! empty($filters['environmentTypeSearch'])) {
                $baseQuery->whereIn('im.dtoe_id', $filters['environmentTypeSearch']);
            }

            if (! empty($filters['environmentCategorySearch'])) {
                $baseQuery->whereIn('im.dcoe_id', $filters['environmentCategorySearch']);
            }

            if (! empty($filters['substances'])) {
                $baseQuery->whereIn('im.substance_id', $filters['substances']);
            }

            if (! empty($filters['concentrationIndicatorSearch'])) {
                $baseQuery->whereIn('im.dic_id', $filters['concentrationIndicatorSearch']);
            }

            // Apply SLE source filter (via substance relationship)
            if (! empty($filters['sourceSearch'])) {
                $baseQuery->join('susdat_substances as ss_src', 'im.substance_id', '=', 'ss_src.id')
                    ->join('susdat_source_substance as sss', 'ss_src.id', '=', 'sss.substance_id')
                    ->whereIn('sss.source_id', $filters['sourceSearch'])
                    ->distinct();
            }

            // Apply category filter (via substance relationship)
            if (! empty($filters['categoriesSearch'])) {
                $baseQuery->join('susdat_substances as ss_cat', 'im.substance_id', '=', 'ss_cat.id')
                    ->join('susdat_category_substance as scs', 'ss_cat.id', '=', 'scs.substance_id')
                    ->whereIn('scs.category_id', $filters['categoriesSearch'])
                    ->distinct();
            }

            // Process records in chunks
            $totalExported = 0;

            $baseQuery->orderBy('im.id')->chunk(1000, function ($records) use ($handle, &$totalExported) {
                foreach ($records as $record) {
                    // Format sampling date
                    $samplingDate = '';
                    if ($record->sampling_date_y) {
                        $samplingDate = $record->sampling_date_y;
                        if ($record->sampling_date_m) {
                            $samplingDate .= '-'.str_pad($record->sampling_date_m, 2, '0', STR_PAD_LEFT);
                            if ($record->sampling_date_d) {
                                $samplingDate .= '-'.str_pad($record->sampling_date_d, 2, '0', STR_PAD_LEFT);
                            }
                        }
                    }

                    $row = [
                        $record->id,
                        $record->country_name ?? $record->country_code ?? '',
                        $record->station_name ?? '',
                        $record->sample_code ?? '',
                        $record->matrix_name ?? '',
                        $record->environment_type_name ?? '',
                        $record->environment_category_name ?? '',
                        $record->concentration_value ?? '',
                        $record->concentration_unit ?? '',
                        $samplingDate,
                        $record->latitude_decimal ?? '',
                        $record->longitude_decimal ?? '',
                        $record->remark ?? '',
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

            Log::info("Indoor export complete: {$totalExported} records exported in {$processingTime} seconds. File size: {$formattedFileSize}");

            // Redirect directly to download since processing is complete
            return redirect()->route('indoor.csv.download', ['filename' => $filename]);

        } catch (\Exception $e) {
            Log::error('Indoor export failed: '.$e->getMessage());

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
        $directory = 'exports/indoor';
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

                    return str_contains($fileBasename, explode('_', $requestBasename)[3] ?? '') ||
                           str_contains($fileBasename, explode('_', $requestBasename)[2] ?? '');
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
