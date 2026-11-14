<?php

declare(strict_types=1);

namespace App\Models\Hazards;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function parseRun(): BelongsTo
    {
        return $this->belongsTo(ParseRun::class, 'parse_run_id');
    }

    public function payload(): BelongsTo
    {
        return $this->belongsTo(ComptoxPayload::class, 'comptox_payload_id');
    }
}
