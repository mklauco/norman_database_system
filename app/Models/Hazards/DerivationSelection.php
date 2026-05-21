<?php

namespace App\Models\Hazards;

use App\Models\Susdat\Substance;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DerivationSelection extends Model
{
    protected $table = 'hazards_derivation_selections';

    protected $fillable = [
        'susdat_substance_id',
        'bucket',
        'hazards_substance_data_id',
        'source_label',
        'kind',
        'user_id',
        'is_current',
    ];

    protected $casts = [
        'susdat_substance_id' => 'integer',
        'hazards_substance_data_id' => 'integer',
        'user_id' => 'integer',
        'is_current' => 'boolean',
    ];

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

    public function metadata()
    {
        return $this->hasMany(DerivationMetadata::class, 'selection_id');
    }
}
