<?php

namespace Database\Seeders;

use App\Models\Ecotox\EcotoxComparativeTableConfig;
use App\Models\Ecotox\EcotoxCredQuestion;
use App\Models\Ecotox\EcotoxCredQuestionParameter;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Spatie\SimpleExcel\SimpleExcelReader;

class EcotoxCredQuestionParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EcotoxCredQuestionParameter::truncate();
        $now = Carbon::now();
        $path = base_path().'/database/seeders/seeds/ecotox_tables/table_cred.csv';
        $rows = SimpleExcelReader::create($path)->getRows();

        $parameters = [];

        // Group rows by cca_number to understand which parameters belong to which sub-questions
        $groupedRows = [];
        foreach ($rows as $r) {
            $ccaNumber = (int) $r['cca_number'];
            if ($ccaNumber > 0) {
                $groupedRows[$ccaNumber][] = $r;
            }
        }

        foreach ($groupedRows as $ccaNumber => $groupRows) {
            // Find the representative row for this group (one that has question data)
            $representativeRow = null;
            foreach ($groupRows as $r) {
                if (! empty(trim($r['cred_main_question'])) || ! empty(trim($r['cred_sub_question_number']))) {
                    $representativeRow = $r;
                    break;
                }
            }

            if (! $representativeRow) {
                continue;
            }

            $mainQuestionNumber = (int) $representativeRow['cred_main_question_number'];
            $subQuestionLetter = trim($representativeRow['cred_sub_question_number']);
            $questionRunningNumber = (int) $representativeRow['question_running_number'];

            // Find the question_id for this group
            $questionId = $this->findQuestionId($mainQuestionNumber, $subQuestionLetter, $questionRunningNumber);

            if (! $questionId) {
                echo "Warning: Could not find question for cca_group: {$ccaNumber}, main: {$mainQuestionNumber}, sub: {$subQuestionLetter}\n";

                continue;
            }

            // Process all tab_ids in this group as parameters for the same question
            foreach ($groupRows as $r) {
                $tabId = (int) $r['tab_id'];

                // Skip rows where tab_id is 0 (no parameter mapping)
                if ($tabId === 0) {
                    continue;
                }

                // Find the ecotox_config_id by matching tab_id to column_id
                $ecotoxConfigId = $this->findEcotoxConfigId($tabId);

                if (! $ecotoxConfigId) {
                    echo "Warning: Could not find ecotox config for tab_id: {$tabId}\n";

                    continue;
                }

                // Get parameter label from the ecotox config column_name
                $parameterLabel = $this->getParameterLabel($ecotoxConfigId);

                // Determine if this is a required parameter (main questions are usually required)
                $isRequired = ! empty(trim($r['cred_main_question'])) || ! empty(trim($r['cred_sub_question']));

                $parameters[] = [
                    'question_id' => $questionId,
                    'ecotox_config_id' => $ecotoxConfigId,
                    'parameter_label' => $parameterLabel,
                    'is_required' => $isRequired,
                    'sort_order' => $questionRunningNumber,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Insert parameters in chunks
        $chunkSize = 100;
        $chunks = array_chunk($parameters, $chunkSize);
        $k = 0;
        $count = ceil(count($parameters) / $chunkSize) - 1;

        foreach ($chunks as $c) {
            echo $k++.'/'.$count."; \n";
            EcotoxCredQuestionParameter::insert($c);
        }

        echo "EcotoxCredQuestionParameter table seeded successfully!\n";
        echo 'Total parameters: '.count($parameters)."\n";
    }

    /**
     * Find the question ID based on the question structure
     */
    protected function findQuestionId(int $mainQuestionNumber, string $subQuestionLetter, int $questionRunningNumber): ?int
    {
        if (! empty($subQuestionLetter)) {
            // This is a sub-question, find by main question number and sub-question letter
            $question = EcotoxCredQuestion::where('question_number', $mainQuestionNumber)
                ->where('question_letter', $subQuestionLetter)
                ->whereNotNull('parent_id')
                ->first();

            if ($question) {
                return $question->id;
            }
        } else {
            // This is a main question, find by question number and no letter
            $question = EcotoxCredQuestion::where('question_number', $mainQuestionNumber)
                ->whereNull('question_letter')
                ->whereNull('parent_id')
                ->first();

            if ($question) {
                return $question->id;
            }
        }

        // Fallback: try to find by running number if the above fails
        $question = EcotoxCredQuestion::where('sort_order', $questionRunningNumber)
            ->first();

        return $question ? $question->id : null;
    }

    /**
     * Find the ecotox config ID by matching tab_id to column_id
     */
    protected function findEcotoxConfigId(int $tabId): ?int
    {
        $config = EcotoxComparativeTableConfig::where('column_id', $tabId)->first();

        return $config ? $config->id : null;
    }

    /**
     * Get parameter label from the ecotox config column_name
     */
    protected function getParameterLabel(int $ecotoxConfigId): string
    {
        $config = EcotoxComparativeTableConfig::find($ecotoxConfigId);

        if ($config && ! empty($config->column_name)) {
            return $config->column_name;
        }

        // Fallback to a generic label
        return "Parameter for config_id {$ecotoxConfigId}";
    }
}

// php artisan db:seed --class=EcotoxCredQuestionParameterSeeder
