<?php

namespace App\Models\List;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataSourceOrganisation extends Model
{
    use HasFactory;

    protected $table = 'list_data_source_organisations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'city',
        'acronym',
        'country_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'country_id' => 'integer',
    ];

    /**
     * Get the country that this organisation belongs to.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Get the full organisation name with city, acronym and country.
     */
    public function getFullNameAttribute()
    {
        $parts = [];

        if ($this->name) {
            $parts[] = $this->name;
        }

        if ($this->acronym) {
            $parts[] = "({$this->acronym})";
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

    /**
     * Scope to filter by acronym.
     */
    public function scopeByAcronym($query, $acronym)
    {
        return $query->where('acronym', 'like', "%{$acronym}%");
    }
}
