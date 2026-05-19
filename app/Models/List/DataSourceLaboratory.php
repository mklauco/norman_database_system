<?php

namespace App\Models\List;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataSourceLaboratory extends Model
{
    use HasFactory;

    protected $table = 'list_data_source_laboratories';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'city',
        'country_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'country_id' => 'integer',
    ];

    /**
     * Get the country that this laboratory belongs to.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Get the full laboratory name with city and country.
     */
    public function getFullNameAttribute()
    {
        $parts = [];

        if ($this->name) {
            $parts[] = $this->name;
        }

        if ($this->city) {
            $parts[] = $this->city;
        }

        if ($this->country) {
            $parts[] = $this->country->name;
        }

        return implode(', ', $parts);
    }

    /**
     * Scope to filter by country.
     */
    public function scopeByCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope to filter by city.
     */
    public function scopeByCity($query, $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }
}
