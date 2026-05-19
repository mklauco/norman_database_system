<?php

namespace App\Models\Ecotox;

use App\Models\Susdat\Substance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'lowest_active' => 'integer',
        'lowest_color' => 'integer',
        'lowest_year' => 'datetime',
        'lowest_pnec' => 'integer',
        'lowest_sum_vote' => 'integer',
        'sus_id_origin' => 'integer',
    ];

    /**
     * Get the substance that owns the lowest PNEC main record.
     */
    public function substance()
    {
        return $this->belongsTo(Substance::class, 'sus_id', 'code');
    }

    /**
     * Get the origin substance that owns the lowest PNEC main record.
     */
    public function originSubstance()
    {
        return $this->belongsTo(Substance::class, 'sus_id_origin', 'code');
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

    /**
     * Get the matrix type mapping.
     */
    public static function getMatrixTypes(): array
    {
        return [
            1 => 'freshwater',
            2 => 'marine water',
            3 => 'sediments',
            4 => 'biota',
        ];
    }

    /**
     * Get the matrix type name for the current record.
     */
    public function getMatrixTypeAttribute(): ?string
    {
        $matrixTypes = self::getMatrixTypes();

        return $matrixTypes[$this->lowest_matrix] ?? "Unknown ({$this->lowest_matrix})";
    }

    /**
     * Scope a query to filter by matrix type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|int  $matrixType  Matrix type name or ID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMatrixType($query, string|int $matrixType)
    {
        if (is_string($matrixType)) {
            // Convert string to ID (only for known mappings)
            $matrixTypes = array_flip(self::getMatrixTypes());
            $matrixId = $matrixTypes[strtolower($matrixType)] ?? null;

            if ($matrixId === null) {
                // Return empty result if invalid matrix type name
                return $query->whereRaw('1 = 0');
            }

            return $query->where('lowest_matrix', $matrixId);
        }

        // For integer values, allow any value (including those > 4)
        return $query->where('lowest_matrix', $matrixType);
    }

    /**
     * Get formatted date attribute.
     */
    public function getFormattedDateAttribute(): ?string
    {
        return $this->lowest_year ? $this->lowest_year->format('Y-m-d') : null;
    }

    /**
     * Get endpoint, duration, and effect formatted as a single field.
     */
    public function getEndpointDurationEffectAttribute(): string
    {
        $parts = [];

        if ($this->lowest_test_endpoint) {
            $parts[] = "Endpoint: {$this->lowest_test_endpoint}";
        }

        // Note: Duration and Effect fields may need to be added if they exist in the database
        // For now, only using endpoint which is available

        return implode(' | ', $parts) ?: 'N/A';
    }
}
