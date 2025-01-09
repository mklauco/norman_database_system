<?php

namespace App\Models\Empodat;

use App\Models\List\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SearchCountries extends Model
{
    use HasFactory;

    protected $table = 'empodat_search_countries';

    protected $fillable = [
        'country_id',
        'country_other',
    ];

    public function country(){
      return $this->hasOne(Country::class, 'id', 'country_id');
    }
}
