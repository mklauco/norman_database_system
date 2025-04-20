<?php

namespace App\Models\Indoor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndoorMain extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'indoor_main';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sus_id',
        'country',
        'country_other',
        'station_name',
        'national_name',
        'short_sample_code',
        'sample_code',
        'provider_code',
        'code_ec',
        'code_other',
        'east_west',
        'longitude_d',
        'longitude_m',
        'longitude_s',
        'longitude_decimal',
        'north_south',
        'latitude_d',
        'latitude_m',
        'latitude_s',
        'latitude_decimal',
        'dpc_id',
        'altitude',
        'matrix_id',
        'matrix_other',
        'dcot_id',
        'dic_id',
        'concentration_value',
        'concentration_unit',
        'estimated_age',
        'sampling_date_y',
        'sampling_date_m',
        'sampling_date_d',
        'sampling_date_t',
        'sampling_duration',
        'dtoe_id',
        'dcoe_id',
        'dcoe_other',
        'id_method',
        'id_data',
        'remark',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'sus_id' => 'integer',
        'concentration_value' => 'float',
        'estimated_age' => 'float',
        'sampling_date_y' => 'integer',
        'sampling_date_m' => 'integer',
        'sampling_date_d' => 'integer',
        'sampling_date_t' => 'datetime',
        'dpc_id' => 'integer',
        'matrix_id' => 'integer',
        'dcot_id' => 'integer',
        'dic_id' => 'integer',
        'dtoe_id' => 'integer',
        'dcoe_id' => 'integer',
        'id_method' => 'integer',
        'id_data' => 'integer',
    ];

    /**
     * Get the country record associated with the indoor record.
     */
    public function country()
    {
        return $this->belongsTo(IndoorDataCountry::class, 'country', 'id');
    }

    /**
     * Get the other country record associated with the indoor record.
     */
    public function countryOther()
    {
        return $this->belongsTo(IndoorDataCountryOther::class, 'country_other', 'id');
    }

    /**
     * Get the purpose code record associated with the indoor record.
     */
    public function purposeCode()
    {
        return $this->belongsTo(IndoorDataDpc::class, 'dpc_id', 'id');
    }

    /**
     * Get the matrix record associated with the indoor record.
     */
    public function matrix()
    {
        return $this->belongsTo(IndoorDataMatrix::class, 'matrix_id', 'id');
    }

    /**
     * Get the observation type record associated with the indoor record.
     */
    public function observationType()
    {
        return $this->belongsTo(IndoorDataDcot::class, 'dcot_id', 'id');
    }

    /**
     * Get the collection code record associated with the indoor record.
     */
    public function collectionCode()
    {
        return $this->belongsTo(IndoorDataDic::class, 'dic_id', 'id');
    }

    /**
     * Get the environment type record associated with the indoor record.
     */
    public function environmentType()
    {
        return $this->belongsTo(IndoorDataDtoe::class, 'dtoe_id', 'id');
    }

    /**
     * Get the environment category record associated with the indoor record.
     */
    public function environmentCategory()
    {
        return $this->belongsTo(IndoorDataDcoe::class, 'dcoe_id', 'id');
    }

    /**
     * Get a formatted sampling date
     * 
     * @return string
     */
    public function getSamplingDateAttribute()
    {
        if (!$this->sampling_date_y || !$this->sampling_date_m || !$this->sampling_date_d) {
            return null;
        }
        
        return $this->sampling_date_y . '-' . 
               str_pad($this->sampling_date_m, 2, '0', STR_PAD_LEFT) . '-' . 
               str_pad($this->sampling_date_d, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get formatted coordinates
     * 
     * @return string|null
     */
    public function getFormattedCoordinatesAttribute()
    {
        if (empty($this->latitude_decimal) && empty($this->longitude_decimal) && 
            empty($this->latitude_d) && empty($this->longitude_d)) {
            return null;
        }
        
        $latitude = $this->latitude_decimal ?: 
                   ($this->latitude_d . '° ' . $this->latitude_m . '\' ' . $this->latitude_s . '" ' . $this->north_south);
        
        $longitude = $this->longitude_decimal ?: 
                    ($this->longitude_d . '° ' . $this->longitude_m . '\' ' . $this->longitude_s . '" ' . $this->east_west);
        
        return $latitude . ', ' . $longitude;
    }
}