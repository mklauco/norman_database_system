<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;

class ServerPayment extends Model
{
    protected $table = 'server_payment_main';

    protected $fillable = [
        'period_start_date',
        'period_end_date',
        'status',
        'amount_without_vat',
        'variable_symbol',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'amount_without_vat' => 'decimal:2',
    ];

    public function getFormattedPeriodAttribute(): string
    {
        $start = $this->period_start_date?->format('Y-m-d');
        $end = $this->period_end_date?->format('Y-m-d');

        return trim(($start ?? '').' → '.($end ?? ''));
    }
}
