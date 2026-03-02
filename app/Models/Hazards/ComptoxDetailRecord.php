<?php

namespace App\Models\Hazards;

use Illuminate\Database\Eloquent\Model;

class ComptoxDetailRecord extends Model
{
    protected $table = 'hazards_comptox_detail_records';

    protected $fillable = [
        'parse_run_id',
        'comptox_payload_id',
        'susdat_substance_id',
        'dtxid',
        'preferred_name',
        'casrn',
        'inchikey',
        'smiles',
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

