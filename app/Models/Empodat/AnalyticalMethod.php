<?php

namespace App\Models\Empodat;

use Illuminate\Database\Eloquent\Model;

class AnalyticalMethod extends Model
{
    //
    
    protected $table = 'empodat_analytical_methods';
    
    protected $fillable = [
        'lod',
        'loq',
        'uncertainty_loq',
        'coverage_factor_id',
        'sample_preparation_method_id',
        'sample_preparation_method_other',
        'analytical_method_id',
        'analytical_method_other',
        'standardised_method_id',
        'standardised_method_other',
        'standardised_method_number',
        'validated_method_id',
        'corrected_recovery_id',
        'field_blank_id',
        'iso_id',
        'given_analyte_id',
        'laboratory_participate_id',
        'summary_performance_id',
        'control_charts_id',
        'internal_standards_id',
        'authority_id',
        'rating',
        'remark',
        'sampling_method_id',
        'sampling_collection_device_id',
        'foa',
        'created_at',
        'updated_at',
    ];
    
    public function listAnalyticalMethod(){
        return $this->belongsTo(AnalyticalMethod::class, 'analytical_method_id', 'id');
    }
}
