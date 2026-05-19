<?php

namespace App\Models\Susdat;

use Illuminate\Database\Eloquent\Model;

class UsepaCategories extends Model
{
    protected $table = 'susdat_usepa_categories';

    protected $fillable = [
        'sus_id',
        'substance_id',
        'category_name',
    ];

    public $timestamps = false;
}
