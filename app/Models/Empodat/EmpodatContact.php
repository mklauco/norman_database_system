<?php

declare(strict_types=1);

namespace App\Models\Empodat;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpodatContact extends Model
{
    protected $table = 'empodat_contacts';

    protected $fillable = [
        'first_name',
        'family_name',
        'email',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn (): string => trim(implode(' ', array_filter([$this->first_name, $this->family_name], static fn (?string $part): bool => $part !== null && trim($part) !== '')))
        );
    }
}
