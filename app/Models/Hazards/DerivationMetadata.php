<?php

namespace App\Models\Hazards;

use App\Models\Susdat\Substance;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DerivationMetadata extends Model
{
    protected $table = 'hazards_derivation_metadata';

    protected $fillable = [
        'selection_id',
        'susdat_substance_id',
        'bucket',
        'hazards_substance_data_id',
        'user_id',
        'data_source',
        'editor',
        'record_date',
        'reference_type',
        'title',
        'authors',
        'year',
        'bibliographic_source',
        'hazards_file_doi',
        'test_type',
        'performed_under_glp',
        'standard_test',
        'substance_name',
        'cas_number',
        'radio_labeled_substance',
        'standard_qualifier',
        'standard_used',
        'test_matrix',
        'test_species',
        'duration_days',
        'exposure_concentration',
        'ph',
        'temperature_c',
        'total_organic_carbon',
        'original_parameter_name',
        'original_qualifier',
        'original_value',
        'original_value_range',
        'original_unit',
        'assessment_parameter_name',
        'assessment_qualifier',
        'assessment_value',
        'assessment_unit',
        'hazard_criterion',
        'original_classification',
        'classification_score',
        'general_comment',
        'applicability_domain',
        'applicability_domain_score',
        'reliability_score',
        'reliability_score_system',
        'reliability_rational',
        'institution_of_reliability_score',
        'regulatory_context',
        'institution_original_classification',
        'norman_classification',
        'norman_vote',
        'automated_expert_vote',
    ];

    protected $casts = [
        'selection_id' => 'integer',
        'susdat_substance_id' => 'integer',
        'hazards_substance_data_id' => 'integer',
        'user_id' => 'integer',
        'year' => 'integer',
        'duration_days' => 'float',
        'exposure_concentration' => 'float',
        'ph' => 'float',
        'temperature_c' => 'float',
        'total_organic_carbon' => 'float',
        'original_value' => 'float',
        'assessment_value' => 'float',
        'classification_score' => 'float',
        'applicability_domain_score' => 'float',
        'reliability_score' => 'float',
        'norman_vote' => 'integer',
        'record_date' => 'datetime',
    ];

    public function selection()
    {
        return $this->belongsTo(DerivationSelection::class, 'selection_id');
    }

    public function substance()
    {
        return $this->belongsTo(Substance::class, 'susdat_substance_id');
    }

    public function hazardsSubstanceData()
    {
        return $this->belongsTo(ComptoxSubstanceData::class, 'hazards_substance_data_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
