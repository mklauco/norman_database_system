<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\DatabaseEntity;
use App\Models\User;

class Template extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'version',
        'database_entity_id',
        'file_path',
        'is_active',
        'valid_from',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'database_entity_id' => 'integer',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'valid_from' => 'date',
    ];

    /**
     * Get the database entity that owns the template.
     */
    public function databaseEntity(): BelongsTo
    {
        return $this->belongsTo(DatabaseEntity::class, 'database_entity_id');
    }

    /**
     * Get the user who created the template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Get the user who last updated the template.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the files associated with this template.
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }
}