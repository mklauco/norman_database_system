<?php

namespace App\Livewire\Susdat;

use Livewire\Component;

class DuplicateLoadPubchem extends Component
{

    public $response = '';
    public $pubchemIds;

    public function mount($pubchemIds)
    {
        return $this->pubchemIds = $pubchemIds;
    }

    public function init(){
        $client = new \GuzzleHttp\Client();
        $url = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/'. implode(",", $this->pubchemIds) . '/property/MolecularFormula,MolecularWeight,InChIKey,IUPACName,Title,CanonicalSMILES,IsomericSMILES,InChI,MonoisotopicMass/JSON';
        $response = $client->request('GET', $url);
        $jsonData = json_decode($response->getBody())->PropertyTable->Properties;
        $pubchem = [];
        foreach($jsonData as $key => $value){
            $pubchem[$value->CID] = collect($value)->mapWithKeys(function($value, $key){
                return [$this->remapPubchemToNorman()[$key] ?? null => $value];
            });
        }
        
        $this->response = $pubchem;
    }

    public function render()
    {
        return view('livewire.susdat.duplicate-load-pubchem', [
            'response' => $this->response,
            // 'dtxsid_out' => $this->dtxsid,
            'columns' => $this->remapPubchemToNorman(),
        ]);
    }

    public function remapPubchemToNorman(){
        return [
            'Title'             => 'name',
            // 'casrn'               => 'cas_number',
            'CanonicalSMILES'   => 'smiles',
            'InChIKey'          => 'stdinchikey',
            'CID'               => 'pubchem_cid',
            'MolecularFormula'  => 'molecular_formula',
            'MonoisotopicMass'  => 'mass_iso',
        ];
    }
}
