<?php

namespace App\Models\Ecotox;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcotoxCredQuestion extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecotox_cred_questions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question_number',
        'question_letter',
        'question_text',
        'parent_id',
        'max_score',
        'screening_score',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'question_number' => 'integer',
        'parent_id' => 'integer',
        'max_score' => 'decimal:2',
        'screening_score' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Get the parent question (for sub-questions).
     */
    public function parent()
    {
        return $this->belongsTo(EcotoxCredQuestion::class, 'parent_id');
    }

    /**
     * Get the sub-questions (for main questions).
     */
    public function subQuestions()
    {
        return $this->hasMany(EcotoxCredQuestion::class, 'parent_id');
    }

    /**
     * Get the parameters for this question.
     */
    public function parameters()
    {
        return $this->hasMany(EcotoxCredQuestionParameter::class, 'question_id');
    }

    /**
     * Scope to get only main questions.
     */
    public function scopeMainQuestions($query)
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
    }

    /**
     * Scope to get only sub-questions.
     */
    public function scopeSubQuestions($query)
    {
        return $query->whereNotNull('parent_id')->orderBy('sort_order');
    }

    /**
     * Get the formatted question identifier (e.g., "1", "1a", "2b").
     *
     * @return string
     */
    public function getFormattedNumberAttribute()
    {
        if ($this->question_letter) {
            return $this->question_number.$this->question_letter;
        }

        return (string) $this->question_number;
    }

    /**
     * Get the full question label (number + text).
     *
     * @return string
     */
    public function getFullLabelAttribute()
    {
        return $this->formatted_number.'. '.$this->question_text;
    }

    /**
     * Check if this is a main question.
     *
     * @return bool
     */
    public function getIsMainQuestionAttribute()
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if this is a sub-question.
     *
     * @return bool
     */
    public function getIsSubQuestionAttribute()
    {
        return ! is_null($this->parent_id);
    }

    /**
     * Get all questions with their sub-questions for display.
     */
    public static function getQuestionsHierarchy()
    {
        return self::mainQuestions()
            ->with(['subQuestions.parameters.ecotoxConfig'])
            ->get();
    }
}
