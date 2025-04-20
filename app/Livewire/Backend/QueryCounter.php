<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\Backend\QueryLog;
use Illuminate\Support\Facades\DB;

class QueryCounter extends Component
{

    public $queryId; // The ID of the query to execute
    public $countResult; // The result of the COUNT operation
    public $isLoaded = false; // Flag to indicate if the query has been executed
    public $count_again = true; // Flag to indicate if the query has been executed
    public $sqlQuery;
    public $resultsCount;
    public $loadingMessage = null;

    public function mount($queryId, $resultsCount, $count_again)
    {
        $this->queryId = $queryId;
        $this->count_again = $count_again;
        $this->resultsCount = $resultsCount;
    }

    public function render()
    {
        return view('livewire.backend.query-counter');
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
}
