<?php

namespace App\Models\Empodat;

use App\Models\Empodat\DCTFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
