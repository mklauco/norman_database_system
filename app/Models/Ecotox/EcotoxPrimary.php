<?php

namespace App\Models\Ecotox;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Susdat\Substance;
use App\Models\User;

class EcotoxPrimary extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecotox_main_3';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ecotox_id',
        'data_source',
        'data_source_id',
        'data_source_ref',
        'data_protection',
        'data_source_link',
        'edit_editor',
        'edit_date',
        'reference_type',
        'reference_id',
        'study_title',
        'authors',
        'year_publication',
        'bibliographic_source',
        'testing_laboratory',
        'matrix_habitat',
        'test_type',
        'acute_or_chronic',
        'sus_id',
        'substance_id',
        'substance_name',
        'cas_number',
        'ec_number',
        'purity',
        'supplier',
        'vehicle_substance',
        'known_concentrations',
        'radio_substance',
        'preparation_solutions',
        'standard_qualifier',
        'standard_used',
        'deviations_from_standard',
        'principles',
        'glp_certificate',
        'effect',
        'effect_measurement',
        'endpoint',
        'duration',
        'total_test_duration',
        'recovery_considered',
        'scientific_name',
        'common_name',
        'taxonomic_group',
        'body_length',
        'body_weight',
        'initial_cell_density',
        'reproductive_condition',
        'other_effects',
        'lipid',
        'age',
        'life_stage',
        'gender',
        'strain_clone',
        'organism_source',
        'culture_handling',
        'acclimation',
        'nominal_concentrations',
        'measured_or_nominal',
        'limit_test',
        'range_finding_study',
        'analytical_matrix',
        'analytical_schedule',
        'analytical_method',
        'analytical_recovery',
        'limit_of_quantification',
        'exposure_regime',
        'exposure_duration',
        'application_freq',
        'exposure_route',
        'positive_control_used',
        'positive_control_substance',
        'effects_control',
        'vehicle_control',
        'effects_vehicle',
        'intervals_water',
        'ph',
        'adjustment_ph',
        'temperature',
        'conductivity',
        'light_intensity',
        'light_quality',
        'photo_period',
        'hardness',
        'chlorine',
        'alkalinity',
        'salinity',
        'organic_carbon',
        'dissolved_oxygen',
        'material_vessel',
        'volume_vessel',
        'open_closed',
        'aeration',
        'description_medium',
        'culture_medium',
        'feeding_protocols',
        'type_amount_food',
        'number_organisms',
        'number_replicates',
        'statistical_method',
        'trend',
        'significance_result',
        'significance_level',
        'concentration_qualifier',
        'concentration_value',
        'estimate_variability',
        'test_item',
        'result_comment',
        'dose_response',
        'availability_raw_data',
        'study_available',
        'general_comment',
        'reliability_study',
        'reliability_score',
        'existing_rational_reliability',
        'regulatory_purpose',
        'final_cell_density',
        'used_for_regulaltory_purpose',
        'institution_study',
        'deformed_or_abnormal_cells',
        'negative_control_used',
        'response_site',
        'final_body_length_of_control',
        'unit_concentration',
        'biotest_id',
        'standard_test',
        'final_body_weight_of_control',
        'use_study',
        'editor',
        'color_tx',
        'cred'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'sus_id' => 'integer',
        'substance_id' => 'integer',
        'year_publication' => 'integer',
        'concentration_value' => 'double',
        'reliability_study' => 'integer',
        'editor' => 'integer',
        'color_tx' => 'integer',
        'cred' => 'double',
        'edit_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the substance associated with the ecotox record (using sus_id).
     */
    public function substanceOld()
    {
        return $this->belongsTo(Substance::class, 'sus_id', 'id');
    }

    /**
     * Get the substance associated with the ecotox record (using substance_id).
     * 
     * This is the preferred relationship method that uses the foreign key constraint.
     */
    public function substance()
    {
        return $this->belongsTo(Substance::class, 'substance_id');
    }

    /**
     * Get the editor/user who last edited this record.
     */
    public function editorUser()
    {
        return $this->belongsTo(User::class, 'editor', 'id');
    }

    /**
     * Get the CRED evaluations for this ecotox record.
     */
    public function credEvaluations()
    {
        return $this->hasMany(CredEvaluationMain::class, 'ecotox_id', 'ecotox_id');
    }

    /**
     * Get the CRED sub-evaluations for this ecotox record.
     */
    public function credSubEvaluations()
    {
        return $this->hasMany(CredEvaluationSub::class, 'ecotox_id', 'ecotox_id');
    }

    /**
     * Get the CRED final evaluations for this ecotox record.
     */
    // public function credFinalEvaluations()
    // {
    //     return $this->hasMany(CredEvaluationFinal::class, 'ecotox_id', 'ecotox_id');
    // }

    /**
     * Get the derivations associated with this ecotox record.
     */
    public function derivations()
    {
        return $this->hasMany(EcotoxDerivation::class, 'ecotox_id', 'ecotox_id');
    }

    /**
     * Get the votes associated with this ecotox record.
     */
    public function votes()
    {
        return $this->hasMany(EcotoxVote::class, 'ecotox_id', 'ecotox_id');
    }

    /**
     * Get a formatted concentration with qualifier and unit.
     * 
     * @return string|null
     */
    public function getFormattedConcentrationAttribute()
    {
        if (empty($this->concentration_value)) {
            return null;
        }
        
        $qualifier = $this->concentration_qualifier ? $this->concentration_qualifier . ' ' : '';
        $unit = $this->unit_concentration ? ' ' . $this->unit_concentration : '';
        
        return $qualifier . $this->concentration_value . $unit;
    }

    /**
     * Get a formatted duration with unit.
     * 
     * @return string|null
     */
    public function getFormattedDurationAttribute()
    {
        if (empty($this->duration)) {
            return null;
        }
        
        return $this->duration;
    }

    /**
     * Scope a query to only include ecotox records with specific matrix habitat.
     */
    public function scopeWithMatrixHabitat($query, $matrixHabitat)
    {
        if (is_array($matrixHabitat) && !empty($matrixHabitat)) {
            return $query->whereIn('matrix_habitat', $matrixHabitat);
        }
        return $query;
    }

    /**
     * Scope a query to only include ecotox records with specific taxonomic group.
     */
    public function scopeWithTaxonomicGroup($query, $taxonomicGroup)
    {
        if (is_array($taxonomicGroup) && !empty($taxonomicGroup)) {
            return $query->whereIn('taxonomic_group', $taxonomicGroup);
        }
        return $query;
    }

    /**
     * Scope a query to only include ecotox records with specific acute or chronic classification.
     */
    public function scopeWithAcuteOrChronic($query, $acuteOrChronic)
    {
        if (is_array($acuteOrChronic) && !empty($acuteOrChronic)) {
            return $query->whereIn('acute_or_chronic', $acuteOrChronic);
        }
        return $query;
    }

    /**
     * Scope a query to only include ecotox records with specific endpoint.
     */
    public function scopeWithEndpoint($query, $endpoint)
    {
        if (is_array($endpoint) && !empty($endpoint)) {
            return $query->whereIn('endpoint', $endpoint);
        }
        return $query;
    }

    /**
     * Scope a query to only include ecotox records with specific scientific name.
     */
    public function scopeWithScientificName($query, $scientificName)
    {
        if (is_array($scientificName) && !empty($scientificName)) {
            return $query->whereIn('scientific_name', $scientificName);
        }
        return $query;
    }

    /**
     * Scope a query to only include ecotox records with specific reliability study score.
     */
    public function scopeWithReliabilityStudy($query, $reliabilityStudy)
    {
        if (is_numeric($reliabilityStudy) && $reliabilityStudy > 0) {
            return $query->where('reliability_study', $reliabilityStudy);
        }
        return $query;
    }

    /**
     * Scope a query to only include ecotox records with specific use study value.
     */
    public function scopeWithUseStudy($query, $useStudy)
    {
        if (!empty($useStudy)) {
            return $query->where('use_study', $useStudy);
        }
        return $query;
    }
}