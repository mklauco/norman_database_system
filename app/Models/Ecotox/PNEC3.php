<?php

namespace App\Models\Ecotox;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Susdat\Substance;

class PNEC3 extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecotox_pnec3';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'norman_pnec_id',
        'norman_dataset_id',
        'data_source_name',
        'data_source_link',
        'data_source_id',
        'study_title',
        'authors',
        'year',
        'bibliographic_source',
        'dossier_available',
        'sus_id',
        'cas',
        'substance_name',
        'country_or_region',
        'institution',
        'matrix_habitat',
        'legal_status',
        'protected_asset',
        'pnec_type',
        'pnec_type_country',
        'monitoring_frequency',
        'concentration_specification',
        'taxonomic_group',
        'scientific_name',
        'endpoint',
        'effect_measurement',
        'duration',
        'exposure_regime',
        'measured_or_nominal',
        'test_item',
        'purity',
        'AF',
        'justification',
        'derivation_method',
        'value',
        'ecotox_id',
        'remarks',
        'reliability_study',
        'reliability_score',
        'institution_study',
        'vote',
        'regulatory_context',
        'concentration_qualifier',
        'concentration_value',
        'link_directive',
        'date',
        'use_study',
        'editor',
        'color_tx',
        'publication_year',
        'pnec_quality_class',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'sus_id' => 'integer',
        'reliability_study' => 'integer',
        'editor' => 'integer',
        'color_tx' => 'integer',
        'publication_year' => 'integer',
        'date' => 'date',
    ];

    /**
     * Get the substance that the PNEC3 record belongs to.
     */
    public function substance()
    {
        return $this->belongsTo(Substance::class, 'sus_id', 'id');
    }

    
}