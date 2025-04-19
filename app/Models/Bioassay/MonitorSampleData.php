<?php

namespace App\Models\Bioassay;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorSampleData extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bioassay_monitor_sample_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'm_ds_id',
        'auxiliary_sample_identification',
        'm_country_id',
        'country_other',
        'station_name',
        'station_national_code',
        'station_ec_code_wise',
        'station_ec_code_other',
        'other_station_code',
        'longitude',
        'latitude',
        'm_precision_coordinates_id',
        'altitude',
        'm_sample_matrix_id',
        'sample_matrix_other',
        'm_type_sampling_id',
        'm_sampling_technique_id',
        'sampling_technique_other',
        'sampling_start_day',
        'sampling_start_month',
        'sampling_start_year',
        'sampling_start_hour',
        'sampling_start_minute',
        'sampling_duration_days',
        'sampling_duration_hours',
        'm_fraction_id',
        'fraction_other',
        'name',
        'river_basin_name',
        'river_km',
        'm_proxy_pressures_id',
        'proxy_pressures_other',
        'sampling_depth',
        'surface_area',
        'salinity_mean',
        'spm_concentration',
        'ph',
        'temperature',
        'dissolved_organic_carbon',
        'conductivity',
        'guideline',
        'reference',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'sampling_start_day' => 'integer',
        'sampling_start_month' => 'integer',
        'sampling_start_year' => 'integer',
        'sampling_start_hour' => 'integer',
        'sampling_start_minute' => 'integer',
        'sampling_duration_days' => 'integer',
        'sampling_duration_hours' => 'decimal:2',
        'm_country_id' => 'integer',
        'm_precision_coordinates_id' => 'integer',
        'm_sample_matrix_id' => 'integer',
        'm_type_sampling_id' => 'integer',
        'm_sampling_technique_id' => 'integer',
        'm_fraction_id' => 'integer',
        'm_proxy_pressures_id' => 'integer',
    ];

    /**
     * Relationship with data source
     */
    public function dataSource()
    {
        return $this->belongsTo(MonitorDataSource::class, 'm_ds_id');
    }

    /**
     * Relationship with country
     */
    public function country()
    {
        return $this->belongsTo(MonitorXCountry::class, 'm_country_id', 'id');
    }

    /**
     * Relationship with precision coordinates
     */
    public function precisionCoordinates()
    {
        return $this->belongsTo(MonitorXPrecisionCoordinates::class, 'm_precision_coordinates_id', 'id');
    }

    /**
     * Relationship with sample matrix
     */
    public function sampleMatrix()
    {
        return $this->belongsTo(MonitorXSampleMatrix::class, 'm_sample_matrix_id', 'id');
    }

    /**
     * Relationship with type sampling
     */
    public function typeSampling()
    {
        return $this->belongsTo(MonitorXTypeSampling::class, 'm_type_sampling_id', 'id');
    }

    /**
     * Relationship with sampling technique
     */
    public function samplingTechnique()
    {
        return $this->belongsTo(MonitorXSamplingTechnique::class, 'm_sampling_technique_id', 'id');
    }

    /**
     * Relationship with fraction
     */
    public function fraction()
    {
        return $this->belongsTo(MonitorXFraction::class, 'm_fraction_id', 'id');
    }

    /**
     * Relationship with proxy pressures
     */
    public function proxyPressures()
    {
        return $this->belongsTo(MonitorXProxyPressures::class, 'm_proxy_pressures_id', 'id');
    }
}
