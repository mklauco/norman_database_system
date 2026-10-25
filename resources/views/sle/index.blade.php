<x-app-layout>
  <x-slot name="header">
    @include('sle.header')
  </x-slot>
  

  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <!-- Title -->
          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            NORMAN Suspect List Exchange – NORMAN SLE
          </h1>
          
          <!-- Online Content -->
          @if(isset($onlineContent) && count($onlineContent) > 0)
            @php
              // Filter out empty content first
              $filteredContent = [];
              foreach($onlineContent as $content) {
                $cleanContent = str_replace('&nbsp;', '', $content);
                $cleanContent = str_replace("\u{A0}", '', $cleanContent); // Remove Unicode non-breaking space
                $cleanContent = trim($cleanContent);
                if (!empty($cleanContent)) {
                  $filteredContent[] = $cleanContent; // Store the cleaned content, not the original
                }
              }
            @endphp
            
            @if(count($filteredContent) > 0)
              @foreach($filteredContent as $content)
                <p class="text-gray-700 leading-relaxed mb-4 online-content">
                  {!! $content !!}
                </p>
              @endforeach
            @else
              <!-- No meaningful content available -->
              <p class="text-gray-600 italic">No content available from the online source.</p>
            @endif
          @else
            <!-- Fallback Description if online content is not available -->
            <p class="text-gray-700 leading-relaxed mb-4">
              The NORMAN Suspect List Exchange (NORMAN-SLE) was established in 2015 as a central access point for NORMAN members (and others) to find suspect lists relevant for their environmental monitoring questions. The NORMAN-SLE documents all individual collections that form a part of the merged collection NORMAN SusDat. The original SLE lists should be consulted to verify SusDat information if necessary (see Source column in SusDat). NORMAN-SLE versions are tracked on Zenodo. 
            </p>

            <p class="text-gray-700 leading-relaxed mb-4">
              NEW: Check out our NORMAN-SLE publication in ESEU @ DOI: 10.1186/s12302-022-00680-6 
            </p>
            
            <p class="text-gray-700 leading-relaxed mb-4">
              Comments and contributions are welcome - please email us at suspects@normandata.eu.
            </p>

            <p class="text-gray-700 leading-relaxed mb-4">
              Please refer to our documentation pages for: citation instructions, credits, updates, license details, SDFs and other useful tips!
            </p>
          @endif

          <!-- Data Source Info -->
          <div class="bg-sky-50 border-l-4 border-sky-400 p-4 mb-4">
            <div class="flex">
              <div class="shrink-0">
                <i class="fa fa-info-circle text-sky-400"></i>
              </div>
              <div class="ml-3">
                <p class="text-sm text-sky-700">
                  <strong>Live Data:</strong> This page displays data directly from the online source in real-time. 
                  Data is fetched from <a href="https://www.norman-network.com/nds/SLE/index_body.php" class="link-lime-text" target="_blank">https://www.norman-network.com/nds/SLE/index_body.php</a> on each page load. <strong>Last Updated:</strong> {{ now()->format('Y-m-d H:i:s') }} (UTC)
                </p>
              </div>
            </div>
          </div>


          {{-- <!-- Navigation -->
          <div class="mb-4 flex justify-between items-center">
            @role('admin|super_admin|sle')
            <div class="flex space-x-2">
              <a href="{{ route('sle.sources.database') }}" class="btn-submit">
                <i class="fa fa-database mr-2"></i>
                Database View (Development)
              </a>
              <a href="{{ route('sle.sources.refresh') }}" class="btn-create" onclick="return confirm('Are you sure you want to refresh the database with this online data?')">
                <i class="fa fa-download mr-2"></i>
                Update Database
              </a>
            </div>
            @else
            <div class="text-sm text-gray-600">
              <i class="fa fa-lock mr-1"></i>
              Database management requires administrator access
            </div>
            @endrole
          </div> --}}

          <!-- Data Table -->
          @if(isset($onlineData) && count($onlineData) > 0)
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th>Code</th>
                  <th>Name</th>
                  <th>Description</th>
                  <th>Link Full List</th>
                  <th>Link InChI Key List</th>
                  <th>Link References</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($onlineData as $item)
                <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="p-1 text-center font-medium">
                    {{ $item['code'] }}
                  </td>
                  <td class="p-1 text-center">
                    {{ $item['name'] }}
                  </td>
                  <td class="p-1 text-center">
                    {{ $item['description'] }}
                  </td>
                  <td class="p-1 text-left">
                    @if($item['link_full_list'])
                      <div class="prose max-w-none">
                        {!! preg_replace('/<a\s+href=/', '<a class="link-lime-text" href=', $item['link_full_list']) !!}
                      </div>
                    @else
                      <span class="text-gray-400">-</span>
                    @endif
                  </td>
                  <td class="p-1 text-left">
                    @if($item['link_inchikey_list'])
                      <div class="prose max-w-none">
                        {!! preg_replace('/<a\s+href=/', '<a class="link-lime-text" href=', $item['link_inchikey_list']) !!}
                      </div>
                    @else
                      <span class="text-gray-400">-</span>
                    @endif
                  </td>
                  <td class="p-1 text-left">
                    @if($item['link_references'])
                      <div class="prose max-w-none">
                        {!! preg_replace('/<a\s+href=/', '<a class="link-lime-text" href=', $item['link_references']) !!}
                      </div>
                    @else
                      <span class="text-gray-400">-</span>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>

            <!-- Data Count -->
            <div class="mt-4 text-center text-sm text-gray-600">
              Total records: {{ count($onlineData) }}
            </div>

          @else
            <!-- No Data Message -->
            <div class="text-center py-8">
              <div class="text-gray-400 mb-4">
                <i class="fa fa-exclamation-triangle text-6xl"></i>
              </div>
              <h3 class="text-lg font-medium text-gray-900 mb-2">No Data Available</h3>
              <p class="text-gray-600">Unable to fetch data from the online source. Please try again later.</p>
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</x-app-layout>