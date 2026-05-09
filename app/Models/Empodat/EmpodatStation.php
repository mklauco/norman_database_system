<?php

namespace App\Models\Empodat;

use App\Models\List\Country;
use Illuminate\Database\Eloquent\Model;

class EmpodatStation extends Model
{
    //
    protected $table = 'empodat_stations';

    protected $fillable = [
        'name',
        'country_id',
        'country_other_id',
        'country',
        'country_other',
        'national_name',
        'short_sample_code',
        'sample_code',
        'provider_code',
        'code_ec_wise',
        'code_ec_other',
        'code_other',
        'specific_locations',
        'latitude',
        'longitude',
        'is_deprecated',
    ];

    protected $casts = [
        'is_deprecated' => 'boolean',
    ];

    public function countryRelation()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function countryOtherRelation()
    {
        return $this->belongsTo(Country::class, 'country_other_id');
    }

    /**
     * Scope to only include active (non-deprecated) stations
     */
    public function scopeActive($query)
    {
        return $query->where('is_deprecated', false);
    }

    /**
     * Scope to only include deprecated stations
     */
    public function scopeDeprecated($query)
    {
        return $query->where('is_deprecated', true);
    }

    /**
     * Get all merge logs where this station is canonical (absorbed duplicates)
     */
    public function mergedDuplicates()
    {
        return $this->hasMany(StationMergeLog::class, 'canonical_station_id');
    }

    /**
     * Get merge log if this station was deprecated (merged into another)
     */
    public function mergeRecord()
    {
        return $this->hasOne(StationMergeLog::class, 'deprecated_station_id');
    }

    /**
     * Check if this station was merged into another
     */
    public function isMerged()
    {
        return $this->mergeRecord()->exists();
    }

    /**
     * Get the canonical station (if this was merged, return the canonical; otherwise self)
     */
    public function getCanonicalStation()
    {
        if ($mergeRecord = $this->mergeRecord) {
            return $mergeRecord->canonicalStation;
        }

        return $this;
    }
}
