<?php

namespace App\Models\Hazards;

use Illuminate\Database\Eloquent\Model;

class ParseRun extends Model
{
    protected $table = 'hazards_parse_runs';

    protected $fillable = [
        'source_api_run_id',
        'trigger',
        'status',
        'total_payloads',
        'processed_payloads',
        'successful_payloads',
        'failed_payloads',
        'new_records',
        'updated_records',
        'unchanged_records',
        'counts_by_type',
        'duration_seconds',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected $casts = [
        'source_api_run_id' => 'integer',
        'total_payloads' => 'integer',
        'processed_payloads' => 'integer',
        'successful_payloads' => 'integer',
        'failed_payloads' => 'integer',
        'new_records' => 'integer',
        'updated_records' => 'integer',
        'unchanged_records' => 'integer',
        'counts_by_type' => 'array',
        'duration_seconds' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function sourceApiRun()
    {
        return $this->belongsTo(ApiRun::class, 'source_api_run_id');
    }

    public function fateRecords()
    {
        return $this->hasMany(ComptoxFateRecord::class, 'parse_run_id');
    }

    public function propertyRecords()
    {
        return $this->hasMany(ComptoxPropertyRecord::class, 'parse_run_id');
    }

    public function detailRecords()
    {
        return $this->hasMany(ComptoxDetailRecord::class, 'parse_run_id');
    }
}

