<?php

namespace App\Models\Backend;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ExportDownload extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'filename',
        'format',
        'ip_address',
        'user_agent',
        'record_count',
        'database_key',
        'status',
        'message',
        'file_size_bytes',
        'file_size_formatted',
        'processing_time_seconds',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'processing_time_seconds' => 'decimal:2',
        'file_size_bytes' => 'integer',
    ];

    /**
     * Get the user that initiated the download.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the query logs associated with this download.
     */
    public function queryLogs(): BelongsToMany
    {
        return $this->belongsToMany(QueryLog::class, 'export_download_query_log');
    }

    /**
     * Get created_at in Central European Time (Bratislava timezone)
     */
    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->timezone('Europe/Bratislava')->format('Y-m-d G:i:s');
    }

    /**
     * Get started_at in Central European Time (Bratislava timezone)
     */
    public function getStartedAtAttribute($value)
    {
        if (! $value) {
            return null;
        }

        return \Carbon\Carbon::parse($value)->timezone('Europe/Bratislava')->format('Y-m-d G:i:s');
    }

    /**
     * Get completed_at in Central European Time (Bratislava timezone)
     */
    public function getCompletedAtAttribute($value)
    {
        if (! $value) {
            return null;
        }

        return \Carbon\Carbon::parse($value)->timezone('Europe/Bratislava')->format('Y-m-d G:i:s');
    }

    /**
     * Get updated_at in Central European Time (Bratislava timezone)
     */
    public function getUpdatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->timezone('Europe/Bratislava')->format('Y-m-d G:i:s');
    }

    /**
     * Get the duration of the export process
     */
    public function getDurationAttribute(): ?string
    {
        // First try to use the stored processing_time_seconds for accuracy
        if ($this->processing_time_seconds) {
            $seconds = (float) $this->processing_time_seconds;
            if ($seconds < 60) {
                return $seconds.'s';
            } elseif ($seconds < 3600) {
                return round($seconds / 60, 1).'m';
            } else {
                return round($seconds / 3600, 1).'h';
            }
        }

        // Fall back to calculating from timestamps if processing_time_seconds is not available
        $startedAtRaw = $this->getOriginal('started_at') ?? $this->attributes['started_at'] ?? null;
        $completedAtRaw = $this->getOriginal('completed_at') ?? $this->attributes['completed_at'] ?? null;

        if ($startedAtRaw && $completedAtRaw) {
            try {
                $startedAt = \Carbon\Carbon::parse($startedAtRaw);
                $completedAt = \Carbon\Carbon::parse($completedAtRaw);
                $duration = abs($completedAt->diffInSeconds($startedAt));

                if ($duration < 60) {
                    return $duration.'s';
                } elseif ($duration < 3600) {
                    return round($duration / 60, 1).'m';
                } else {
                    return round($duration / 3600, 1).'h';
                }
            } catch (\Exception $e) {
                // If there's an error, return null
            }
        }

        return null;
    }

    /**
     * Get formatted file size or fall back to file_size_formatted field
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if ($this->file_size_formatted) {
            return $this->file_size_formatted;
        }

        if ($this->file_size_bytes) {
            return $this->formatBytes($this->file_size_bytes);
        }

        return null;
    }

    /**
     * Check if the download is ready for download
     */
    public function getIsDownloadableAttribute(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Format bytes to human-readable file size
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
