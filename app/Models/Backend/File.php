<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Empodat\EmpodatMain;
use App\Models\Backend\Project;
use App\Models\DatabaseEntity;
use App\Models\User;

class File extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'files';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'original_name',
        'description',
        'file_path',
        'file_size',
        'mime_type',
        'template_id',
        'database_entity_id',
        'processing_notes',
        'uploaded_by',
        'uploaded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'template_id' => 'integer',
        'database_entity_id' => 'integer',
        'file_size' => 'integer',
        'uploaded_by' => 'integer',
        'uploaded_at' => 'datetime',
    ];

    /**
     * Get the template that owns the file.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Get the database entity that owns the file.
     */
    public function databaseEntity(): BelongsTo
    {
        return $this->belongsTo(DatabaseEntity::class);
    }

    /**
     * Get the user who uploaded the file.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * The projects that belong to the file.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'file_project')
                    ->withPivot('notes')
                    ->withTimestamps();
    }

    /**
     * The empodat records that belong to the file.
     */
    public function empodatRecords(): BelongsToMany
    {
        return $this->belongsToMany(EmpodatMain::class, 'empodat_main_file', 'file_id', 'empodat_main_id')
                    ->withPivot('notes')
                    ->withTimestamps();
    }
}