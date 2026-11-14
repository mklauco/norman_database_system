<x-app-layout>
  <x-slot name="header">
    @include('hazards.header')
  </x-slot>

  @php
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

    $formatConclusionSupport = static function ($winnerPoints, $allPoints) {
      if ($winnerPoints === null || $winnerPoints === '') {
        return '-';
      }

      $winner = (int) $winnerPoints;
      $all = is_numeric($allPoints) ? (int) $allPoints : 0;

      if ($all <= 0) {
        return (string) $winner;
      }

      $percent = (int) round(($winner / $all) * 100);

      return $winner . ' (' . $percent . '%)';
    };

    $criteria = ['P', 'B', 'M', 'T'];
  @endphp

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900 space-y-6">
          <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Hazards Classification</h1>
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
              <a href="{{ route('hazards.classification.search.filter') }}" class="btn-clear">Change Substance</a>
            </div>
          </div>

          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-slate-100 px-4 py-3 border-b border-gray-200">
              <h2 class="text-lg font-semibold text-gray-900">Classification table</h2>
            </div>

            <form method="POST" action="{{ route('hazards.classification.vote') }}">
              @csrf
              <input type="hidden" name="susdat_substance_id" value="{{ $susdatSubstanceId }}">

              <div class="overflow-x-auto">
                <table class="table-standard text-sm">
                  <thead>
                    <tr class="bg-gray-600 text-white">
                      <th>Type</th>
                      <th>P</th>
                      <th>P vote</th>
                      <th>&sum; P</th>
                      <th>B</th>
                      <th>B vote</th>
                      <th>&sum; B</th>
                      <th>M</th>
                      <th>M vote</th>
                      <th>&sum; M</th>
                      <th>T</th>
                      <th>T vote</th>
                      <th>&sum; T</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($classificationRows as $row)
                      <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                        <td class="p-2 text-center font-medium">{{ $row['type'] }}</td>

                        @foreach ($criteria as $criterion)
                          @php
                            $cell = $row[$criterion];
                            $sumKey = 'sum_' . $criterion;
                            $voteFieldKey = $loop->parent->index . '_' . $criterion;
                          @endphp
                          <td class="p-2 text-center">{{ $cell['classification'] ?? '-' }}</td>
                          <td class="p-2 text-center">
                            @if (! empty($cell['classification']))
                              <input type="hidden" name="votes[{{ $voteFieldKey }}][classification_type]" value="{{ $row['type'] }}">
                              <input type="hidden" name="votes[{{ $voteFieldKey }}][criterion]" value="{{ $criterion }}">
                              <input type="hidden" name="votes[{{ $voteFieldKey }}][classification_code]" value="{{ $cell['classification'] }}">
                              <input
                                type="number"
                                name="votes[{{ $voteFieldKey }}][vote_value]"
                                class="w-16 border-gray-300 bg-white text-sm text-center"
                                min="1"
                                max="3"
                                step="1"
                                inputmode="numeric"
                                oninput="
                                  this.value=this.value.replace(/[^0-9]/g,'');
                                  if(this.value!==''&&(this.value<1||this.value>3))this.value='';
                                "
                                value="{{ $cell['prefill_vote'] ?? '' }}">
                            @else
                              <input
                                type="number"
                                class="w-16 border-gray-300 bg-white text-sm text-center opacity-50"
                                disabled>
                            @endif
                          </td>
                          <td class="p-2 text-center">{{ $row[$sumKey] ?? '-' }}</td>
                        @endforeach
                      </tr>
                    @empty
                      <tr>
                        <td colspan="13" class="p-3 text-center text-gray-500">
                          No classification data available.
                        </td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>

              <div class="flex justify-end px-4 py-3 border-t border-gray-200 bg-slate-50">
                <button type="submit" class="btn-submit">Vote</button>
              </div>
            </form>
          </div>

          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-slate-100 px-4 py-3 border-b border-gray-200">
              <h2 class="text-lg font-semibold text-gray-900">Conclusion table</h2>
            </div>

            <div class="overflow-x-auto">
              <table class="table-standard text-sm">
                <thead>
                  <tr class="bg-gray-600 text-white">
                    <th>P</th>
                    <th>&sum; P</th>
                    <th>B</th>
                    <th>&sum; B</th>
                    <th>M</th>
                    <th>&sum; M</th>
                    <th>T</th>
                    <th>&sum; T</th>
                    <th>Editor</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($conclusions as $row)
                    <tr class="@if ((int) $row->id === (int) $activeConclusionId) bg-green-50 @elseif ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                      <td class="p-2 text-center">{{ $row->P ?? '-' }}</td>
                      <td class="p-2 text-center">{{ $formatConclusionSupport($row->p_total_points, $row->p_all_points) }}</td>
                      <td class="p-2 text-center">{{ $row->B ?? '-' }}</td>
                      <td class="p-2 text-center">{{ $formatConclusionSupport($row->b_total_points, $row->b_all_points) }}</td>
                      <td class="p-2 text-center">{{ $row->M ?? '-' }}</td>
                      <td class="p-2 text-center">{{ $formatConclusionSupport($row->m_total_points, $row->m_all_points) }}</td>
                      <td class="p-2 text-center">{{ $row->T ?? '-' }}</td>
                      <td class="p-2 text-center">{{ $formatConclusionSupport($row->t_total_points, $row->t_all_points) }}</td>
                      <td class="p-2 text-center">{{ $row->editor_display_name }}</td>
                      <td class="p-2 text-center">{{ $formatDate($row->updated_at ?? $row->created_at) }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="10" class="p-3 text-center text-gray-500">
                        No conclusion stored yet.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>


