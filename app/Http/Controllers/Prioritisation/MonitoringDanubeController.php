<?php

namespace App\Http\Controllers\Prioritisation;

use App\Http\Controllers\Controller;
use App\Models\Backend\ExportDownload;
use App\Models\Prioritisation\MonitoringDanube;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MonitoringDanubeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $resultsObjects = MonitoringDanube::orderby('id', 'asc')->get();

        return view('prioritisation.monitoring-danube.index', compact(
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
        $record = MonitoringDanube::findOrFail($id);

        return view('prioritisation.monitoring-danube.show', [
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
            $filename = 'prioritisation_monitoring_danube_uid_'.Auth::id().'_'.now()->format('YmdHis').'.csv';
            $directory = 'exports/prioritisation';

            $exportDownload = ExportDownload::create([
                'user_id' => Auth::id(),
                'filename' => $filename,
                'format' => 'csv',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'database_key' => 'prioritisation_monitoring_danube',
                'status' => 'processing',
                'started_at' => Carbon::now(),
            ]);

            $startTime = microtime(true);
            Storage::makeDirectory($directory);

            $path = Storage::path("{$directory}/{$filename}");
            $handle = fopen($path, 'w');

            if (! $handle) {
                throw new \Exception("Unable to open file for writing: {$path}");
            }

            $headers = [
                'ID',
                'Pri No',
                'Substance ID',
                'Substance',
                'CAS No',
                'Position Prioritisation 2014',
                'Category',
                'No of Sites Where MEC/Site > PNEC',
                'MEC/Site Max',
                '95th MEC/Site',
                'Lowest PNEC',
                'Reference Key Study',
                'PNEC Type',
                'Species',
                'AF',
                'Extent of Exceedance',
                'Score EOE',
                'Score FOE',
                'Final Score',
                'Created At',
                'Updated At',
                'Export Date',
            ];
            fputcsv($handle, $headers);

            $totalExported = 0;
            $exportDate = Carbon::now()->format('Y-m-d H:i:s');

            MonitoringDanube::orderBy('id', 'asc')->chunk(500, function ($records) use ($handle, $exportDate, &$totalExported) {
                foreach ($records as $record) {
                    $row = [
                        $record->id,
                        $record->pri_no,
                        $record->substance_id,
                        $record->pri_substance,
                        $record->pri_cas_no,
                        $record->pri_position_prioritisation_2014,
                        $record->pri_category,
                        $record->pri_no_of_sites_where_mecsite_pnec,
                        $record->pri_mecsite_max,
                        $record->{'pri_95th_mecsite'},
                        $record->pri_lowest_pnec,
                        $record->pri_reference_key_study,
                        $record->pri_pnec_type,
                        $record->pri_species,
                        $record->pri_af,
                        $record->pri_extent_of_exceedence,
                        $record->pri_score_eoe,
                        $record->pri_score_foe,
                        $record->pri_final_score,
                        $record->created_at,
                        $record->updated_at,
                        $exportDate,
                    ];
                    fputcsv($handle, $row);
                    $totalExported++;
                }
            });

            fclose($handle);

            $fileSize = Storage::size("{$directory}/{$filename}");
            $formattedFileSize = $this->formatBytes($fileSize);
            $processingTime = round(microtime(true) - $startTime, 2);

            $exportDownload->update([
                'status' => 'completed',
                'record_count' => $totalExported,
                'file_size_bytes' => $fileSize,
                'file_size_formatted' => $formattedFileSize,
                'processing_time_seconds' => $processingTime,
                'completed_at' => Carbon::now(),
            ]);

            Log::info("Prioritisation Monitoring Danube export complete: {$totalExported} records in {$processingTime}s. Size: {$formattedFileSize}");

            return response()->download($path, $filename, ['Content-Type' => 'text/csv']);

        } catch (\Exception $e) {
            Log::error('Prioritisation Monitoring Danube export failed: '.$e->getMessage());

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
