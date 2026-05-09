<?php

namespace App\Models\Ecotox;

use App\Models\Susdat\Substance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EcotoxSubstanceDistinctPnec3 extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecotox_pnec3_substance_distinct';

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
     * Get all PNEC3 records for this substance
     */
    public function pnec3Records()
    {
        return $this->hasMany(PNEC3::class, 'substance_id', 'substance_id');
    }

    /**
     * Refresh the record counts from the PNEC3 table
     * Optimized version using a single update query
     */
    public static function refreshRecordCounts()
    {
        // Use raw SQL for better performance with large datasets
        $affectedRows = DB::update('
            UPDATE ecotox_pnec3_substance_distinct AS epsd
            INNER JOIN (
                SELECT substance_id, COUNT(*) as count
                FROM pnec3
                WHERE substance_id IS NOT NULL
                GROUP BY substance_id
            ) AS counts ON epsd.substance_id = counts.substance_id
            SET epsd.record_count = counts.count,
                epsd.updated_at = ?
        ', [Carbon::now()]);

        return $affectedRows;
    }

    /**
     * Check for new substances in the PNEC3 table and add them
     * OPTIMIZED VERSION - Uses bulk insert instead of individual creates
     */
    public static function syncNewSubstances()
    {
        $now = Carbon::now();

        // Option 1: Single INSERT INTO ... SELECT query (fastest)
        $insertedRows = DB::insert('
            INSERT INTO ecotox_pnec3_substance_distinct (substance_id, sus_id, record_count, created_at, updated_at)
            SELECT 
                p.substance_id,
                p.sus_id,
                COUNT(*) as record_count,
                ? as created_at,
                ? as updated_at
            FROM ecotox_pnec3 p
            WHERE p.substance_id IS NOT NULL
                AND NOT EXISTS (
                    SELECT 1 
                    FROM ecotox_pnec3_substance_distinct epsd 
                    WHERE epsd.substance_id = p.substance_id
                )
            GROUP BY p.substance_id, p.sus_id
        ', [$now, $now]);

        return $insertedRows;
    }

    /**
     * Alternative sync method using chunked processing for very large datasets
     * Use this if the above method causes memory issues
     */
    public static function syncNewSubstancesChunked($chunkSize = 1000)
    {
        $now = Carbon::now();
        $totalInserted = 0;

        // Get existing substance_ids to avoid duplicates
        $existingIds = static::pluck('substance_id')->toArray();

        // Process in chunks to avoid memory issues
        PNEC3::selectRaw('substance_id, sus_id, COUNT(*) as record_count')
            ->whereNotNull('substance_id')
            ->whereNotIn('substance_id', $existingIds)
            ->groupBy(['substance_id', 'sus_id'])
            ->chunk($chunkSize, function ($substances) use ($now, &$totalInserted) {
                if ($substances->isEmpty()) {
                    return false;
                }

                $data = $substances->map(function ($substance) use ($now) {
                    return [
                        'substance_id' => $substance->substance_id,
                        'sus_id' => $substance->sus_id,
                        'record_count' => $substance->record_count,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->toArray();

                // Bulk insert
                DB::table('ecotox_pnec3_substance_distinct')->insert($data);
                $totalInserted += count($data);
            });

        return $totalInserted;
    }

    /**
     * Combined sync and refresh operation
     * This is more efficient than running them separately
     */
    public static function fullSync()
    {
        DB::beginTransaction();

        try {
            // First, add new substances
            $newSubstances = static::syncNewSubstances();

            // Then, refresh counts for existing ones
            $updatedCounts = static::refreshRecordCounts();

            // Also update counts for substances that might have been deleted from PNEC3
            static::whereNotIn('substance_id', function ($query) {
                $query->select('substance_id')
                    ->from('pnec3')
                    ->whereNotNull('substance_id');
            })->update(['record_count' => 0]);

            DB::commit();

            return [
                'new_substances' => $newSubstances,
                'updated_counts' => $updatedCounts,
            ];
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get substances with zero records (possibly deleted from PNEC3)
     */
    public function scopeWithZeroRecords($query)
    {
        return $query->where('record_count', 0);
    }

    /**
     * Clean up substances that no longer exist in PNEC3
     */
    public static function cleanupOrphanedSubstances()
    {
        return static::whereNotIn('substance_id', function ($query) {
            $query->select('substance_id')
                ->from('pnec3')
                ->whereNotNull('substance_id');
        })->delete();
    }
}
