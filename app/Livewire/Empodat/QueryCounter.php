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
    public $sqlQuery;

    public function mount($queryId)
    {
        $this->queryId = $queryId;
    }

    public function init()
    {
        
            // Retrieve the SQL query from the QueryLog table
            $this->sqlQuery = QueryLog::where('id', $this->queryId)->value('query');

            if ($this->sqlQuery) {
                // Modify the query to perform COUNT operation
                $countQuery = "SELECT COUNT(*) as count FROM ({$this->sqlQuery}) as subquery";

                // Execute the COUNT query and fetch the result
                $this->countResult = DB::select($countQuery)[0]->count;
            } else {
                $this->countResult = 'Query not found.';
            }

            $this->isLoaded = true;

    }

    public function render()
    {
        return view('livewire.empodat.query-counter', [
            'countResult' => $this->countResult,
            'isLoaded' => $this->isLoaded,
            'queryId' => $this->queryId,
            'sqlQuery' => $this->sqlQuery,
        ]);
    }
}
