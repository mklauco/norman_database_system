<?php

namespace App\Models\Ecotox;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Susdat\Substance;

class LowestPNEC extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecotox_lowest_pnec';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sus_id',
        'substance_id',
        'lowest_pnec_value_1',
        'lowest_pnec_value_2',
        'lowest_pnec_value_3',
        'lowest_pnec_value_4',
        'lowest_pnec_value_5',
        'lowest_pnec_value_6',
        'lowest_pnec_value_7',
        'lowest_pnec_value_8',
        'lowest_exp_pred',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'sus_id' => 'integer',
        'substance_id' => 'integer',
        'lowest_pnec_value_1' => 'float',
        'lowest_pnec_value_2' => 'float',
        'lowest_pnec_value_3' => 'float',
        'lowest_pnec_value_4' => 'float',
        'lowest_pnec_value_5' => 'float',
        'lowest_pnec_value_6' => 'float',
        'lowest_pnec_value_7' => 'float',
        'lowest_pnec_value_8' => 'float',
        'lowest_exp_pred' => 'boolean',
    ];

    /**
     * Get the substance that owns the lowest PNEC through sus_id.
     */
    public function substanceBySusId()
    {
        return $this->belongsTo(Substance::class, 'sus_id', 'id');
    }
    
    /**
     * Get the substance that owns the lowest PNEC through substance_id.
     */
    public function substance()
    {
        return $this->belongsTo(Substance::class, 'substance_id');
    }

    /**
     * Get the PNEC3 record associated with this record.
     * 
     * RelationInfo: ecotox_lowestpnec_main.lowest_base_id = ecotox_pnec3.norman_pnec_id
     */
    public function pnec3()
    {
        return $this->belongsTo(PNEC3::class, 'lowest_base_id', 'norman_pnec_id');
    }

    /**
     * Get the editor (user) associated with this record.
     * 
     * RelationInfo: ecotox_lowestpnec_main.lowest_editor = users.id
     */
    public function editor()
    {
        return $this->belongsTo(\App\Models\User::class, 'lowest_editor', 'id');
    }
}