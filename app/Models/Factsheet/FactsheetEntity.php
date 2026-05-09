<?php

namespace App\Models\Factsheet;

use Illuminate\Database\Eloquent\Model;

class FactsheetEntity extends Model
{
    protected $table = 'factsheet_entities';

    protected $fillable = [
        'name',
        'sort_order',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}
