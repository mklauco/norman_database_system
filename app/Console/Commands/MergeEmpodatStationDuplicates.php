<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MergeEmpodatStationDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'empodat-stations:merge-duplicates
                            {--pattern= : Pattern to match in short_sample_code (e.g., "APEX")}
                            {--file-id= : Specific file_id to process from suspect mapping}
                            {--dry-run : Preview without making changes}
                            {--delete : Hard delete duplicate stations instead of keeping them}
                            {--stats : Show statistics after merge}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge duplicate Empodat station records based on suspect mapping';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║        Empodat Stations - Merge Duplicates                   ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $pattern = $this->option('pattern');
        $fileId = $this->option('file-id');
        $dryRun = $this->option('dry-run');
        $hardDelete = $this->option('delete');

        if ($dryRun) {
            $this->warn('⚠ DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        try {
            $startTime = microtime(true);

            // Find duplicate groups from suspect mapping
            $duplicateGroups = $this->findDuplicateGroups($pattern, $fileId);

            if ($duplicateGroups->isEmpty()) {
                $this->info('✓ No duplicate groups found.');

                return Command::SUCCESS;
            }

            $this->info("Found {$duplicateGroups->count()} duplicate groups to process");
            $this->newLine();

            $totalMerged = 0;
            $totalRecordsUpdated = 0;
            $errors = 0;

            foreach ($duplicateGroups as $group) {
                try {
                    $result = $this->processDuplicateGroup($group, $dryRun, $hardDelete);
                    $totalMerged += $result['merged'];
                    $totalRecordsUpdated += $result['updated'];
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("  ✗ Error processing group: {$e->getMessage()}");
                    Log::error('Station merge error', [
                        'group' => $group,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $duration = round(microtime(true) - $startTime, 2);

            // Show statistics if requested
            if ($this->option('stats')) {
                $this->newLine();
                $this->showStatistics();
            }

            $this->newLine();
            $this->info('╔════════════════════════════════════════╗');
            $this->info('║         Merge Summary                  ║');
            $this->info('╚════════════════════════════════════════╝');
            $this->line("  Duplicate groups:        {$duplicateGroups->count()}");
            $this->line("  Stations merged:         {$totalMerged}");
            $this->line("  Main records updated:    {$totalRecordsUpdated}");
            if ($errors > 0) {
                $this->line("  Errors:                  {$errors}");
            }
            $this->line("  Duration:                {$duration}s");

            if ($dryRun) {
                $this->newLine();
                $this->warn('⚠ This was a dry run. No changes were made.');
                $this->info('Run without --dry-run to apply changes.');
            }

            Log::info('Station merge completed', [
                'duration' => $duration,
                'merged' => $totalMerged,
                'updated' => $totalRecordsUpdated,
                'errors' => $errors,
                'dry_run' => $dryRun,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Failed to merge stations:');
            $this->error('  '.$e->getMessage());

            if ($this->getOutput()->isVerbose()) {
                $this->newLine();
                $this->error($e->getTraceAsString());
            }

            Log::error('Station merge failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Find duplicate groups from suspect mapping table
     */
    private function findDuplicateGroups($pattern, $fileId)
    {
        $this->info('→ Finding duplicate groups from suspect mapping...');

        $query = DB::table('empodat_suspect_xlsx_stations_mapping')
            ->select('id', 'xlsx_name', 'station_id', 'count', 'ids', 'file_id')
            ->where('count', '>=', 2)
            ->whereNotNull('ids');

        if ($pattern) {
            // Join with stations to filter by pattern
            $query->join('empodat_stations', 'empodat_suspect_xlsx_stations_mapping.station_id', '=', 'empodat_stations.id')
                ->where('empodat_stations.short_sample_code', 'ilike', "%{$pattern}%");
        }

        if ($fileId) {
            $query->where('file_id', $fileId);
        }

        $groups = $query->get();

        $this->info("  ✓ Found {$groups->count()} duplicate groups");

        return $groups;
    }

    /**
     * Process a single duplicate group
     */
    private function processDuplicateGroup($group, $dryRun, $hardDelete)
    {
        // Parse the IDs from the mapping
        $stationIds = array_map('intval', explode(',', $group->ids));

        if (count($stationIds) < 2) {
            return ['merged' => 0, 'updated' => 0];
        }

        // Fetch all station records
        $stations = DB::table('empodat_stations')
            ->whereIn('id', $stationIds)
            ->get();

        if ($stations->isEmpty()) {
            $this->warn("  ⚠ No stations found for IDs: {$group->ids}");

            return ['merged' => 0, 'updated' => 0];
        }

        // Determine canonical station (most complete)
        $canonical = $this->selectCanonicalStation($stations);
        $duplicates = $stations->where('id', '!=', $canonical->id);

        if ($duplicates->isEmpty()) {
            return ['merged' => 0, 'updated' => 0];
        }

        $this->line("\n📍 Group: {$group->xlsx_name}");
        $this->line("   Sample Code: {$canonical->short_sample_code}");
        $this->line("   Canonical:   Station ID {$canonical->id} ({$canonical->name})");
        $this->line('   Duplicates:  '.$duplicates->pluck('id')->implode(', '));

        if ($this->getOutput()->isVerbose()) {
            $this->line('   Reason:      '.$this->getCanonicalReason($canonical));
        }

        if ($dryRun) {
            // Just show what would be done
            $affectedCount = DB::table('empodat_main')
                ->whereIn('station_id', $duplicates->pluck('id'))
                ->count();
            $this->line("   Would update: {$affectedCount} empodat_main records");

            return ['merged' => $duplicates->count(), 'updated' => $affectedCount];
        }

        // Start transaction for this group
        DB::beginTransaction();

        try {
            $updatedCount = 0;

            // Update empodat_main records for each duplicate
            foreach ($duplicates as $duplicate) {
                $affected = DB::table('empodat_main')
                    ->where('station_id', $duplicate->id)
                    ->update(['station_id' => $canonical->id]);

                $updatedCount += $affected;

                // Log the merge
                DB::table('empodat_station_merge_log')->insert([
                    'deprecated_station_id' => $duplicate->id,
                    'canonical_station_id' => $canonical->id,
                    'merge_reason' => "Duplicate station merged: {$duplicate->short_sample_code}",
                    'deprecated_data' => json_encode($duplicate),
                    'merged_by' => null, // Console command has no user
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Mark duplicates as deprecated
            DB::table('empodat_stations')
                ->whereIn('id', $duplicates->pluck('id'))
                ->update([
                    'is_deprecated' => true,
                    'updated_at' => now(),
                ]);

            // Update the mapping to point to canonical station
            DB::table('empodat_suspect_xlsx_stations_mapping')
                ->where('id', $group->id)
                ->update([
                    'station_id' => $canonical->id,
                    'count' => 1,
                    'ids' => (string) $canonical->id,
                    'updated_at' => now(),
                ]);

            // Handle duplicates
            if ($hardDelete) {
                DB::table('empodat_stations')
                    ->whereIn('id', $duplicates->pluck('id'))
                    ->delete();
                $this->line("   ✓ Deleted {$duplicates->count()} duplicate stations");
            } else {
                // Just leave them - they're tracked in merge log
                $this->line('   ✓ Kept duplicate stations (tracked in merge log)');
            }

            $this->line("   ✓ Updated {$updatedCount} empodat_main records");

            DB::commit();

            return ['merged' => $duplicates->count(), 'updated' => $updatedCount];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Select the canonical station from a collection
     * Priority: provider_code > coordinates > name > lowest ID
     */
    private function selectCanonicalStation($stations)
    {
        return $stations->sortBy([
            // 1. Has provider_code (ascending, so nulls last)
            fn ($s) => empty($s->provider_code) ? 1 : 0,
            // 2. Has valid coordinates (ascending, so 0,0 or null last)
            fn ($s) => ($s->latitude == 0 && $s->longitude == 0) || ! $s->latitude ? 1 : 0,
            // 3. Has name (ascending, so nulls last)
            fn ($s) => empty($s->name) ? 1 : 0,
            // 4. Lowest ID
            fn ($s) => $s->id,
        ])->first();
    }

    /**
     * Get reason why station was selected as canonical
     */
    private function getCanonicalReason($station)
    {
        if (! empty($station->provider_code)) {
            return "Has provider_code: {$station->provider_code}";
        }
        if ($station->latitude != 0 && $station->longitude != 0) {
            return "Has valid coordinates: {$station->latitude}, {$station->longitude}";
        }
        if (! empty($station->name)) {
            return "Has name: {$station->name}";
        }

        return "Lowest ID: {$station->id}";
    }

    /**
     * Show statistics about the merge log
     */
    private function showStatistics(): void
    {
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║         Merge Statistics               ║');
        $this->info('╚════════════════════════════════════════╝');

        try {
            // Get total merges
            $totalMerges = DB::table('empodat_station_merge_log')->count();
            $this->line('  Total merges logged:     '.number_format($totalMerges));

            // Get unique canonical stations
            $canonicalCount = DB::table('empodat_station_merge_log')
                ->distinct('canonical_station_id')
                ->count('canonical_station_id');
            $this->line('  Unique canonical IDs:    '.number_format($canonicalCount));

            // Get recent merges
            $recentCount = DB::table('empodat_station_merge_log')
                ->where('created_at', '>', now()->subDay())
                ->count();
            $this->line('  Merges (last 24h):       '.number_format($recentCount));

            // Get suspect mapping status
            $duplicatesRemaining = DB::table('empodat_suspect_xlsx_stations_mapping')
                ->where('count', '>=', 2)
                ->count();
            $this->line('  Duplicates remaining:    '.number_format($duplicatesRemaining));

        } catch (\Exception $e) {
            $this->warn('  Could not retrieve all statistics: '.$e->getMessage());
        }
    }
}
