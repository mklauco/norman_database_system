<?php

namespace App\Models\Empodat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpodatMinor extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'empodat_minor';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dpc_id',
        'altitude',
        'matrix_other',
        'compound',
        'dcod_id',
        'unit_extra',
        'tier',
        'sampling_technique',
        'sampling_date',
        'sampling_date_t',
        'sampling_date1_y',
        'sampling_date1_m',
        'sampling_date1_d',
        'sampling_date1_t',
        'sampling_date1',
        'analysis_date_y',
        'analysis_date_m',
        'analysis_date_d',
        'sampling_duration_day',
        'sampling_duration_hour',
        'description',
        'remark',
        'remark_add',
        'show_date',
        'dtod_id',
        'dtod_other',
        'agg_uncertainty',
        'dmm_id',
        'agg_max',
        'agg_min',
        'agg_number',
        'agg_deviation',
        'dtl_id',
        'dtl_other',
        'dst_id',
        'dst_other',
        'dtos_id',
        'dplu_id',
        'noexport',
        'list_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'dpc_id' => 'integer',
        'compound' => 'integer',
        'dcod_id' => 'integer',
        'tier' => 'integer',
        'sampling_technique' => 'integer',
        'sampling_date' => 'datetime',
        'sampling_date_t' => 'datetime:H:i:s',
        'sampling_date1_y' => 'string',
        'sampling_date1_m' => 'integer',
        'sampling_date1_d' => 'integer',
        'sampling_date1_t' => 'datetime:H:i:s',
        'sampling_date1' => 'datetime',
        'analysis_date_y' => 'string',
        'analysis_date_m' => 'integer',
        'analysis_date_d' => 'integer',
        'show_date' => 'integer',
        'dtod_id' => 'integer',
        'dmm_id' => 'integer',
        'dtl_id' => 'integer',
        'dst_id' => 'integer',
        'dtos_id' => 'integer',
        'dplu_id' => 'integer',
        'noexport' => 'integer',
        'list_id' => 'integer',
    ];

    /**
     * Get the main empodat record that owns this minor record.
     */
    public function empodatMain()
    {
        return $this->belongsTo(EmpodatMain::class, 'id', 'id');
    }

    /**
     * Get formatted sampling date from individual components.
     * 
     * @return string|null
     */
    public function getFormattedSamplingDate1Attribute()
    {
        if (!$this->sampling_date1_y || !$this->sampling_date1_m || !$this->sampling_date1_d) {
            return null;
        }
        
        return $this->sampling_date1_y . '-' . 
               str_pad($this->sampling_date1_m, 2, '0', STR_PAD_LEFT) . '-' . 
               str_pad($this->sampling_date1_d, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get formatted analysis date from individual components.
     * 
     * @return string|null
     */
    public function getFormattedAnalysisDateAttribute()
    {
        if (!$this->analysis_date_y || !$this->analysis_date_m || !$this->analysis_date_d) {
            return null;
        }
        
        return $this->analysis_date_y . '-' . 
               str_pad($this->analysis_date_m, 2, '0', STR_PAD_LEFT) . '-' . 
               str_pad($this->analysis_date_d, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Check if the record should be exported.
     * 
     * @return bool
     */
    public function shouldExport()
    {
        return $this->noexport !== 1;
    }

    /**
     * Get sampling duration in a readable format.
     * 
     * @return string|null
     */
    public function getFormattedSamplingDurationAttribute()
    {
        if (empty($this->sampling_duration_day) && empty($this->sampling_duration_hour)) {
            return null;
        }
        
        $parts = [];
        
        if (!empty($this->sampling_duration_day)) {
            $parts[] = $this->sampling_duration_day . ' day(s)';
        }
        
        if (!empty($this->sampling_duration_hour)) {
            $parts[] = $this->sampling_duration_hour . ' hour(s)';
        }
        
        return implode(', ', $parts);
    }
}