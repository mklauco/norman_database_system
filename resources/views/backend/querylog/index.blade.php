<x-app-layout>
  <x-slot name="header">
    @include('backend.header')
  </x-slot>
  
  <div class="py-4">
    
    
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      
      
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <div class="overflow-x-auto">
            To speedup development, only last 100 queries are shown.
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="py-1 px-2">ID</th>
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
                $content = json_decode($q->content, TRUE);
                $request = $content['request'];
                
                $fields = [
                'Country' => [
                'requestKey' => 'countrySearch',
                'dataList'   => $countries,
                ],
                'Matrices' => [
                'requestKey' => 'matrixSearch',
                'dataList'   => $matrices,
                ],
                'Sources' => [
                'requestKey' => 'sourceSearch',
                'dataList'   => $sources,
                ],
                'Categories' => [
                'requestKey' => 'categoriesSearch',
                'dataList'   => $categories,
                ],
                'Type of data source' => [
                'requestKey' => 'typeDataSourcesSearch',
                'dataList'   => $typeDataSources,
                ],
                'Concetration Indicators' => [
                'requestKey' => 'concentrationIndicatorSearch',
                'dataList'   => $concentrationIndicators,
                ],
                'Organisation' => [
                'requestKey' => 'dataSourceOrganisationSearch',
                'dataList'   => $dataSourceOrganisations,
                ],
                'Laboratory' => [
                'requestKey' => 'dataSourceLaboratorySearch',
                'dataList'   => $dataSourceLaboratories,
                ],
                'Analytical method' => [
                'requestKey' => 'analyticalMethodSearch',
                'dataList'   => $analyticalMethods,
                ],
                'Quality information category' => [
                'requestKey' => 'qualityAnalyticalMethodsSearch',
                'dataList'   => $qualityAnalyticalMethods,
                ],
                ];
                @endphp
                <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                  <td class="py-1 px-2">
                    {{ $q->id }}
                  </td>
                  <td class="py-1 px-2 text-xs">
                    {{-- <pre>{{ json_encode(json_decode($q->content), JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) }}</pre> --}}
                    
                    @foreach ($fields as $label => $field)
                    @php
                    // Cast the value to an array or default to an empty array
                    $values = (array) data_get($request, $field['requestKey'], []);
                    @endphp
                    
                    <div class="flex justify-between py-1">
                      <div class="px-1 font-semibold">
                        {{ $label }}:
                      </div>
                      <div class="px-1 text-right">
                        @if (empty($values))
                        <span class="text-gray-500">n/a</span>
                        @else
                        @foreach ($values as $value)
                        {{ $field['dataList'][$value] ?? 'Unknown' }}@if (!$loop->last), @endif
                        @endforeach
                        @endif
                      </div>
                    </div>
                    @endforeach
                    
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
                        @if(!empty($request['substances']))
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
                        
                        {{ !is_null($count) ? number_format($count, 0, ".", " ") : 'n/a' }}
                      </div>
                    </div>
                    
                    <div class="flex justify-between py-1">
                      <div class="px-1 font-semibold">
                        Time:
                      </div>
                      <div class="px-1">
                        @php
                        $loadExecutionTime  = data_get($content, 'loadExecutionTime');
                        $countExecutionTime = data_get($content, 'countExecutionTime');
                        @endphp
                        
                        @if(is_null($loadExecutionTime) && is_null($countExecutionTime))
                        n/a
                        @else
                        @if(!is_null($loadExecutionTime))
                        <div>Load Execution Time: {{ $loadExecutionTime }}</div>
                        @endif
                        
                        @if(!is_null($countExecutionTime))
                        <div>Count Execution Time: {{ $countExecutionTime }}</div>
                        @endif
                        @endif
                      </div>
                    </div>
                    
                  </td>
                  @role('super_admin')
                  <td class="py-1 px-2 font-mono  text-xs">
                    {!!  $q->formatted_query !!}
                  </td>
                  @endrole
                  <td class="py-1 px-2">
                    @if(is_null($q->user_id))
                    Guest
                    @else
                    {{ $q->users->last_name }}
                    @endif
                  </td>
                  <td class="py-1 px-2">
                    {{ $q->created_at }}
                  </td>
                  <td class="py-1 px-2">
                    <a href=" {{ route('codsearch.filter', [
                        'countrySearch'                   => data_get($request, 'countrySearch', null),
                        'matrixSearch'                    => data_get($request, 'matrixSearch', null),
                        'sourceSearch'                    => data_get($request, 'sourceSearch', null),
                        'year_from'                       => data_get($request, 'year_from', null),
                        'year_to'                         => data_get($request, 'year_to', null),
                        'displayOption'                   => data_get($request, 'displayOption', null),
                        'substances'                      => data_get($request, 'substances', null),
                        'categoriesSearch'                => data_get($request, 'categoriesSearch', null),
                        'typeDataSourcesSearch'           => data_get($request, 'typeDataSourcesSearch', null),
                        'concentrationIndicatorSearch'    => data_get($request, 'concentrationIndicatorSearch', null),
                        'analyticalMethodSearch'          => data_get($request, 'analyticalMethodSearch', null),
                        'dataSourceLaboratorySearch'      => data_get($request, 'dataSourceLaboratorySearch', null),
                        'dataSourceOrganisationSearch'    => data_get($request, 'dataSourceOrganisationSearch', null),
                        'qualityAnalyticalMethodsSearch'  => data_get($request, 'qualityAnalyticalMethodsSearch', null),
                      ]) }}" class="text-blue-500 hover:underline">
                    View
                  </a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
          {{$queries->links('pagination::tailwind')}}
        </div>
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