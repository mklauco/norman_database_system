<?php

declare(strict_types=1);

namespace App\Models\Hazards;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiRun extends Model
{
    protected $table = 'hazards_api_runs';

    protected $fillable = [
        'trigger',
        'status',
        'total_dtxids',
        'processed_dtxids',
        'successful_dtxids',
        'failed_dtxids',
        'new_payloads',
        'updated_payloads',
        'failed_endpoints',
        'duration_seconds',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected $casts = [
        'total_dtxids' => 'integer',
        'processed_dtxids' => 'integer',
        'successful_dtxids' => 'integer',
        'failed_dtxids' => 'integer',
        'new_payloads' => 'integer',
        'updated_payloads' => 'integer',
        'failed_endpoints' => 'array',
        'duration_seconds' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function payloads(): HasMany
    {
        return $this->hasMany(ComptoxPayload::class, 'api_run_id');
    }

    public function parseRuns(): HasMany
    {
        return $this->hasMany(ParseRun::class, 'source_api_run_id');
    }
}
