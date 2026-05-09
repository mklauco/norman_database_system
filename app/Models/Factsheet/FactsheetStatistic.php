<?php

namespace App\Models\Factsheet;

use App\Models\Susdat\Substance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactsheetStatistic extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'factsheet_substance_statistics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'substance_id',
        'meta_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta_data' => 'array',
    ];

    /**
     * Get the substance that owns the statistic.
     */
    public function substance()
    {
        return $this->belongsTo(Substance::class);
    }
}
