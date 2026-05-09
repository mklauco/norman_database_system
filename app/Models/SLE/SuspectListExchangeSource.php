<?php

namespace App\Models\SLE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuspectListExchangeSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'link_full_list',
        'link_inchikey_list',
        'link_references',
    ];

    // public function getNameAttribute($value)
    // {
    //     return $this->attributes['code']. '-'. $this->attributes['name'];
    // }

    /**
     * Get sanitized name for safe display in JavaScript and Blade templates
     * Strips newlines, quotes, apostrophes, and other dangerous characters
     */
    public function getSanitizedNameAttribute()
    {
        if (! $this->name) {
            return '';
        }

        return preg_replace('/[\r\n\t\'"`<>]/', '', trim($this->name));
    }
}
