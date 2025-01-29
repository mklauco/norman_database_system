<?php

namespace App\Models\Empodat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataCollectionFileUpload extends Model
{
    use HasFactory;

    protected $table = 'data_collection_file_uploads';

    protected $fillable = [
        'path',
        'filename',
        'database_entity_id',
        'data_collection_template_id',
        'is_public',
    ];


}
