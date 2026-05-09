<?php

namespace App\Livewire\Susdat;

use App\Models\Susdat\Substance;
use Livewire\Component;

class SubstanceSearch extends Component
{
    public $search = '';

    public $searchType = 'name';

    public function render()
    {
        $results = [];
        $resultsAvailable = false;
        // Detect "starts with" mode: user appends % to the search term
        $startsWithMode = str_ends_with($this->search, '%');
        $searchTerm = $startsWithMode ? rtrim($this->search, '%') : $this->search;

        if (strlen($searchTerm) > 2) {

            $results = Substance::orderBy('id', 'asc');
            if ($this->searchType == 'cas_number') {
                $pattern = $startsWithMode ? $searchTerm.'%' : '%'.$searchTerm.'%';
                $results = $results->where('cas_number', 'ilike', $pattern);
            } elseif ($this->searchType == 'name') {
                $pattern = $startsWithMode ? $searchTerm.'%' : '%'.$searchTerm.'%';
                $results = $results->where('name', 'ilike', $pattern);
            } elseif ($this->searchType == 'stdinchikey') {
                $results = $results->where('stdinchikey', 'ilike', $searchTerm);
            } elseif ($this->searchType == 'code') {
                // Strip "NS" prefix if present and search by code
                $searchCode = $searchTerm;
                if (strtoupper(substr($searchCode, 0, 2)) === 'NS') {
                    $searchCode = substr($searchCode, 2);
                }
                $pattern = $startsWithMode ? $searchCode.'%' : '%'.$searchCode.'%';
                $results = $results->where('code', 'ilike', $pattern);
            } else {
                $results = $results->where('id', '<=', 50);
            }

            $results = $results->limit(50)->get();
            $resultsAvailable = true;
        }

        return view('livewire.susdat.substance-search', [
            'results' => $results,
            'resultsAvailable' => $resultsAvailable,
            'searchType' => $this->searchType,
        ]);
    }
}
