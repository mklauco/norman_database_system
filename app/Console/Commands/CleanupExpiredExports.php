<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Backend\ExportDownload;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredExports extends Command
{
    protected $signature = 'exports:cleanup
        {--hours=24 : Retention window in hours; files older than this are deleted}
        {--dry-run : Report what would be deleted without touching the filesystem or database}';

    protected $description = 'Delete export files past their retention window and mark matching ExportDownload rows.';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        if ($hours < 1) {
            $this->error('--hours must be a positive integer');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $cutoff = Carbon::now()->subHours($hours);
        $root = storage_path('app/exports');

        if (! is_dir($root)) {
            $this->info("No exports directory at {$root}; nothing to do.");

            return self::SUCCESS;
        }

        $deleted = 0;
        $bytesFreed = 0;
        $orphans = 0;
        $dbUpdated = 0;
        $errors = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile()) {
                continue;
            }

            $mtime = $fileInfo->getMTime();
            if ($mtime === false || $mtime >= $cutoff->getTimestamp()) {
                continue;
            }

            $path = $fileInfo->getPathname();
            $size = $fileInfo->getSize() ?: 0;
            $filename = $fileInfo->getFilename();

            $this->line(sprintf(
                '%s %s (%s, %s old)',
                $dryRun ? '[dry-run]' : 'deleting',
                $filename,
                $this->formatBytes($size),
                Carbon::createFromTimestamp($mtime)->diffForHumans(null, true)
            ));

            if (! $dryRun) {
                if (! @unlink($path)) {
                    $this->warn("  failed to delete: {$path}");
                    $errors++;

                    continue;
                }

                $updated = ExportDownload::where('filename', $filename)
                    ->whereNotNull('file_size_bytes')
                    ->update([
                        'message' => "File deleted after {$hours}h retention window.",
                        'file_size_bytes' => null,
                        'file_size_formatted' => null,
                    ]);

                if ($updated > 0) {
                    $dbUpdated += $updated;
                } else {
                    $orphans++;
                }
            }

            $deleted++;
            $bytesFreed += $size;
        }

        $summary = sprintf(
            '%s%d file(s), %s freed, %d ExportDownload row(s) updated, %d orphan file(s), %d error(s)',
            $dryRun ? '[dry-run] would delete: ' : 'deleted: ',
            $deleted,
            $this->formatBytes($bytesFreed),
            $dbUpdated,
            $orphans,
            $errors
        );

        $this->info($summary);

        if (! $dryRun && $deleted > 0) {
            Log::info('exports:cleanup completed', [
                'hours' => $hours,
                'deleted_files' => $deleted,
                'bytes_freed' => $bytesFreed,
                'db_rows_updated' => $dbUpdated,
                'orphan_files' => $orphans,
                'errors' => $errors,
            ]);
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return sprintf('%.1f %s', $value, $units[$i]);
    }
}
