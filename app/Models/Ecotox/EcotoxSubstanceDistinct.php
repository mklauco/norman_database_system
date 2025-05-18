<?php

namespace App\Models\Ecotox;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Susdat\Substance;

class EcotoxSubstanceDistinct extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecotox_main_3_substance_distinct';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'substance_id',
        'sus_id',
        'record_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'substance_id' => 'integer',
        'sus_id' => 'integer',
        'record_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the substance associated with this record
     */
    public function substance()
    {
        return $this->belongsTo(Substance::class, 'substance_id');
    }

    /**
     * Get all ecotox records for this substance
     */
    public function ecotoxRecords()
    {
        return $this->hasMany(EcotoxPrimary::class, 'substance_id', 'substance_id');
    }

    /**
     * Refresh the record counts from the main ecotox table
     */
    public static function refreshRecordCounts()
    {
        // Get counts from the main table
        $counts = EcotoxPrimary::selectRaw('substance_id, COUNT(*) as count')
            ->whereNotNull('substance_id')
            ->groupBy('substance_id')
            ->pluck('count', 'substance_id');
        
        // Update each record
        foreach ($counts as $substanceId => $count) {
            static::where('substance_id', $substanceId)
                ->update(['record_count' => $count]);
        }

        return count($counts);
    }

    /**
     * Check for new substances in the main table and add them
     */
    public static function syncNewSubstances()
    {
        // Find substance_ids in main table not in distinct table
        $substanceIds = EcotoxPrimary::selectRaw('
                substance_id, 
                sus_id,
                COUNT(*) as record_count
            ')
            ->whereNotNull('substance_id')
            ->whereNotIn('substance_id', function($query) {
                $query->select('substance_id')
                    ->from('ecotox_main_3_substance_distinct');
            })
            ->groupBy(['substance_id', 'sus_id'])
            ->get();
        
        // Add any new substances
        foreach ($substanceIds as $substance) {
            static::create([
                'substance_id' => $substance->substance_id,
                'sus_id' => $substance->sus_id,
                'record_count' => $substance->record_count
            ]);
        }

        return count($substanceIds);
    }
}