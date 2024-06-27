<?php

namespace App\Livewire\Susdat;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class DuplicateLoadComptox extends Component
{
    
    public $response = '';
    public $dtxsid;

    public function mount($dtxsid)
    {
        return $this->dtxsid = $dtxsid;
    }

    public function init(){
        foreach($this->dtxsid as $dtx){
            $h = Http::withHeaders([
                'accept' => 'application/json',
                'x-api-key' => '30348b9a-9119-418e-85eb-7f7bbf4606c8'])
                ->get('https://api-ccte.epa.gov/chemical/detail/search/by-dtxsid/'.$dtx);
            $response[$dtx] = collect(json_decode($h->getBody(), true))->mapWithKeys(function($value, $key){
                return [$this->remapComptoxToNorman()[$key] ?? null => $value];
            });
        }
        
        $this->response = $response;
    }
    
    public function render()
    {
        return view('livewire.susdat.duplicate-load-comptox', [
            'response' => $this->response,
            'dtxsid_out' => $this->dtxsid,
            'columns' => $this->remapComptoxToNorman(),
        ]);
    }
    
    
    public function remapComptoxToNorman(){
        return [
            'preferredName'       => 'name',
            'casrn'               => 'cas_number',
            'smiles'              => 'smiles',
            'inchikey'            => 'stdinchikey',
            'dtxsid'              => 'dtxid',
            'pubchemCid'          => 'pubchem_cid',
            'molFormula'          => 'molecular_formula',
            'monoisotopicMass'    => 'mass_iso',
        ];
    }
}
