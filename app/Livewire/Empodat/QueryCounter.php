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
    public $loadingMessage = null;
    
    
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
            
            $q = QueryLog::find($this->queryId);
            $content = json_decode($q->content, true);
            // put the new key value pair in the content array
            $content['count'] = $this->countResult;
            // update the content with the new key value pair
            $q->content = json_encode($content);
            // save the updated content
            $q->save();              
        } else {
            $this->countResult = 'Query not found.';
        }
        
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
