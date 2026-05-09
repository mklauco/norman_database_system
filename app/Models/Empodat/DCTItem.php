<?php

namespace App\Models\Empodat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DCTItem extends Model
{
    use HasFactory;

    protected $table = 'dct_items';

    protected $fillable = [
        'name',
        'description',
        'created_at',
        'updated_at',
    ];

    public function files()
    {
        return $this->hasMany(DCTFile::class, 'dct_item_id', 'id')->orderBy('updated_at', 'desc');
    }
}
