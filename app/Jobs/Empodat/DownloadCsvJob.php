<?php

namespace App\Jobs\Empodat;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Models\Backend\QueryLog;
use Illuminate\Support\Facades\DB;
use App\Mail\Empodat\CsvExportReady;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DownloadCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $qeuryLogId;
    protected $user;
    /**
    * Create a new job instance.
    */
    public function __construct($qeuryLogId, $user)
    {
        $this->qeuryLogId = $qeuryLogId;
        $this->user = $user;
    }
    
    /**
    * Execute the job.
    */
    public function handle(): void
    {
        //
        
        // Execute the original SQL query to retrieve the list of IDs
        try {         
            $filename = 'export_uid_'.$this->user->id.'_'.Carbon::now()->format('YmdTGis').'.csv';
            $directory = 'exports/empodat';
            
            // Make sure the directory exists
            Storage::makeDirectory($directory);
            
            $path = Storage::path("{$directory}/{$filename}");
            $handle = fopen($path, 'w');
            
            fputcsv($handle, ['id', 'dct_analysis_id']);
            
            // 1. Get raw array of rows
            $q = QueryLog::where('id', $this->qeuryLogId)->first();
            
            $rows = DB::select($q->query);
            // 2. Break into chunks (of 1,000 here)
            $chunks = array_chunk($rows, 5000);
            
            foreach ($chunks as $chunk) {
                foreach ($chunk as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->dct_analysis_id,
                    ]);
                }
            }
            
            fclose($handle);
            
            $messageContent = [
                'user' => $this->user->id,
                'filename' => $filename,
                'download_link' => route('csv.download', ['filename' => $filename]),
            ];
            
        } catch (Exception $e) {
            \Log::error('Failure ' . $e->getMessage(). ' ' . $e->getLine());
        }
        
        try {
            Mail::to($this->user->email)->queue(new CsvExportReady($messageContent));
        } catch (Exception $e) {
            \Log::error('Failure ' . $e->getMessage(). ' ' . $e->getLine());
        }
        
    }
}
