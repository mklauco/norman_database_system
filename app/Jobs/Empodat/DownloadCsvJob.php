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
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DownloadCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $queryLogId;
    protected $user;
    protected $maxExecutionTime = 1800; // 30 minutes
    protected $idChunkSize = 500; // Process smaller chunks of IDs
    
    /**
    * Create a new job instance.
    */
    public function __construct($queryLogId, $user)
    {
        $this->queryLogId = $queryLogId;
        $this->user = $user;
    }
    
    /**
    * Execute the job.
    */
    public function handle(): void
    {
        // Increase memory limit if possible
        ini_set('memory_limit', '2G');
        
        // Set maximum execution time to avoid timeout
        set_time_limit($this->maxExecutionTime);
        
        // Default message content
        $filename = 'empodat_export_uid_'.$this->user->id.'_'.Carbon::now()->format('YmdHis').'.csv';
        $messageContent = [
            'user' => $this->user->name ?? $this->user->email,
            'filename' => $filename,
            'download_link' => route('csv.download', ['filename' => $filename]),
            'total_records' => 0,
            'processing_time' => 0,
            'export_failed' => false
        ];
        
        try {
            $startTime = microtime(true);
            $directory = 'exports/empodat';
            
            // Make sure the directory exists
            Storage::makeDirectory($directory);
            
            $path = Storage::path("{$directory}/{$filename}");
            $handle = fopen($path, 'w');
            
            if (!$handle) {
                throw new Exception("Unable to open file for writing: {$path}");
            }
            
            // Define CSV header columns
            $headers = [
                'ID', 
                'DCT Analysis ID', 
                'Station Name',
                'Country',
                'Country Code',
                'Matrix',
                'Concentration Unit',
                'Substance',
                'CAS Number',
                'Sampling Year',
                'Concentration Value',
                'Latitude',
                'Longitude',
                'Export Date'
            ];
            
            fputcsv($handle, $headers);
            
            // Get the query log record
            $queryLog = QueryLog::findOrFail($this->queryLogId);
            
            // Extract just the IDs using a cursor for minimal memory usage
            \Log::info("Starting ID extraction from query");
            
            // Get all IDs from raw query - memory efficient approach
            $rawQuery = preg_replace('/SELECT\s+.*?\s+FROM/is', 'SELECT empodat_main.id FROM', $queryLog->query);
            $idQuery = preg_replace('/ORDER\s+BY.*$/is', '', $rawQuery);
            
            // Extract IDs using a generator to minimize memory usage
            $idGenerator = function() use ($idQuery) {
                $cursor = DB::cursor($idQuery);
                foreach ($cursor as $record) {
                    if (isset($record->id)) {
                        yield $record->id;
                    }
                }
            };
            
            $exportDate = Carbon::now()->format('Y-m-d H:i:s');
            $totalExported = 0;
            $idBatch = [];
            
            // Process IDs in small batches using the generator
            foreach ($idGenerator() as $id) {
                $idBatch[] = $id;
                
                // When we have enough IDs for a batch, process them
                if (count($idBatch) >= $this->idChunkSize) {
                    $this->processIdBatch($idBatch, $handle, $exportDate, $totalExported);
                    $idBatch = []; // Clear the batch
                    
                    // Free up memory
                    gc_collect_cycles();
                }
            }
            
            // Process any remaining IDs
            if (!empty($idBatch)) {
                $this->processIdBatch($idBatch, $handle, $exportDate, $totalExported);
            }
            
            fclose($handle);
            
            $processingTime = round(microtime(true) - $startTime, 2);
            
            // Update message content
            $messageContent['total_records'] = $totalExported;
            $messageContent['processing_time'] = $processingTime;
            
            \Log::info("EmpodatMain export complete: {$totalExported} records exported in {$processingTime} seconds");
            
        } catch (Exception $e) {
            \Log::error('EmpodatMain export failed: ' . $e->getMessage() . ' at line ' . $e->getLine() . ' in ' . $e->getFile());
            
            // Close file handle if it's open
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            
            // Update message content with error information
            $messageContent['export_failed'] = true;
            $messageContent['error'] = $e->getMessage();
        }
        
        try {
            Mail::to($this->user->email)->queue(new CsvExportReady($messageContent));
        } catch (Exception $e) {
            \Log::error('Failed to send email: ' . $e->getMessage() . ' at line ' . $e->getLine());
        }
    }
    
    /**
     * Process a batch of IDs and write records to CSV
     * 
     * @param array $idBatch Array of IDs to process
     * @param resource $handle File handle for writing
     * @param string $exportDate Formatted export date
     * @param int &$totalExported Counter for exported records (passed by reference)
     */
    protected function processIdBatch(array $idBatch, $handle, string $exportDate, &$totalExported): void
    {
        try {
            // Log the batch processing
            \Log::info("Processing batch of " . count($idBatch) . " records");
            
            // Use a generator to process records one at a time without loading all into memory
            $recordGenerator = DB::table('empodat_main')
                ->select(
                    'empodat_main.id',
                    'empodat_main.dct_analysis_id',
                    'empodat_main.sampling_date_year',
                    'empodat_main.concentration_value',
                    'empodat_stations.name as station_name',
                    'list_countries.name as country_name',
                    'list_countries.code as country_code',
                    'list_matrices.name as matrix_name',
                    'list_matrices.unit as concentration_unit',
                    'susdat_substances.name as substance_name',
                    'susdat_substances.cas_number',
                    'empodat_stations.latitude',
                    'empodat_stations.longitude'
                )
                ->leftJoin('susdat_substances', 'empodat_main.substance_id', '=', 'susdat_substances.id')
                ->leftJoin('list_matrices', 'empodat_main.matrix_id', '=', 'list_matrices.id')
                ->leftJoin('empodat_stations', 'empodat_main.station_id', '=', 'empodat_stations.id')
                ->leftJoin('list_countries', 'empodat_stations.country_id', '=', 'list_countries.id')
                ->whereIn('empodat_main.id', $idBatch)
                ->cursor();
                
            foreach ($recordGenerator as $record) {
                // Write to CSV - only do field access right when needed
                fputcsv($handle, [
                    $record->id ?? 'N/A',
                    $record->dct_analysis_id ?? 'N/A',
                    $record->station_name ?? 'N/A',
                    $record->country_name ?? 'N/A',
                    $record->country_code ?? 'N/A',
                    $record->matrix_name ?? 'N/A',
                    $record->concentration_unit ?? 'N/A',
                    $record->substance_name ?? 'N/A',
                    $record->cas_number ?? 'N/A',
                    $record->sampling_date_year ?? 'N/A',
                    $record->concentration_value ?? 'N/A',
                    $record->latitude ?? 'N/A',
                    $record->longitude ?? 'N/A',
                    $exportDate
                ]);
                $totalExported++;
            }
            
            // Force database cursor to be released
            unset($recordGenerator);
            
        } catch (Exception $e) {
            \Log::error("Error processing batch: " . $e->getMessage());
            throw $e; // Re-throw to be caught by main try-catch
        }
    }
}