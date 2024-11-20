<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubstanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [ 
            // 'id' => $this->id,
            'code' => $this->code,
            'prefixed_code' => $this->prefixed_code,
            'name' => $this->name,
            'name_dashboard' => $this->name_dashboard,
            'name_chemspider' => $this->name_chemspider,
            'name_iupac' => $this->name_iupac,
            'cas_number' => $this->cas_number,
            'smiles' => $this->smiles,
            'smiles_dashboard' => $this->smiles_dashboard,
            'stdinchi' => $this->stdinchi,
            'stdinchikey' => $this->stdinchikey,
            'pubchem_cid' => $this->pubchem_cid,
            'chemspider_id' => $this->chemspider_id,
            'dtxid' => $this->dtxid,
            'molecular_formula' => $this->molecular_formula,
            'mass_iso' => $this->mass_iso,
            'metadata_synonyms' => $this->metadata_synonyms,
            'metadata_cas' => $this->metadata_cas,
            'metadata_ms_ready' => $this->metadata_ms_ready,
            'metadata_general' => $this->metadata_general,
        ]; 
    }
}
