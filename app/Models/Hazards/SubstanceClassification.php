<?php

namespace App\Models\Hazards;

use App\Models\Susdat\Substance;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SubstanceClassification extends Model
{
    protected $table = 'hazards_substance_classifications';

    protected $fillable = [
        'susdat_substance_id',
        'editor_user_id',
        'P',
        'p_auto_points',
        'p_vote_points',
        'p_total_points',
        'p_all_points',
        'B',
        'b_auto_points',
        'b_vote_points',
        'b_total_points',
        'b_all_points',
        'M',
        'm_auto_points',
        'm_vote_points',
        'm_total_points',
        'm_all_points',
        'T',
        't_auto_points',
        't_vote_points',
        't_total_points',
        't_all_points',
        'source_type',
        'is_current',
        'kind',
    ];

    protected $casts = [
        'susdat_substance_id' => 'integer',
        'editor_user_id' => 'integer',
        'p_auto_points' => 'integer',
        'p_vote_points' => 'integer',
        'p_total_points' => 'integer',
        'p_all_points' => 'integer',
        'b_auto_points' => 'integer',
        'b_vote_points' => 'integer',
        'b_total_points' => 'integer',
        'b_all_points' => 'integer',
        'm_auto_points' => 'integer',
        'm_vote_points' => 'integer',
        'm_total_points' => 'integer',
        'm_all_points' => 'integer',
        't_auto_points' => 'integer',
        't_vote_points' => 'integer',
        't_total_points' => 'integer',
        't_all_points' => 'integer',
        'is_current' => 'boolean',
    ];

    public function substance()
    {
        return $this->belongsTo(Substance::class, 'susdat_substance_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_user_id');
    }

    public function supports()
    {
        return $this->hasMany(ClassificationSupport::class, 'substance_classification_id');
    }
}
