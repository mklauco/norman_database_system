<?php

namespace App\Models\Ecotox;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Susdat\Substance;

class LowestPNECMain extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecotox_lowestpnec_main';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'lowest_id',
        'lowest_matrix',
        'sus_id',
        'der_id',
        'norman_pnec_id',
        'lowesta_id',
        'lowest_pnec_type',
        'lowest_institution',
        'lowest_test_endpoint',
        'lowest_AF',
        'lowest_pnec_value',
        'lowest_derivation_method',
        'lowest_editor',
        'lowest_active',
        'lowest_color',
        'lowest_year',
        'lowest_pnec',
        'lowest_base_name',
        'lowest_base_id',
        'lowest_sum_vote',
        'sus_id_origin',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'lowest_id' => 'integer',
        'sus_id' => 'integer',
        'lowest_AF' => 'integer',
        'lowest_pnec_value' => 'float',
        'lowest_editor' => 'integer',
        'lowest_active' => 'boolean',
        'lowest_color' => 'boolean',
        'lowest_year' => 'datetime',
        'lowest_pnec' => 'boolean',
        'lowest_sum_vote' => 'integer',
        'sus_id_origin' => 'integer',
    ];

    /**
     * Get the substance that owns the lowest PNEC main record.
     */
    public function substance()
    {
        return $this->belongsTo(Substance::class, 'sus_id', 'id');
    }

    /**
     * Get the origin substance that owns the lowest PNEC main record.
     */
    public function originSubstance()
    {
        return $this->belongsTo(Substance::class, 'sus_id_origin', 'id');
    }
}