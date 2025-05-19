<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Backend\Template;
use App\Models\Backend\File;

class DatabaseEntity extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'database_entities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'image_path',
        'code',
        'dashboard_route_name',
        'last_update',
        'number_of_records',
        'parent_id',
        'show_in_dashboard',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'last_update' => 'datetime',
        'number_of_records' => 'integer',
        'parent_id' => 'integer',
        'show_in_dashboard' => 'boolean',
    ];

    /**
     * Get the templates associated with this database entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class, 'database_entity_id');
    }

    /**
     * Get the files associated with this database entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'database_entity_id');
    }

    /**
     * Get the parent database entity.
     */
    public function parent()
    {
        return $this->belongsTo(DatabaseEntity::class, 'parent_id');
    }

    /**
     * Get the child database entities.
     */
    public function children()
    {
        return $this->hasMany(DatabaseEntity::class, 'parent_id');
    }

    /**
     * Get the route for the database entity dashboard.
     *
     * @return string|null
     */
    public function getDashboardRouteAttribute()
    {
        if (!$this->dashboard_route_name) {
            return null;
        }

        try {
            return route($this->dashboard_route_name);
        } catch (\Exception $e) {
            return null;
        }
    }
}