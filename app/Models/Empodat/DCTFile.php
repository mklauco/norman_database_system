<?php

namespace App\Models\Empodat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DCTFile extends Model
{
    use HasFactory;

    protected $table = 'dct_files';

    protected $fillable = [
        'dct_item_id',
        'path',
        'filename',
    ];
}
