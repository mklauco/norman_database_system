<?php

namespace App\Models\Empodat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataCollectionTemplate extends Model
{
    use HasFactory;

    protected $table = 'data_collection_templates';

    protected $fillable = [
        'name',
        'description',
        'database_entity_id',
    ];

    public function files()
    {
        return $this->hasMany(DataCollectionTemplateFile::class, 'data_collection_template_id', 'id')->orderBy('updated_at', 'desc');
    }
}
