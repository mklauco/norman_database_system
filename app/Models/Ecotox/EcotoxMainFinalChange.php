<?php

namespace App\Models\Ecotox;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcotoxMainFinalChange extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecotox_main_3_changes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'column_name',
        'user_id',
        'change_date',
        'ecotox_id',
        'change_old',
        'change_new',
        'change_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'change_date' => 'datetime',
        'change_type' => 'integer',
    ];

    /**
     * Get the user who made the change.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the ecotox record that was changed.
     */
    public function ecotox()
    {
        return $this->belongsTo(EcotoxFinal::class, 'ecotox_id', 'ecotox_id');
    }
}
