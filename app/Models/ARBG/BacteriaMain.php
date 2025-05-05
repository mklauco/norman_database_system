<?php

namespace App\Models\ARBG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BacteriaMain extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'arbg_bacteria_main';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sample_matrix_id',
        'sample_matrix_other',
        'bacterial_group_id',
        'bacterial_group_other',
        'concentration_data_id',
        'ar_phenotype',
        'ar_phenotype_class',
        'abundance',
        'value',
        'sampling_date_day',
        'sampling_date_month',
        'sampling_date_year',
        'sampling_date_hour',
        'sampling_date_minute',
        'name_of_the_wider_area_of_sampling',
        'river_basin_name',
        'type_of_depth_sampling_id',
        'depth',
        'soil_type_id',
        'soil_texture_id',
        'concentration_normalised',
        'grain_size_distribution_id',
        'grain_size_distribution_other',
        'dry_wet_ratio',
        'ph',
        'total_organic_carbon',
        'method_id',
        'source_id',
        'coordinate_id',
        'remark',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'sample_matrix_id' => 'integer',
        'bacterial_group_id' => 'integer',
        'concentration_data_id' => 'integer',
        'sampling_date_day' => 'integer',
        'sampling_date_month' => 'integer',
        'sampling_date_year' => 'integer',
        'type_of_depth_sampling_id' => 'integer',
        'soil_type_id' => 'integer',
        'soil_texture_id' => 'integer',
        'grain_size_distribution_id' => 'integer',
        'method_id' => 'integer',
        'source_id' => 'integer',
        'coordinate_id' => 'integer',
    ];

    /**
     * Get the sample matrix record associated with the bacteria record.
     */
    public function sampleMatrix()
    {
        return $this->belongsTo(DataSampleMatrix::class, 'sample_matrix_id', 'id');
    }

    /**
     * Get the bacterial group record associated with the bacteria record.
     */
    public function bacterialGroup()
    {
        return $this->belongsTo(DataBacterialGroup::class, 'bacterial_group_id', 'id');
    }

    /**
     * Get the concentration data record associated with the bacteria record.
     */
    public function concentrationData()
    {
        return $this->belongsTo(DataConcentrationData::class, 'concentration_data_id', 'concentration_data_id');
    }

    /**
     * Get the grain size distribution record associated with the bacteria record.
     */
    public function grainSizeDistribution()
    {
        return $this->belongsTo(DataGrainSizeDistribution::class, 'grain_size_distribution_id', 'grain_size_distribution_id');
    }

    /**
     * Get the soil texture record associated with the bacteria record.
     */
    public function soilTexture()
    {
        return $this->belongsTo(DataSoilTexture::class, 'soil_texture_id', 'soil_texture_id');
    }

    /**
     * Get the soil type record associated with the bacteria record.
     */
    public function soilType()
    {
        return $this->belongsTo(DataSoilType::class, 'soil_type_id', 'soil_type_id');
    }

    /**
     * Get the depth sampling type record associated with the bacteria record.
     */
    public function depthSamplingType()
    {
        return $this->belongsTo(DataTypeOfDepthSampling::class, 'type_of_depth_sampling_id', 'id');
    }

    /**
     * Get the coordinate record associated with the bacteria record.
     */
    public function coordinate()
    {
        return $this->belongsTo(BacteriaCoordinate::class, 'coordinate_id', 'id');
    }

    /**
     * Get the method record associated with the bacteria record.
     */
    public function method()
    {
        return $this->belongsTo(DataMethod::class, 'method_id', 'method_id');
    }

    /**
     * Get the source record associated with the bacteria record.
     */
    public function source()
    {
        return $this->belongsTo(DataSource::class, 'source_id', 'source_id');
    }

    /**
     * Get formatted sampling date
     * 
     * @return string|null
     */
    public function getSamplingDateAttribute()
    {
        if (!$this->sampling_date_year || !$this->sampling_date_month || !$this->sampling_date_day) {
            return null;
        }
        
        return $this->sampling_date_year . '-' . 
               str_pad($this->sampling_date_month, 2, '0', STR_PAD_LEFT) . '-' . 
               str_pad($this->sampling_date_day, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get formatted sampling time
     * 
     * @return string|null
     */
    public function getSamplingTimeAttribute()
    {
        if (!$this->sampling_date_hour && !$this->sampling_date_minute) {
            return null;
        }
        
        return ($this->sampling_date_hour ?? '00') . ':' . ($this->sampling_date_minute ?? '00');
    }
}