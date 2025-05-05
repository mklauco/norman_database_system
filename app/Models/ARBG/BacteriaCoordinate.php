<?php

namespace App\Models\ARBG;

use Illuminate\Database\Eloquent\Model;

class BacteriaCoordinate extends Model
{
    
    protected $table = 'arbg_bacteria_coordinates';
    
    protected $fillable = [
        'country_id',
        'country_other',
        'station_name',
        'national_code',
        'relevant_ec_code_wise',
        'relevant_ec_code_other',
        'other_code',
        'east_west',
        'longitude1',
        'longitude2',
        'longitude3',
        'longitude_decimal',
        'north_south',
        'latitude1',
        'latitude2',
        'latitude3',
        'latitude_decimal',
        'precision_coordinates_id',
        'altitude',
    ];
    
    /**
    * The attributes that should be cast.
    *
    * @var array
    */
    protected $casts = [
        'precision_coordinates_id' => 'integer',
    ];
    
    /**
    * Get the precision coordinates record associated with the coordinate.
    */
    public function precisionCoordinates()
    {
        return $this->belongsTo(DataPrecisionCoordinates::class, 'precision_coordinates_id', 'precision_coordinates_id');
    }
    
    /**
    * Get formatted longitude coordinates
    * 
    * @return string|null
    */
    public function getFormattedLongitudeAttribute()
    {
        if ($this->longitude_decimal) {
            return $this->longitude_decimal;
        }
        
        if ($this->longitude1 && $this->longitude2 && $this->longitude3) {
            return $this->longitude1 . '° ' . $this->longitude2 . '\' ' . $this->longitude3 . '" ' . $this->east_west;
        }
        
        return null;
    }
    
    /**
    * Get formatted latitude coordinates
    * 
    * @return string|null
    */
    public function getFormattedLatitudeAttribute()
    {
        if ($this->latitude_decimal) {
            return $this->latitude_decimal;
        }
        
        if ($this->latitude1 && $this->latitude2 && $this->latitude3) {
            return $this->latitude1 . '° ' . $this->latitude2 . '\' ' . $this->latitude3 . '" ' . $this->north_south;
        }
        
        return null;
    }
    
    /**
    * Get formatted full coordinates
    * 
    * @return string|null
    */
    public function getFormattedCoordinatesAttribute()
    {
        $latitude = $this->formatted_latitude;
        $longitude = $this->formatted_longitude;
        
        if ($latitude && $longitude) {
            return $latitude . ', ' . $longitude;
        }
        
        return null;
    }
    
    /**
    * Get the full station identifier
    * 
    * @return string
    */
    public function getFullStationNameAttribute()
    {
        $parts = array_filter([
            $this->station_name,
            $this->national_code,
            $this->relevant_ec_code_wise,
            $this->relevant_ec_code_other,
            $this->other_code
        ]);
        
        return implode(' / ', $parts);
    }
}
