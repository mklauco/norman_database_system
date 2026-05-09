<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

class EcotoxCredQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'ecotox_cred_questions';
        DB::table($target_table_name)->truncate();
        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/ecotox_tables/table_cred.csv';
        $rows = SimpleExcelReader::create($path)->getRows();

        $mainQuestions = [];
        $subQuestions = [];

        foreach ($rows as $r) {
            $mainQuestionNumber = (int) $r['cred_main_question_number'];
            $subQuestionLetter = trim($r['cred_sub_question_number']);
            $weightingFactor = (float) $r['weighting_factor'];
            $sortOrder = (int) $r['cred_question_running_number'];

            // Skip rows that are just placeholders (no meaningful content)
            if ($mainQuestionNumber === 0 && empty($subQuestionLetter) && empty(trim($r['cred_main_question']))) {
                continue;
            }

            // If this row has main question text, create a main question record
            if (! empty(trim($r['cred_main_question']))) {
                $mainQuestions[] = [
                    'question_number' => $mainQuestionNumber,
                    'question_letter' => null,
                    'question_text' => trim($r['cred_main_question']),
                    'parent_id' => null,
                    'max_score' => $weightingFactor,
                    'screening_score' => $weightingFactor,
                    'sort_order' => $sortOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // If this row has a sub-question, create a sub-question record
            if (! empty($subQuestionLetter)) {
                // For sub-questions with cred_main_question_number = 0, infer the main question number from the letter
                $actualMainQuestionNumber = $mainQuestionNumber;
                if ($mainQuestionNumber === 0) {
                    // Extract the number from the sub-question letter (e.g., "3b" -> 3, "4c" -> 4)
                    if (preg_match('/^(\d+)/', $subQuestionLetter, $matches)) {
                        $actualMainQuestionNumber = (int) $matches[1];
                    }
                }

                $subQuestions[] = [
                    'question_number' => $actualMainQuestionNumber,
                    'question_letter' => $subQuestionLetter,
                    'question_text' => trim($r['cred_sub_question']),
                    'parent_id' => null, // Will be set after main questions are created
                    'max_score' => $weightingFactor,
                    'screening_score' => null, // Sub-questions don't have screening scores
                    'sort_order' => $sortOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Insert main questions first
        $chunkSize = 100;
        $chunks = array_chunk($mainQuestions, $chunkSize);
        $k = 0;
        $count = ceil(count($mainQuestions) / $chunkSize) - 1;
        foreach ($chunks as $c) {
            echo $k++.'/'.$count."; \n";
            DB::table($target_table_name)->insert($c);
        }

        // Get the inserted main questions to set parent_id for sub-questions
        $mainQuestionIds = DB::table($target_table_name)
            ->whereNull('parent_id')
            ->pluck('id', 'question_number');

        // Update sub-questions with parent_id
        foreach ($subQuestions as &$subQuestion) {
            if (isset($mainQuestionIds[$subQuestion['question_number']])) {
                $subQuestion['parent_id'] = $mainQuestionIds[$subQuestion['question_number']];
            }
        }

        // Insert sub-questions
        $chunks = array_chunk($subQuestions, $chunkSize);
        $k = 0;
        $count = ceil(count($subQuestions) / $chunkSize) - 1;
        foreach ($chunks as $c) {
            echo $k++.'/'.$count."; \n";
            DB::table($target_table_name)->insert($c);
        }

        echo "EcotoxCredQuestion table seeded successfully!\n";
        echo 'Main questions: '.count($mainQuestions)."\n";
        echo 'Sub-questions: '.count(array_filter($subQuestions, fn ($q) => $q['parent_id']))."\n";
    }

    protected function isEmptyThenNull($value)
    {
        return empty($value) ? null : $value;
    }
}

// php artisan db:seed --class=EcotoxCredQuestionSeeder
