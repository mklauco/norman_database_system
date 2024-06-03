<?php

namespace App\Models\Susdat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Substance extends Model
{
    use HasFactory;

    protected $table = 'susdat_substances';

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'susdat_category_substance', 'substance_id', 'category_id');
    }
}
