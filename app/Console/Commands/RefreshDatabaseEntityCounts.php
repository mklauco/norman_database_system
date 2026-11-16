<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\RefreshDatabaseEntityCountsAction;
use App\Models\DatabaseEntity;
use Illuminate\Console\Command;

class RefreshDatabaseEntityCounts extends Command
{
    /**
     * @var string
     */
    protected $signature = 'database-entities:refresh-counts
                            {--code=* : Only refresh these module codes}';

    /**
     * @var string
     */
    protected $description = 'Recompute number_of_records and last_update of the database modules (EMPODAT and EMPODAT Suspect excluded)';

    public function handle(RefreshDatabaseEntityCountsAction $action): int
    {
        /** @var array<int, string> $codes */
        $codes = $this->option('code');
        $codes = $codes === [] ? null : $codes;

        $old = DatabaseEntity::query()->pluck('number_of_records', 'code');
        $results = $action->handle($codes);

        $rows = [];
        foreach ($results as $code => $count) {
            $rows[] = [$code, $old[$code] ?? '-', $count];
        }

        $this->table(['code', 'old', 'new'], $rows);

        $requested = $codes ?? $action->codes();
        $missing = array_diff($requested, array_keys($results));

        if ($missing !== []) {
            $this->warn('Not refreshed: '.implode(', ', $missing));
        }

        return self::SUCCESS;
    }
}
