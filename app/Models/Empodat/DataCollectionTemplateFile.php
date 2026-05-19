<?php

namespace App\Models\Empodat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataCollectionTemplateFile extends Model
{
    use HasFactory;

    protected $table = 'data_collection_template_files';

    protected $fillable = [
        'data_collection_template_id',
        'path',
        'filename',
    ];
}
