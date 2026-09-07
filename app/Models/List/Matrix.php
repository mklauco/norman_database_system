<?php

namespace App\Models\List;

use App\Services\Empodat\EmpodatRecordDisplay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matrix extends Model
{
    use HasFactory;

    protected $table = 'list_matrices';

    protected $fillable = [
        'title',
        'subtitle',
        'type',
        'name',
        'dct_name',
        'unit',
        'empodat_matrix_link',
    ];

    /**
     * Attributes appended to the array / JSON form of the model.
     *
     * @var list<string>
     */
    protected $appends = ['display_unit'];

    /**
     * The unit as plain text.
     *
     * `unit` is stored as HTML ("µg/m<sup>3</sup>"), which every consumer
     * renders escaped — so the markup shows up verbatim next to the value.
     */
    public function getDisplayUnitAttribute(): ?string
    {
        return EmpodatRecordDisplay::plainUnit($this->unit);
    }
}
