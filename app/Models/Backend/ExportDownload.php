<?php

namespace App\Models\Backend;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ExportDownload extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'filename',
        'format',
        'ip_address',
        'user_agent',
        'record_count',
        'database_key',
        'status'
    ];

    /**
     * Get the user that initiated the download.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the query logs associated with this download.
     */
    public function queryLogs(): BelongsToMany
    {
        return $this->belongsToMany(QueryLog::class, 'export_download_query_log');
    }

    /**
     * Get created_at in Europe/Berlin timezone
     */
    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->timezone('Europe/Berlin')->format('Y-m-d G:i:s');
    }
}