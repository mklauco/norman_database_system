<?php

namespace App\Models\Empodat;

use Illuminate\Database\Eloquent\Model;
use App\Models\List\ConcentrationIndicator;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmpodatMain extends Model
{
    use HasFactory;

    protected $table = 'empodat_main';

    public function concetrationIndicator(){
        return $this->belongsTo(ConcentrationIndicator::class, 'concentration_indicator_id');
    }
}
