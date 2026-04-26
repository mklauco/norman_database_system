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

        // Reconcile pass: rows still claiming the file is available but whose
        // file is no longer on disk (e.g. deleted manually, or by an earlier
        // buggy run of this command). Mark them so the UI can hide the link.
        $reconciled = $this->reconcileOrphanRows($root, $dryRun);

        $summary = sprintf(
            '%s%d file(s), %s freed, %d row(s) updated by file scan, %d orphan file(s), %d row(s) reconciled, %d error(s)',
            $dryRun ? '[dry-run] would delete: ' : 'deleted: ',
            $deleted,
            $this->formatBytes($bytesFreed),
            $dbUpdated,
            $orphans,
            $reconciled,
            $errors
        );

        $this->info($summary);

        if (! $dryRun && ($deleted > 0 || $reconciled > 0)) {
            Log::info('exports:cleanup completed', [
                'hours' => $hours,
                'deleted_files' => $deleted,
                'bytes_freed' => $bytesFreed,
                'db_rows_updated' => $dbUpdated,
                'orphan_files' => $orphans,
                'rows_reconciled' => $reconciled,
                'errors' => $errors,
            ]);
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Find ExportDownload rows that still advertise a file (file_size_bytes
     * not null, status=completed) but whose file is missing on disk, and
     * mark them so the UI can render an "expired" state.
     */
    private function reconcileOrphanRows(string $root, bool $dryRun): int
    {
        $candidates = ExportDownload::query()
            ->where('status', 'completed')
            ->whereNotNull('file_size_bytes')
            ->select(['id', 'filename', 'database_key'])
            ->get();

        $reconciled = 0;

        foreach ($candidates as $row) {
            $candidatePaths = $this->candidatePathsFor($root, $row->filename, $row->database_key);

            $found = false;
            foreach ($candidatePaths as $path) {
                if (is_file($path)) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                continue;
            }

            $this->line("[reconcile] row {$row->id} filename={$row->filename}: file missing, marking expired");

            if (! $dryRun) {
                ExportDownload::where('id', $row->id)->update([
                    'message' => 'Export file no longer available.',
                    'file_size_bytes' => null,
                    'file_size_formatted' => null,
                ]);
            }

            $reconciled++;
        }

        return $reconciled;
    }

    /**
     * @return array<int, string>
     */
    private function candidatePathsFor(string $root, string $filename, ?string $databaseKey): array
    {
        // Map database_key → expected subdir; fall back to a recursive glob so
        // we work for module exports we haven't explicitly mapped.
        $subdirByKey = [
            'empodat' => 'empodat',
            'empodat_suspect' => 'empodat_suspect',
            'sars' => 'sars',
            'passive' => 'passive',
            'indoor' => 'indoor',
            'literature' => 'literature',
            'prioritisation' => 'prioritisation',
            'susdat' => 'susdat',
        ];

        $paths = [];
        if ($databaseKey !== null && isset($subdirByKey[$databaseKey])) {
            $paths[] = $root.'/'.$subdirByKey[$databaseKey].'/'.$filename;
        }

        $paths = array_merge($paths, glob($root.'/*/'.$filename) ?: []);

        return array_values(array_unique($paths));
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
