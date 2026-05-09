<?php

namespace Database\Seeders;

use App\Models\Susdat\Category;
use App\Models\Susdat\Substance;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class SusdatCategorySubstanceJoinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // get existing ids and create lookup maps
        echo 'Getting substance and category lookups...'.PHP_EOL;
        $substanceCodeToId = Substance::pluck('id', 'code')->toArray();
        $existingCategoryids = Category::pluck('id')->toArray();

        echo 'Found '.count($substanceCodeToId).' substances with codes'.PHP_EOL;
        echo 'Found '.count($existingCategoryids).' categories'.PHP_EOL;

        // Show some sample substance codes for debugging
        $sampleCodes = array_slice(array_keys($substanceCodeToId), 0, 5, true);
        echo 'Sample substance codes: '.implode(', ', $sampleCodes).PHP_EOL;

        // clean join table
        $target_table_name = 'susdat_category_substance';
        $deletedCount = DB::table($target_table_name)->count();
        DB::table($target_table_name)->delete();
        echo 'Deleted '.$deletedCount.' existing records from '.$target_table_name.PHP_EOL;

        echo 'Seeding '.$target_table_name.PHP_EOL;
        $logFileNameCat = base_path().'/database/seeders/seeds/susdat_category_join_cat.log';
        $logFileNameSub = base_path().'/database/seeders/seeds/susdat_category_join_sub.log';
        file_put_contents($logFileNameCat, '');
        file_put_contents($logFileNameSub, '');
        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/susdat_category_join.csv';
        $rows = SimpleExcelReader::create($path)->getRows();
        $p = [];
        $k = 0;
        $processedCount = 0;
        $validCount = 0;
        $missingSubstanceCount = 0;
        $missingCategoryCount = 0;

        foreach ($rows as $r) {
            $processedCount++;
            $raw_sus_id = $r['sus_id'];
            $substance_code = $raw_sus_id; // Keep the original format with leading zeros
            $category_id = (int) $r['sus_cat_id'];

            // Show first few rows for debugging
            if ($processedCount <= 3) {
                echo "Row {$processedCount}: sus_id='{$raw_sus_id}' -> code='{$substance_code}', category_id={$category_id}".PHP_EOL;
            }

            // Look up the actual substance ID using the code
            $substance_id = $substanceCodeToId[$substance_code] ?? null;
            $substance_ok = $substance_id !== null;
            $category_ok = in_array($category_id, $existingCategoryids);

            if ($substance_ok && $category_ok) {
                $validCount++;
                $p[] = [
                    'substance_id' => $substance_id,
                    'category_id' => $category_id,
                ];
            } elseif (! $substance_ok) {
                $missingSubstanceCount++;
                $message = 'Skipping missing substance with code: '.$substance_code."\n";
                file_put_contents($logFileNameSub, $message, FILE_APPEND);
            } elseif (! $category_ok) {
                $missingCategoryCount++;
                $message = 'Skipping missing category_id: '.$category_id."\n";
                file_put_contents($logFileNameCat, $message, FILE_APPEND);
            } else {
                $message = 'Something wrong with substance_code and category_id: '.$substance_code.' - '.$category_id."\n";
                file_put_contents($logFileNameCat, $message, FILE_APPEND);
                file_put_contents($logFileNameSub, $message, FILE_APPEND);
            }

        }

        echo PHP_EOL.'Processing summary:'.PHP_EOL;
        echo 'Total rows processed: '.$processedCount.PHP_EOL;
        echo 'Valid records to insert: '.$validCount.PHP_EOL;
        echo 'Missing substances: '.$missingSubstanceCount.PHP_EOL;
        echo 'Missing categories: '.$missingCategoryCount.PHP_EOL;

        if (count($p) > 0) {
            echo PHP_EOL.'Starting insertion of '.count($p).' valid records...'.PHP_EOL;
            $chunkSize = 1000;
            $chunks = array_chunk($p, $chunkSize);
            $k = 0;
            $totalChunks = count($chunks);
            $insertedTotal = 0;

            foreach ($chunks as $c) {
                $k++;
                echo "Processing chunk {$k}/{$totalChunks} (".count($c).' records)...';
                $inserted = DB::table($target_table_name)->insertOrIgnore($c);
                $insertedTotal += count($c);
                echo ' done'.PHP_EOL;
            }

            echo PHP_EOL.'Insertion completed!'.PHP_EOL;
            echo 'Total records inserted: '.$insertedTotal.PHP_EOL;

            // Verify final count
            $finalCount = DB::table($target_table_name)->count();
            echo 'Final table count: '.$finalCount.PHP_EOL;
        } else {
            echo PHP_EOL.'No valid records to insert!'.PHP_EOL;
        }

    }

    protected function isEmptyThenNull($value)
    {
        return empty($value) ? null : $value;
    }
}

// php artisan db:seed --class=Database\Seeders\SusdatCategorySubstanceJoinSeeder
