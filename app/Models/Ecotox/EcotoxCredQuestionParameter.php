<?php

namespace App\Models\Ecotox;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcotoxCredQuestionParameter extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecotox_cred_question_parameters';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'question_id',
        'ecotox_config_id',
        'parameter_label',
        'is_required',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'question_id' => 'integer',
        'ecotox_config_id' => 'integer',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the question this parameter belongs to.
     */
    public function question()
    {
        return $this->belongsTo(EcotoxCredQuestion::class, 'question_id');
    }

    /**
     * Get the ecotox config this parameter maps to.
     */
    public function ecotoxConfig()
    {
        return $this->belongsTo(EcotoxComparativeTableConfig::class, 'ecotox_config_id');
    }

    /**
     * Get the input type from the ecotox config.
     *
     * @return string|null
     */
    public function getInputTypeAttribute()
    {
        return $this->ecotoxConfig ? $this->ecotoxConfig->input_type : null;
    }

    /**
     * Get the column name from the ecotox config.
     *
     * @return string|null
     */
    public function getColumnNameAttribute()
    {
        return $this->ecotoxConfig ? $this->ecotoxConfig->column_name : null;
    }

    /**
     * Check if this parameter is editable.
     *
     * @return bool
     */
    public function getIsEditableAttribute()
    {
        return $this->ecotoxConfig ? $this->ecotoxConfig->is_editable : false;
    }

    /**
     * Get the description from the ecotox config.
     *
     * @return string|null
     */
    public function getDescriptionAttribute()
    {
        return $this->ecotoxConfig ? $this->ecotoxConfig->description : null;
    }

    /**
     * Scope to get only required parameters.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope to get only optional parameters.
     */
    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }
}
