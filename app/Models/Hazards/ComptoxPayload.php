<?php

declare(strict_types=1);

namespace App\Models\Hazards;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComptoxPayload extends Model
{
    protected $table = 'hazards_comptox_payloads';

    protected $fillable = [
        'api_run_id',
        'susdat_substance_id',
        'dtxid',
        'fate',
        'detail',
        'property',
        'synonym',
        'fetched_at',
        'endpoint_status',
    ];

    protected $casts = [
        'api_run_id' => 'integer',
        'susdat_substance_id' => 'integer',
        'fate' => 'array',
        'detail' => 'array',
        'property' => 'array',
        'synonym' => 'array',
        'fetched_at' => 'datetime',
        'endpoint_status' => 'array',
    ];

    public function apiRun(): BelongsTo
    {
        return $this->belongsTo(ApiRun::class, 'api_run_id');
    }

    public function fateRecords(): HasMany
    {
        return $this->hasMany(ComptoxFateRecord::class, 'comptox_payload_id');
    }

    public function propertyRecords(): HasMany
    {
        return $this->hasMany(ComptoxPropertyRecord::class, 'comptox_payload_id');
    }

    public function detailRecords(): HasMany
    {
        return $this->hasMany(ComptoxDetailRecord::class, 'comptox_payload_id');
    }
}
