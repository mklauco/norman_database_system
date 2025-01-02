<?php

namespace App\Livewire\Empodat;

use Livewire\Component;
use App\Models\Susdat\Substance;

class SubstanceSearch extends Component
{
    public $search = '';
    public $searchType = 'name';
    public $selectedSubstanceIds = []; // Track selected substance IDs
    public $selectedSubstances = []; // Store selected substances
    
    public function render()
    {
        $results = [];
        $resultsAvailable = false;
        if(strlen($this->search) > 2) {
            $results = Substance::orderBy('id', 'asc');
            if($this->searchType == 'cas_number') {
                $results = $results->where('cas_number', 'like', '%' . $this->search . '%');
            } elseif($this->searchType == 'name') {
                $results = $results->where('name', 'like', '%' . $this->search . '%');
            } elseif($this->searchType == 'stdinchikey') {
                $results = $results->where('stdinchikey', 'like', $this->search);
            } else{
                $results = $results->where('id', '<=', 50);
            }
            
            $results = $results->limit(50)->get();
            $resultsAvailable = true;
        }
        return view('livewire.empodat.substance-search', [
            'results' => $results,
            'resultsAvailable' => $resultsAvailable,
            'searchType' => $this->searchType,
            'selectedSubstances' => $this->selectedSubstances, // Pass selected substances to the view
        ]);
        
        
    }
    
    public function applySubstanceFilter()
    {
        // Fetch the selected substances based on their IDs
        $this->selectedSubstances = Substance::whereIn('id', $this->selectedSubstanceIds)
        ->get()
        ->map(function ($substance) {
            return [
                'id' => $substance->id,
                'name' => $substance->name,
                'cas_number' => $substance->cas_number,
                'stdinchikey' => $substance->stdinchikey,
            ];
        })
        ->toArray();
    }
}
