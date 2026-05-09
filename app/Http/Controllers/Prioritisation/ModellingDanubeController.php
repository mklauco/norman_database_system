<?php

namespace App\Http\Controllers\Prioritisation;

use App\Http\Controllers\Controller;
use App\Models\Backend\ExportDownload;
use App\Models\Prioritisation\ModellingDanube;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ModellingDanubeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $resultsObjects = ModellingDanube::orderby('id', 'asc')->paginate(50);

        return view('prioritisation.modelling-danube.index', compact(
            'resultsObjects'
        ));
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
        $record = ModellingDanube::with([
            'substance',
        ])->findOrFail($id);

        return view('prioritisation.modelling-danube.show', [
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

    /**
     * Download entire dataset as CSV
     */
    public function downloadCsv()
    {
        if (! Auth::check()) {
            session()->flash('error', 'You must be logged in to download the CSV file.');

            return back();
        }

        try {
            // Generate filename
            $filename = 'prioritisation_modelling_danube_uid_'.Auth::id().'_'.now()->format('YmdHis').'.csv';
            $directory = 'exports/prioritisation';

            // Create an export download record for tracking
            $exportDownload = ExportDownload::create([
                'user_id' => Auth::id(),
                'filename' => $filename,
                'format' => 'csv',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'database_key' => 'prioritisation_modelling_danube',
                'status' => 'processing',
                'started_at' => Carbon::now(),
            ]);

            $startTime = microtime(true);

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
                'Original ID',
                'Substance ID',
                'CAS',
                'Name',
                'Emissions',
                'Correct',
                'Score 1',
                'Score 2',
                'Score 3',
                'Score 4',
                'Score 5',
                'Created At',
                'Updated At',
                'Export Date',
            ];
            fputcsv($handle, $headers);

            // Process records in chunks
            $totalExported = 0;
            $exportDate = Carbon::now()->format('Y-m-d H:i:s');

            ModellingDanube::orderBy('id', 'asc')->chunk(500, function ($records) use ($handle, $exportDate, &$totalExported) {
                foreach ($records as $record) {
                    $row = [
                        $record->id,
                        $record->pri_id,
                        $record->substance_id,
                        $record->pri_cas,
                        $record->pri_name,
                        $record->pri_emissions,
                        $record->pri_correct,
                        $record->pri_score1,
                        $record->pri_score2,
                        $record->pri_score3,
                        $record->pri_score4,
                        $record->pri_score5,
                        $record->created_at,
                        $record->updated_at,
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

            Log::info("Prioritisation Modelling Danube export complete: {$totalExported} records in {$processingTime}s. Size: {$formattedFileSize}");

            // Return the file for download
            return response()->download($path, $filename, [
                'Content-Type' => 'text/csv',
            ]);

        } catch (\Exception $e) {
            Log::error('Prioritisation Modelling Danube export failed: '.$e->getMessage());

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
