<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateEmpodatFileIds extends Command
{
    protected $signature = 'empodat:populate-file-ids
                            {--batch-size=50000 : Number of records to update per batch}
                            {--min-id=1263993 : Minimum empodat_main.id to process}
                            {--dry-run : Show what would be updated without making changes}
                            {--sleep=1 : Seconds to sleep between batches}';

    protected $description = 'Populate NULL file_id values in empodat_main based on files.main_id_from/main_id_to ranges';

    public function handle(): int
    {
        $batchSize = (int) $this->option('batch-size');
        $minId = (int) $this->option('min-id');
        $dryRun = $this->option('dry-run');
        $sleepSeconds = (int) $this->option('sleep');

        $this->info('Empodat File ID Population');
        $this->info('==========================');
        $this->newLine();

        // Get total count of records needing update
        $totalCount = DB::table('empodat_main')
            ->where('id', '>', $minId)
            ->whereNull('file_id')
            ->count();

        $this->info('Records with NULL file_id (id > '.$minId.'): '.number_format($totalCount));

        if ($totalCount === 0) {
            $this->info('No records to update.');

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
            $this->showSampleMatches($minId);

            return Command::SUCCESS;
        }

        if (! $this->confirm("This will update up to {$totalCount} records. Continue?")) {
            $this->info('Aborted.');

            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info("Processing in batches of {$batchSize}...");
        $this->newLine();

        $totalUpdated = 0;
        $batchNumber = 0;
        $startTime = microtime(true);

        // Process by fetching actual IDs that need updating, in batches
        while (true) {
            $batchNumber++;

            // Get the next batch of IDs that need updating
            $idsToUpdate = DB::table('empodat_main')
                ->where('id', '>', $minId)
                ->whereNull('file_id')
                ->orderBy('id')
                ->limit($batchSize)
                ->pluck('id');

            if ($idsToUpdate->isEmpty()) {
                break;
            }

            $batchMinId = $idsToUpdate->min();
            $batchMaxId = $idsToUpdate->max();

            // Single efficient UPDATE query with JOIN for this ID range
            $updated = DB::update('
                UPDATE empodat_main
                SET file_id = files.id
                FROM files
                WHERE empodat_main.id >= ?
                  AND empodat_main.id <= ?
                  AND empodat_main.file_id IS NULL
                  AND empodat_main.id BETWEEN files.main_id_from AND files.main_id_to
            ', [$batchMinId, $batchMaxId]);

            $totalUpdated += $updated;

            $remaining = $totalCount - $totalUpdated;
            $elapsed = round(microtime(true) - $startTime, 1);
            $rate = $totalUpdated > 0 ? round($totalUpdated / $elapsed) : 0;

            $this->line("Batch {$batchNumber}: Updated {$updated} records (Total: ".number_format($totalUpdated).', Remaining: ~'.number_format(max(0, $remaining)).", Rate: {$rate}/s)");

            // Sleep between batches to reduce database load
            if ($sleepSeconds > 0) {
                sleep($sleepSeconds);
            }
        }

        $this->newLine();

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->info("Completed in {$elapsed} seconds");
        $this->info('Total records updated: '.number_format($totalUpdated));

        // Verify remaining NULL count
        $remainingNull = DB::table('empodat_main')
            ->where('id', '>', $minId)
            ->whereNull('file_id')
            ->count();

        if ($remainingNull > 0) {
            $this->warn('Records still with NULL file_id: '.number_format($remainingNull));
            $this->warn('These may be outside any file range.');
        } else {
            $this->info('All records now have file_id populated.');
        }

        return Command::SUCCESS;
    }

    private function showSampleMatches(int $minId): void
    {
        $this->info('Sample records that would be updated:');
        $this->newLine();

        // Show 10 sample matches
        $samples = DB::select('
            SELECT e.id as empodat_id, f.id as file_id, f.main_id_from, f.main_id_to
            FROM empodat_main e
            JOIN files f ON e.id BETWEEN f.main_id_from AND f.main_id_to
            WHERE e.id > ?
              AND e.file_id IS NULL
            LIMIT 10
        ', [$minId]);

        if (empty($samples)) {
            $this->warn('No matching records found.');

            return;
        }

        $this->table(
            ['empodat_main.id', 'Would set file_id to', 'File range'],
            collect($samples)->map(fn ($row) => [
                $row->empodat_id,
                $row->file_id,
                "{$row->main_id_from} - {$row->main_id_to}",
            ])->toArray()
        );

        // Show distribution by file
        $this->newLine();
        $this->info('Distribution by file (top 10):');

        $distribution = DB::select('
            SELECT f.id as file_id, f.name, COUNT(*) as record_count
            FROM empodat_main e
            JOIN files f ON e.id BETWEEN f.main_id_from AND f.main_id_to
            WHERE e.id > ?
              AND e.file_id IS NULL
            GROUP BY f.id, f.name
            ORDER BY record_count DESC
            LIMIT 10
        ', [$minId]);

        $this->table(
            ['File ID', 'File Name', 'Records to Update'],
            collect($distribution)->map(fn ($row) => [
                $row->file_id,
                substr($row->name ?? 'N/A', 0, 50),
                number_format($row->record_count),
            ])->toArray()
        );
    }
}
