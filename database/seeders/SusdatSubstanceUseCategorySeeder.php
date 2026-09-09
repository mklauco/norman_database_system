<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Susdat\Substance;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Links substances to the use-category tree in susdat_substance_use_category.
 *
 * susdat_category_join.csv carries a sus_subcat_id column that the older flat
 * seeder discards. Each row produces a link to the category and, when the row
 * names one, to the subcategory as well, so filtering on any node in the tree
 * is a plain whereIn with no recursive query.
 *
 * Safe to run repeatedly, including on production: writes use insertOrIgnore
 * and nothing is ever deleted. Run SusdatUseCategorySeeder first.
 */
class SusdatSubstanceUseCategorySeeder extends Seeder
{
    private const TABLE = 'susdat_substance_use_category';

    private const CHUNK = 2000;

    public function run(): void
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        DB::disableQueryLog();

        $now = Carbon::now();
        $substanceIds = Substance::pluck('id', 'code')->all();
        $categoryIds = DB::table('susdat_use_categories')
            ->get(['id', 'level', 'legacy_id'])
            ->mapWithKeys(fn ($row): array => [$row->level.':'.$row->legacy_id => (int) $row->id])
            ->all();

        if ($categoryIds === []) {
            echo 'susdat_use_categories is empty - run SusdatUseCategorySeeder first.'.PHP_EOL;

            return;
        }

        echo 'Loaded '.count($substanceIds).' substances and '.count($categoryIds).' use categories'.PHP_EOL;

        $path = base_path().'/database/seeders/seeds/susdat_category_join.csv';
        $buffer = [];
        $seen = [];
        $processed = 0;
        $inserted = 0;
        $missingSubstance = 0;
        $missingCategory = 0;

        foreach (SimpleExcelReader::create($path)->getRows() as $r) {
            $processed++;

            $substanceId = $substanceIds[(string) $r['sus_id']] ?? null;

            if ($substanceId === null) {
                $missingSubstance++;

                continue;
            }

            foreach ($this->categoryKeysFor($r) as $key) {
                $categoryId = $categoryIds[$key] ?? null;

                if ($categoryId === null) {
                    $missingCategory++;

                    continue;
                }

                $pairKey = $substanceId.':'.$categoryId;

                if (isset($seen[$pairKey])) {
                    continue;
                }

                $seen[$pairKey] = true;
                $buffer[] = [
                    'substance_id' => $substanceId,
                    'use_category_id' => $categoryId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (count($buffer) >= self::CHUNK) {
                $inserted += $this->flush($buffer);
                echo 'Processed '.$processed.' rows, inserted '.$inserted.' links'.PHP_EOL;
            }
        }

        $inserted += $this->flush($buffer);

        echo 'Done. Rows read: '.$processed.PHP_EOL;
        echo 'Links inserted: '.$inserted.PHP_EOL;
        echo 'Rows skipped, substance not found: '.$missingSubstance.PHP_EOL;
        echo 'Rows skipped, category not found: '.$missingCategory.PHP_EOL;
    }

    /**
     * A row always names a category and may also name a subcategory; the
     * legacy export uses 0 to mean "no subcategory".
     *
     * @param  array<string, mixed>  $r
     * @return array<int, string>
     */
    private function categoryKeysFor(array $r): array
    {
        $keys = ['1:'.(int) $r['sus_cat_id']];
        $subcategoryId = (int) ($r['sus_subcat_id'] ?? 0);

        if ($subcategoryId !== 0) {
            $keys[] = '2:'.$subcategoryId;
        }

        return $keys;
    }

    /**
     * @param  array<int, array<string, mixed>>  $buffer
     */
    private function flush(array &$buffer): int
    {
        if ($buffer === []) {
            return 0;
        }

        $count = count($buffer);
        DB::table(self::TABLE)->insertOrIgnore($buffer);
        $buffer = [];

        return $count;
    }
}

// php artisan db:seed --class=SusdatSubstanceUseCategorySeeder
