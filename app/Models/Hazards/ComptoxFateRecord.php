<?php

namespace App\Models\Hazards;

use Illuminate\Database\Eloquent\Model;

class ComptoxFateRecord extends Model
{
    protected $table = 'hazards_comptox_fate_records';

    protected $fillable = [
        'parse_run_id',
        'comptox_payload_id',
        'susdat_substance_id',
        'fate_id',
        'dtxid',
        'endpoint_name',
        'result_value',
        'model_source',
        'unit',
        'value_type',
        'source_json',
    ];

    protected $casts = [
        'parse_run_id' => 'integer',
        'comptox_payload_id' => 'integer',
        'susdat_substance_id' => 'integer',
        'source_json' => 'array',
    ];

    public function parseRun()
    {
        return $this->belongsTo(ParseRun::class, 'parse_run_id');
    }

    public function payload()
    {
        return $this->belongsTo(ComptoxPayload::class, 'comptox_payload_id');
    }
}

