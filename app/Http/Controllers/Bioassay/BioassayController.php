<?php

namespace App\Http\Controllers\Bioassay;

use App\Http\Controllers\Controller;
use App\Models\Backend\ExportDownload;
use App\Models\Backend\QueryLog;
use App\Models\Bioassay\FieldStudy;
use App\Models\Bioassay\MonitorXBioassayName;
use App\Models\Bioassay\MonitorXCountry;
use App\Models\Bioassay\MonitorXEndpoint;
use App\Models\Bioassay\MonitorXMainDeterminand;
use App\Models\DatabaseEntity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BioassayController extends Controller
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
        $record = FieldStudy::with([
            'sampleData.country',
            'sampleData.dataSource',
            'bioassayName',
            'endpoint',
            'mainDeterminand',
        ])->findOrFail($id);

        return view('bioassay.show', [
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
        $countryList = FieldStudy::join('bioassay_monitor_sample_data', 'bioassay_field_studies.m_sd_id', '=', 'bioassay_monitor_sample_data.id')
            ->join('monitor_x_country', 'bioassay_monitor_sample_data.m_country_id', '=', 'monitor_x_country.id')
            ->select('monitor_x_country.id', 'monitor_x_country.name')
            ->distinct()
            ->orderBy('monitor_x_country.name')
            ->pluck('monitor_x_country.name', 'monitor_x_country.id')
            ->toArray();

        $bioassayNameList = MonitorXBioassayName::orderBy('name')->pluck('name', 'id')->toArray();
        $endpointList = MonitorXEndpoint::orderBy('name')->pluck('name', 'id')->toArray();
        $determinandList = MonitorXMainDeterminand::orderBy('name')->pluck('name', 'id')->toArray();

        return view('bioassay.filter', [
            'request' => $request,
            'countryList' => $countryList,
            'bioassayNameList' => $bioassayNameList,
            'endpointList' => $endpointList,
            'determinandList' => $determinandList,
        ]);
    }

    public function search(Request $request)
    {

        // Define the input fields to process
        $searchFields = ['countrySearch', 'bioassayNameSearch', 'endpointSearch', 'determinandSearch'];

        // Process each field with the same logic
        /*
        ORIGINAL CODE
        if(is_array($request->input('countrySearch'))){
        $countrySearch = $request->input('countrySearch');
        } else{
        $countrySearch = json_decode($request->input('countrySearch'));
        }
        END ORIGINAL CODE
        */
        foreach ($searchFields as $field) {
            $value = is_array($request->input($field))
            ? $request->input($field)
            : json_decode((string) $request->input($field), true);

            ${$field} = is_array($value) ? array_values(array_filter($value, 'is_scalar')) : [];
        }

        // dd($request->all(), ${$field});
        $resultsObjects = FieldStudy::with(['sampleData.country', 'sampleData.dataSource', 'bioassayName', 'endpoint', 'mainDeterminand']);

        $searchParameters = [];
        if (! empty($countrySearch)) {
            $resultsObjects = $resultsObjects->whereHas('sampleData.country', function ($query) use ($countrySearch) {
                $query->whereIn('id', $countrySearch);
            });
            $searchParameters['countrySearch'] = MonitorXCountry::whereIn('id', $countrySearch)->pluck('name');
        }

        if (! empty($bioassayNameSearch)) {
            $resultsObjects = $resultsObjects->whereHas('bioassayName', function ($query) use ($bioassayNameSearch) {
                $query->whereIn('id', $bioassayNameSearch);
            });
            $searchParameters['bioassayNameSearch'] = MonitorXBioassayName::whereIn('id', $bioassayNameSearch)->pluck('name');
        }
        if (! empty($endpointSearch)) {
            $resultsObjects = $resultsObjects->whereHas('endpoint', function ($query) use ($endpointSearch) {
                $query->whereIn('id', $endpointSearch);
            });
            $searchParameters['endpointSearch'] = MonitorXEndpoint::whereIn('id', $endpointSearch)->pluck('name');
        }
        if (! empty($determinandSearch)) {
            $resultsObjects = $resultsObjects->whereHas('mainDeterminand', function ($query) use ($determinandSearch) {
                $query->whereIn('id', $determinandSearch);
            });
            $searchParameters['determinandSearch'] = MonitorXMainDeterminand::whereIn('id', $determinandSearch)->pluck('name');
        }

        if (! is_null($request->input('year_from'))) {
            $resultsObjects = $resultsObjects->where('date_performed_year', '>=', $request->input('year_from'));
            $searchParameters['year_from'] = $request->input('year_from');
        }
        if (! is_null($request->input('year_to'))) {
            $resultsObjects = $resultsObjects->where('date_performed_year', '<=', $request->input('year_to'));
            $searchParameters['year_to'] = $request->input('year_to');
        }

        $main_request = [
            'countrySearch' => $countrySearch,
            'bioassayNameSearch' => $bioassayNameSearch,
            'endpointSearch' => $endpointSearch,
            'determinandSearch' => $determinandSearch,
            'displayOption' => $request->input('displayOption'),
            'year_from' => $request->input('year_from'),
            'year_to' => $request->input('year_to'),
        ];

        $database_key = 'bioassay';
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
            $resultsObjects = $resultsObjects->orderBy('bioassay_field_studies.id', 'asc')
                ->simplePaginate(200)
                ->withQueryString();
        } else {
            // use cursor pagination
            $resultsObjects = $resultsObjects->orderBy('bioassay_field_studies.id', 'asc')
                ->paginate(200)
                ->withQueryString();
        }

        // dd($resultsObjects[0], $countrySearch);
        // dd($searchParameters);
        return view('bioassay.index', [
            'resultsObjects' => $resultsObjects,
            'resultsObjectsCount' => $resultsObjectsCount,
            'query_log_id' => QueryLog::orderBy('id', 'desc')->first()->id,
            'request' => $request,
            'searchParameters' => $searchParameters,
        ], $main_request);
    }

    /**
     * Start direct CSV download for Bioassay data
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
            $filename = 'bioassay_export_uid_'.Auth::id().'_'.now()->format('YmdHis').'.csv';

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
                'database_key' => 'bioassay',
                'status' => 'processing',
                'started_at' => Carbon::now(),
            ]);

            // Associate with the query log
            $exportDownload->queryLogs()->attach($query_log_id);

            // Process the export directly
            $startTime = microtime(true);
            $directory = 'exports/bioassay';

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
                'Bioassay Name',
                'Endpoint',
                'Main Determinand',
                'Value Determinand',
                'Date Performed (Year)',
                'Date Performed (Month)',
                'Export Date',
            ];
            fputcsv($handle, $headers);

            // Build the query from the query log
            $baseQuery = FieldStudy::with(['sampleData.country', 'bioassayName', 'endpoint', 'mainDeterminand']);
            $content = json_decode($queryLog->content, true);
            $requestData = $content['request'] ?? [];

            // Process search fields
            $countrySearch = is_array($requestData['countrySearch'] ?? null)
                ? $requestData['countrySearch']
                : json_decode($requestData['countrySearch'] ?? '[]', true);

            $bioassayNameSearch = is_array($requestData['bioassayNameSearch'] ?? null)
                ? $requestData['bioassayNameSearch']
                : json_decode($requestData['bioassayNameSearch'] ?? '[]', true);

            $endpointSearch = is_array($requestData['endpointSearch'] ?? null)
                ? $requestData['endpointSearch']
                : json_decode($requestData['endpointSearch'] ?? '[]', true);

            $determinandSearch = is_array($requestData['determinandSearch'] ?? null)
                ? $requestData['determinandSearch']
                : json_decode($requestData['determinandSearch'] ?? '[]', true);

            // Apply filters
            if (! empty($countrySearch)) {
                $baseQuery->whereHas('sampleData.country', function ($query) use ($countrySearch) {
                    $query->whereIn('id', $countrySearch);
                });
            }

            if (! empty($bioassayNameSearch)) {
                $baseQuery->whereHas('bioassayName', function ($query) use ($bioassayNameSearch) {
                    $query->whereIn('id', $bioassayNameSearch);
                });
            }

            if (! empty($endpointSearch)) {
                $baseQuery->whereHas('endpoint', function ($query) use ($endpointSearch) {
                    $query->whereIn('id', $endpointSearch);
                });
            }

            if (! empty($determinandSearch)) {
                $baseQuery->whereHas('mainDeterminand', function ($query) use ($determinandSearch) {
                    $query->whereIn('id', $determinandSearch);
                });
            }

            if (! empty($requestData['year_from'])) {
                $baseQuery->where('date_performed_year', '>=', $requestData['year_from']);
            }

            if (! empty($requestData['year_to'])) {
                $baseQuery->where('date_performed_year', '<=', $requestData['year_to']);
            }

            // Process records in chunks
            $totalExported = 0;
            $exportDate = Carbon::now()->format('Y-m-d H:i:s');

            $baseQuery->chunk(500, function ($records) use ($handle, $exportDate, &$totalExported) {
                foreach ($records as $record) {
                    $row = [
                        $record->id,
                        $record->sampleData?->country?->name ?? 'N/A',
                        $record->bioassayName?->name ?? ($record->bioassay_name_other ?? 'N/A'),
                        $record->endpoint?->name ?? ($record->endpoint_other ?? 'N/A'),
                        $record->mainDeterminand?->name ?? ($record->main_determinand_other ?? 'N/A'),
                        $record->value_determinand ?? 'N/A',
                        $record->date_performed_year ?? 'N/A',
                        $record->date_performed_month ?? 'N/A',
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

            // Update the export download record
            $exportDownload->update([
                'status' => 'completed',
                'record_count' => $totalExported,
                'file_size_bytes' => $fileSize,
                'file_size_formatted' => $formattedFileSize,
                'processing_time_seconds' => $processingTime,
                'completed_at' => Carbon::now(),
            ]);

            Log::info("Bioassay export complete: {$totalExported} records exported in {$processingTime} seconds. File size: {$formattedFileSize}");

            // Redirect to download
            return redirect()->route('bioassay.csv.download', ['filename' => $filename]);

        } catch (\Exception $e) {
            Log::error('Bioassay export failed: '.$e->getMessage());

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
        $directory = 'exports/bioassay';
        $path = Storage::path("{$directory}/{$filename}");

        if (! file_exists($path)) {
            Log::warning("Bioassay CSV file not found: {$filename}");
            session()->flash('error', 'The requested CSV file does not exist. It may have expired or failed to generate.');

            return back();
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(false);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
