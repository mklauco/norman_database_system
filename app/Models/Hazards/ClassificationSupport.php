<?php

namespace App\Models\Hazards;

use App\Models\Susdat\Substance;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ClassificationSupport extends Model
{
    protected $table = 'hazards_classification_supports';

    protected $fillable = [
        'substance_classification_id',
        'susdat_substance_id',
        'criterion',
        'classification_code',
        'points',
        'source_type',
        'origin_type',
        'origin_user_id',
        'derivation_selection_id',
        'classification_vote_id',
        'is_winner',
    ];

    protected $casts = [
        'substance_classification_id' => 'integer',
        'susdat_substance_id' => 'integer',
        'points' => 'integer',
        'origin_user_id' => 'integer',
        'derivation_selection_id' => 'integer',
        'classification_vote_id' => 'integer',
        'is_winner' => 'boolean',
    ];

    public function classification()
    {
        return $this->belongsTo(SubstanceClassification::class, 'substance_classification_id');
    }

    public function substance()
    {
        return $this->belongsTo(Substance::class, 'susdat_substance_id');
    }

    public function originUser()
    {
        return $this->belongsTo(User::class, 'origin_user_id');
    }

    public function derivationSelection()
    {
        return $this->belongsTo(DerivationSelection::class, 'derivation_selection_id');
    }

    public function classificationVote()
    {
        return $this->belongsTo(ClassificationVote::class, 'classification_vote_id');
    }
}
