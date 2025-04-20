<?php

namespace App\Models\Bioassay;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldStudy extends Model
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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
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
     * Get the sample data associated with the bioassay field study.
     */
    public function sampleData()
    {
        return $this->belongsTo(MonitorSampleData::class, 'm_sd_id');
    }

    /**
     * Get the data source associated with the bioassay field study.
     */
    public function dataSource()
    {
        return $this->belongsTo(MonitorDataSource::class, 'm_ds_id');
    }

    /**
     * Get the bioassay type associated with the bioassay field study.
     */
    public function bioassayType()
    {
        return $this->belongsTo(MonitorXBioassayType::class, 'm_bioassay_type_id', 'id');
    }

    /**
     * Get the bioassay name associated with the bioassay field study.
     */
    public function bioassayName()
    {
        return $this->belongsTo(MonitorXBioassayName::class, 'm_bioassay_name_id', 'id');
    }

    /**
     * Get the adverse outcome associated with the bioassay field study.
     */
    public function adverseOutcome()
    {
        return $this->belongsTo(MonitorXAdverseOutcome::class, 'm_adverse_outcome_id', 'id');
    }

    /**
     * Get the test organism associated with the bioassay field study.
     */
    public function testOrganism()
    {
        return $this->belongsTo(MonitorXTestOrganism::class, 'm_test_organism_id', 'id');
    }

    /**
     * Get the cell line strain associated with the bioassay field study.
     */
    public function cellLineStrain()
    {
        return $this->belongsTo(MonitorXCellLineStrain::class, 'm_cell_line_strain_id', 'id');
    }

    /**
     * Get the endpoint associated with the bioassay field study.
     */
    public function endpoint()
    {
        return $this->belongsTo(MonitorXEndpoint::class, 'm_endpoint_id', 'id');
    }

    /**
     * Get the effect associated with the bioassay field study.
     */
    public function effect()
    {
        return $this->belongsTo(MonitorXEffect::class, 'm_effect_id', 'id');
    }

    /**
     * Get the measured parameter associated with the bioassay field study.
     */
    public function measuredParameter()
    {
        return $this->belongsTo(MonitorXMeasuredParameter::class, 'm_measured_parameter_id', 'id');
    }

    /**
     * Get the main determinand associated with the bioassay field study.
     */
    public function mainDeterminand()
    {
        return $this->belongsTo(MonitorXMainDeterminand::class, 'm_main_determinand_id', 'id');
    }

    /**
     * Get the effect equivalent associated with the bioassay field study.
     */
    public function effectEquivalent()
    {
        return $this->belongsTo(MonitorXEffectEquivalent::class, 'm_effect_equivalent_id', 'id');
    }

    /**
     * Get the standard substance associated with the bioassay field study.
     */
    public function standardSubstance()
    {
        return $this->belongsTo(MonitorXStandardSubstance::class, 'm_standard_substance_id', 'id');
    }

    /**
     * Get the assay format associated with the bioassay field study.
     */
    public function assayFormat()
    {
        return $this->belongsTo(MonitorXAssayFormat::class, 'm_assay_format_id', 'id');
    }

    /**
     * Get the solvent associated with the bioassay field study.
     */
    public function solvent()
    {
        return $this->belongsTo(MonitorXSolvent::class, 'm_solvent_id', 'id');
    }

    /**
     * Get the test system associated with the bioassay field study.
     */
    public function testSystem()
    {
        return $this->belongsTo(MonitorXTestSystem::class, 'm_test_system_id', 'id');
    }

    /**
     * Get the life stage associated with the bioassay field study.
     */
    public function lifeStage()
    {
        return $this->belongsTo(MonitorXLifeStage::class, 'm_life_stage_id', 'id');
    }

    /**
     * Get the effect level associated with the bioassay field study.
     */
    public function effectLevel()
    {
        return $this->belongsTo(MonitorXEffectLevel::class, 'm_effect_level_id', 'id');
    }

    /**
     * Get the positive control associated with the bioassay field study.
     */
    public function positiveControl()
    {
        return $this->belongsTo(MonitorXPositiveControl::class, 'm_positive_control_id', 'id');
    }
}
