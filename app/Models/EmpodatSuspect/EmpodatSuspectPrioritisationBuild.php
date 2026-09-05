<?php

declare(strict_types=1);

namespace App\Models\EmpodatSuspect;

use Illuminate\Database\Eloquent\Model;

/**
 * One rebuild of a single `empodat_suspect_prioritisation_dataset` partition,
 * written by App\Console\Commands\RefreshEmpodatSuspectPrioritisation.
 *
 * A materialized view is self-evidently a snapshot; a partitioned table is
 * not. These rows are the only record of when a partition was last built and
 * how many rows it holds.
 *
 * A row can stay {@see STATUS_RUNNING} forever: the command writes its
 * terminal status from PHP, so a process killed mid-build (OOM, container
 * restart) never records one. Treat a long-running row as unfinished, not as
 * in progress.
 */
class EmpodatSuspectPrioritisationBuild extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    /**
     * The table associated with the model.
     */
    protected $table = 'empodat_suspect_prioritisation_builds';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_id',
        'started_at',
        'finished_at',
        'duration_ms',
        'row_count',
        'status',
        'error',
        'triggered_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_id' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_ms' => 'integer',
            'row_count' => 'integer',
        ];
    }

    /**
     * Whether this build completed and populated its partition.
     */
    public function succeeded(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }
}
