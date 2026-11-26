<?php

namespace App\Models\Backend;

use App\Models\DatabaseEntity;
use App\Models\Empodat\EmpodatMain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class File extends Model implements Auditable
{
    use AuditableTrait;
    use HasFactory;

    /**
     * Private disk folder that keeps a file replaced in the edit form, under its old file_path.
     */
    public const HISTORY_FOLDER = 'files_history';

    public const EMPODAT_DATABASE_ENTITY_ID = 2;

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
        'is_protected',
        'number_of_records',
        'doi',
        'main_id_from',
        'main_id_to',
        'analysis_number',
        'source_id_from',
        'source_id_to',
        'source_number',
        'method_id_from',
        'method_id_to',
        'method_number',
        'list_type',
        'note',
        'matrice_dct',
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
        'is_protected' => 'boolean',
        'number_of_records' => 'integer',
        'main_id_from' => 'integer',
        'main_id_to' => 'integer',
        'analysis_number' => 'integer',
        'source_id_from' => 'integer',
        'source_id_to' => 'integer',
        'source_number' => 'integer',
        'method_id_from' => 'integer',
        'method_id_to' => 'integer',
        'method_number' => 'integer',
        'matrice_dct' => 'integer',
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
     * Get the empodat suspect records for this file.
     */
    public function empodatSuspectRecords()
    {
        return $this->hasMany(\App\Models\EmpodatSuspect\EmpodatSuspectMain::class, 'file_id');
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
        if (! $this->file_size) {
            return null;
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get file extension from file path.
     *
     * @return string|null
     */
    public function getFileExtensionAttribute()
    {
        if (! $this->file_path && ! $this->original_name) {
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
     * Changes made by seeders and artisan commands are audited too (config audit.console stays false for other models).
     */
    public static function isAuditingEnabled(): bool
    {
        return (bool) Config::get('audit.enabled', true);
    }

    /**
     * Private-disk path where a replaced file is kept.
     */
    public static function historyPath(string $filePath): string
    {
        return self::HISTORY_FOLDER.'/'.ltrim($filePath, '/');
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
        if (! $this->file_path) {
            return false;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->exists($this->file_path);
    }

    /**
     * Get the full file path.
     *
     * @return string|null
     */
    public function getFullPathAttribute()
    {
        if (! $this->file_path) {
            return null;
        }

        return storage_path('app/'.$this->file_path);
    }

    /**
     * Get download URL for the file.
     *
     * @return string|null
     */
    public function getDownloadUrlAttribute()
    {
        if (! $this->file_path) {
            return null;
        }

        return route('files.download', $this->id);
    }

    /**
     * Get the count of empodat records associated with this file.
     * Uses direct pivot table query for better performance.
     *
     * @return int
     */
    public function getEmpodatRecordsCountAttribute()
    {
        return DB::table('empodat_main_file')
            ->where('file_id', $this->id)
            ->count();
    }

    /**
     * ID ranges of EMPODAT records for the given files, read from files.main_id_from / main_id_to
     * (never counted from empodat_main), ordered by the first ID.
     *
     * @param  array<int, int|string>  $fileIds
     * @return Collection<int, array{id: int, name: string, from: int, to: int}>
     */
    public static function empodatIdRanges(array $fileIds): Collection
    {
        $fileIds = array_values(array_filter(array_map('intval', $fileIds)));

        if ($fileIds === []) {
            return collect();
        }

        return static::query()
            ->whereIn('id', $fileIds)
            ->where('database_entity_id', self::EMPODAT_DATABASE_ENTITY_ID)
            ->whereNotNull('main_id_from')
            ->whereNotNull('main_id_to')
            ->orderBy('main_id_from')
            ->get(['id', 'name', 'original_name', 'main_id_from', 'main_id_to'])
            ->map(fn (self $file): array => [
                'id' => $file->id,
                'name' => $file->original_name ?: $file->name,
                'from' => (int) $file->main_id_from,
                'to' => (int) $file->main_id_to,
            ])
            ->values();
    }
}
