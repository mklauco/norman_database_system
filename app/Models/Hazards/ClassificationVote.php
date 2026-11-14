<?php

declare(strict_types=1);

namespace App\Models\Hazards;

use App\Models\Susdat\Substance;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassificationVote extends Model
{
    protected $table = 'hazards_classification_votes';

    protected $fillable = [
        'susdat_substance_id',
        'user_id',
        'classification_type',
        'criterion',
        'classification_code',
        'vote_value',
        'is_current',
    ];

    protected $casts = [
        'susdat_substance_id' => 'integer',
        'user_id' => 'integer',
        'vote_value' => 'integer',
        'is_current' => 'boolean',
    ];

    public function substance(): BelongsTo
    {
        return $this->belongsTo(Substance::class, 'susdat_substance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
