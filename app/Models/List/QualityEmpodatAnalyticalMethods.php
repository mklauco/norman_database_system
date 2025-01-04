<?php

namespace App\Models\List;

use Illuminate\Database\Eloquent\Model;

class QualityEmpodatAnalyticalMethods extends Model
{
    //
    protected $fillable = ['name', 'min_rating', 'max_rating'];

    protected $table = 'list_quality_empodat_analytical_methods';
}
