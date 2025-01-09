<?php

namespace App\Models\Empodat;

use App\Models\List\Country;
use Illuminate\Database\Eloquent\Model;

class EmpodatStation extends Model
{
    //
    protected $table = 'empodat_stations';

    protected $fillable = [
        'name',
        'country_id',
        'country_other_id',
        'country',
        'country_other',
        'national_name',
        'short_sample_code',
        'sample_code',
        'provider_code',
        'code_ec_wise',
        'code_ec_other',
        'code_other',
        'specific_locations',
        'latitude',
        'longitude',
    ];
    
    public function country(){
        return $this->belongsTo(Country::class, 'country_id');
    }
}
