@props(['authCheck' => false, 'isSuperAdmin' => false])

<!-- CRED Evaluation Modal Window -->
<div x-show="showCredEvaluationModal" 
     x-cloak 
     @keydown.escape.window="closeModalCredEvaluation()"
     class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50">

    <div class="bg-white w-11/12 md:w-4/5 lg:w-4/5 xl:w-3/4 rounded-sm shadow-lg relative" 
         x-transition>

        <!-- Modal Header -->
        <div class="flex justify-between items-center border-b px-4 py-2 bg-sky-600 text-white">
            <div class="flex items-center space-x-4">
                <h3 class="text-lg font-semibold">
                    CRED Evaluation for Record ID: <span x-text="credEvaluationRecordId"></span>
                </h3>
                <h3 class="text-lg font-semibold text-sky-200">
                    Biotest ID: <span x-text="credEvaluationRecord?.ecotox_id || 'N/A'"></span>
                </h3>
            </div>
            <div class="flex items-center space-x-2">
                <!-- Debug button -->
                <button @click="console.log('Modal state:', { showCredEvaluationModal, credQuestions: credQuestions?.length || 0, record: credEvaluationRecord })" 
                        class="px-2 py-1 bg-yellow-600 text-white text-xs rounded-sm hover:bg-yellow-700">
                    Debug
                </button>
                <button @click="closeModalCredEvaluation()" class="text-white hover:text-gray-200 text-xl">
                    &times;
                </button>
            </div>
        </div>

        <!-- Modal Content -->
        <div class="p-4 max-h-[70vh] overflow-y-auto">
            <!-- Loading State -->
            <div x-show="!credEvaluationRecord && showCredEvaluationModal" class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-sky-600 mx-auto"></div>
                <p class="mt-2 text-gray-600">Loading CRED evaluation data...</p>
            </div>

            <!-- CRED Evaluation Content -->
            <div x-show="credEvaluationRecord" x-transition>
                <!-- Debug Info -->
                <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="text-sm text-yellow-800">
                        <strong>Debug Info:</strong>
                        <span x-text="'Questions loaded: ' + (credQuestions?.length || 0)"></span> |
                        <span x-text="'Record ID: ' + (credEvaluationRecordId || 'None')"></span> |
                        <span x-text="'Modal open: ' + showCredEvaluationModal"></span>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <!-- Record Information Summary -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-3">Record Information</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="font-medium text-gray-600">Taxonomic Group:</span>
                                <span x-text="credEvaluationRecord?.taxonomic_group || 'N/A'" class="ml-2"></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-600">Scientific Name:</span>
                                <span x-text="credEvaluationRecord?.scientific_name || 'N/A'" class="ml-2"></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-600">Endpoint:</span>
                                <span x-text="credEvaluationRecord?.endpoint || 'N/A'" class="ml-2"></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-600">Matrix:</span>
                                <span x-text="credEvaluationRecord?.matrix_habitat || 'N/A'" class="ml-2"></span>
                            </div>
                        </div>
                    </div>

                    

                    <!-- CRED Questions Table -->
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-4">CRED Evaluation Questions</h4>
                        
                        <!-- Questions Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full border border-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="border border-gray-200 px-3 py-2 text-left text-sm font-medium text-gray-700 w-20">Number</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left text-sm font-medium text-gray-700">Question</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left text-sm font-medium text-gray-700 w-24">Screening Score</th>
                                        <th class="border border-gray-200 px-3 py-2 text-left text-sm font-medium text-gray-700 w-20">Max Score</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <!-- Main Questions and Sub-questions -->
                                    <template x-for="question in credQuestions" :key="question.id">
                                        <!-- Main Question Row - Spans all columns -->
                                        <tr class="border-b border-gray-200 bg-blue-600 text-white">
                                            <td colspan="4" class="px-3 py-3">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="font-semibold text-lg" x-text="question.question_number"></span>
                                                        <span class="text-blue-100" x-text="question.question_text"></span>
                                                    </div>
                                                    <div class="flex items-center space-x-4 text-sm">
                                                        <span>Screening: <span x-text="question.screening_score || '0.00'"></span></span>
                                                        <span>Max: <span x-text="question.max_score || '0'"></span></span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Sub-questions -->
                                        <template x-for="subQuestion in question.sub_questions" :key="subQuestion.id">
                                            <tr class="border-b border-gray-100 bg-gray-25">
                                                <td class="border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                                    <span class="font-medium text-gray-900" x-text="question.question_number + subQuestion.question_letter"></span>
                                                </td>
                                                <td class="border border-gray-200 px-3 py-2 text-sm text-gray-700" x-text="subQuestion.question_text"></td>
                                                <td class="border border-gray-200 px-3 py-2 text-sm text-gray-600 text-center" x-text="subQuestion.screening_score || '0.00'"></td>
                                                <td class="border border-gray-200 px-3 py-2 text-sm text-gray-600 text-center" x-text="subQuestion.max_score || '0'"></td>
                                            </tr>
                                        </template>
                                        
                                        <!-- Empty row for spacing if no sub-questions -->
                                        <template x-if="!question.sub_questions || question.sub_questions.length === 0">
                                            <tr class="border-b border-gray-100 bg-gray-25">
                                                <td class="border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                                    <span class="font-medium text-gray-900" x-text="question.question_number + 'a'"></span>
                                                </td>
                                                <td class="border border-gray-200 px-3 py-2 text-sm text-gray-700 text-gray-500 italic">No sub-questions defined</td>
                                                <td class="border border-gray-200 px-3 py-2 text-sm text-gray-600 text-center">-</td>
                                                <td class="border border-gray-200 px-3 py-2 text-sm text-gray-600 text-center">-</td>
                                            </tr>
                                        </template>
                                    </template>
                                    
                                    <!-- Loading State for Questions -->
                                    <tr x-show="!credQuestions || credQuestions.length === 0">
                                        <td colspan="4" class="border border-gray-200 px-3 py-8 text-center text-gray-500">
                                            <div class="flex items-center justify-center space-x-2">
                                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-sky-600"></div>
                                                <span>Loading CRED questions...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Total Score Summary -->
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-700">Total Screening Score:</span>
                                <span class="font-semibold text-lg text-gray-900" x-text="totalScreeningScore || '0.00'"></span>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="font-medium text-gray-700">Total Max Score:</span>
                                <span class="font-semibold text-lg text-gray-900" x-text="totalMaxScore || '0'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Previous Evaluations (if any) -->
                    <div x-show="credEvaluationHistory && credEvaluationHistory.length > 0" class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-3">Previous Evaluations</h4>
                        <div class="space-y-2">
                            <template x-for="evaluation in credEvaluationHistory" :key="evaluation.id">
                                <div class="bg-white p-3 rounded-sm border border-gray-200">
                                    <div class="flex justify-between items-start">
                                        <div class="text-sm">
                                            <span class="font-medium">Reliability:</span>
                                            <span x-text="evaluation.reliability_score" class="ml-2"></span>
                                            <span class="font-medium ml-4">Use:</span>
                                            <span x-text="evaluation.use_of_study" class="ml-2"></span>
                                        </div>
                                        <div class="text-xs text-gray-500" x-text="evaluation.evaluated_at"></div>
                                    </div>
                                    <div x-show="evaluation.comments" class="text-sm text-gray-600 mt-2" x-text="evaluation.comments"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-between border-t px-4 py-2">
            <button @click="saveCredEvaluation()" 
                    class="px-4 py-2 bg-green-500 text-white rounded-sm hover:bg-green-600">
                Save Evaluation
            </button>
            <button @click="closeModalCredEvaluation()" 
                    class="px-4 py-2 bg-red-500 text-white rounded-sm hover:bg-red-600">
                Close
            </button>
        </div>
    </div>
</div>
