<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  
  <div class="py-4">
    
    {{-- // Leaflet --}}
    {{-- // A basic map is as easy as using the x blade component. --}}
    
    
    {{-- <x-maps-leaflet></x-maps-leaflet> --}}
    
    {{-- // Set the centerpoint of the map: --}}
    {{-- <x-maps-leaflet :centerPoint="['lat' => 52.16, 'long' => 5]"></x-maps-leaflet> --}}
    
    {{-- // Set a zoomlevel: --}}
    {{-- <x-maps-leaflet :zoomLevel="6"></x-maps-leaflet> --}}
    
    {{-- // Set markers on the map: --}}
    
    {{-- </div> --}}
    
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      
      {{-- <div class="w-32 aspect-square bg-blue-500 flex items-center justify-center"> --}}
        <div class="bg-white overflow-hidden shadow rounded-lg divide-y divide-gray-200">
          <div class="px-4 py-5 sm:p-6">
            <div id="showingMap" class="w-64 h-64">Map:
              @include('_t.map', ['id' => "1", 'latitude' => 55, 'longitude' => 5, 'locations' => [['lat' => 55, 'lng' => 5, 'popup' => 'ASDF']]])
            </div>
          </div>
        </div>
        
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
                  <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
                    <td class="py-1 px-2">
                      {{ $q->id }}
                    </td>
                    <td class="py-1 px-2 text-xs">
                      <pre>{{ json_encode(json_decode($q->content), JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) }}
                      </pre>
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
                      @php
                      $content = json_decode($q->content, TRUE);
                      $request = $content['request'];
                      @endphp
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