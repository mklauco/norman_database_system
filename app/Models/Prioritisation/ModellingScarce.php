<?php

namespace App\Models\Prioritisation;

use App\Models\Susdat\Substance;
use Illuminate\Database\Eloquent\Model;

class ModellingScarce extends Model
{
    //
    protected $table = 'prioritisation_modelling_scarce';


    public function substance()
    {
        return $this->belongsTo(Substance::class, 'substance_id', 'id');
    }
}
