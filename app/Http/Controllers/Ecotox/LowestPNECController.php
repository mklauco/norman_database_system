<?php

namespace App\Http\Controllers\Ecotox;

use App\Http\Controllers\Controller;
use App\Models\Backend\ExportDownload;
use App\Models\DatabaseEntity;
use App\Models\Ecotox\LowestPNEC;
use App\Models\Ecotox\LowestPNECMain;
use App\Models\Ecotox\PNEC3;
use App\Models\Susdat\Substance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LowestPNECController extends Controller
{
    /**
     * Display a listing of the LowestPNEC resources.
     */
    public function index()
    {
        $lowestPnecs = LowestPNEC::with('substance')
            ->orderBy('id')
            ->paginate(50);

        return view('ecotox.lowestpnec.index', [
            'lowestPnecs' => $lowestPnecs,
            'displayOption' => 0,
        ]);
    }

    /**
     * Search for LowestPNEC records based on exp_pred filter.
     */
    /**
     * AJAX endpoint for LowestPNEC data with search and filtering
     */
    public function getData(Request $request)
    {
        $perPage = $request->get('per_page', 25);
        $sortColumn = $request->get('sort', 'id');
        $sortDirection = $request->get('direction', 'asc');
        $search = $request->get('search', '');
        $expPred = $request->get('exp_pred', '');

        $query = LowestPNEC::with('substance');

        // Apply substance name search (only within substances that exist in LowestPNEC table)
        if (! empty(trim($search))) {
            $query->whereHas('substance', function ($subQuery) use ($search) {
                $subQuery->where('name', 'ILIKE', '%'.trim($search).'%');
            });
        }

        // Apply exp_pred filter (experimental vs predicted)
        // Database values: 1 = Experimental, 2 = Predicted
        if (! empty($expPred)) {
            $query->where('lowest_exp_pred', (int) $expPred);
        }

        // Apply sorting
        $allowedSortColumns = ['id', 'sus_id', 'substance_id', 'lowest_exp_pred'];
        if (in_array($sortColumn, $allowedSortColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('id', 'asc');
        }

        $results = $query->paginate($perPage);

        return response()->json($results);
    }

    public function search(Request $request)
    {
        $searchParameters = [];
        $resultsObjects = LowestPNEC::with('substance');

        // Apply substance name filter
        if ($request->has('substance_name') && trim($request->substance_name) !== '') {
            $substanceName = trim($request->substance_name);
            $resultsObjects = $resultsObjects->whereHas('substance', function ($query) use ($substanceName) {
                $query->where('name', 'ILIKE', '%'.$substanceName.'%');
            });
            $searchParameters['Substance Name'] = $substanceName;
        }

        // Apply exp_pred filter (experimental vs predicted)
        // Database values: 1 = Experimental, 2 = Predicted
        if ($request->has('exp_pred') && $request->exp_pred !== '') {
            $expPredValue = (int) $request->exp_pred;
            $resultsObjects = $resultsObjects->where('lowest_exp_pred', $expPredValue);
            $searchParameters['Data Type'] = $expPredValue == 1 ? 'Experimental' : 'Predicted';
        }

        // Order and paginate results
        $resultsObjects = $resultsObjects->orderBy('id', 'asc')
            ->paginate(50)
            ->withQueryString();

        return view('ecotox.lowestpnec.index', [
            'lowestPnecs' => $resultsObjects,
            'searchParameters' => $searchParameters,
            'request' => $request,
            'displayOption' => 0,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($sus_id)
    {
        // Find the LowestPNEC record by sus_id
        $lowestPnec = LowestPNEC::where('sus_id', $sus_id)->firstOrFail();

        // Get the substance - try substance_id first, then sus_id (both correspond to substance ID)
        $substance = null;
        if ($lowestPnec->substance_id) {
            $substance = Substance::find($lowestPnec->substance_id);
        }
        if (! $substance) {
            $substance = Substance::find($sus_id);
        }

        // Find the related LowestPNECMain record if exists
        $lowestPnecMain = LowestPNECMain::with(['substance', 'editor'])
            ->where('sus_id', $sus_id)
            ->orderby('lowest_active', 'desc')
            ->first();

        // Look up PNEC3 record if we have the base_id
        $pnec3 = null;
        if ($lowestPnecMain && $lowestPnecMain->lowest_base_id) {
            $pnec3 = PNEC3::where('norman_pnec_id', $lowestPnecMain->lowest_base_id)->first();
        }

        return view('ecotox.lowestpnec.show', [
            'lowestPnec' => $lowestPnec,
            'lowestPnecMain' => $lowestPnecMain,
            'pnec3' => $pnec3,
            'substance' => $substance,
        ]);
    }

    /**
     * Start direct CSV download for LowestPNEC data (no queue needed due to manageable dataset)
     */
    public function startDownloadJob(Request $request)
    {
        if (! Auth::check()) {
            session()->flash('error', 'You must be logged in to download the CSV file.');

            return back();
        }

        try {
            // Generate filename
            $filename = 'lowestpnec_export_uid_'.Auth::id().'_'.now()->format('YmdHis').'.csv';

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
                'database_key' => 'ecotox.pnec',
                'status' => 'processing',
                'started_at' => Carbon::now(),
            ]);

            // Process the export directly (no queue needed for manageable dataset)
            $startTime = microtime(true);
            $directory = 'exports/lowestpnec';

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
                'Norman SusDat ID',
                'Substance Name',
                'Freshwater PNEC [µg/l]',
                'Marine water PNEC [µg/l]',
                'Sediments PNEC [µg/kg dw]',
                'Biota (fish) PNEC [µg/kg ww]',
                'Marine biota (fish) PNEC [µg/kg ww]',
                'Biota (mollusc) PNEC [µg/kg ww]',
                'Marine biota (mollusc) PNEC [µg/kg ww]',
                'Biota (WFD) PNEC [µg/kg ww]',
                'Data Type',
                'Export Date',
            ];
            fputcsv($handle, $headers);

            // Build the query with same filters as getData method
            $baseQuery = LowestPNEC::with('substance');

            // Apply search filters from request
            $search = $request->get('search', '');
            $expPred = $request->get('exp_pred', '');

            if (! empty(trim($search))) {
                $baseQuery->whereHas('substance', function ($subQuery) use ($search) {
                    $subQuery->where('name', 'ILIKE', '%'.trim($search).'%');
                });
            }

            if (! empty($expPred)) {
                $baseQuery->where('lowest_exp_pred', (int) $expPred);
            }

            // Process records in chunks to manage memory
            $totalExported = 0;
            $exportDate = Carbon::now()->format('Y-m-d H:i:s');

            $baseQuery->orderBy('id', 'asc')->chunk(500, function ($records) use ($handle, $exportDate, &$totalExported) {
                foreach ($records as $record) {
                    $row = [
                        $record->id,
                        $record->substance ? $record->substance->prefixed_code : 'Unknown',
                        $record->substance ? $record->substance->name : 'Unknown',
                        $record->lowest_pnec_value_1,
                        $record->lowest_pnec_value_2,
                        $record->lowest_pnec_value_3,
                        $record->lowest_pnec_value_4,
                        $record->lowest_pnec_value_5,
                        $record->lowest_pnec_value_6,
                        $record->lowest_pnec_value_7,
                        $record->lowest_pnec_value_8,
                        $record->lowest_exp_pred == 1 ? 'Experimental' : 'Predicted',
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

            Log::info("LowestPNEC export complete: {$totalExported} records exported in {$processingTime} seconds. File size: {$formattedFileSize}");

            // Redirect directly to download since processing is complete
            return redirect()->route('ecotox.lowestpnec.csv.download', ['filename' => $filename]);

        } catch (\Exception $e) {
            Log::error('LowestPNEC export failed: '.$e->getMessage());

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
        $directory = 'exports/lowestpnec';
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

    public function countAll()
    {
        DatabaseEntity::where('code', 'ecotox.pnec')->update([
            'last_update' => LowestPNEC::max('updated_at'),
            'number_of_records' => LowestPNEC::count(),
        ]);
        session()->flash('success', 'Database counts updated successfully');

        return redirect()->back();
    }
}
