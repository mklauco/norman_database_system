<?php

namespace App\Models\Ecotox;

use App\Models\Susdat\Substance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcotoxDerivation extends Model
{
    use SoftDeletes;

    protected $table = 'ecotox_derivation';

    protected $primaryKey = 'id';

    protected $fillable = [
        'vote_matrix',
        'acute_or_chronic',
        'der_concentration',
        'der_color',
        'der_active',
        'der_date',
        'der_save',
        'der_base',
        'der_order',
        'norman_pnec_id',
        'norman_dataset_id',
        'data_source_name',
        'data_source_link',
        'data_source_id',
        'study_title',
        'authors',
        'date',
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
        'use_study',
        'der_editor',
        'color_tx',
        'substance_id',
    ];

    protected $casts = [
        'der_color' => 'boolean',
        'der_active' => 'boolean',
        'der_date' => 'datetime',
        'der_save' => 'boolean',
        'der_base' => 'boolean',
        'der_order' => 'integer',
        'sus_id' => 'integer',
        'reliability_study' => 'integer',
        'der_editor' => 'integer',
        'color_tx' => 'boolean',
        'substance_id' => 'integer',
    ];

    /**
     * Get the substance that owns this derivation
     */
    public function substance(): BelongsTo
    {
        return $this->belongsTo(Substance::class, 'substance_id');
    }

    /**
     * Get the legacy substance reference
     */
    public function legacySubstance(): BelongsTo
    {
        return $this->belongsTo(Substance::class, 'sus_id', 'sus_id');
    }

    /**
     * Get the ecotox test that owns this derivation
     */
    public function ecotox(): BelongsTo
    {
        return $this->belongsTo(EcotoxFinal::class, 'ecotox_id', 'ecotox_id');
    }

    /**
     * Get formatted sampling date
     */
    public function getDerDateAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('d.m.Y') : null;
    }

    /**
     * Get formatted concentration value
     */
    public function getConcentrationValueAttribute($value)
    {
        return $value ?: 'N/A';
    }

    /**
     * Get formatted concentration qualifier
     */
    public function getConcentrationQualifierAttribute($value)
    {
        return $value ?: 'N/A';
    }
}
