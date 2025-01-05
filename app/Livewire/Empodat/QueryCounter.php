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
    public $empodatsCount;
    

    public function mount($queryId, $empodatsCount)
    {
        $this->queryId = $queryId;
        $this->empodatsCount = $empodatsCount;
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

    public function downloadCsv()
    {
        if (!$this->sqlQuery) {
            session()->flash('error', 'No query available to execute.');
            return;
        }

        // Execute the original SQL query to retrieve the list of IDs
        $query = "SELECT dct_analysis_id FROM ({$this->sqlQuery}) as subquery";
        $ids = DB::select($query);
        

        // Create a StreamedResponse for the CSV download
        return response()->streamDownload(function () use ($ids) {
            $output = fopen('php://output', 'w');



            // Add headers
            fputcsv($output, ['dct_analysis_id']);

            // Add rows
            foreach ($ids as $row) {
                fputcsv($output, [$row->dct_analysis_id]);
            }

            fclose($output);
        }, 'ids.csv');
    }

    public function render()
    {
        return view('livewire.empodat.query-counter', [
            'countResult' => $this->countResult,
            'isLoaded' => $this->isLoaded,
            'queryId' => $this->queryId,
            'empodatsCount' => $this->empodatsCount,
        ]);
    }
}
