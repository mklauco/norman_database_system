<?php

namespace App\Livewire\Empodat;

use Livewire\Component;
use App\Models\Backend\QueryLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryCounter extends Component
{
    public $queryId; // The ID of the query to execute
    public $countResult; // The result of the COUNT operation
    public $isLoaded = false; // Flag to indicate if the query has been executed
    public $count_again = true; // Flag to indicate if the query has been executed
    public $sqlQuery;
    public $empodatsCount;
    public $loadingMessage = null;
    
    
    public function mount($queryId, $empodatsCount, $count_again)
    {
        $this->queryId = $queryId;
        $this->count_again = $count_again;
        $this->empodatsCount = $empodatsCount;
    }
    
    public function init()
    {
        // 1. Fetch the QueryLog record
        $q = QueryLog::find($this->queryId);
        
        // If the record doesn't exist, bail out
        if (!$q) {
            $this->countResult = 'Query not found.';
            return;
        }
        
        $this->sqlQuery = $q->query;
        
        // 2. If user wants to recount
        if ($this->sqlQuery && $this->count_again) {
            // See if there's a cached 'actual_count' already stored
            $actualCount = QueryLog::where('query_hash', hash('sha256', $this->sqlQuery))
            ->where('actual_count', '>', 0)
            ->value('actual_count');
            
            // Start measuring time
            $startTime = microtime(true);
            
            // 2a. If an 'actual_count' is already known, use that
            if ($actualCount) {
                $this->countResult = $actualCount;
                $executionTimeKey  = 'loadExecutionTime';
            } 
            // 2b. Otherwise, run a fresh COUNT query
            else {
                $countQuery = "SELECT COUNT(*) as count FROM ({$this->sqlQuery}) as subquery";
                $this->countResult = DB::select($countQuery)[0]->count;
                $executionTimeKey  = 'countExecutionTime';
            }
            
            // End time measurement and store it
            $executionTime = microtime(true) - $startTime;
            
            // Update JSON content
            $content = json_decode($q->content, true);
            $content['count']               = $this->countResult;
            $content[$executionTimeKey]     = number_format($executionTime, 2, ".", "") . ' s';
            
            // Persist changes
            $q->content      = json_encode($content);
            $q->actual_count = $this->countResult;
            $q->save();
        } 
        // 3. If *not* recounting, just use the previous actual_count
        else {
            $this->countResult = $q->actual_count ?? 'Count of records for this search has failed.';
        }
        
        // 4. Indicate data has been loaded
        $this->isLoaded = true;
    }
    
    
    public function downloadCsv()
    {
        $this->loadingMessage = 'File storing...';
        
        if (!$this->sqlQuery) {
            session()->flash('error', 'No query available to execute.');
            return;
        }
        
        // Execute the original SQL query to retrieve the list of IDs
        $query = "SELECT eid, dct_analysis_id FROM (" . preg_replace('/"empodat_main"\."id"/','"empodat_main"."id" AS eid',$this->sqlQuery) . ") as subquery";
        
        // Fetch data as a cursor to handle large datasets
        $ids = DB::cursor($query);
        
        // Define the file path where the CSV will be saved
        $path = storage_path('app/public/');
        $name = 'ids_'.$this->queryId.'.csv';
        $filePath = $path.$name; // Adjust path if needed
        
        // Open file for writing
        $file = fopen($filePath, 'w');
        
        if ($file === false) {
            session()->flash('error', 'Unable to create file for download.');
            return;
        }
        
        // Add headers to the CSV file
        fputcsv($file, ['empodat_main.id', 'dct_analysis_id']);
        
        foreach ($ids as $row) {
            fputcsv($file, [(string) $row->eid, (string) $row->dct_analysis_id]);
        }
        
        // Close the file
        fclose($file);
        
        // Offer the file as a download link
        $this->loadingMessage = null;
        
        return response()->download($filePath, $name)->deleteFileAfterSend();
    }
    
    public function render()
    {
        return view('livewire.empodat.query-counter', [
            'countResult' => $this->countResult,
            'isLoaded' => $this->isLoaded,
            'queryId' => $this->queryId,
            'loadingMessage' => $this->loadingMessage,
            'empodatsCount' => $this->empodatsCount,
        ]);
    }
}
