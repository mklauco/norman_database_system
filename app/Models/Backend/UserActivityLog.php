<?php

declare(strict_types=1);

namespace App\Models\Backend;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    public $timestamps = false;

    protected $table = 'user_activity_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'occurred_at',
        'channel',
        'module',
        'action',
        'route_name',
        'method',
        'path',
        'status',
        'duration_ms',
        'token_id',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'meta' => 'array',
            'status' => 'integer',
            'duration_ms' => 'integer',
            'token_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
