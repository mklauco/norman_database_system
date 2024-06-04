<?php

namespace App\Models\MariaDB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SLE extends Model
{
    use HasFactory;

    protected $connection = 'norman-mariadb';

    protected $table = 'sle';
}


// id
// sle
// susdat_id
// name
// smiles
// preferred_name
// iupac_name
// monoisotopic_mass
// molecular_formula
// molecular_weight
// inchi
// inchikey
// cas
// dtxsid
// pubchem_cid
// chemspider_id
// other
// status
// status_text
// new_sus_id
// deleted_at
// created_at
// updated_at
