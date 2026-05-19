<?php

namespace App\Models\Susdat;

use Illuminate\Database\Eloquent\Model;

class Usepa extends Model
{
    protected $table = 'susdat_usepa';

    protected $fillable = [
        'sus_id',
        'substance_id',
        'dtsxid',
        'usepa_formula',
        'usepa_wikipedia',
        'usepa_wikipedia_url',
        'usepa_Log_Kow_experimental',
        'usepa_Log_Kow_predicted',
        'usepa_solubility_experimental',
        'usepa_solubility_predicted',
        'usepa_Koc_min_experimental',
        'usepa_Koc_max_experimental',
        'usepa_Koc_min_predicted',
        'usepa_Koc_max_predicted',
        'usepa_Life_experimental',
        'usepa_Life_predicted',
        'usepa_BCF_experimental',
        'usepa_BCF_predicted',
    ];

    protected $casts = [
        'usepa_Log_Kow_experimental' => 'float',
        'usepa_Log_Kow_predicted' => 'float',
        'usepa_solubility_experimental' => 'float',
        'usepa_solubility_predicted' => 'float',
        'usepa_Koc_min_experimental' => 'float',
        'usepa_Koc_max_experimental' => 'float',
        'usepa_Koc_min_predicted' => 'float',
        'usepa_Koc_max_predicted' => 'float',
        'usepa_Life_experimental' => 'float',
        'usepa_Life_predicted' => 'float',
        'usepa_BCF_experimental' => 'float',
        'usepa_BCF_predicted' => 'float',
    ];

    public $timestamps = false;
}
