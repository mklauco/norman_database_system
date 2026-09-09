<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Seeds the three-level SusDat use-category tree into susdat_use_categories.
 *
 * Safe to run repeatedly, including on production: every write is an upsert
 * keyed on (level, legacy_id) and nothing is ever deleted. Level 1 comes from
 * susdat_category.csv (the same file the legacy flat seeder uses), levels 2
 * and 3 from susdat_subcategory.csv.
 */
class SusdatUseCategorySeeder extends Seeder
{
    private const TABLE = 'susdat_use_categories';

    /**
     * Names that must sort after everything else within their parent.
     */
    private const TRAILING_NAMES = ['other', 'nr'];

    public function run(): void
    {
        $now = Carbon::now();

        $this->upsertLevelOne($now);
        $this->upsertDeeperLevels($now);
        $this->refreshPathsAndOrdering($now);

        $counts = DB::table(self::TABLE)
            ->select('level', DB::raw('count(*) as total'))
            ->groupBy('level')
            ->orderBy('level')
            ->pluck('total', 'level');

        foreach ($counts as $level => $total) {
            echo 'Level '.$level.': '.$total.' categories'.PHP_EOL;
        }
    }

    private function upsertLevelOne(Carbon $now): void
    {
        $path = base_path().'/database/seeders/seeds/susdat_category.csv';
        $rows = [];

        foreach (SimpleExcelReader::create($path)->getRows() as $r) {
            $rawName = (string) $r['sus_cat_name'];

            $rows[] = [
                'level' => 1,
                'legacy_id' => (int) $r['sus_cat_id'],
                'name' => $this->stripAbbreviation($rawName),
                'abbreviation' => $this->extractAbbreviation($rawName),
                'parent_id' => null,
                'path' => '',
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->upsertRows($rows);
        echo 'Upserted '.count($rows).' level 1 categories'.PHP_EOL;
    }

    private function upsertDeeperLevels(Carbon $now): void
    {
        $path = base_path().'/database/seeders/seeds/susdat_subcategory.csv';
        $rows = [];

        foreach (SimpleExcelReader::create($path)->getRows() as $r) {
            $rows[] = [
                'level' => (int) $r['level'],
                'legacy_id' => (int) $r['legacy_id'],
                'name' => trim((string) $r['name']),
                'abbreviation' => null,
                'parent_level' => (int) $r['parent_level'],
                'parent_legacy_id' => (int) $r['parent_legacy_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ([2, 3] as $level) {
            $levelRows = array_values(array_filter($rows, fn (array $r): bool => $r['level'] === $level));

            if ($levelRows === []) {
                continue;
            }

            $parentIds = $this->keyedIds();

            $payload = [];
            foreach ($levelRows as $r) {
                $parentKey = $r['parent_level'].':'.$r['parent_legacy_id'];
                $parentId = $parentIds[$parentKey] ?? null;

                if ($parentId === null) {
                    echo 'Skipping level '.$level.' "'.$r['name'].'": parent '.$parentKey.' not found'.PHP_EOL;

                    continue;
                }

                $payload[] = [
                    'level' => $r['level'],
                    'legacy_id' => $r['legacy_id'],
                    'name' => $r['name'],
                    'abbreviation' => null,
                    'parent_id' => $parentId,
                    'path' => '',
                    'sort_order' => 0,
                    'created_at' => $r['created_at'],
                    'updated_at' => $r['updated_at'],
                ];
            }

            $this->upsertRows($payload);
            echo 'Upserted '.count($payload).' level '.$level.' categories'.PHP_EOL;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function upsertRows(array $rows): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table(self::TABLE)->upsert(
                $chunk,
                ['level', 'legacy_id'],
                ['name', 'abbreviation', 'parent_id', 'updated_at']
            );
        }
    }

    /**
     * @return array<string, int> keyed "level:legacy_id" => id
     */
    private function keyedIds(): array
    {
        return DB::table(self::TABLE)
            ->get(['id', 'level', 'legacy_id'])
            ->mapWithKeys(fn ($row): array => [$row->level.':'.$row->legacy_id => (int) $row->id])
            ->all();
    }

    /**
     * Materialises path and sort_order once the whole tree exists.
     */
    private function refreshPathsAndOrdering(Carbon $now): void
    {
        $all = DB::table(self::TABLE)->get(['id', 'parent_id', 'level', 'name']);
        $byId = $all->keyBy('id');

        $childrenOf = [];
        foreach ($all as $row) {
            $childrenOf[$row->parent_id ?? 0][] = $row;
        }

        foreach ($childrenOf as $parentId => $siblings) {
            usort($siblings, function ($a, $b): int {
                $aTrailing = $this->isTrailing($a->name);
                $bTrailing = $this->isTrailing($b->name);

                if ($aTrailing !== $bTrailing) {
                    return $aTrailing ? 1 : -1;
                }

                return strcasecmp($a->name, $b->name);
            });

            foreach ($siblings as $index => $row) {
                DB::table(self::TABLE)->where('id', $row->id)->update([
                    'path' => $this->buildPath($row, $byId),
                    'sort_order' => $index + 1,
                    'updated_at' => $now,
                ]);
            }
        }

        echo 'Refreshed path and sort_order for '.$all->count().' categories'.PHP_EOL;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $byId
     */
    private function buildPath(object $row, $byId): string
    {
        $segments = [$row->id];
        $cursor = $row;

        while ($cursor->parent_id !== null && $byId->has($cursor->parent_id)) {
            $cursor = $byId->get($cursor->parent_id);
            array_unshift($segments, $cursor->id);
        }

        return implode('/', $segments);
    }

    private function isTrailing(string $name): bool
    {
        return in_array(mb_strtolower(trim($name)), self::TRAILING_NAMES, true);
    }

    private function extractAbbreviation(string $text): ?string
    {
        return preg_match('/\(([^)]+)\)/', $text, $matches) === 1 ? $matches[1] : null;
    }

    private function stripAbbreviation(string $text): string
    {
        return trim((string) preg_replace('/\s*\(.*$/', '', $text));
    }
}

// php artisan db:seed --class=SusdatUseCategorySeeder
