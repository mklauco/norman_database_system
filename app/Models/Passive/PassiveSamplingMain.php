<?php

namespace App\Models\Passive;

use App\Models\Susdat\Substance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PassiveSamplingMain extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'passive_sampling_main';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sus_id',
        'country_id',
        'country_other',
        'station_name',
        'short_sample_code',
        'sample_code',
        'provider_code',
        'national_code',
        'code_ec_wise',
        'code_ec_other',
        'code_other',
        'specific_locations',
        'longitude_decimal',
        'latitude_decimal',
        'dpc_id',
        'altitude',
        'dpr_id',
        'dpr_other',
        'ds_passive_sampling_stretch',
        'ds_stretch_start_and_end',
        'ds_longitude_start_point_decimal',
        'ds_latitude_start_point_decimal',
        'ds_longitude_end_point_decimal',
        'ds_latitude_end_point_decimal',
        'ds_dpc_id',
        'ds_altitude',
        'ds_dpr_id',
        'ds_dpr_other',
        'matrix_id',
        'matrix_other',
        'type_sampling_id',
        'type_sampling_other',
        'passive_sampler_id',
        'passive_sampler_other',
        'sampler_type_id',
        'sampler_type_other',
        'sampler_mass',
        'sampler_surface_area',
        'date_sampling_start_day',
        'date_sampling_start_month',
        'date_sampling_start_year',
        'exposure_time_days',
        'exposure_time_hours',
        'date_of_analysis',
        'time_of_analysis',
        'name',
        'basin_name_id',
        'basin_name_other',
        'dts_id',
        'dts_other',
        'dtm_id',
        'dtm_other',
        'dic_id',
        'concentration_value',
        'unit',
        'title_of_project',
        'ph',
        'temperature',
        'spm_conc',
        'salinity',
        'doc',
        'hardness',
        'o2_1',
        'o2_2',
        'bod5',
        'h2s',
        'p_po4',
        'n_no2',
        'tss',
        'p_total',
        'n_no3',
        'n_total',
        'remark_1',
        'remark_2',
        'am_id',
        'org_id',
        'orig_compound',
        'orig_cas_no',
        'p_determinand_id',
        'p_a_exposure_time',
        'p_a_cruise_dates',
        'p_a_river_km',
        'p_a_sampler_sheets_disks_nr',
        'p_a_sample_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'sus_id' => 'integer',
        'dpc_id' => 'integer',
        'dpr_id' => 'integer',
        'ds_dpc_id' => 'integer',
        'ds_dpr_id' => 'integer',
        'matrix_id' => 'integer',
        'type_sampling_id' => 'integer',
        'passive_sampler_id' => 'integer',
        'sampler_type_id' => 'integer',
        'date_sampling_start_day' => 'integer',
        'date_sampling_start_month' => 'integer',
        'date_sampling_start_year' => 'integer',
        'basin_name_id' => 'integer',
        'dts_id' => 'integer',
        'dtm_id' => 'integer',
        'dic_id' => 'integer',
        'concentration_value' => 'float',
        'am_id' => 'integer',
        'org_id' => 'integer',
        'date_of_analysis' => 'date',
        'time_of_analysis' => 'datetime',
    ];

    public function substance()
    {
        return $this->belongsTo(Substance::class, 'substance_id', 'id');
    }


    /**
     * Get the country record associated with the passive sampling record.
     */
    public function country()
    {
        return $this->belongsTo(PassiveDataCountry::class, 'country_id', 'id');
    }

    /**
     * Get the other country record associated with the passive sampling record.
     */
    public function countryOther()
    {
        return $this->belongsTo(PassiveDataCountryOther::class, 'country_other', 'id');
    }

    /**
     * Get the precision coordinates record associated with the passive sampling record.
     */
    public function precisionCoordinates()
    {
        return $this->belongsTo(PassiveDataPrecisionCoordinates::class, 'dpc_id', 'id');
    }

    /**
     * Get the proxy pressures record associated with the passive sampling record.
     */
    public function proxyPressures()
    {
        return $this->belongsTo(PassiveDataProxyPressures::class, 'dpr_id', 'id');
    }

    /**
     * Get the dynamic sampling precision coordinates record associated with the passive sampling record.
     */
    public function dsPrecisionCoordinates()
    {
        return $this->belongsTo(PassiveDataPrecisionCoordinates::class, 'ds_dpc_id', 'id');
    }

    /**
     * Get the dynamic sampling proxy pressures record associated with the passive sampling record.
     */
    public function dsProxyPressures()
    {
        return $this->belongsTo(PassiveDataProxyPressures::class, 'ds_dpr_id', 'id');
    }

    /**
     * Get the matrix record associated with the passive sampling record.
     */
    public function matrix()
    {
        return $this->belongsTo(PassiveDataMatrix::class, 'matrix_id', 'id');
    }

    /**
     * Get the type sampling record associated with the passive sampling record.
     */
    public function typeSampling()
    {
        return $this->belongsTo(PassiveDataTypeSampling::class, 'type_sampling_id', 'id');
    }

    /**
     * Get the passive sampler record associated with the passive sampling record.
     */
    public function passiveSampler()
    {
        return $this->belongsTo(PassiveDataPassiveSampler::class, 'passive_sampler_id', 'id');
    }

    /**
     * Get the sampler type record associated with the passive sampling record.
     */
    public function samplerType()
    {
        return $this->belongsTo(PassiveDataSamplerType::class, 'sampler_type_id', 'id');
    }

    /**
     * Get the basin name record associated with the passive sampling record.
     */
    public function basinName()
    {
        return $this->belongsTo(PassiveDataBasinName::class, 'basin_name_id', 'id');
    }

    /**
     * Get the type data source record associated with the passive sampling record.
     */
    public function typeDataSource()
    {
        return $this->belongsTo(PassiveDataTypeDataSource::class, 'dts_id', 'id');
    }

    /**
     * Get the type monitoring record associated with the passive sampling record.
     */
    public function typeMonitoring()
    {
        return $this->belongsTo(PassiveDataTypeMonitoring::class, 'dtm_id', 'id');
    }

    /**
     * Get the concentration record associated with the passive sampling record.
     */
    public function indConcentration()
    {
        return $this->belongsTo(PassiveDataIndConcentration::class, 'dic_id', 'id');
    }

    /**
     * Get a formatted sampling date
     * 
     * @return string|null
     */
    public function getSamplingStartDateAttribute()
    {
        if (!$this->date_sampling_start_year || !$this->date_sampling_start_month || !$this->date_sampling_start_day) {
            return null;
        }
        
        return $this->date_sampling_start_year . '-' . 
               str_pad($this->date_sampling_start_month, 2, '0', STR_PAD_LEFT) . '-' . 
               str_pad($this->date_sampling_start_day, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get formatted coordinates
     * 
     * @return string|null
     */
    public function getFormattedCoordinatesAttribute()
    {
        if (empty($this->latitude_decimal) || empty($this->longitude_decimal)) {
            return null;
        }
        
        return $this->latitude_decimal . ', ' . $this->longitude_decimal;
    }

    /**
     * Get dynamic sampling formatted coordinates
     * 
     * @return string|null
     */
    public function getDsFormattedCoordinatesAttribute()
    {
        $startCoords = (!empty($this->ds_latitude_start_point_decimal) && !empty($this->ds_longitude_start_point_decimal))
            ? $this->ds_latitude_start_point_decimal . ', ' . $this->ds_longitude_start_point_decimal
            : null;
            
        $endCoords = (!empty($this->ds_latitude_end_point_decimal) && !empty($this->ds_longitude_end_point_decimal))
            ? $this->ds_latitude_end_point_decimal . ', ' . $this->ds_longitude_end_point_decimal
            : null;
            
        if (!$startCoords && !$endCoords) {
            return null;
        }
        
        if ($startCoords && $endCoords) {
            return 'Start: ' . $startCoords . ' | End: ' . $endCoords;
        }
        
        return $startCoords ?: $endCoords;
    }
}