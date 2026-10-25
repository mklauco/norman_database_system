<?php

namespace App\Http\Controllers\Passive;

use App\Http\Controllers\Controller;
use App\Models\Backend\DisplaySection;
use App\Models\Backend\ExportDownload;
use App\Models\Backend\QueryLog;
use App\Models\DatabaseEntity;
use App\Models\Passive\PassiveDataCountry;
use App\Models\Passive\PassiveDataMatrix;
use App\Models\Passive\PassiveSamplingMain;
use App\Models\Susdat\Substance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PassiveController extends Controller
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
    public function show(string $search, Request $request)
    {
        $passive = PassiveSamplingMain::with([
            'country',
            'matrix',
            'substance',
            'organisation',
            'analyticalMethod',
        ])->findOrFail($search);

        // Load display configuration for passive module
        $sections = DisplaySection::with(['columns' => function ($query) {
            $query->where('is_visible', true)->orderBy('display_order');
        }, 'sectionType'])
            ->forModule('passive')
            ->where('is_visible', true)
            ->orderBy('display_order')
            ->get();

        // Get glance columns across all sections
        $glanceColumns = $sections->flatMap(function ($section) {
            return $section->columns->where('is_glance', true);
        });

        return view('passive.show', compact('passive', 'request', 'sections', 'glanceColumns'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $search, Request $request)
    {
        $passive = PassiveSamplingMain::with([
            'country',
            'matrix',
            'substance',
        ])->findOrFail($search);

        return view('passive.edit', compact('passive', 'request'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $search)
    {
        $passive = PassiveSamplingMain::findOrFail($search);

        $validated = $request->validate([
            'sus_id' => 'nullable|integer',
            'country_id' => 'nullable|string|max:2',
            'country_other' => 'nullable|string|max:2',
            'station_name' => 'nullable|string|max:255',
            'short_sample_code' => 'nullable|string',
            'sample_code' => 'nullable|string',
            'provider_code' => 'nullable|string',
            'national_code' => 'nullable|string',
            'code_ec_wise' => 'nullable|string|max:255',
            'code_ec_other' => 'nullable|string|max:255',
            'code_other' => 'nullable|string|max:255',
            'specific_locations' => 'nullable|string',
            'longitude_decimal' => 'nullable|string|max:20',
            'latitude_decimal' => 'nullable|string|max:20',
            'dpc_id' => 'nullable|integer',
            'altitude' => 'nullable|string|max:20',
            'dpr_id' => 'nullable|integer',
            'dpr_other' => 'nullable|string|max:255',
            'ds_passive_sampling_stretch' => 'nullable|string|max:255',
            'ds_stretch_start_and_end' => 'nullable|string',
            'ds_longitude_start_point_decimal' => 'nullable|string|max:20',
            'ds_latitude_start_point_decimal' => 'nullable|string|max:20',
            'ds_longitude_end_point_decimal' => 'nullable|string|max:20',
            'ds_latitude_end_point_decimal' => 'nullable|string|max:20',
            'ds_dpc_id' => 'nullable|integer',
            'ds_altitude' => 'nullable|string|max:20',
            'ds_dpr_id' => 'nullable|integer',
            'ds_dpr_other' => 'nullable|string|max:255',
            'matrix_id' => 'nullable|integer',
            'matrix_other' => 'nullable|string|max:30',
            'type_sampling_id' => 'required|integer',
            'type_sampling_other' => 'nullable|string|max:255',
            'passive_sampler_id' => 'nullable|integer',
            'passive_sampler_other' => 'nullable|string|max:255',
            'sampler_type_id' => 'nullable|integer',
            'sampler_type_other' => 'nullable|string|max:255',
            'sampler_mass' => 'nullable|string|max:20',
            'sampler_surface_area' => 'nullable|string|max:20',
            'date_sampling_start_day' => 'nullable|integer|min:1|max:31',
            'date_sampling_start_month' => 'nullable|integer|min:1|max:12',
            'date_sampling_start_year' => 'required|integer|min:1900|max:2100',
            'exposure_time_days' => 'nullable|string|max:20',
            'exposure_time_hours' => 'nullable|string|max:20',
            'date_of_analysis' => 'nullable|date',
            'time_of_analysis' => 'nullable|date_format:H:i:s',
            'name' => 'nullable|string|max:255',
            'basin_name_id' => 'nullable|integer',
            'basin_name_other' => 'nullable|string|max:255',
            'dts_id' => 'nullable|integer',
            'dts_other' => 'nullable|string|max:255',
            'dtm_id' => 'nullable|integer',
            'dtm_other' => 'nullable|string|max:255',
            'dic_id' => 'required|integer',
            'concentration_value' => 'required|numeric',
            'unit' => 'required|string|max:20',
            'title_of_project' => 'nullable|string|max:255',
            'ph' => 'nullable|string|max:255',
            'temperature' => 'nullable|string|max:255',
            'spm_conc' => 'nullable|string|max:255',
            'salinity' => 'nullable|string|max:255',
            'doc' => 'nullable|string|max:255',
            'hardness' => 'nullable|string|max:255',
            'o2_1' => 'nullable|string|max:255',
            'o2_2' => 'nullable|string|max:255',
            'bod5' => 'nullable|string|max:255',
            'h2s' => 'nullable|string|max:255',
            'p_po4' => 'nullable|string|max:255',
            'n_no2' => 'nullable|string|max:255',
            'tss' => 'nullable|string|max:255',
            'p_total' => 'nullable|string|max:255',
            'n_no3' => 'nullable|string|max:255',
            'n_total' => 'nullable|string|max:255',
            'remark_1' => 'nullable|string|max:255',
            'remark_2' => 'nullable|string|max:255',
            'am_id' => 'required|integer',
            'org_id' => 'required|integer',
            'orig_compound' => 'required|string|max:255',
            'orig_cas_no' => 'required|string|max:255',
            'p_determinand_id' => 'required|string|max:255',
            'p_a_exposure_time' => 'nullable|string',
            'p_a_cruise_dates' => 'nullable|string',
            'p_a_river_km' => 'nullable|string',
            'p_a_sampler_sheets_disks_nr' => 'nullable|string',
            'p_a_sample_code' => 'nullable|string',
        ]);

        $passive->update($validated);

        session()->flash('success', 'Passive sampling record updated successfully.');

        return redirect()->route('passive.search.show', ['search' => $passive->id] + $request->all());
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
        $countryIds = PassiveSamplingMain::distinct('country_id')
            ->whereNotNull('country_id')
            ->pluck('country_id')
            ->toArray();

        // Get the country names in alphabetical order
        $countryList = PassiveDataCountry::whereIn('abbreviation', $countryIds)
            ->orderBy('name')
            ->pluck('name', 'abbreviation')
            ->toArray();

        // Get matrices that are actually used in the main table
        $matrixIds = PassiveSamplingMain::distinct('matrix_id')
            ->whereNotNull('matrix_id')
            ->pluck('matrix_id')
            ->toArray();

        // Get the matrix names in alphabetical order
        $matrixList = PassiveDataMatrix::whereIn('id', $matrixIds)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return view('passive.filter', [
            'request' => $request,
            'countryList' => $countryList,
            // 'environmentTypeList'     => $environmentTypeList,
            // 'environmentCategoryList' => $environmentCategoryList,
            'matrixList' => $matrixList,
        ]);
    }

    public function search(Request $request)
    {

        // Define the input fields to process
        $searchFields = ['countrySearch', 'matrixSearch'];

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
        $searchParameters = [];

        $resultsObjects = PassiveSamplingMain::with([
            'country',
            'substance',
        ]);

        // Apply country filter
        if (! empty($countrySearch)) {
            $resultsObjects = $resultsObjects->whereIn('country_id', $countrySearch); // TOTO JE ZLE !
            $searchParameters['countrySearch'] = PassiveDataCountry::whereIn('abbreviation', $countrySearch)->pluck('name');
        }

        // Apply matrix filter
        if (! empty($matrixSearch)) {
            $resultsObjects = $resultsObjects->whereIn('matrix_id', $matrixSearch);
            $searchParameters['matrixSearch'] = PassiveDataMatrix::whereIn('id', $matrixSearch)->pluck('name');
        }

        if (! empty($request->input('substances'))) {
            $resultsObjects = $resultsObjects->whereIn('passive_sampling_main.substance_id', $request->input('substances'));
            $searchParameters['substances'] = Substance::whereIn('id', $request->input('substances'))->pluck('name');
        } else {
            // TASK fix this:
            $request->merge(['substances' => []]);
        }

        $main_request = $request->all();

        $database_key = 'passive';
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

        // dd($main_request);

        return view('passive.index', [
            'resultsObjects' => $resultsObjects,
            'resultsObjectsCount' => $resultsObjectsCount,
            'query_log_id' => QueryLog::orderBy('id', 'desc')->first()->id,
            'request' => $request,
            'searchParameters' => $searchParameters,
        ], $main_request);

    }

    /**
     * Start direct CSV download for Passive sampling data (no queue needed due to small dataset)
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
            $filename = 'passive_export_uid_'.Auth::id().'_'.now()->format('YmdHis').'.csv';

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
                'database_key' => 'passive',
                'status' => 'processing',
                'started_at' => Carbon::now(),
            ]);

            // Associate with the query log
            $exportDownload->queryLogs()->attach($query_log_id);

            // Process the export directly (no queue needed for small dataset)
            $startTime = microtime(true);
            $directory = 'exports/passive';

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
                'Substance',
                'Country',
                'Matrix',
                'Organisation',
                'Station Name',
                'Sample Code',
                'Sampling Date',
                'Exposure Time (Days)',
                'Concentration Value',
                'Unit',
                'Export Date',
            ];
            fputcsv($handle, $headers);

            // Build the query from the query log
            $baseQuery = PassiveSamplingMain::with(['country', 'matrix', 'substance', 'organisation']);
            $content = json_decode($queryLog->content, true);
            $requestData = $content['request'] ?? [];

            // Process search fields to handle JSON strings properly
            $countrySearch = is_array($requestData['countrySearch'] ?? null)
              ? $requestData['countrySearch']
              : json_decode($requestData['countrySearch'] ?? '[]', true);

            $matrixSearch = is_array($requestData['matrixSearch'] ?? null)
              ? $requestData['matrixSearch']
              : json_decode($requestData['matrixSearch'] ?? '[]', true);

            // Apply the same filters as in the search method
            if (! empty($countrySearch)) {
                $baseQuery->whereIn('country_id', $countrySearch);
            }

            if (! empty($matrixSearch)) {
                $baseQuery->whereIn('matrix_id', $matrixSearch);
            }

            if (! empty($requestData['substances'])) {
                $baseQuery->whereIn('substance_id', $requestData['substances']);
            }

            // Process records in chunks to manage memory
            $totalExported = 0;
            $exportDate = Carbon::now()->format('Y-m-d H:i:s');

            $baseQuery->chunk(500, function ($records) use ($handle, $exportDate, &$totalExported) {
                foreach ($records as $record) {
                    $row = [
                        $record->id,
                        $record->substance->name ?? 'N/A',
                        $record->country->name ?? 'N/A',
                        $record->matrix ? $record->matrix->name : ($record->matrix_other ?? 'N/A'),
                        $record->organisation->org_name ?? 'N/A',
                        $record->station_name ?? 'N/A',
                        $record->sample_code ?? 'N/A',
                        $record->date_sampling_start_year ?
                          $record->date_sampling_start_year.'-'.
                          sprintf('%02d', $record->date_sampling_start_month).'-'.
                          sprintf('%02d', $record->date_sampling_start_day) : 'N/A',
                        $record->exposure_time_days ?? 'N/A',
                        $record->concentration_value ?? 'N/A',
                        $record->unit ?? 'N/A',
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

            Log::info("Passive export complete: {$totalExported} records exported in {$processingTime} seconds. File size: {$formattedFileSize}");

            // Redirect directly to download since processing is complete
            return redirect()->route('passive.csv.download', ['filename' => $filename]);

        } catch (\Exception $e) {
            Log::error('Passive export failed: '.$e->getMessage());

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
        $directory = 'exports/passive';
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
