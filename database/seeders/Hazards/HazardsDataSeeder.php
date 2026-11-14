<?php

declare(strict_types=1);

namespace Database\Seeders\Hazards;

use App\Services\Hazards\HazardsReseedService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Hazards-only reseed from the source files, repeatable at any time. Touches only hazards tables:
 * 1. hazards_janus: from storage/app/hazards/hazards_janus.xlsx (emptied, then filled, in one
 *    transaction; a missing file leaves the table as it is);
 * 2. hazards_pikme: from storage/app/hazards/hazards_pikme.csv (same);
 * 3. HazardsReseedService::rebuild(): rebuilds the derived hazards data from those sources and
 *    keeps the experts' votes attached to the same substance and parameter.
 * susdat_substance_id is resolved here from NORMAN_ID ('NS' + code) and dtxid with one in-memory
 * map of active susdat_substances (read only); unknown or ambiguous values become NULL.
 *
 * This and the three seeders below are the only hazards seeders meant to be run; the loader
 * classes in this folder are helpers. Not listed in DatabaseSeeder. Run by hand only:
 * php artisan db:seed --class="Database\Seeders\Hazards\HazardsDataSeeder" --force
 *
 * Undo (empties all hazards tables): HazardsDataRestoreSeeder.
 */
class HazardsDataSeeder extends Seeder
{
    public function run(): void
    {
        ini_set('memory_limit', '1G');

        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        DB::disableQueryLog();

        $say = function (string $level, string $message): void {
            match ($level) {
                'warn' => $this->command->warn($message),
                'error' => $this->command->error($message),
                default => $this->command->info($message),
            };
        };

        $this->command->info('Loading susdat_substances map (read only)...');
        $map = HazardsSubstanceMap::load();

        $summary = [];
        foreach ([new HazardsJanusLoader($say), new HazardsPikmeLoader($say)] as $loader) {
            $stats = $loader->load($map);
            if ($stats === null) {
                continue;
            }

            foreach ($stats as $metric => $count) {
                $summary[] = [$loader::class === HazardsJanusLoader::class ? 'janus' : 'pikme', $metric, (string) $count];
            }
        }

        if ($summary !== []) {
            $this->command->table(['source', 'metric', 'count'], $summary);
        }

        $this->command->info('Rebuilding derived hazards data...');
        $result = app(HazardsReseedService::class)->rebuild();

        $this->command->table(
            ['metric', 'value'],
            array_map(
                fn (mixed $key, mixed $value): array => [(string) $key, is_scalar($value) || $value === null ? (string) $value : (string) json_encode($value)],
                array_keys($result),
                array_values($result),
            ),
        );

        $this->command->info('Hazards data seeder completed.');
    }
}
