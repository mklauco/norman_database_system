<?php

namespace App\Models\Empodat;

use Illuminate\Database\Eloquent\Model;

class DataSources extends Model
{
    //
    protected $table = 'empodat_data_sources';

    protected $fillable = [
        'id',
        'type_data_source_id',
        'type_data_source_other',
        'type_monitoring_id',
        'type_monitoring_other',
        'data_accessibility_id',
        'data_accessibility_other',
        'project_title',
        'organisation_id',
        'laboratory1_id',
        'laboratory2_id',
        'author',
        'email',
        'reference1',
        'reference2',
        'created_at',
        'updated_at',
    ];
}
