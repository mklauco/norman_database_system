<?php

namespace App\Models\Hazards;

use App\Models\Susdat\Substance;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ComptoxSubstanceData extends Model
{
    protected $table = 'hazards_comptox_substance_data';

    protected $fillable = [
        'parse_run_id',
        'comptox_payload_id',
        'source_record_type',
        'source_record_id',
        'data_domain',
        'data_source',
        'editor',
        'date',
        'reference_type',
        'title',
        'authors',
        'year',
        'bibliographic_source',
        'physico_chemical_source_doi',
        'test_type',
        'performed_under_glp',
        'standard_test',
        'susdat_substance_id',
        'dtxid',
        'substance_name',
        'cas_no',
        'inchikey',
        'smiles',
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
        'norman_parameter_name',
        'specific_parameter_name',
        'assessment_qualifier',
        'assessment_class',
        'value_assessment_index',
        'value_standardised_score',
        'unit',
        'general_comment',
        'applicability_domain',
        'applicability_domain_score',
        'reliability_score',
        'reliability_score_system',
        'reliability_rational',
        'institution_of_reliability_score',
        'regulatory_purpose',
        'use_of_study',
    ];

    protected $casts = [
        'parse_run_id' => 'integer',
        'comptox_payload_id' => 'integer',
        'source_record_id' => 'integer',
        'editor' => 'integer',
        'performed_under_glp' => 'boolean',
        'standard_test' => 'boolean',
        'susdat_substance_id' => 'integer',
        'radio_labeled_substance' => 'boolean',
        'duration_days' => 'float',
        'exposure_concentration' => 'float',
        'ph' => 'float',
        'temperature_c' => 'float',
        'total_organic_carbon' => 'float',
        'original_value' => 'float',
        'value_assessment_index' => 'float',
        'value_standardised_score' => 'float',
        'applicability_domain_score' => 'float',
        'reliability_score' => 'float',
    ];

    public function parseRun()
    {
        return $this->belongsTo(ParseRun::class, 'parse_run_id');
    }

    public function payload()
    {
        return $this->belongsTo(ComptoxPayload::class, 'comptox_payload_id');
    }

    public function substance()
    {
        return $this->belongsTo(Substance::class, 'susdat_substance_id');
    }

    public function editorUser()
    {
        return $this->belongsTo(User::class, 'editor');
    }
}

