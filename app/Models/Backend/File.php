<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Empodat\EmpodatMain;
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
        'is_deleted',
        'project_id',
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
        'project_id' => 'integer',
        'uploaded_at' => 'datetime',
        'is_deleted' => 'boolean',
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
     * Get the project that owns the file.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who uploaded the file.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * The empodat records that belong to the file.
     */
    public function empodatRecords(): BelongsToMany
    {
        return $this->belongsToMany(EmpodatMain::class, 'empodat_main_file', 'file_id', 'empodat_main_id');
    }

    /**
     * Scope to filter out deleted files.
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope to filter only deleted files.
     */
    public function scopeDeleted($query)
    {
        return $query->where('is_deleted', true);
    }

    /**
     * Scope to filter by project.
     */
    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to filter by database entity.
     */
    public function scopeByDatabaseEntity($query, $entityId)
    {
        return $query->where('database_entity_id', $entityId);
    }

    /**
     * Get formatted file size.
     * 
     * @return string|null
     */
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) {
            return null;
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file extension from file path.
     * 
     * @return string|null
     */
    public function getFileExtensionAttribute()
    {
        if (!$this->file_path && !$this->original_name) {
            return null;
        }
        
        $fileName = $this->original_name ?: $this->file_path;
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }

    /**
     * Check if file is an image.
     * 
     * @return bool
     */
    public function getIsImageAttribute()
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
        return in_array($this->file_extension, $imageExtensions);
    }

    /**
     * Check if file is a document.
     * 
     * @return bool
     */
    public function getIsDocumentAttribute()
    {
        $documentExtensions = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];
        return in_array($this->file_extension, $documentExtensions);
    }

    /**
     * Check if file is a spreadsheet.
     * 
     * @return bool
     */
    public function getIsSpreadsheetAttribute()
    {
        $spreadsheetExtensions = ['xls', 'xlsx', 'csv', 'ods'];
        return in_array($this->file_extension, $spreadsheetExtensions);
    }

    /**
     * Get file icon class based on file type.
     * 
     * @return string
     */
    public function getFileIconAttribute()
    {
        if ($this->is_image) {
            return 'fa fa-image';
        }
        
        if ($this->is_document) {
            return 'fa fa-file-text';
        }
        
        if ($this->is_spreadsheet) {
            return 'fa fa-table';
        }
        
        switch ($this->file_extension) {
            case 'pdf':
                return 'fa fa-file-pdf';
            case 'zip':
            case 'rar':
            case '7z':
                return 'fa fa-file-archive';
            case 'mp4':
            case 'avi':
            case 'mov':
                return 'fa fa-file-video';
            case 'mp3':
            case 'wav':
            case 'flac':
                return 'fa fa-file-audio';
            default:
                return 'fa fa-file';
        }
    }

    /**
     * Soft delete the file by setting is_deleted to true.
     * 
     * @return bool
     */
    public function softDelete()
    {
        $this->is_deleted = true;
        return $this->save();
    }

    /**
     * Restore the file by setting is_deleted to false.
     * 
     * @return bool
     */
    public function restore()
    {
        $this->is_deleted = false;
        return $this->save();
    }

    /**
     * Check if the file exists on disk.
     * 
     * @return bool
     */
    public function existsOnDisk()
    {
        if (!$this->file_path) {
            return false;
        }
        
        $fullPath = storage_path('app/' . $this->file_path);
        return file_exists($fullPath);
    }

    /**
     * Get the full file path.
     * 
     * @return string|null
     */
    public function getFullPathAttribute()
    {
        if (!$this->file_path) {
            return null;
        }
        
        return storage_path('app/' . $this->file_path);
    }

    /**
     * Get download URL for the file.
     * 
     * @return string|null
     */
    public function getDownloadUrlAttribute()
    {
        if (!$this->file_path) {
            return null;
        }
        
        return route('files.download', $this->id);
    }
}