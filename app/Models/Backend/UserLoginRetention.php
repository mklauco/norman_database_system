<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginRetention extends Model
{
    use HasFactory;

    protected $table = 'user_login_retentions';

    protected $fillable = [
        'user_id',
        'ip_address',
        'login_datetime',
        'meta_data',
    ];

    protected $casts = [
        'login_datetime' => 'datetime',
        'meta_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function getFormattedLoginDateAttribute(): string
    {
        return $this->login_datetime->setTimezone('Europe/Bratislava')->format('Y-m-d H:i:s');
    }
}
