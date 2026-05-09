<?php

namespace App\Models\Ecotox;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcotoxCredEvaluationFinal extends Model
{
    protected $table = 'ecotox_cred_evaluation_final';

    protected $fillable = [
        'ecotox_id',
        'user_id',
        'cred_final_score',
        'cred_final_score_total',
        'cred_final_close',
        'cred_final_evaluation',
        'cred_final_comment',
        'cred_final_date',
    ];

    protected $casts = [
        'cred_final_score' => 'decimal:4',
        'cred_final_score_total' => 'decimal:4',
        'cred_final_close' => 'integer',
        'cred_final_evaluation' => 'integer',
        'cred_final_date' => 'datetime',
    ];

    /**
     * Get the user who made this evaluation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the ecotox record this evaluation belongs to
     */
    public function ecotoxRecord(): BelongsTo
    {
        return $this->belongsTo(EcotoxFinal::class, 'ecotox_id', 'ecotox_id');
    }

    /**
     * Get formatted score percentage
     */
    public function getScorePercentageAttribute(): float
    {
        if ($this->cred_final_score_total && $this->cred_final_score_total > 0) {
            return round(($this->cred_final_score / $this->cred_final_score_total) * 100, 2);
        }

        return 0;
    }

    /**
     * Get formatted evaluation date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->cred_final_date ? $this->cred_final_date->format('Y-m-d H:i') : 'N/A';
    }
}
