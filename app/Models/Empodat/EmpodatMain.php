<?php

namespace App\Models\Empodat;

use App\Models\Susdat\Substance;
use App\Models\Empodat\AnalyticalMethod AS EmpodatAnalyticalMethod;
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

    public function station(){
        return $this->belongsTo(EmpodatStation::class, 'station_id');
    }

    public function substance(){
        return $this->belongsTo(Substance::class, 'substance_id');
    }

    // public function dataSource(){
    //     return $this->belongsTo(EmpodatDataSource::class, 'data_source_id');
    // }

    public function analyticalMethod(){
        return $this->belongsTo(EmpodatAnalyticalMethod::class, 'method_id');
    }

    public function dataSource(){
        return $this->belongsTo(DataSources::class, 'data_source_id');
    }

}
