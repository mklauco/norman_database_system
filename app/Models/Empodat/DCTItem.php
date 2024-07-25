<?php

namespace App\Models\Empodat;

use App\Models\Empodat\DCTFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DCTItem extends Model
{
    use HasFactory;

    protected $table = 'dct_items';

    public function files()
    {
        return $this->hasMany(DCTFile::class, 'dct_item_id', 'id')->orderBy('updated_at', 'desc');
    }
}
