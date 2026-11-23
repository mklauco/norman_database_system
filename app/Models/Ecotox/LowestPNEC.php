<?php

namespace App\Models\Ecotox;

use App\Models\Susdat\Substance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'lowest_exp_pred' => 'integer',
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
     * Filters by Norman SusDat ID, substance name and CAS No., matched like the legacy Lowest PNECs page.
     *
     * - SusDat ID: exact, "NS" prefix and leading zeros optional (NS00000060, 00000060 and 60 are the same).
     *   It is compared with `sus_id`, which holds the numeric SusDat code.
     * - Substance name: full or partial, case-insensitive.
     * - CAS No.: exact, an optional "CAS_RN:" prefix is ignored.
     *
     * Empty values are ignored; the filters given are combined with AND.
     *
     * @param  Builder<LowestPNEC>  $query
     * @return Builder<LowestPNEC>
     */
    public function scopeSearchSubstance(Builder $query, ?string $susId, ?string $name, ?string $cas): Builder
    {
        $susId = trim((string) $susId);
        if ($susId !== '') {
            $digits = preg_replace('/^NS/i', '', $susId) ?? '';
            if (preg_match('/^\d{1,8}$/', $digits) === 1) {
                $query->where('sus_id', (int) $digits);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $name = trim((string) $name);
        if ($name !== '') {
            $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $name).'%';
            $query->whereHas('substance', function (Builder $substance) use ($pattern): void {
                $substance->where('name', 'ilike', $pattern);
            });
        }

        $cas = trim((string) (preg_replace('/^\s*CAS_RN:/i', '', (string) $cas) ?? ''));
        if ($cas !== '') {
            $query->whereHas('substance', function (Builder $substance) use ($cas): void {
                $substance->whereRaw('TRIM(cas_number) = ?', [$cas]);
            });
        }

        return $query;
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
