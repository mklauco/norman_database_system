<x-app-layout>
  <div class="container mx-auto px-4 py-8" 
       x-data="{
         showChangesModal: false,
         selectedEcotoxId: null,
         selectedColumnName: '',
         changesData: null,
         
         openChangesModal(ecotoxId, columnName) {
           console.log('Opening modal for:', ecotoxId, columnName);
           this.selectedEcotoxId = ecotoxId;
           this.selectedColumnName = columnName;
           this.showChangesModal = true;
           this.changesData = null;
           this.loadChangesData(ecotoxId, columnName);
         },
         
         closeChangesModal() {
           this.showChangesModal = false;
           this.selectedEcotoxId = null;
           this.selectedColumnName = '';
           this.changesData = null;
         },
         
         async loadChangesData(ecotoxId, columnName) {
           console.log('Loading changes data for:', ecotoxId, columnName);
           try {
             const url = `/ecotox/credevaluation/changes/${ecotoxId}/${encodeURIComponent(columnName)}`;
             console.log('Fetching from URL:', url);
             const response = await fetch(url);
             if (!response.ok) {
               throw new Error('Failed to fetch changes data');
             }
             this.changesData = await response.json();
             console.log('Changes data loaded:', this.changesData);
           } catch (error) {
             console.error('Error loading changes data:', error);
             this.changesData = [];
           }
         }
       }">
    

    <div>

        <!-- Primary information -->
      <div class="grid grid-cols-3 gap-4">
        <div class="col-span-1">
          @if (!empty(request()->get('returnUrl')))
            <div class="mb-4">
              <a href="{{ request()->get('returnUrl') }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                ← Go Back to Search Results
              </a>
            </div>
          @endif
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
              @if ($recordId)
                CRED Evaluation Form
              @else
                CRED Evaluation Demo Form
              @endif
            </h1>
            @if ($recordId)
              <p class="text-gray-700">Evaluating Record ID: {{ $recordId }}</p>
            @else
              <p class="text-gray-700">This is a demonstration of the CRED evaluation form. You can test the scoring
                system and see how questions are organized.</p>
            @endif
          </div>
        </div>
        <div class="col-span-2">
          @if ($record)
            <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-xs">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Record Information</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">ECOTOX ID</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->ecotox_id }}</p>
                </div>
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Substance</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->substance->name ?? 'N/A' }}</p>
                </div>
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Taxonomic Group</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->taxonomic_group ?? 'N/A' }}</p>
                </div>
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Scientific Name</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->scientific_name ?? 'N/A' }}</p>
                </div>
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Endpoint</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->endpoint ?? 'N/A' }}</p>
                </div>
                <div>
                  <h3 class="text-sm font-medium text-gray-800 mb-1">Test Type</h3>
                  <p class="text-sm text-teal-800 font-mono">{{ $record->test_type ?? 'N/A' }}</p>
                </div>
              </div>
            </div>

            <!-- Substances Context -->
            @if ($substances && $substances->count() > 0)
              <div class="mb-6 bg-slate-50 border border-slate-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-slate-700 mb-2">Search Context</h3>
                <p class="text-sm text-slate-600 mb-2">You are evaluating this record as part of a search for the
                  following
                  substances:</p>
                <div class="flex flex-wrap gap-2">
                  @foreach ($substances as $substance)
                    <span
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                      {{ $substance->name }}
                    </span>
                  @endforeach
                </div>
              </div>
            @endif
          @endif
        </div>
      </div>
      <!-- End of Primary information -->
    </div>

    <!-- Top Go Back Button -->




    <!-- Record Information Section -->


    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-xs">
      <div class="px-1 py-2 bg-gray-50 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">CRED Evaluation Questions</h2>
        <p class="text-sm text-gray-700 mt-1">Rate each question based on the quality and reliability of the data</p>
      </div>

      <table class="w-full border border-gray-300">
        <thead class="bg-gray-100">
          <tr>
            <th
              class="px-1 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-20 border border-gray-300"
              colspan="2">Question
            </th>
            <th
              class="px-1 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-32 border border-gray-300">
              Parameters
            </th>
            <th
              class="px-1 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-48 border border-gray-300">
              Value
            </th>
            <th
              class="px-1 py-2 text-center text-xs font-medium text-gray-700 uppercase tracking-wider w-24 border border-gray-300">
              Screening
              Score</th>
            <th
              class="px-1 py-2 text-center text-xs font-medium text-gray-700 uppercase tracking-wider w-24 border border-gray-300">
              Max Score
            </th>
            <th
              class="px-1 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-40 border border-gray-300">
              Comment
            </th>
            <th
              class="px-1 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-32 border border-gray-300">
              Evaluation
            </th>
            <th
              class="px-1 py-2 text-center text-xs font-medium text-gray-700 uppercase tracking-wider w-20 border border-gray-300">
              Edit
            </th>
          </tr>
        </thead>
        <tbody class="bg-white">
          @if ($credQuestions && $credQuestions->count() > 0)
            @foreach ($credQuestions as $question)
              <!-- Main Question -->
              <tr class="bg-gray-100 border border-gray-300">
                <td class="px-1 py-2 text-sm font-semibold text-center text-black border border-gray-300">
                  {{ $question->question_number }}.
                </td>
                <td class="px-1 py-2 text-sm font-medium text-gray-900 border border-gray-300" colspan="3">
                  {{ $question->question_text }}
                </td>
                <td class="px-1 py-2 text-center border border-gray-300">
                  -
                </td>
                <td class="px-1 py-2 text-sm text-center text-gray-800 font-medium border border-gray-300">
                  {{ $question->max_score ?? '-' }}
                </td>
                <td class="px-1 py-2 text-center border border-gray-300">
                  <textarea name="comment_{{ $question->id }}" rows="3"
                    class="w-full px-1 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-slate-500 focus:border-slate-500"
                    placeholder="Add comment for main question..."></textarea>
                </td>
                <td class="px-1 py-2 text-center border border-gray-300">
                  <select
                    class="w-full px-1 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-slate-500 focus:border-slate-500"
                    name="evaluation_{{ $question->id }}" id="evaluation{{ $question->id }}">
                    <option value="1">not reported</option>
                    <option value="2">not applicable</option>
                    <option value="3">not fulfilled</option>
                    <option value="4">partially fulfilled</option>
                    <option value="5">fulfilled</option>
                  </select>
                </td>
                <td class="px-1 py-2 text-center border border-gray-300">
                  -
                </td>
              </tr>

              <!-- Sub Questions -->
              @if ($question->subQuestions && $question->subQuestions->count() > 0)
                @foreach ($question->subQuestions as $subQuestion)
                  @if ($subQuestion->parameters && $subQuestion->parameters->count() > 0)
                    @foreach ($subQuestion->parameters as $index => $parameter)
                      @php
                        $hasChanges = false;
                        if (isset($parameterValues[$parameter->id]) && is_array($parameterValues[$parameter->id])) {
                            $hasChanges = $parameterValues[$parameter->id]['hasChanges'] ?? false;
                        }
                      @endphp
                      <tr class="{{ $hasChanges ? 'bg-rose-300' : 'bg-white' }} border border-gray-300">
                        @if ($index === 0)
                          <!-- First parameter row - show sub-question info with rowspan -->
                          <td class="px-1 py-2 text-sm  text-center text-gray-700 border border-gray-300"
                            rowspan="{{ $subQuestion->parameters->count() }}">
                            {{ $subQuestion->question_letter }}
                          </td>
                          <td class="px-1 py-2 text-sm text-gray-700 border border-gray-300"
                            rowspan="{{ $subQuestion->parameters->count() }}">
                            {{ $subQuestion->question_text }}
                          </td>
                          <td class="px-1 py-2 text-sm text-gray-700 border border-gray-300">
                            {{ $parameter->parameter_label }}
                          </td>
                          <td class="px-1 py-2 text-sm text-gray-700 border border-gray-300">
                            @include('ecotox.credevaluation.partials.parameter-input', [
                                'parameter' => $parameter,
                                'parameterValues' => $parameterValues,
                            ])
                          </td>
                          <td class="px-1 py-2 text-sm text-center text-gray-700 border border-gray-300"
                            rowspan="{{ $subQuestion->parameters->count() }}">
                            {{ $subQuestion->max_score }}
                          </td>
                          <td class="px-1 py-2 text-sm text-center text-gray-500 border border-gray-300"
                            rowspan="{{ $subQuestion->parameters->count() }}">
                            -
                          </td>
                          <td class="px-1 py-2 text-sm text-gray-700 border border-gray-300"
                            rowspan="{{ $subQuestion->parameters->count() }}">
                            <textarea name="comment_{{ $subQuestion->id }}" rows="3"
                              class="w-full px-1 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-slate-500 focus:border-slate-500"
                              placeholder="Add comment..."></textarea>
                          </td>
                          <td class="px-1 py-2 text-sm text-gray-700 border border-gray-300"
                            rowspan="{{ $subQuestion->parameters->count() }}">
                            <select
                              class="w-full px-1 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-slate-500 focus:border-slate-500"
                              name="evaluation_{{ $subQuestion->id }}" id="evaluation{{ $subQuestion->id }}">
                              <option value="1">not reported</option>
                              <option value="2">not applicable</option>
                              <option value="3">not fulfilled</option>
                              <option value="4">partially fulfilled</option>
                              <option value="5">fulfilled</option>
                            </select>
                          </td>
                          <td class="px-1 py-2 text-sm text-center text-gray-700 border border-gray-300"
                            rowspan="{{ $subQuestion->parameters->count() }}">
                            @if (isset($parameterValues[$subQuestion->parameters->first()->id]['hasChanges']) && $parameterValues[$subQuestion->parameters->first()->id]['hasChanges'])
                              <button 
                                @click="openChangesModal('{{ $recordId }}', '{{ $parameterValues[$subQuestion->parameters->first()->id]['columnName'] }}')"
                                class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full hover:bg-green-200 cursor-pointer transition-colors">
                                <i class="fas fa-edit mr-1"></i>
                                Edited
                              </button>
                            @else
                              <span class="text-gray-400">-</span>
                            @endif
                          </td>
                        @else
                          <!-- Subsequent parameter rows - only show parameter label and value input -->
                          <td class="px-1 py-2 text-sm text-gray-700 border border-gray-300">
                            {{ $parameter->parameter_label }}
                          </td>
                          <td class="px-1 py-2 text-sm text-gray-700 border border-gray-300">
                            @include('ecotox.credevaluation.partials.parameter-input', [
                                'parameter' => $parameter,
                                'parameterValues' => $parameterValues,
                            ])
                          </td>
                        @endif
                      </tr>
                    @endforeach
                  @else
                    <!-- Sub-question with no parameters -->
                    <tr class="bg-white border border-gray-300">
                      <td class="px-1 py-2 text-sm text-gray-700 border border-gray-300">
                        {{ $subQuestion->question_letter }}
                      </td>
                      <td class="px-1 py-2 text-sm text-gray-700 border border-gray-300">
                        {{ $subQuestion->question_text }}
                      </td>
                      <td class="px-1 py-2 text-sm text-gray-700 border border-gray-300">
                        <span class="text-gray-400 text-xs">No parameters</span>
                      </td>
                      <td class="px-1 py-2 text-sm text-gray-700 border border-gray-300">
                        <!-- No value input for questions without parameters -->
                      </td>
                      <td class="px-1 py-2 text-sm text-center text-gray-700 border border-gray-300">
                        {{ $subQuestion->max_score }}
                      </td>
                      <td class="px-1 py-2 text-sm text-center text-gray-500 border border-gray-300">
                        -
                      </td>
                      <td class="px-1 py-2 text-sm text-gray-700 border border-gray-300">
                        <textarea name="comment_{{ $subQuestion->id }}" rows="3"
                          class="w-full px-1 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-slate-500 focus:border-slate-500"
                          placeholder="Add comment..."></textarea>
                      </td>
                      <td class="px-1 py-2 text-sm text-gray-700 border border-gray-300">
                        <select
                          class="w-full px-1 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-slate-500 focus:border-slate-500"
                          name="evaluation_{{ $subQuestion->id }}" id="evaluation{{ $subQuestion->id }}">
                          <option value="1">not reported</option>
                          <option value="2">not applicable</option>
                          <option value="3">not fulfilled</option>
                          <option value="4">partially fulfilled</option>
                          <option value="5">fulfilled</option>
                        </select>
                      </td>
                      <td class="px-1 py-2 text-sm text-center text-gray-300 border border-gray-300">
                        -
                      </td>
                    </tr>
                  @endif
                @endforeach
              @endif
            @endforeach
          @else
            <tr>
              <td colspan="10" class="px-1 py-2 text-sm text-gray-500 text-center">
                No CRED questions available
              </td>
            </tr>
          @endif
        </tbody>
      </table>
    </div>

    <!-- Summary Section -->
    {{-- @if ($credQuestions && $credQuestions->count() > 0)
      <div class="mt-6 bg-white border border-gray-200 rounded-lg p-6 shadow-xs">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Evaluation Summary</h2>
        
        <!-- Progress Bar -->
        <div class="mb-6">
          <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium text-gray-700">Evaluation Progress</span>
            <span class="text-sm text-gray-500" id="progress-text">0%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-slate-600 h-2 rounded-full transition-all duration-300" id="progress-bar" style="width: 0%"></div>
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-800 mb-2">Total Questions</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $credQuestions->count() }}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-800 mb-2">Total Sub-questions</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $credQuestions->sum(function($q) { return $q->subQuestions->count(); }) }}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-800 mb-2">Maximum Possible Score</h3>
            <p class="text-2xl font-bold text-gray-900" id="max-possible-score">{{ $credQuestions->sum('max_score') + $credQuestions->sum(function($q) { return $q->subQuestions->sum('max_score'); }) }}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-800 mb-2">Your Total Score</h3>
            <p class="text-2xl font-bold text-slate-600" id="your-total-score">0</p>
          </div>
        </div>
        
        <!-- Score Quality Indicator -->
        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
          <h3 class="text-sm font-medium text-gray-800 mb-2">Score Quality Assessment</h3>
          <div class="flex items-center space-x-2">
            <div class="w-4 h-4 rounded-full" id="quality-indicator"></div>
            <span class="text-sm text-gray-700" id="quality-text">Enter scores to see quality assessment</span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mt-6 flex justify-between items-center">
        <!-- Left side - Go Back button -->
        @if ($returnUrl)
          <a href="{{ $returnUrl }}" class="px-6 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
            ← Go Back to Search Results
          </a>
        @else
          <a href="{{ route('ecotox.credevaluation.home.index') }}" class="px-6 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
            ← Back to Home
          </a>
        @endif
        
        <!-- Right side - Action buttons -->
        <div class="flex space-x-4">
          @if ($recordId)
            <button type="button" class="btn-submit px-6 py-2 text-sm font-medium text-white bg-slate-600 border border-transparent rounded-md hover:bg-slate-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
              Save Evaluation
            </button>
          @else
            <button type="button" class="btn-submit px-6 py-2 text-sm font-medium text-white bg-slate-600 border border-transparent rounded-md hover:bg-slate-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
              Export Scores
            </button>
          @endif
        </div>
      </div>
    @endif --}}
  </div>

  <!-- Changes Modal -->
  <div x-show="showChangesModal" 
       x-cloak 
       @keydown.escape.window="closeChangesModal()"
       class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50">

    <div class="bg-white w-11/12 md:w-3/4 lg:w-3/4 xl:w-2/3 rounded-sm shadow-lg relative">

      <!-- Modal Header -->
      <div class="flex justify-between items-center border-b px-4 py-2 bg-lime-600 text-white">
        <div class="flex items-center space-x-4">
          <h3 class="text-lg font-semibold">
            Changes History
          </h3>
        </div>
        <button @click="closeChangesModal()" class="text-white hover:text-gray-200 text-xl">
          &times;
        </button>
      </div>

      <!-- Modal Content -->
      <div class="p-4 max-h-[70vh] overflow-y-auto">
        <!-- Loading State -->
        <div x-show="!changesData && showChangesModal" class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-lime-600 mx-auto"></div>
          <p class="mt-2 text-gray-600">Loading changes data...</p>
        </div>

        <!-- Changes Content -->
        <div x-show="changesData" x-transition>
          <div class="text-sm py-2 px-2">
            For column: <span x-text="selectedColumnName" class="font-mono"></span>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full border border-gray-300 text-sm">
              <thead>
                <tr class="bg-gray-100">
                  <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                    Date
                  </th>
                  <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                    User
                  </th>
                  <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                    Old Value
                  </th>
                  <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                    New Value
                  </th>
                  <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                    Change Type
                  </th>
                </tr>
              </thead>
              <tbody>
                <template x-for="change in changesData" :key="change.id">
                  <tr class="border-b border-gray-200">
                    <td class="border border-gray-300 px-3 py-2" x-text="change.change_date"></td>
                    <td class="border border-gray-300 px-3 py-2" x-text="change.user_name || 'Unknown'"></td>
                    <td class="border border-gray-300 px-3 py-2" x-text="change.change_old || 'N/A'"></td>
                    <td class="border border-gray-300 px-3 py-2" x-text="change.change_new || 'N/A'"></td>
                    <td class="border border-gray-300 px-3 py-2" x-text="change.change_type || 'N/A'"></td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="flex justify-between border-t px-4 py-2">
        <button @click="closeChangesModal()" 
                class="px-4 py-2 bg-red-500 text-white rounded-sm hover:bg-red-600">
          Close
        </button>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const scoreInputs = document.querySelectorAll('.score-input');
      const yourTotalScore = document.getElementById('your-total-score');
      const progressBar = document.getElementById('progress-bar');
      const progressText = document.getElementById('progress-text');
      const qualityIndicator = document.getElementById('quality-indicator');
      const qualityText = document.getElementById('quality-text');

      function calculateTotalScore() {
        let total = 0;
        scoreInputs.forEach(input => {
          const value = parseFloat(input.value) || 0;
          total += value;
        });
        yourTotalScore.textContent = total.toFixed(1);

        // Update color based on score
        const maxPossible = parseFloat(document.getElementById('max-possible-score').textContent);
        const percentage = (total / maxPossible) * 100;

        if (percentage >= 80) {
          yourTotalScore.className = 'text-2xl font-bold text-green-600';
          qualityIndicator.className = 'bg-green-500';
          qualityText.textContent = 'Excellent';
        } else if (percentage >= 60) {
          yourTotalScore.className = 'text-2xl font-bold text-yellow-600';
          qualityIndicator.className = 'bg-yellow-500';
          qualityText.textContent = 'Good';
        } else {
          yourTotalScore.className = 'text-2xl font-bold text-red-600';
          qualityIndicator.className = 'bg-red-500';
          qualityText.textContent = 'Poor';
        }

        // Update progress bar
        progressBar.style.width = `${percentage}%`;
        progressText.textContent = `${percentage.toFixed(0)}%`;
      }

      // Add event listeners to all score inputs
      scoreInputs.forEach(input => {
        input.addEventListener('input', calculateTotalScore);
        input.addEventListener('change', calculateTotalScore);
      });

      // Initial calculation
      calculateTotalScore();
    });
  </script>
</x-app-layout>
