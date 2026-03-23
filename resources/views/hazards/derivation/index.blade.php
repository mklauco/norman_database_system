<x-app-layout>
  <x-slot name="header">
    @include('hazards.header')
  </x-slot>

  @php
    $currentUserDisplayName = trim((string) (auth()->user()?->formatted_name ?? ''));
    if ($currentUserDisplayName === '') {
      $currentUserDisplayName = trim((string) (auth()->user()?->full_name ?? ''));
    }
    if ($currentUserDisplayName === '') {
      $currentUserDisplayName = trim((string) (auth()->user()?->username ?? ''));
    }
    if ($currentUserDisplayName === '') {
      $currentUserDisplayName = (string) (auth()->user()?->email ?? 'NDSEXPERT');
    }

    $bucketGroups = [
      'P' => ['pred' => 'P_pred', 'exp' => 'P_exp'],
      'B' => ['pred' => 'B_pred', 'exp' => 'B_exp'],
      'M' => ['pred' => 'M_pred', 'exp' => 'M_exp'],
      'T' => ['pred' => 'T_pred', 'exp' => 'T_exp'],
    ];

    $bucketTitles = [
      'P' => 'Persistency',
      'B' => 'Bioaccumulation',
      'M' => 'Mobility',
      'T' => 'Toxicity',
    ];

    $criterionCounts = [];
    foreach ($bucketGroups as $criterion => $group) {
      $criterionCounts[$criterion] = count($candidates[$group['pred']] ?? []) + count($candidates[$group['exp']] ?? []);
    }
    $allCandidatesCount = array_sum($criterionCounts);

    $formatDate = static function ($value) {
      if (empty($value)) {
        return 'N/A';
      }

      try {
        return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d H:i');
      } catch (\Throwable $e) {
        return (string) $value;
      }
    };
  @endphp

  <div class="py-4" x-data="{ activeCriterion: 'all' }">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900 space-y-6">
          <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Hazards Derivation</h1>
              <div class="text-sm text-gray-600 mt-2">
                <span class="font-medium">{{ $substance->substance_name ?? 'Substance' }}</span>
                |
                CAS: {{ $substance->cas_no ?? 'N/A' }}
                |
                NORMAN SusDat ID: {{ $susdatSubstanceId ? 'NS' . str_pad((string) $susdatSubstanceId, 8, '0', STR_PAD_LEFT) : 'N/A' }}
                |
                {{ $substance->inchikey ?? 'N/A' }}
              </div>
            </div>

            <div class="flex gap-2">
              <a href="{{ route('hazards.derivation.search.filter') }}" class="btn-clear">Change Substance</a>
            </div>
          </div>

          <div class="border-b border-gray-200">
            <nav class="-mb-px flex flex-wrap gap-6">
              <button
                type="button"
                @click="activeCriterion = 'all'"
                :class="activeCriterion === 'all'
                  ? 'border-sky-500 text-sky-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                All Results
                <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-sky-100 text-sky-800 rounded-full">
                  {{ $allCandidatesCount }}
                </span>
              </button>

              @foreach ($bucketGroups as $criterion => $group)
                <button
                  type="button"
                  @click="activeCriterion = '{{ $criterion }}'"
                  :class="activeCriterion === '{{ $criterion }}'
                    ? 'border-sky-500 text-sky-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                  class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                  {{ $criterion }}
                  <span class="ml-2 py-0.5 px-2.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ $criterionCounts[$criterion] }}
                  </span>
                </button>
              @endforeach
            </nav>
          </div>

          @foreach ($bucketGroups as $criterion => $group)
            <div
              x-show="activeCriterion === 'all' || activeCriterion === '{{ $criterion }}'"
              class="border border-gray-200 rounded-lg overflow-hidden">
              <div class="bg-slate-100 px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">{{ $bucketTitles[$criterion] }} ({{ $criterion }})</h2>
              </div>

              <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 p-4">
                @foreach (['pred' => 'Predicted', 'exp' => 'Experimental'] as $key => $label)
                  @php
                    $bucket = $group[$key];
                    $bucketCandidates = $candidates[$bucket] ?? [];
                    $bucketAuto = $currentAuto[$bucket] ?? null;
                    $bucketVotes = $currentVotes[$bucket] ?? [];
                    $bucketVotedMap = $votedMap[$bucket] ?? [];
                  @endphp

                  <div class="border border-gray-200 rounded-lg overflow-hidden bg-white">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                      <h3 class="font-semibold text-gray-800">{{ $label }}</h3>
                      <span class="text-xs text-gray-500">{{ count($bucketCandidates) }} candidates</span>
                    </div>

                    <div class="px-4 py-3 bg-white border-b border-gray-200">
                      <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Candidate Records</div>
                      <div class="overflow-x-auto">
                        <table class="table-standard text-sm">
                          <thead>
                            <tr class="bg-gray-600 text-white">
                              <th rowspan="2">ID</th>
                              <th rowspan="2">Data source</th>
                              <th rowspan="2">Test type</th>
                              <th rowspan="2">Parameter name</th>
                              <th colspan="2">Original</th>
                              <th colspan="2">Assessment</th>
                              <th rowspan="2">Suggested assessment class</th>
                              <th rowspan="2">AD</th>
                              <th rowspan="2">Reliability score</th>
                              <th rowspan="2">Select</th>
                              <th rowspan="2">Auto</th>
                              <th rowspan="2">Expert</th>
                            </tr>
                            <tr class="bg-gray-600 text-white">
                              <th>Value</th>
                              <th>Unit</th>
                              <th>Value</th>
                              <th>Unit</th>
                            </tr>
                          </thead>
                          <tbody>
                            @forelse ($bucketCandidates as $row)
                              <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                                <td class="p-1 text-center">
                                  <a href="{{ route('hazards.data.form', $row['hazards_substance_data_id']) }}" class="link-lime-text" target="_blank">
                                    {{ $row['hazards_substance_data_id'] }}
                                  </a>
                                </td>
                                <td class="p-1 text-center">{{ $row['data_source'] }}</td>
                                <td class="p-1 text-center">{{ $row['test_type'] }}</td>
                                <td class="p-1 text-center">{{ $row['norman_parameter_name'] }}</td>
                                <td class="p-1 text-center">{{ $row['original_value'] }}</td>
                                <td class="p-1 text-center">{{ $row['original_unit'] }}</td>
                                <td class="p-1 text-center">{{ $row['value_assessment_index'] }}</td>
                                <td class="p-1 text-center">{{ $row['unit'] }}</td>
                                <td class="p-1 text-center">{{ $row['assessment_class'] }}</td>
                                <td class="p-1 text-center">{{ $row['applicability_domain_score'] }}</td>
                                <td class="p-1 text-center">{{ $row['reliability_score'] }}</td>
                                <td class="p-1 text-center">
                                  @if ($row['auto_selected'])
                                    <span class="text-xs px-2 py-1 bg-gray-200 text-gray-700">Auto</span>
                                  @elseif (isset($bucketVotedMap[$row['hazards_substance_data_id']]))
                                    <form method="POST" action="{{ route('hazards.derivation.vote.remove') }}">
                                      @csrf
                                      <input type="hidden" name="selection_id" value="{{ $bucketVotedMap[$row['hazards_substance_data_id']] }}">
                                      <button type="submit" class="text-xs px-2 py-1 bg-red-100 text-red-700">Remove vote</button>
                                    </form>
                                  @else
                                    <button
                                      type="button"
                                      class="text-xs px-2 py-1 bg-lime-100 text-lime-800"
                                      data-susdat-substance-id="{{ $susdatSubstanceId }}"
                                      data-bucket="{{ $bucket }}"
                                      data-hazard-criterion="{{ $criterion }}"
                                      data-hazards-substance-data-id="{{ $row['hazards_substance_data_id'] }}"
                                      data-data-source="{{ $row['data_source'] }}"
                                      data-test-type="{{ $row['test_type'] }}"
                                      data-original-value="{{ $row['original_value'] }}"
                                      data-original-unit="{{ $row['original_unit'] }}"
                                      data-assessment-value="{{ $row['value_assessment_index'] }}"
                                      data-unit="{{ $row['unit'] }}"
                                      data-assessment-class="{{ $row['assessment_class'] }}"
                                      data-reliability-score="{{ $row['reliability_score'] }}"
                                      onclick="openHazardsVoteModal(this)">
                                      Vote
                                    </button>
                                  @endif
                                </td>
                                <td class="p-1 text-center">@if ($row['auto_selected']) yes @endif</td>
                                <td class="p-1 text-center">-</td>
                              </tr>
                            @empty
                              <tr>
                                <td colspan="14" class="p-3 text-center text-gray-500">No data</td>
                              </tr>
                            @endforelse
                          </tbody>
                        </table>
                      </div>
                    </div>

                    <div class="px-4 py-3 bg-slate-50">
                      <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Selection History</div>
                      <div class="overflow-x-auto">
                        <table class="table-standard text-sm">
                          <thead>
                            <tr class="bg-gray-600 text-white">
                              <th rowspan="2">Selection</th>
                              <th rowspan="2">Data source</th>
                              <th rowspan="2">Test type</th>
                              <th rowspan="2">Parameter name</th>
                              <th colspan="2">Original</th>
                              <th colspan="2">Assessment</th>
                              <th rowspan="2">NORMAN Classification</th>
                              <th rowspan="2">Classification type</th>
                              <th rowspan="2">Vote</th>
                              <th rowspan="2">Expert</th>
                              <th rowspan="2">Date</th>
                            </tr>
                            <tr class="bg-gray-600 text-white">
                              <th>Value</th>
                              <th>Unit</th>
                              <th>Value</th>
                              <th>Unit</th>
                            </tr>
                          </thead>
                          <tbody>
                            @if ($bucketAuto)
                              <tr class="bg-green-50">
                                <td class="p-1 text-center">
                                  <a href="{{ route('hazards.derivation.metadata.show', $bucketAuto['selection_id']) }}" class="link-lime-text" target="_blank">
                                    {{ $bucketAuto['selection_id'] }}
                                  </a>
                                </td>
                                <td class="p-1 text-center">{{ $bucketAuto['data_source'] }}</td>
                                <td class="p-1 text-center">{{ $bucketAuto['test_type'] }}</td>
                                <td class="p-1 text-center">{{ $bucketAuto['norman_parameter_name'] }}</td>
                                <td class="p-1 text-center">{{ $bucketAuto['original_value'] }}</td>
                                <td class="p-1 text-center">{{ $bucketAuto['original_unit'] }}</td>
                                <td class="p-1 text-center">{{ $bucketAuto['value_assessment_index'] }}</td>
                                <td class="p-1 text-center">{{ $bucketAuto['unit'] }}</td>
                                <td class="p-1 text-center">{{ $bucketAuto['assessment_class'] }}</td>
                                <td class="p-1 text-center">
                                  <div>{{ $bucketAuto['classification_type'] }}</div>
                                  <div class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 bg-slate-200 text-slate-800 text-xs font-medium rounded-full">
                                      Auto
                                    </span>
                                  </div>
                                </td>
                                <td class="p-1 text-center">{{ $bucketAuto['vote'] }}</td>
                                <td class="p-1 text-center">{{ $bucketAuto['expert'] }}</td>
                                <td class="p-1 text-center">{{ $formatDate($bucketAuto['date']) }}</td>
                              </tr>
                            @endif

                            @forelse ($bucketVotes as $vote)
                              <tr class="@if ($vote['active']) bg-green-50 @elseif ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                                <td class="p-1 text-center">
                                  <a href="{{ route('hazards.derivation.metadata.show', $vote['selection_id']) }}" class="link-lime-text" target="_blank">
                                    {{ $vote['selection_id'] }}
                                  </a>
                                </td>
                                <td class="p-1 text-center">{{ $vote['data_source'] }}</td>
                                <td class="p-1 text-center">{{ $vote['test_type'] }}</td>
                                <td class="p-1 text-center">{{ $vote['norman_parameter_name'] }}</td>
                                <td class="p-1 text-center">{{ $vote['original_value'] }}</td>
                                <td class="p-1 text-center">{{ $vote['original_unit'] }}</td>
                                <td class="p-1 text-center">{{ $vote['value_assessment_index'] }}</td>
                                <td class="p-1 text-center">{{ $vote['unit'] }}</td>
                                <td class="p-1 text-center">{{ $vote['assessment_class'] }}</td>
                                <td class="p-1 text-center">
                                  <div>{{ $vote['classification_type'] }}</div>
                                  @if ($vote['active'])
                                    <div class="mt-1">
                                      <span class="inline-flex items-center px-2 py-0.5 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                                        Current vote
                                      </span>
                                    </div>
                                  @endif
                                </td>
                                <td class="p-1 text-center">{{ $vote['vote'] }}</td>
                                <td class="p-1 text-center">{{ $vote['expert'] }}</td>
                                <td class="p-1 text-center">{{ $formatDate($vote['date']) }}</td>
                              </tr>
                            @empty
                              @if (! $bucketAuto)
                                <tr>
                                  <td colspan="13" class="p-3 text-center text-gray-500">No data</td>
                                </tr>
                              @endif
                            @endforelse
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      </div>
      <div id="hazards-vote-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="fixed inset-0 bg-gray-800 bg-opacity-50" onclick="closeHazardsVoteModal()"></div>
        <div class="relative min-h-full px-4 py-6">
          <div class="bg-white w-full md:w-3/4 lg:w-2/3 max-w-4xl rounded-lg shadow-xl mx-auto overflow-hidden">
            <div class="flex justify-between items-center border-b px-4 py-3 bg-stone-600 text-white">
              <h3 class="text-lg font-semibold">Confirm Derivation Vote</h3>
              <button type="button" class="text-white hover:text-gray-200 text-2xl leading-none p-1" onclick="closeHazardsVoteModal()">
                &times;
              </button>
            </div>

            <form method="POST" action="{{ route('hazards.derivation.vote') }}">
            @csrf
            <input type="hidden" id="vote-susdat-substance-id" name="susdat_substance_id">
            <input type="hidden" id="vote-bucket" name="bucket">
            <input type="hidden" id="vote-hazards-substance-data-id" name="hazards_substance_data_id">

            <div class="p-4 space-y-5">
              <div class="space-y-3">
                <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Source and Reference</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Editor</label>
                    <input type="text" id="vote-editor" name="meta_editor" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Record date</label>
                    <input type="text" id="vote-date" name="meta_date" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PBMT Classification code</label>
                    <input type="text" id="vote-pbmt-classification-code" name="meta_pbmt_classification_code" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference type</label>
                    <input type="text" id="vote-reference-type" name="meta_reference_type" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                  <input type="text" id="vote-title" name="meta_title" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Authors</label>
                  <input type="text" id="vote-authors" name="meta_authors" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                    <input type="text" id="vote-year" name="meta_year" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">DOI</label>
                    <input type="text" id="vote-doi" name="meta_doi" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Bibliographic source</label>
                  <textarea id="vote-bibliographic-source" name="meta_bibliographic_source" rows="2" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm"></textarea>
                </div>
              </div>

              <div class="space-y-3">
                <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Test and Identity</h4>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Test type</label>
                  <input type="text" id="vote-test-type" name="meta_test_type" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Performed under GLP</label>
                    <input type="text" id="vote-glp" name="meta_glp" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Standard test</label>
                    <input type="text" id="vote-standard-test" name="meta_standard_test" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Substance name</label>
                    <input type="text" id="vote-substance-name" name="meta_substance_name" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CAS</label>
                    <input type="text" id="vote-cas" name="meta_cas" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Radio labeled substance</label>
                    <input type="text" id="vote-radio-labeled" name="meta_radio_labeled" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Standard qualifier</label>
                    <input type="text" id="vote-standard-qualifier" name="meta_standard_qualifier" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Standard used</label>
                    <input type="text" id="vote-standard-used" name="meta_standard_used" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Test matrix</label>
                    <input type="text" id="vote-test-matrix" name="meta_test_matrix" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Test species</label>
                    <input type="text" id="vote-test-species" name="meta_test_species" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duration days</label>
                    <input type="text" id="vote-duration-days" name="meta_duration_days" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Exposure concentration</label>
                    <input type="text" id="vote-exposure-concentration" name="meta_exposure_concentration" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">pH</label>
                    <input type="text" id="vote-ph" name="meta_ph" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Temperature C</label>
                    <input type="text" id="vote-temperature-c" name="meta_temperature_c" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total organic carbon</label>
                    <input type="text" id="vote-total-organic-carbon" name="meta_total_organic_carbon" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
              </div>

              <div class="space-y-3">
                <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Original and Assessment</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Original parameter name</label>
                    <input type="text" id="vote-original-parameter-name" name="meta_original_parameter_name" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Original qualifier</label>
                    <input type="text" id="vote-original-qualifier" name="meta_original_qualifier" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Original value</label>
                    <input type="text" id="vote-original-value" name="meta_original_value" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Original value range</label>
                    <input type="text" id="vote-original-value-range" name="meta_original_value_range" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Original unit</label>
                    <input type="text" id="vote-original-unit" name="meta_original_unit" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assessment parameter name</label>
                    <input type="text" id="vote-assessment-parameter-name" name="meta_assessment_parameter_name" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assessment qualifier</label>
                    <input type="text" id="vote-assessment-qualifier" name="meta_assessment_qualifier" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assessment value</label>
                    <input type="text" id="vote-assessment-value" name="meta_assessment_value" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assessment unit</label>
                    <input type="text" id="vote-assessment-unit" name="meta_assessment_unit" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hazard criterion</label>
                    <input type="text" id="vote-hazard-criterion" name="meta_hazard_criterion" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Original classification</label>
                    <input type="text" id="vote-original-classification" name="meta_original_classification" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Classification score</label>
                    <input type="text" id="vote-classification-score" name="meta_classification_score" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
              </div>

              <div class="space-y-3">
                <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Quality and Conclusion</h4>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">General comment</label>
                  <textarea id="vote-general-comment" name="meta_general_comment" rows="3" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm"></textarea>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Applicability domain</label>
                  <textarea id="vote-applicability-domain" name="meta_applicability_domain" rows="2" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Applicability domain score</label>
                    <input type="text" id="vote-applicability-domain-score" name="meta_applicability_domain_score" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reliability score</label>
                    <input type="text" id="vote-reliability-score" name="meta_reliability_score" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reliability score system</label>
                    <input type="text" id="vote-reliability-system" name="meta_reliability_system" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Reliability rational</label>
                  <textarea id="vote-reliability-rational" name="meta_reliability_rational" rows="2" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Institution of reliability score</label>
                    <input type="text" id="vote-reliability-institution" name="meta_reliability_institution" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Regulatory context</label>
                    <input type="text" id="vote-regulatory-context" name="meta_regulatory_context" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Institution original classification</label>
                    <input type="text" id="vote-institution-original-classification" name="meta_institution_original_classification" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Automated expert vote</label>
                    <input type="text" id="vote-automated-expert-vote" name="meta_automated_expert_vote" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NORMAN Classification</label>
                    <select id="vote-norman-classification" name="meta_norman_classification" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                      <option value="">Select classification</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NORMAN Vote</label>
                    <select id="vote-norman-vote" name="meta_norman_vote" class="w-full border-gray-300 focus:border-lime-500 focus:ring-lime-500 text-sm">
                      <option value="1">1</option>
                      <option value="2">2</option>
                      <option value="3">3</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-2 border-t px-4 py-3 bg-slate-50">
              <button type="button" class="btn-clear" onclick="closeHazardsVoteModal()">Cancel</button>
              <button type="submit" class="btn-submit">Store Vote</button>
            </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>

