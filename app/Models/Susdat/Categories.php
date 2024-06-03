<?php

namespace App\Models\Susdat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    use HasFactory;

    protected $table = 'susdat_categories';

    public function substances()
    {
        return $this->belongsToMany(Substances::class, 'susdat_category_substance', 'category_id', 'substance_id');
    }
}
