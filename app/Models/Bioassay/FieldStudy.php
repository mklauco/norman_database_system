<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BioassayFieldStudy extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bioassay_field_studies';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'm_sd_id',
        'm_ds_id',
        'm_auxiliary_sample_identification',
        'm_bioassay_type_id',
        'm_bioassay_type_other',
        'm_bioassay_name_id',
        'bioassay_name_other',
        'm_adverse_outcome_id',
        'adverse_outcome_other',
        'm_test_organism_id',
        'test_organism_other',
        'm_cell_line_strain_id',
        'cell_line_strain_other',
        'm_endpoint_id',
        'endpoint_other',
        'm_effect_id',
        'effect_other',
        'm_measured_parameter_id',
        'measured_parameter_other',
        'exposure_duration',
        'effect_significantly',
        'maximal_tested_ref',
        'dose_response_relationship',
        'm_main_determinand_id',
        'main_determinand_other',
        'value_determinand',
        'm_effect_equivalent_id',
        'effect_equivalent_other',
        'value_effect_equivalent',
        'm_standard_substance_id',
        'standard_substance_other',
        'limit_of_detection',
        'limit_of_quantification',
        'date_performed_month',
        'date_performed_year',
        'bioassay_performed',
        'guideline',
        'deviation',
        'describe_deviation',
        'm_assay_format_id',
        'assay_format_other',
        'm_solvent_id',
        'solvent_other',
        'max_solvent_concentration',
        'test_medium',
        'm_test_system_id',
        'test_system_other',
        'no_organisms',
        'age_organisms',
        'm_life_stage_id',
        'life_stage_other',
        'no_experiment_repetitions',
        'no_replicates_per_treatment',
        'no_concentration_treatments',
        'm_effect_level_id',
        'effect_level_other',
        'cv_main_determinand',
        'average_cv_resopnse',
        'statistical_assessment',
        'significance_level',
        'statistical_calculation',
        'positive_control_tested',
        'm_positive_control_id',
        'positive_control_other',
        'compliance_guideline_values',
        'compliance_long_term',
        'solvent_control_tested',
        'respective_blank_sample',
        'temperature_test',
        'temperature_compliance',
        'ph_sample_test',
        'ph_sample_adjusted',
        'ph_compliance',
        'do_sample_test',
        'do_compliance',
        'conductivity_sample_test',
        'conductivity_compliance',
        'ammonium_measured',
        'ammonium_compliance',
        'light_intensity',
        'photoperiod',
        'reference_method',
        'reference_paper',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'm_sd_id' => 'integer',
        'm_ds_id' => 'integer',
        'm_bioassay_type_id' => 'integer',
        'm_bioassay_name_id' => 'integer',
        'm_adverse_outcome_id' => 'integer',
        'm_test_organism_id' => 'integer',
        'm_cell_line_strain_id' => 'integer',
        'm_endpoint_id' => 'integer',
        'm_effect_id' => 'integer',
        'm_measured_parameter_id' => 'integer',
        'm_main_determinand_id' => 'integer',
        'm_effect_equivalent_id' => 'integer',
        'm_standard_substance_id' => 'integer',
        'm_assay_format_id' => 'integer',
        'm_solvent_id' => 'integer',
        'm_test_system_id' => 'integer',
        'm_life_stage_id' => 'integer',
        'm_effect_level_id' => 'integer',
        'm_positive_control_id' => 'integer',
    ];

    /**
     * Get the bioassay main record associated with this field study.
     */
    public function bioassayMain()
    {
        return $this->belongsTo(BioassayMain::class, 'm_sd_id', 'id');
    }

    /**
     * Get the user that created the record.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user that last updated the record.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the bioassay type.
     */
    public function bioassayType()
    {
        // Assuming you have a BioassayType model
        return $this->belongsTo(BioassayType::class, 'm_bioassay_type_id');
    }

    /**
     * Get the bioassay name.
     */
    public function bioassayName()
    {
        // Assuming you have a BioassayName model
        return $this->belongsTo(BioassayName::class, 'm_bioassay_name_id');
    }

    /**
     * Get the adverse outcome.
     */
    public function adverseOutcome()
    {
        // Assuming you have an AdverseOutcome model
        return $this->belongsTo(AdverseOutcome::class, 'm_adverse_outcome_id');
    }

    /**
     * Get the test organism.
     */
    public function testOrganism()
    {
        // Assuming you have a TestOrganism model
        return $this->belongsTo(TestOrganism::class, 'm_test_organism_id');
    }

    // Additional relationships can be added here as needed
}
