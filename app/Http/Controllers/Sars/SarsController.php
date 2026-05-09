<?php

namespace App\Http\Controllers\Sars;

use App\Http\Controllers\Controller;
use App\Models\Backend\ExportDownload;
use App\Models\Backend\QueryLog;
use App\Models\DatabaseEntity;
use App\Models\Sars\SarsMain;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SarsController extends Controller
{
    //
    public function filter(Request $request)
    {
        // extract distinct values of the country column from SarsMain model
        $countryList = SarsMain::distinct()
            ->orderBy('name_of_country')
            ->get(['name_of_country'])
            ->pluck('name_of_country')
            ->mapWithKeys(function ($item) {
                return [$item => $item];
            });
        $matrixList = SarsMain::distinct()
            ->orderBy('sample_matrix')
            ->get(['sample_matrix'])
            ->pluck('sample_matrix')
            ->mapWithKeys(function ($item) {
                return [$item => $item];
            });

        $laboratoryList = SarsMain::distinct()
            ->orderBy('data_provider')
            ->get(['data_provider'])
            ->pluck('data_provider')
            ->mapWithKeys(function ($item) {
                return [$item => $item];
            });

        return view('sars.filter', [
            'request' => $request,
            'countryList' => $countryList,
            'matrixList' => $matrixList,
            'laboratoryList' => $laboratoryList,
        ]);
    }

    public function search(Request $request)
    {

        if (is_array($request->input('countrySearch'))) {
            $countrySearch = $request->input('countrySearch');
        } else {
            $countrySearch = json_decode($request->input('countrySearch'));
        }

        if (is_array($request->input('matrixSearch'))) {
            $matrixSearch = $request->input('matrixSearch');
        } else {
            $matrixSearch = json_decode($request->input('matrixSearch'));
        }

        $sarsObjects = SarsMain::query();

        $searchParameters = [];
        if (! empty($countrySearch)) {
            $sarsObjects = $sarsObjects->whereIn('name_of_country', $countrySearch);
            $searchParameters['countrySearch'] = $countrySearch;
        }

        if (! empty($matrixSearch)) {
            $sarsObjects = $sarsObjects->whereIn('sample_matrix', $matrixSearch);
            $searchParameters['matrixSearch'] = $matrixSearch;
        }

        $database_key = 'sars';
        $sarsObjectsCount = DatabaseEntity::where('code', $database_key)->first()->number_of_records;

        $main_request = [
            'countrySearch' => $countrySearch,
            'matrixSearch' => $matrixSearch,
            'displayOption' => $request->input('displayOption'),
            'year_from' => $request->input('year_from'),
            'year_to' => $request->input('year_to'),
        ];

        if (! $request->has('page')) {
            $now = now();
            $bindings = $sarsObjects->getBindings();
            $sql = vsprintf(str_replace('?', "'%s'", $sarsObjects->toSql()), $bindings);
            // try to find same SQL query in the QueryLog table with same total_count based on the query_hash
            $actual_count = QueryLog::where('query_hash', hash('sha256', $sql))->where('total_count', $sarsObjectsCount)->value('actual_count');

            try {
                QueryLog::insert([
                    'content' => json_encode(['request' => $main_request, 'bindings' => $bindings]),
                    'query' => $sql,
                    'user_id' => Auth::check() ? Auth::id() : null,
                    'total_count' => $sarsObjectsCount,
                    'actual_count' => is_null($actual_count) ? null : $actual_count,
                    'database_key' => $database_key,
                    'query_hash' => hash('sha256', $sql),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } catch (\Exception $e) {
                // dd($e, hash('sha256', $sql));
                session()->flash('error', 'An error occurred while processing your request.');
            }
        }

        if ($request->displayOption == 1) {
            // use simple pagination
            $sarsObjects = $sarsObjects->orderBy('id', 'asc')
                ->simplePaginate(200)
                ->withQueryString();
        } else {
            // use cursor pagination
            $sarsObjects = $sarsObjects->orderBy('id', 'asc')
                ->paginate(200)
                ->withQueryString();
        }

        return view('sars.index', [
            'sarsObjects' => $sarsObjects,
            'sarsObjectsCount' => $sarsObjectsCount,
            'query_log_id' => QueryLog::orderBy('id', 'desc')->first()->id,
            'request' => $request,
            'searchParameters' => $searchParameters,
        ], $main_request);
    }

    /**
     * Start direct CSV download for SARS data (no queue needed due to small dataset)
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
            $filename = 'sars_export_uid_'.Auth::id().'_'.now()->format('YmdHis').'.csv';

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
                'database_key' => 'sars',
                'status' => 'processing',
                'started_at' => Carbon::now(),
            ]);

            // Associate with the query log
            $exportDownload->queryLogs()->attach($query_log_id);

            // Process the export directly (no queue needed for small dataset)
            $startTime = microtime(true);
            $directory = 'exports/sars';

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
                'Sampling Date',
                'Gene 1',
                'Gene 2',
                'Ct Value',
                'Station Name',
                'Population Served',
                'People Positive',
                'Country',
                'Sample Matrix',
                'Data Provider',
                'Sample From Year',
                'Sample From Month',
                'Sample From Day',
                'Export Date',
            ];
            fputcsv($handle, $headers);

            // Build the query from the query log
            $baseQuery = SarsMain::query();
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
                $baseQuery->whereIn('name_of_country', $countrySearch);
            }

            if (! empty($matrixSearch)) {
                $baseQuery->whereIn('sample_matrix', $matrixSearch);
            }

            // Process records in chunks to manage memory
            $totalExported = 0;
            $exportDate = Carbon::now()->format('Y-m-d H:i:s');

            $baseQuery->chunk(500, function ($records) use ($handle, $exportDate, &$totalExported) {
                foreach ($records as $record) {
                    $row = [
                        $record->id,
                        $record->sample_from_year.'-'.$record->sample_from_month.'-'.$record->sample_from_day,
                        $record->gene1,
                        $record->gene2,
                        $record->ct,
                        $record->station_name,
                        $record->population_served,
                        $record->people_positive,
                        $record->name_of_country,
                        $record->sample_matrix,
                        $record->data_provider,
                        $record->sample_from_year,
                        $record->sample_from_month,
                        $record->sample_from_day,
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

            Log::info("SARS export complete: {$totalExported} records exported in {$processingTime} seconds. File size: {$formattedFileSize}");

            // Redirect directly to download since processing is complete
            return redirect()->route('sars.csv.download', ['filename' => $filename]);

        } catch (\Exception $e) {
            Log::error('SARS export failed: '.$e->getMessage());

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
        $directory = 'exports/sars';
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
     * Display the specified resource.
     */
    public function show($id)
    {
        $sars = SarsMain::findOrFail($id);

        return view('sars.show', [
            'sars' => $sars,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        if (! auth()->check() ||
            ! (auth()->user()->hasRole('super_admin') ||
              auth()->user()->hasRole('admin') ||
              auth()->user()->hasRole('sars'))) {
            session()->flash('error', 'You do not have permission to edit SARS records.');

            return redirect()->route('sars.search.search');
        }

        $sars = SarsMain::findOrFail($id);

        return view('sars.edit', [
            'sars' => $sars,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (! auth()->check() ||
            ! (auth()->user()->hasRole('super_admin') ||
              auth()->user()->hasRole('admin') ||
              auth()->user()->hasRole('sars'))) {
            session()->flash('error', 'You do not have permission to update SARS records.');

            return redirect()->route('sars.search.search');
        }

        $sars = SarsMain::findOrFail($id);

        // Update the record with the request data
        $sars->update($request->all());

        session()->flash('success', 'SARS record updated successfully.');

        return redirect()->route('sars.search.show', $sars->id);
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
