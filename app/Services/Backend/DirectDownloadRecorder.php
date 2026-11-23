<?php

declare(strict_types=1);

namespace App\Services\Backend;

use App\Models\Backend\ExportDownload;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Records a download that is streamed straight to the browser (no export file is kept)
 * in export_downloads, so it appears in the Downloads list and in the Usage Statistics.
 * A failure to record never blocks the download itself.
 */
class DirectDownloadRecorder
{
    /**
     * export_downloads.status of a direct download: delivered at once, nothing left on disk.
     */
    public const STATUS = 'downloaded';

    public function record(string $databaseKey, string $filename, string $format, ?int $recordCount = null, string $message = ''): void
    {
        try {
            $now = Carbon::now();

            ExportDownload::create([
                'user_id' => Auth::id(),
                'filename' => $filename,
                'format' => $format,
                'ip_address' => (string) request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
                'record_count' => $recordCount,
                'database_key' => $databaseKey,
                'status' => self::STATUS,
                'message' => $message,
                'started_at' => $now,
                'completed_at' => $now,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Direct download could not be recorded', [
                'database_key' => $databaseKey,
                'filename' => $filename,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
