<?php

declare(strict_types=1);

namespace App\Models\EmpodatSuspect;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One execution attempt of an allowlisted `empodat-suspect:*` artisan
 * command, queued and run by App\Jobs\EmpodatSuspect\RunEmpodatSuspectCommandJob.
 */
class EmpodatSuspectCommandRun extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUSED = 'refused';

    /**
     * Statuses that mean "still in flight" — used to guard against
     * overlapping runs from the UI.
     *
     * @var array<int, string>
     */
    public const ACTIVE_STATUSES = [self::STATUS_QUEUED, self::STATUS_RUNNING];

    /**
     * The table associated with the model.
     */
    protected $table = 'empodat_suspect_command_runs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'command_key',
        'arguments',
        'user_id',
        'queued_at',
        'started_at',
        'finished_at',
        'duration_ms',
        'exit_code',
        'status',
        'output',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'arguments' => 'array',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_ms' => 'integer',
            'exit_code' => 'integer',
        ];
    }

    /**
     * The user who triggered this run, if it was triggered interactively.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this run is still queued or currently running.
     */
    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    /**
     * Human-readable label for the command, from the allowlist config.
     */
    public function label(): string
    {
        return (string) config("empodat_suspect_commands.commands.{$this->command_key}.label", $this->command_key);
    }
}
