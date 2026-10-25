@foreach ($queries as $q)
                  @php
                    $content = json_decode($q->content, true);
                    $request = $content['request'];
                    $moduleKey = $q->database_key;

                    $fields = [
                        'Country' => [
                            'requestKey' => 'countrySearch',
                            'dataList' => $countries,
                        ],
                        'Matrices' => [
                            'requestKey' => 'matrixSearch',
                            'dataList' => $matrices,
                        ],
                        'Sources' => [
                            'requestKey' => 'sourceSearch',
                            'dataList' => $sources,
                        ],
                        'Categories' => [
                            'requestKey' => 'categoriesSearch',
                            'dataList' => $categories,
                        ],
                        'Type of Data Source' => [
                            'requestKey' => 'typeDataSourcesSearch',
                            'dataList' => $typeDataSources,
                        ],
                        'Concentration Indicators' => [
                            'requestKey' => 'concentrationIndicatorSearch',
                            'dataList' => $concentrationIndicators,
                        ],
                        'Organisation' => [
                            'requestKey' => 'dataSourceOrganisationSearch',
                            'dataList' => $dataSourceOrganisations,
                        ],
                        'Laboratory' => [
                            'requestKey' => 'dataSourceLaboratorySearch',
                            'dataList' => $dataSourceLaboratories,
                        ],
                        'Analytical Method' => [
                            'requestKey' => 'analyticalMethodSearch',
                            'dataList' => $analyticalMethods,
                        ],
                        'Quality information category' => [
                            'requestKey' => 'qualityAnalyticalMethodsSearch',
                            'dataList' => $qualityAnalyticalMethods,
                        ],
                    ];
                  @endphp
                  <tr class="@if ($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                    <td class="py-1 px-2">
                      {{ $q->id }}
                    </td>
                    <td class="py-1 px-2 text-xs">
                      @include('backend.querylog.moduleBadges', ['moduleKey' => $moduleKey])
                    </td>
                    <td class="py-1 px-2 text-xs">
                      @if (str_starts_with($moduleKey, 'empodat'))
                        @foreach ($fields as $label => $field)
                          @php
                            $values = (array) data_get($request, $field['requestKey'], []);
                          @endphp
                          <div class="flex justify-between py-1">
                            <div class="px-1 font-semibold">{{ $label }}:</div>
                            <div class="px-1 text-right">
                              @if (empty($values))
                                <span class="text-gray-500">n/a</span>
                              @else
                                @foreach ($values as $value)
                                  {{ $field['dataList'][$value] ?? 'Unknown' }}@if (!$loop->last)
                                    ,
                                  @endif
                                @endforeach
                              @endif
                            </div>
                          </div>
                        @endforeach
                      @else
                        @php
                          $displayed = 0;
                        @endphp
                        @foreach ($request ?? [] as $key => $value)
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

                      <div class="flex justify-between py-1">
                        <div class="px-1 font-semibold">
                          Year:
                        </div>
                        <div class="px-1">
                          {{ data_get($request, 'year_from', 'n/a') }} - {{ data_get($request, 'year_to', 'n/a') }}
                        </div>
                      </div>
                      <div class="flex justify-between py-1">
                        <div class="px-1 font-semibold">
                          Substances:
                        </div>
                        <div class="px-1">
                          @if (!empty($request['substances']))
                            applied
                          @else
                            <span class="text-gray-500">n/a</span>
                          @endif
                        </div>
                      </div>

                      <div class="flex justify-between py-1">
                        <div class="px-1 font-semibold">
                          Count:
                        </div>
                        <div class="px-1">
                          @php
                            $count = data_get($content, 'count');
                          @endphp

                          {{ !is_null($count) ? number_format($count, 0, '.', ' ') : 'n/a' }}
                        </div>
                      </div>

                      <div class="flex justify-between py-1">
                        <div class="px-1 font-semibold">
                          Time:
                        </div>
                        <div class="px-1">
                          @php
                            $loadExecutionTime = data_get($content, 'loadExecutionTime');
                            $countExecutionTime = data_get($content, 'countExecutionTime');
                          @endphp

                          @if (is_null($loadExecutionTime) && is_null($countExecutionTime))
                            n/a
                          @else
                            @if (!is_null($loadExecutionTime))
                              <div>Load Execution Time: {{ $loadExecutionTime }}</div>
                            @endif

                            @if (!is_null($countExecutionTime))
                              <div>Count Execution Time: {{ $countExecutionTime }}</div>
                            @endif
                          @endif
                        </div>
                      </div>

                    </td>
                    @role('super_admin')
                      <td class="py-1 px-2 font-mono  text-xs">
                        {!! $q->formatted_query !!}
                      </td>
                    @endrole
                    <td class="py-1 px-2">
                      @if (is_null($q->user_id))
                        Guest
                      @else
                        {{ $q->users->last_name }}
                      @endif
                    </td>
                    <td class="py-1 px-2">
                      {{ $q->created_at }}
                    </td>
                    <td class="py-1 px-2">
                      @if (str_starts_with($moduleKey, 'empodat'))
                        <a href="{{ route('codsearch.filter', [
                            'countrySearch' => data_get($request, 'countrySearch', null),
                            'matrixSearch' => data_get($request, 'matrixSearch', null),
                            'sourceSearch' => data_get($request, 'sourceSearch', null),
                            'year_from' => data_get($request, 'year_from', null),
                            'year_to' => data_get($request, 'year_to', null),
                            'displayOption' => data_get($request, 'displayOption', null),
                            'substances' => data_get($request, 'substances', null),
                            'categoriesSearch' => data_get($request, 'categoriesSearch', null),
                            'typeDataSourcesSearch' => data_get($request, 'typeDataSourcesSearch', null),
                            'concentrationIndicatorSearch' => data_get($request, 'concentrationIndicatorSearch', null),
                            'analyticalMethodSearch' => data_get($request, 'analyticalMethodSearch', null),
                            'dataSourceLaboratorySearch' => data_get($request, 'dataSourceLaboratorySearch', null),
                            'dataSourceOrganisationSearch' => data_get($request, 'dataSourceOrganisationSearch', null),
                            'qualityAnalyticalMethodsSearch' => data_get($request, 'qualityAnalyticalMethodsSearch', null),
                        ]) }}"
                          class="text-sky-500 hover:underline">
                          View
                        </a>

                    </td>
                  </tr>
                @endforeach