<?php

namespace App\Models\Empodat;

use App\Models\List\Matrix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SearchMatrix extends Model
{
    use HasFactory;

    protected $table = 'empodat_search_matrices';

    public function matrix(){
        return $this->hasOne(Matrix::class, 'id', 'matrix_id');
      }
}
