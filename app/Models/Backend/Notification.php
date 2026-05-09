<?php

namespace App\Models\Backend;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'title',
        'message',
        'start_datetime',
        'end_datetime',
        'is_active',
        'created_by_user_id',
        'turned_off_datetime',
        'turned_off_by_user_id',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'turned_off_datetime' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function turnedOffBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'turned_off_by_user_id');
    }

    public function scopeActive($query)
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where('start_datetime', '<=', $now)
            ->where('end_datetime', '>=', $now)
            ->whereNull('turned_off_datetime');
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active || $this->turned_off_datetime) {
            return false;
        }

        $now = Carbon::now();

        return $this->start_datetime <= $now && $this->end_datetime >= $now;
    }

    public function turnOff(int $userId): void
    {
        $this->update([
            'turned_off_datetime' => Carbon::now(),
            'turned_off_by_user_id' => $userId,
        ]);
    }

    /**
     * Get start datetime in Central European Time for display
     */
    public function getStartDatetimeCetAttribute(): string
    {
        return $this->start_datetime->setTimezone('Europe/Prague')->format('Y-m-d\TH:i');
    }

    /**
     * Get end datetime in Central European Time for display
     */
    public function getEndDatetimeCetAttribute(): string
    {
        return $this->end_datetime->setTimezone('Europe/Prague')->format('Y-m-d\TH:i');
    }

    /**
     * Get start datetime formatted for display in CET
     */
    public function getStartDatetimeFormattedAttribute(): string
    {
        return $this->start_datetime->setTimezone('Europe/Prague')->format('Y-m-d H:i');
    }

    /**
     * Get end datetime formatted for display in CET
     */
    public function getEndDatetimeFormattedAttribute(): string
    {
        return $this->end_datetime->setTimezone('Europe/Prague')->format('Y-m-d H:i');
    }

    /**
     * Get turned off datetime formatted for display in CET
     */
    public function getTurnedOffDatetimeFormattedAttribute(): ?string
    {
        return $this->turned_off_datetime
            ? $this->turned_off_datetime->setTimezone('Europe/Prague')->format('Y-m-d H:i')
            : null;
    }

    /**
     * Get created at formatted for display in CET
     */
    public function getCreatedAtFormattedAttribute(): string
    {
        return $this->created_at->setTimezone('Europe/Prague')->format('Y-m-d H:i');
    }
}