<script>
    const hazardsVoteClassificationMap = {
        P: ['vP', 'P', 'sP', 'nP', 'probably-nP'],
        B: ['vB', 'B', 'sB', 'nB', 'probably-nB'],
        M: ['vM', 'M', 'sM', 'nM', 'probably-nM'],
        T: ['T+', 'T', 'sT', 'nT', 'probably-nT'],
    };
    const hazardsVoteRowUrlTemplate = @js(route('hazards.derivation.substance-data.show', ['id' => '__ID__']));
    const hazardsVoteCurrentUser = @js($currentUserDisplayName);

    function getHazardsVoteModal() {
      return document.getElementById('hazards-vote-modal');
    }

    function getHazardsVoteFields() {
      return {
        substanceId: document.getElementById('vote-susdat-substance-id'),
        bucket: document.getElementById('vote-bucket'),
        substanceDataId: document.getElementById('vote-hazards-substance-data-id'),
        classificationCode: document.getElementById('vote-pbmt-classification-code'),
        editor: document.getElementById('vote-editor'),
        date: document.getElementById('vote-date'),
        referenceType: document.getElementById('vote-reference-type'),
        title: document.getElementById('vote-title'),
        authors: document.getElementById('vote-authors'),
        year: document.getElementById('vote-year'),
        bibliographicSource: document.getElementById('vote-bibliographic-source'),
        doi: document.getElementById('vote-doi'),
        testType: document.getElementById('vote-test-type'),
        glp: document.getElementById('vote-glp'),
        standardTest: document.getElementById('vote-standard-test'),
        substanceName: document.getElementById('vote-substance-name'),
        cas: document.getElementById('vote-cas'),
        radioLabeled: document.getElementById('vote-radio-labeled'),
        standardQualifier: document.getElementById('vote-standard-qualifier'),
        standardUsed: document.getElementById('vote-standard-used'),
        testMatrix: document.getElementById('vote-test-matrix'),
        testSpecies: document.getElementById('vote-test-species'),
        durationDays: document.getElementById('vote-duration-days'),
        exposureConcentration: document.getElementById('vote-exposure-concentration'),
        ph: document.getElementById('vote-ph'),
        temperatureC: document.getElementById('vote-temperature-c'),
        totalOrganicCarbon: document.getElementById('vote-total-organic-carbon'),
        hazardCriterion: document.getElementById('vote-hazard-criterion'),
        originalParameterName: document.getElementById('vote-original-parameter-name'),
        originalQualifier: document.getElementById('vote-original-qualifier'),
        originalValue: document.getElementById('vote-original-value'),
        originalValueRange: document.getElementById('vote-original-value-range'),
        originalUnit: document.getElementById('vote-original-unit'),
        assessmentParameterName: document.getElementById('vote-assessment-parameter-name'),
        assessmentQualifier: document.getElementById('vote-assessment-qualifier'),
        assessmentValue: document.getElementById('vote-assessment-value'),
        assessmentUnit: document.getElementById('vote-assessment-unit'),
        originalClassification: document.getElementById('vote-original-classification'),
        classificationScore: document.getElementById('vote-classification-score'),
        applicabilityDomain: document.getElementById('vote-applicability-domain'),
        applicabilityDomainScore: document.getElementById('vote-applicability-domain-score'),
        reliabilityScore: document.getElementById('vote-reliability-score'),
        reliabilitySystem: document.getElementById('vote-reliability-system'),
        reliabilityRational: document.getElementById('vote-reliability-rational'),
        reliabilityInstitution: document.getElementById('vote-reliability-institution'),
        regulatoryContext: document.getElementById('vote-regulatory-context'),
        institutionOriginalClassification: document.getElementById('vote-institution-original-classification'),
        normanClassification: document.getElementById('vote-norman-classification'),
        normanVote: document.getElementById('vote-norman-vote'),
        generalComment: document.getElementById('vote-general-comment'),
        automatedExpertVote: document.getElementById('vote-automated-expert-vote'),
      };
    }

    function resetHazardsVoteClassificationOptions(hazardCriterion, selectedValue) {
        const fields = getHazardsVoteFields();
        const options = hazardsVoteClassificationMap[(hazardCriterion || '').toUpperCase()] || [];
        fields.normanClassification.innerHTML = '<option value=\"\">Select classification</option>';

        options.forEach(function (option) {
          const optionEl = document.createElement('option');
          optionEl.value = option;
          optionEl.textContent = option;
          if (selectedValue === option) {
            optionEl.selected = true;
          }
          fields.normanClassification.appendChild(optionEl);
        });
    }

    window.openHazardsVoteModal = function (button) {
        const modal = getHazardsVoteModal();
        const fields = getHazardsVoteFields();
        fields.substanceId.value = button.dataset.susdatSubstanceId || '';
        fields.bucket.value = button.dataset.bucket || '';
        fields.substanceDataId.value = button.dataset.hazardsSubstanceDataId || '';
        fields.classificationCode.value = button.dataset.dataSource || '';
        fields.editor.value = hazardsVoteCurrentUser;
        fields.date.value = new Date().toISOString().slice(0, 19).replace('T', ' ');
        fields.title.value = '';
        fields.authors.value = '';
        fields.year.value = '';
        fields.bibliographicSource.value = '';
        fields.doi.value = '';

        const testType = button.dataset.testType || 'Other';
        fields.testType.value = testType;
        fields.referenceType.value = testType === 'Experimental' || testType === 'Predicted' ? testType : 'Other';
        fields.glp.value = '';
        fields.standardTest.value = '';
        fields.substanceName.value = '';
        fields.cas.value = '';
        fields.radioLabeled.value = '';
        fields.standardQualifier.value = '';
        fields.standardUsed.value = '';
        fields.testMatrix.value = '';
        fields.testSpecies.value = '';
        fields.durationDays.value = '';
        fields.exposureConcentration.value = '';
        fields.ph.value = '';
        fields.temperatureC.value = '';
        fields.totalOrganicCarbon.value = '';
        fields.hazardCriterion.value = button.dataset.hazardCriterion || '';
        fields.originalParameterName.value = '';
        fields.originalQualifier.value = '';
        fields.originalValue.value = button.dataset.originalValue || '';
        fields.originalValueRange.value = '';
        fields.originalUnit.value = button.dataset.originalUnit || '';
        fields.assessmentParameterName.value = '';
        fields.assessmentQualifier.value = '';
        fields.assessmentValue.value = button.dataset.assessmentValue || '';
        fields.assessmentUnit.value = button.dataset.unit || button.dataset.originalUnit || '';
        fields.originalClassification.value = '';
        fields.classificationScore.value = '';
        fields.applicabilityDomain.value = '';
        fields.applicabilityDomainScore.value = '';
        fields.reliabilityScore.value = button.dataset.reliabilityScore || '';
        fields.reliabilitySystem.value = '';
        fields.reliabilityRational.value = '';
        fields.reliabilityInstitution.value = '';
        fields.regulatoryContext.value = '';
        fields.institutionOriginalClassification.value = '';
        fields.generalComment.value = '';
        fields.automatedExpertVote.value = hazardsVoteCurrentUser;
        fields.normanVote.value = '3';

        resetHazardsVoteClassificationOptions(fields.hazardCriterion.value, button.dataset.assessmentClass || '');

        modal.classList.remove('hidden');

        const url = hazardsVoteRowUrlTemplate.replace('__ID__', button.dataset.hazardsSubstanceDataId || '');
        fetch(url)
          .then(function (response) {
            if (!response.ok) {
              throw new Error('Failed to load row');
            }

            return response.json();
          })
          .then(function (row) {
            fields.classificationCode.value = row.data_source || fields.classificationCode.value;
            fields.editor.value = hazardsVoteCurrentUser;
            fields.date.value = row.date || fields.date.value;
            fields.referenceType.value = row.reference_type || fields.referenceType.value;
            fields.title.value = row.title || '';
            fields.authors.value = row.authors || '';
            fields.year.value = row.year || '';
            fields.bibliographicSource.value = row.bibliographic_source || '';
            fields.doi.value = row.physico_chemical_source_doi || '';
            fields.testType.value = row.test_type === 2 || row.test_type === '2'
              ? 'Experimental'
              : ((row.test_type === 3 || row.test_type === '3') ? 'Predicted' : (row.test_type || fields.testType.value));
            fields.glp.value = row.performed_under_glp ?? '';
            fields.standardTest.value = row.standard_test ?? '';
            fields.substanceName.value = row.substance_name || '';
            fields.cas.value = row.cas_no || '';
            fields.radioLabeled.value = row.radio_labeled_substance ?? '';
            fields.standardQualifier.value = row.standard_qualifier || '';
            fields.standardUsed.value = row.standard_used || '';
            fields.testMatrix.value = row.test_matrix || '';
            fields.testSpecies.value = row.test_species || '';
            fields.durationDays.value = row.duration_days ?? '';
            fields.exposureConcentration.value = row.exposure_concentration ?? '';
            fields.ph.value = row.ph ?? '';
            fields.temperatureC.value = row.temperature_c ?? '';
            fields.totalOrganicCarbon.value = row.total_organic_carbon ?? '';
            fields.originalParameterName.value = row.original_parameter_name || '';
            fields.originalQualifier.value = row.original_qualifier || '';
            fields.originalValue.value = row.original_value ?? fields.originalValue.value;
            fields.originalValueRange.value = row.original_value_range || '';
            fields.originalUnit.value = row.original_unit || fields.originalUnit.value;
            fields.assessmentParameterName.value = row.norman_parameter_name || '';
            fields.assessmentQualifier.value = row.assessment_qualifier || '';
            fields.assessmentValue.value = row.value_assessment_index ?? fields.assessmentValue.value;
            fields.assessmentUnit.value = row.unit || fields.assessmentUnit.value;
            fields.hazardCriterion.value = fields.hazardCriterion.value || '';
            fields.originalClassification.value = '';
            fields.classificationScore.value = '';
            fields.generalComment.value = row.general_comment || '';
            fields.applicabilityDomain.value = row.applicability_domain || '';
            fields.applicabilityDomainScore.value = row.applicability_domain_score ?? '';
            fields.reliabilityScore.value = row.reliability_score ?? fields.reliabilityScore.value;
            fields.reliabilitySystem.value = row.reliability_score_system || '';
            fields.reliabilityRational.value = row.reliability_rational || '';
            fields.reliabilityInstitution.value = row.institution_of_reliability_score || '';
            fields.regulatoryContext.value = row.regulatory_purpose || '';
            fields.institutionOriginalClassification.value = '';
            fields.automatedExpertVote.value = hazardsVoteCurrentUser;

            resetHazardsVoteClassificationOptions(fields.hazardCriterion.value, row.assessment_class || button.dataset.assessmentClass || '');
          })
          .catch(function () {
          });
    };

    window.closeHazardsVoteModal = function () {
        const modal = getHazardsVoteModal();
        modal.classList.add('hidden');
    };

    window.closeHazardsVoteModalOnOverlay = function (event) {
        const modal = getHazardsVoteModal();
        if (event.target === modal) {
          closeHazardsVoteModal();
        }
    };

    document.addEventListener('keydown', function (event) {
        const modal = getHazardsVoteModal();
        if (modal && event.key === 'Escape' && !modal.classList.contains('hidden')) {
          closeHazardsVoteModal();
        }
    });
  </script>
