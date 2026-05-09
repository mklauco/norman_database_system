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

    public function init()
    {
        $client = new \GuzzleHttp\Client;
        $url = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/'.implode(',', $this->pubchemIds).'/property/MolecularFormula,MolecularWeight,InChIKey,IUPACName,Title,CanonicalSMILES,IsomericSMILES,InChI,MonoisotopicMass/JSON';
        $response = $client->request('GET', $url);
        $jsonData = json_decode($response->getBody())->PropertyTable->Properties;
        $pubchem = [];

        // Get all expected Norman field names
        $expectedFields = array_values($this->remapPubchemToNorman());

        foreach ($jsonData as $key => $value) {
            $mappedData = [];
            // Initialize all expected fields with null
            foreach ($expectedFields as $field) {
                $mappedData[$field] = null;
            }

            // Map PubChem data to Norman fields
            foreach ($value as $pubchemKey => $pubchemValue) {
                $normanField = $this->remapPubchemToNorman()[$pubchemKey] ?? null;
                if ($normanField) {
                    $mappedData[$normanField] = $pubchemValue;
                }
            }
            $pubchem[$value->CID] = $mappedData;
        }

        $this->response = $pubchem;
    }

    public function render()
    {
        return view('livewire.susdat.duplicate-load-pubchem', [
            'response' => $this->response,
            'columns' => $this->remapPubchemToNorman(),
        ]);
    }

    public function remapPubchemToNorman()
    {
        return [
            'Title' => 'name',
            // 'casrn'               => 'cas_number',
            'CanonicalSMILES' => 'smiles',
            'InChIKey' => 'stdinchikey',
            'CID' => 'pubchem_cid',
            'MolecularFormula' => 'molecular_formula',
            'MonoisotopicMass' => 'mass_iso',
        ];
    }
}
