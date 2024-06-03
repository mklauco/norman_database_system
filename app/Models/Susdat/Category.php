<?php

namespace App\Models\Susdat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'susdat_categories';

    public function substances()
    {
        return $this->belongsToMany(Substance::class, 'susdat_category_substance', 'category_id', 'substance_id');
    }
}
