<?php

declare(strict_types=1);

namespace App\Models\Hazards;

use App\Models\Susdat\Substance;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function substance(): BelongsTo
    {
        return $this->belongsTo(Substance::class, 'susdat_substance_id');
    }

    public function hazardsSubstanceData(): BelongsTo
    {
        return $this->belongsTo(ComptoxSubstanceData::class, 'hazards_substance_data_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function metadata(): HasMany
    {
        return $this->hasMany(DerivationMetadata::class, 'selection_id');
    }
}
