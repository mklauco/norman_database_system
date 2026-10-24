<x-app-layout>
  <x-slot name="header">
    @include('backend.querylog.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <div class="mb-4">
            <form method="GET" action="{{ route('querylog.index') }}" class="flex items-center gap-2 flex-wrap">
              <label for="module" class="text-sm font-medium">Module:</label>
              <select id="module" name="module" class="border rounded-sm px-2 py-1 text-sm">
                @foreach ($modules ?? [] as $key => $label)
                  <option value="{{ $key }}" @selected(($activeModule ?? '') === $key)>{{ $label }}</option>
                @endforeach
              </select>
              <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded-sm text-sm">Apply</button>
              @if (!empty($activeModule))
                <a href="{{ route('querylog.index') }}" class="px-3 py-1 bg-gray-200 rounded-sm text-sm">Reset</a>
              @endif
            </form>
          </div>
          <div class="overflow-x-auto">
            To speedup development, only last 100 queries are shown.
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="py-1 px-2">ID</th>
                  <th class="py-1 px-2">Module</th>
                  <th class="py-1 px-2">Content</th>
                  @role('super_admin')
                    <th class="py-1 px-2">Query <i class="fas fa-lock"></i></th>
                  @endrole
                  <th class="py-1 px-2">User</th>
                  <th class="py-1 px-2">Created at</th>
                  <th class="py-1 px-2">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($queries as $q)
                  @php
                    $content = json_decode($q->content, true);
                    $request = $content['request'] ?? [];
                    $moduleKey = $q->database_key;

                    $fields = [
                        'Country' => [
                            'requestKey' => 'countrySearch',
                            'dataList' => $countries ?? [],
                        ],
                        'Matrices' => [
                            'requestKey' => 'matrixSearch',
                            'dataList' => $matrices ?? [],
                        ],
                        'Sources' => [
                            'requestKey' => 'sourceSearch',
                            'dataList' => $sources ?? [],
                        ],
                        'Categories' => [
                            'requestKey' => 'categoriesSearch',
                            'dataList' => $categories ?? [],
                        ],
                        'Type of Data Source' => [
                            'requestKey' => 'typeDataSourcesSearch',
                            'dataList' => $typeDataSources ?? [],
                        ],
                        'Concentration Indicators' => [
                            'requestKey' => 'concentrationIndicatorSearch',
                            'dataList' => $concentrationIndicators ?? [],
                        ],
                        'Organisation' => [
                            'requestKey' => 'dataSourceOrganisationSearch',
                            'dataList' => $dataSourceOrganisations ?? [],
                        ],
                        'Laboratory' => [
                            'requestKey' => 'dataSourceLaboratorySearch',
                            'dataList' => $dataSourceLaboratories ?? [],
                        ],
                        'Analytical Method' => [
                            'requestKey' => 'analyticalMethodSearch',
                            'dataList' => $analyticalMethods ?? [],
                        ],
                        'Quality information category' => [
                            'requestKey' => 'qualityAnalyticalMethodsSearch',
                            'dataList' => $qualityAnalyticalMethods ?? [],
                        ],
                    ];
                  @endphp

                  <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="py-1 px-2">
                      {{ $q->id }}
                    </td>

                    <td class="py-1 px-2 text-xs">
                      <span class="px-2 py-0.5 bg-slate-100 text-slate-800 rounded-sm font-mono">{{ $moduleKey }}</span>
                    </td>

                    <td class="py-1 px-2 text-xs">
                      @if (str_starts_with($moduleKey, 'empodat'))
                        {{-- EMPODAT module specific display --}}
                        @foreach ($fields as $label => $field)
                          @php
                            $values = (array) data_get($request, $field['requestKey'], []);
                            $dataList = $field['dataList'] ?? [];
                          @endphp
                          <div class="flex justify-between py-1">
                            <div class="px-1 font-semibold">{{ $label }}:</div>
                            <div class="px-1 text-right">
                              @if (empty($values))
                                <span class="text-gray-500">n/a</span>
                              @else
                                @foreach ($values as $value)
                                  {{ $dataList[$value] ?? 'Unknown' }}@if (!$loop->last)
                                    ,
                                  @endif
                                @endforeach
                              @endif
                            </div>
                          </div>
                        @endforeach
                      @else
                        {{-- Other modules generic display --}}
                        @php $displayed = 0; @endphp
                        @foreach ($request as $key => $value)
                          @continue(in_array($key, ['page']))
                          @php
                            $isEmpty =
                                (is_array($value) &&
                                    count(array_filter($value, fn($v) => $v !== null && $v !== '')) === 0) ||
                                ($value === null || $value === '');
                          @endphp
                          @continue($isEmpty)
                          @php $displayed++; @endphp

                          <div class="flex justify-between py-0.5">
                            <div class="px-1 font-semibold">{{ $key }}:</div>
                            <div class="px-1 text-right">
                              @if (is_array($value))
                                {{ implode(', ', array_map(fn($v) => is_scalar($v) ? (string) $v : json_encode($v), $value)) }}
                              @else
                                {{ is_scalar($value) ? (string) $value : json_encode($value) }}
                              @endif
                            </div>
                          </div>
                        @endforeach

                        @if ($displayed === 0)
                          <span class="text-gray-500">No parameters</span>
                        @endif
                      @endif

                      {{-- Common fields for all modules --}}
                      <div class="flex justify-between py-1">
                        <div class="px-1 font-semibold">Year:</div>
                        <div class="px-1">
                          {{ data_get($request, 'year_from', 'n/a') }} - {{ data_get($request, 'year_to', 'n/a') }}
                        </div>
                      </div>

                      <div class="flex justify-between py-1">
                        <div class="px-1 font-semibold">Substances:</div>
                        <div class="px-1">
                          @if (!empty($request['substances']))
                            applied
                          @else
                            <span class="text-gray-500">n/a</span>
                          @endif
                        </div>
                      </div>

                      <div class="flex justify-between py-1">
                        <div class="px-1 font-semibold">Count:</div>
                        <div class="px-1">
                          @php $count = data_get($content, 'count'); @endphp
                          {{ !is_null($count) ? number_format($count, 0, '.', ' ') : 'n/a' }}
                        </div>
                      </div>

                      <div class="flex justify-between py-1">
                        <div class="px-1 font-semibold">Time:</div>
                        <div class="px-1">
                          @php
                            $loadExecutionTime = data_get($content, 'loadExecutionTime');
                            $countExecutionTime = data_get($content, 'countExecutionTime');
                          @endphp

                          @if (is_null($loadExecutionTime) && is_null($countExecutionTime))
                            n/a
                          @else
                            @if (!is_null($loadExecutionTime))
                              <div>Load: {{ $loadExecutionTime }}</div>
                            @endif
                            @if (!is_null($countExecutionTime))
                              <div>Count: {{ $countExecutionTime }}</div>
                            @endif
                          @endif
                        </div>
                      </div>
                    </td>

                    @role('super_admin')
                      <td class="py-1 px-2 font-mono text-xs">
                        {!! $q->formatted_query !!}
                      </td>
                    @endrole

                    <td class="py-1 px-2">
                      @if (is_null($q->user_id))
                        Guest
                      @else
                        {{ $q->users->last_name ?? 'Unknown User' }}
                      @endif
                    </td>

                    <td class="py-1 px-2">
                      {{ $q->created_at }}
                    </td>

                    <td class="py-1 px-2">
                      @if (str_starts_with($moduleKey, 'empodat'))
                        <a href="{{ route('codsearch.filter', [
                            'countrySearch' => data_get($request, 'countrySearch'),
                            'matrixSearch' => data_get($request, 'matrixSearch'),
                            'sourceSearch' => data_get($request, 'sourceSearch'),
                            'year_from' => data_get($request, 'year_from'),
                            'year_to' => data_get($request, 'year_to'),
                            'displayOption' => data_get($request, 'displayOption'),
                            'substances' => data_get($request, 'substances'),
                            'categoriesSearch' => data_get($request, 'categoriesSearch'),
                            'typeDataSourcesSearch' => data_get($request, 'typeDataSourcesSearch'),
                            'concentrationIndicatorSearch' => data_get($request, 'concentrationIndicatorSearch'),
                            'analyticalMethodSearch' => data_get($request, 'analyticalMethodSearch'),
                            'dataSourceLaboratorySearch' => data_get($request, 'dataSourceLaboratorySearch'),
                            'dataSourceOrganisationSearch' => data_get($request, 'dataSourceOrganisationSearch'),
                            'qualityAnalyticalMethodsSearch' => data_get($request, 'qualityAnalyticalMethodsSearch'),
                        ]) }}"
                          class="text-blue-500 hover:underline">
                          View
                        </a>
                      @else
                        {{-- Add links for other modules here --}}
                        <span class="text-gray-500">N/A</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
            {{ $queries->links('pagination::tailwind') }}
          </div>
        </div>
      </div>
    </div>

    <div class="hidden">
      <span class="text-purple-600"></span>
      <span class="text-teal-600"></span>
      <span class="text-orange-800"></span>
    </div>
</x-app-layout>
