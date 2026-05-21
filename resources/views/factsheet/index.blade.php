<x-app-layout>
  <x-slot name="header">
    @include('factsheet.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          {{-- main div --}}

          @if (isset($substance) && $substance)
            <!-- Primary information -->
            <div class="grid grid-cols-3 gap-4">
              <div class="col-span-1">
                <div class="mb-6">
                  <h1 class="text-2xl font-bold text-teal-800 font-mono mb-2">
                    {{ $substance->name ?? 'Substance Factsheet' }}
                  </h1>
                  @if($substance->structure_image_url)
                  <div class="mt-4" x-data="{ open: false }">
                    <img src="{{ $substance->structure_image_url }}"
                         alt="Structure of {{ $substance->name }}"
                         class="w-40 h-40 object-contain bg-white border border-gray-200 rounded-lg p-2 cursor-pointer hover:border-gray-400 transition-colors"
                         @click="open = true">
                    <!-- Modal overlay -->
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         @click="open = false"
                         @keydown.escape.window="open = false"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
                         style="display: none;">
                      <img src="{{ $substance->structure_image_url }}"
                           alt="Structure of {{ $substance->name }}"
                           class="max-w-full max-h-[90vh] object-contain bg-white rounded-lg p-4 shadow-2xl"
                           @click.stop>
                    </div>
                  </div>
                  @endif
                </div>
                <div class="mb-4 space-y-2">
                  <div class="flex flex-wrap gap-2">
                    <a href="{{ route('factsheets.search.filter') }}"
                      class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                      ← New Search
                    </a>
                    
                    @if(isset($hasStatistics) && $hasStatistics)
                      @auth
                        <a href="{{ route('factsheets.statistics.raw-json', $substance->id) }}" target="_blank"
                          class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                          <i class="fas fa-code mr-1"></i>
                          View Raw Statistics JSON
                        </a>
                        
                        {{-- Generate/Re-generate Statistics Button --}}
                        @php
                          $hasStatisticsData = isset($statisticsData) && $statisticsData !== null;
                          $isAdmin = auth()->user()->hasRole(['admin', 'super_admin']);
                        @endphp
                        
                        @if($hasStatisticsData)
                          {{-- Statistics data exists --}}
                          @if($isAdmin)
                            {{-- Admin/Super Admin can re-generate --}}
                            <form action="{{ route('factsheets.statistics.generate-for-substance') }}" method="POST" class="inline">
                              @csrf
                              <input type="hidden" name="substance_id" value="{{ $substance->id }}">
                              <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                                <i class="fas fa-sync-alt mr-1"></i>
                                Re-generate Statistics
                              </button>
                            </form>
                          @else
                            {{-- Regular users see disabled button --}}
                            <button disabled class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-md cursor-not-allowed">
                              <i class="fas fa-chart-bar mr-1"></i>
                              Generate Statistics
                            </button>
                          @endif
                        @else
                          {{-- No statistics data - all authenticated users can generate --}}
                          <form action="{{ route('factsheets.statistics.generate-for-substance') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="substance_id" value="{{ $substance->id }}">
                            <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                              <i class="fas fa-chart-bar mr-1"></i>
                              Generate Statistics
                            </button>
                          </form>
                        @endif
                      @endauth
                    @endif
                  </div>
                  
                  {{-- Message when substance exists but no statistics data --}}
                  @if(isset($hasStatistics) && $hasStatistics && (!isset($statisticsData) || $statisticsData === null))
                    <div class="mt-2">
                      <div class="inline-flex items-center px-3 py-2 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md">
                        <i class="fas fa-info-circle mr-2"></i>
                        Statistics data from Chemical Occurrence Database were not fetched yet. Please try again later.
                      </div>
                    </div>
                  @endif
                </div>
              </div>
              <div class="col-span-2">
                <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                  <h2 class="text-lg font-semibold text-gray-900 mb-4">Substance Information at Glance</h2>
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                      <h3 class="text-sm font-medium text-gray-800 mb-1">Substance</h3>
                      <p class="text-sm text-teal-800 font-mono">{{ $substance->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                      <h3 class="text-sm font-medium text-gray-800 mb-1">CAS Number</h3>
                      <p class="text-sm text-teal-800 font-mono">{{ $substance->cas_number ?? 'N/A' }}</p>
                    </div>
                    <div>
                      <h3 class="text-sm font-medium text-gray-800 mb-1">StdInChIKey</h3>
                      <p class="text-sm text-teal-800 font-mono">{{ $substance->stdinchikey ?? 'N/A' }}</p>
                    </div>
                    <div>
                      <h3 class="text-sm font-medium text-gray-800 mb-1">NORMAN SusDat ID</h3>
                      <p class="text-sm text-teal-800 font-mono">{{ $substance->prefixed_code ?? 'N/A' }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- End of Primary information -->

                      <!-- Factsheet Sections -->
          @php
            // Comprehensive field translation mapping for all factsheet sections
            $fieldTranslations = [
              // Chemical identity section (from Substance model)
              'prefixed_code' => 'NORMAN SusDat ID',
              'name' => 'Name',
              'cas_number' => 'CAS Registry Number',
              'smiles' => 'SMILES',
              'stdinchikey' => 'InChIKey',
              'molecular_formula' => 'Molecular Formula',
              'mass_iso' => 'Monoisotopic Mass [g/mol]',
              'dtxid' => 'DSSTox Substance ID',
              'pubchem_cid' => 'PubChem CID',
              
              // Major uses section (from UsepaCategories model)
              'category_name' => 'Use Category',
              
              // Properties section (from Usepa model)
              'usepa_formula' => 'Formula',
              'usepa_wikipedia' => 'Wikipedia Entry',
              'usepa_wikipedia_url' => 'Wikipedia URL',
              'usepa_Log_Kow_experimental' => 'LogKow experimental - DashBoard',
              'usepa_Log_Kow_predicted' => 'LogKow predicted - DashBoard',
              'usepa_solubility_experimental' => 'Solubility experimental (mol/L) - DashBoard',
              'usepa_solubility_predicted' => 'Solubility predicted (mol/L) - DashBoard',
              'usepa_Koc_min_experimental' => 'Koc_min_experimental (L/kg) - DashBoard',
              'usepa_Koc_max_experimental' => 'Koc_max_experimental (L/kg) - DashBoard',
              'usepa_Koc_min_predicted' => 'Koc_min_predicted (L/kg) - DashBoard',
              'usepa_Koc_max_predicted' => 'Koc_max_predicted (L/kg) - DashBoard',
              'usepa_Life_experimental' => 'Biodeg. Half-Life experimental (days) - DashBoard',
              'usepa_Life_predicted' => 'Biodeg. Half-Life predicted (days) - DashBoard',
              'usepa_BCF_experimental' => 'BCF experimental - DashBoard',
              'usepa_BCF_predicted' => 'BCF predicted - DashBoard',
              
              // Ecotoxicity section (from LowestPNECMain model - legacy format)
              'lowest_pnec_fresh_water' => 'Lowest PNEC fresh water (μg/L)',
              'experimental_predicted' => 'Experimental / predicted',
              'species' => 'Species',
              'af' => 'AF',
              'endpoint' => 'Endpoint',
              'reference' => 'Reference',
              'lowest_pnec_marine_water' => 'Lowest PNEC marine water (μg/L)',
              'lowest_pnec_sediment' => 'Lowest PNEC sediment (μg/kg dw)',
              'lowest_pnec_biota' => 'Lowest PNEC biota (μg/kg ww)',
              'message' => 'Information',
              'error' => 'Error',
              
              // Environmental occurrence section (from FactsheetStatistic - country_year data)
              'total_countries' => 'Total Countries with Data',
              'year_range' => 'Year Range',
              'total_records' => 'Total Records',
              'top_country_1' => 'Top Country #1',
              'top_country_2' => 'Top Country #2',
              'top_country_3' => 'Top Country #3',
              'top_country_4' => 'Top Country #4',
              'top_country_5' => 'Top Country #5',
              'statistics_generated' => 'Statistics Generated',
            ];
          @endphp

          @if(isset($factsheetEntities) && $factsheetEntities->count() > 0)
            <div class="space-y-6">
              @foreach($factsheetEntities as $entity)
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm" x-data="{ open: false }">
                  <!-- Section Header (Always Visible) -->
                  <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                      <div class="flex items-center space-x-3">
                        <button 
                          @click="open = !open" 
                          class="flex items-center space-x-2 text-left hover:text-slate-600 transition-colors duration-200"
                          :aria-expanded="open"
                        >
                          <!-- Collapsible Arrow -->
                          <svg 
                            class="w-5 h-5 text-slate-500 transition-transform duration-200" 
                            :class="{ 'rotate-90': open }"
                            xmlns="http://www.w3.org/2000/svg" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor"
                          >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                          </svg>
                          <h3 class="text-lg font-semibold text-gray-900">{{ $entity->name }}</h3>
                        </button>
                      </div>
                      <span class="text-sm text-gray-500">Section {{ $entity->sort_order }}</span>
                    </div>
                  </div>
                  
                  <!-- Collapsible Content -->
                  <div 
                    x-show="open" 
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 transform translate-y-0"
                    x-transition:leave-end="opacity-0 transform -translate-y-2"
                    class="p-6"
                  >
                    @if(isset($entity->processed_data))
                      @if($entity->processed_data['type'] === 'database_table')
                        {{-- CASE 1: Database table presentation --}}
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                          @if(!empty($entity->processed_data['key_value_data']))
                            <div class="overflow-x-auto">
                              <table class="min-w-full divide-y divide-gray-200">
                                <tbody class="bg-white divide-y divide-gray-200">
                                  @foreach($entity->processed_data['key_value_data'] as $field => $value)
                                    <tr class="hover:bg-gray-50">
                                      <td class="px-4 py-3 text-sm font-medium text-gray-800 whitespace-nowrap bg-gray-50 border-r border-gray-200">
                                        {{ $fieldTranslations[$field] ?? ucwords(str_replace(['_', '-'], ' ', $field)) }}
                                      </td>
                                      <td class="px-4 py-3 text-sm text-gray-700 font-mono break-all">
                                        @if(is_array($value))
                                          @foreach($value as $item)
                                            <div>{{ $item }}</div>
                                          @endforeach
                                        @else
                                          {{ $value ?: 'N/A' }}
                                        @endif
                                      </td>
                                    </tr>
                                  @endforeach
                                </tbody>
                              </table>
                            </div>
                          @else
                            <div class="text-center py-4">
                              <p class="text-sm text-gray-600">No data available for this section</p>
                              <p class="text-xs text-gray-500 mt-1">Model: {{ $entity->processed_data['model'] ?? 'N/A' }}</p>
                            </div>
                          @endif
                        </div>
                      @elseif($entity->processed_data['type'] === 'text')
                        {{-- CASE 2: Text presentation --}}
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                          <p class="text-sm text-gray-700 leading-relaxed">{{ $entity->processed_data['content'] }}</p>
                        </div>
                      @elseif($entity->processed_data['type'] === 'banner')
                        {{-- CASE 3: Banner presentation --}}
                        @php
                          // Define green color variations based on intensity
                          $colorClasses = [
                            'green' => 'bg-green-100 border-l-green-600 text-green-800',
                            'light-green' => 'bg-green-50 border-l-green-500 text-green-700',
                            'dark-green' => 'bg-green-200 border-l-green-700 text-green-900',
                            'emerald' => 'bg-emerald-100 border-l-emerald-600 text-emerald-800',
                            'teal' => 'bg-teal-100 border-l-teal-600 text-teal-800'
                          ];
                          $bannerColor = $entity->processed_data['color'] ?? 'green';
                          $colorClass = $colorClasses[$bannerColor] ?? $colorClasses['green'];
                        @endphp
                        <div class="border-l-4 p-4 {{ $colorClass }} rounded-r-lg">
                          <p class="text-sm font-medium leading-relaxed">{{ $entity->processed_data['text'] }}</p>
                        </div>
                      @elseif($entity->processed_data['type'] === 'table')
                        {{-- CASE 4: Table presentation for country-year data --}}
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                          {{-- Summary information --}}
                          @if(isset($entity->processed_data['summary']))
                            <div class="mb-4 p-3 bg-slate-50 rounded-lg">
                              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                  <span class="font-medium text-slate-700">Total Countries:</span>
                                  <span class="text-slate-900">{{ $entity->processed_data['summary']['total_countries'] ?? 0 }}</span>
                                </div>
                                <div>
                                  <span class="font-medium text-slate-700">Total Records:</span>
                                  <span class="text-slate-900">{{ number_format($entity->processed_data['summary']['total_records'] ?? 0) }}</span>
                                </div>
                                <div>
                                  <span class="font-medium text-slate-700">Year Range:</span>
                                  <span class="text-slate-900">{{ $entity->processed_data['summary']['year_range'] ?? 'N/A' }}</span>
                                </div>
                                <div>
                                  <span class="font-medium text-slate-700">Generated:</span>
                                  <span class="text-slate-900">{{ $entity->processed_data['summary']['generated_at'] ?? 'N/A' }}</span>
                                </div>
                              </div>
                            </div>
                          @endif
                          
                          {{-- Country-Year Table --}}
                          @if(isset($entity->processed_data['table_data']) && count($entity->processed_data['table_data']) > 0)
                            <div class="overflow-x-auto">
                              <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                  <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50">
                                      Country
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                      Total Records
                                    </th>
                                    @if(isset($entity->processed_data['years']))
                                      @foreach($entity->processed_data['years'] as $year)
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                          {{ $year }}
                                        </th>
                                      @endforeach
                                    @endif
                                  </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                  @foreach($entity->processed_data['table_data'] as $row)
                                    <tr class="hover:bg-gray-50">
                                      <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap sticky left-0 bg-white">
                                        {{ $row['country'] }}
                                      </td>
                                      <td class="px-4 py-3 text-sm text-gray-700 font-mono">
                                        {{ number_format($row['total_records']) }}
                                      </td>
                                      @if(isset($entity->processed_data['years']))
                                        @foreach($entity->processed_data['years'] as $year)
                                          <td class="px-3 py-3 text-sm text-center text-gray-700 font-mono">
                                            @php
                                              $count = $row['years'][$year] ?? 0;
                                            @endphp
                                            @if($count > 0)
                                              <span class="inline-block px-2 py-1 text-xs bg-slate-100 rounded">{{ $count }}</span>
                                            @else
                                              <span class="text-gray-400">-</span>
                                            @endif
                                          </td>
                                        @endforeach
                                      @endif
                                    </tr>
                                  @endforeach
                                </tbody>
                              </table>
                            </div>
                          @else
                            <div class="text-center py-4">
                              <p class="text-sm text-gray-600">No country-year data available</p>
                            </div>
                          @endif
                        </div>
                      @elseif($entity->processed_data['type'] === 'matrix_table')
                        {{-- CASE 5: Matrix Table presentation --}}
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                          {{-- Summary information --}}
                          @if(isset($entity->processed_data['summary']))
                            <div class="mb-4 p-3 bg-slate-50 rounded-lg">
                              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                  <span class="font-medium text-slate-700">Total Matrices:</span>
                                  <span class="text-slate-900">{{ $entity->processed_data['summary']['total_matrices'] ?? 0 }}</span>
                                </div>
                                <div>
                                  <span class="font-medium text-slate-700">Total Records:</span>
                                  <span class="text-slate-900">{{ number_format($entity->processed_data['summary']['total_records'] ?? 0) }}</span>
                                </div>
                                <div>
                                  <span class="font-medium text-slate-700">Generated:</span>
                                  <span class="text-slate-900">{{ $entity->processed_data['summary']['generated_at'] ?? 'N/A' }}</span>
                                </div>
                              </div>
                            </div>
                          @endif
                          
                          {{-- Matrix Table --}}
                          @if(isset($entity->processed_data['matrix_data']) && count($entity->processed_data['matrix_data']) > 0)
                            <div class="overflow-x-auto">
                              <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                  <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                      Matrix
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                      Hierarchy Path
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                      Total Records
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                      Level
                                    </th>
                                  </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                  @foreach($entity->processed_data['matrix_data'] as $matrix)
                                    <tr class="hover:bg-gray-50">
                                      <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $matrix['matrix_name'] }}
                                      </td>
                                      <td class="px-4 py-3 text-sm text-gray-700">
                                        <span class="font-mono text-xs">{{ $matrix['hierarchy_path'] }}</span>
                                      </td>
                                      <td class="px-4 py-3 text-sm text-center text-gray-700 font-mono">
                                        <span class="inline-block px-2 py-1 text-xs bg-slate-100 rounded">
                                          {{ number_format($matrix['record_count']) }}
                                        </span>
                                      </td>
                                      <td class="px-4 py-3 text-sm text-center text-gray-700">
                                        <span class="flex w-6 h-6 text-xs bg-slate-200 rounded-full items-center justify-center">
                                          {{ $matrix['hierarchy_level'] }}
                                        </span>
                                      </td>
                                    </tr>
                                  @endforeach
                                </tbody>
                              </table>
                            </div>
                          @else
                            <div class="text-center py-4">
                              <p class="text-sm text-gray-600">No matrix data available</p>
                            </div>
                          @endif
                        </div>
                      @elseif($entity->processed_data['type'] === 'hazards_pbt_table')
                        {{-- CASE 6: Hazards PBT/PMT table presentation --}}
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                          <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                              <thead class="bg-gray-50">
                                <tr>
                                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Criterion
                                  </th>
                                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Classification
                                  </th>
                                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    P, B, M, T score(i)
                                  </th>
                                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Reference
                                  </th>
                                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Consensus
                                  </th>
                                </tr>
                              </thead>
                              <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($entity->processed_data['rows'] as $row)
                                  <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">
                                      {{ $row['criterion'] }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 font-mono whitespace-nowrap">
                                      {{ $row['classification'] }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 font-mono whitespace-nowrap">
                                      {{ $row['score'] }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                      {{ $row['reference'] }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 font-mono whitespace-nowrap">
                                      {{ $row['consensus'] }}
                                    </td>
                                  </tr>
                                @endforeach
                              </tbody>
                            </table>
                          </div>

                          @if(!empty($entity->processed_data['legend']))
                            <div class="mt-4 border-t border-gray-200 pt-4">
                              <div class="text-sm font-semibold text-gray-900 mb-2">
                                P, B, M, T score(i):
                              </div>
                              <div class="overflow-x-auto">
                                <div class="space-y-2 text-gray-700 min-w-max" style="font-size: 12px; line-height: 1.1;">
                                <div class="whitespace-nowrap">
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 9px;">vP</span><span style="padding-right: 6px;">=</span><span>1</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 9px;">P</span><span style="padding-right: 6px;">=</span><span>0.75</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 12px;">sP (suspectP)</span><span style="padding-right: 6px;">=</span><span>0.5</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 10px;">not P</span><span style="padding-right: 6px;">=</span><span>0</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 9px;">probably-nP</span><span style="padding-right: 6px;">=</span><span>0.1</span></span>
                                  <span class="inline-block whitespace-nowrap"><span style="padding-right: 6px;">no data</span><span style="padding-right: 6px;">=</span><span>0.1</span></span>
                                </div>
                                <div class="whitespace-nowrap">
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 9px;">vB</span><span style="padding-right: 6px;">=</span><span>1</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 8px;">B</span><span style="padding-right: 6px;">=</span><span>0.75</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 12px;">sB (suspectB)</span><span style="padding-right: 6px;">=</span><span>0.5</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 9px;">not B</span><span style="padding-right: 6px;">=</span><span>0</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 9px;">probably-nB</span><span style="padding-right: 6px;">=</span><span>0.1</span></span>
                                  <span class="inline-block whitespace-nowrap"><span style="padding-right: 6px;">no data</span><span style="padding-right: 6px;">=</span><span>0.1</span></span>
                                </div>
                                <div class="whitespace-nowrap">
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 6px;">vM</span><span style="padding-right: 6px;">=</span><span>1</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 6px;">M</span><span style="padding-right: 6px;">=</span><span>0.75</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 6px;">sM (suspectM)</span><span style="padding-right: 6px;">=</span><span>0.5</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 6px;">not M</span><span style="padding-right: 6px;">=</span><span>0</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 6px;">probably-nM</span><span style="padding-right: 6px;">=</span><span>0.1</span></span>
                                  <span class="inline-block whitespace-nowrap"><span style="padding-right: 6px;">no data</span><span style="padding-right: 6px;">=</span><span>0.1</span></span>
                                </div>
                                <div class="whitespace-nowrap">
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 8px;">T+</span><span style="padding-right: 6px;">=</span><span>1</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 10px;">T</span><span style="padding-right: 6px;">=</span><span>0.75</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 13px;">sT (suspectT)</span><span style="padding-right: 6px;">=</span><span>0.5</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 10px;">not T</span><span style="padding-right: 6px;">=</span><span>0</span></span>
                                  <span class="inline-block whitespace-nowrap" style="margin-right: 28px;"><span style="padding-right: 9px;">probably-nT</span><span style="padding-right: 6px;">=</span><span>0.1</span></span>
                                  <span class="inline-block whitespace-nowrap"><span style="padding-right: 6px;">no data</span><span style="padding-right: 6px;">=</span><span>0.1</span></span>
                                </div>
                                </div>
                              </div>
                            </div>
                          @endif

                          @if(!empty($entity->processed_data['classification_rows']))
                            <div class="mt-6 border-t border-gray-200 pt-4">
                              <div class="text-sm font-semibold text-gray-900 mb-3">
                                PBT/vPvB and PMT/vPvM classification
                              </div>
                              <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                  <thead class="bg-gray-50">
                                    <tr>
                                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                      </th>
                                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Classification
                                      </th>
                                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        PBT/vPvB &amp; PMT/vPvM score(ii)
                                      </th>
                                    </tr>
                                  </thead>
                                  <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($entity->processed_data['classification_rows'] as $row)
                                      <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">
                                          {{ $row['classification'] }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 font-mono whitespace-nowrap">
                                          {{ $row['result'] }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 font-mono whitespace-nowrap">
                                          {{ $row['score'] }}
                                        </td>
                                      </tr>
                                    @endforeach
                                  </tbody>
                                </table>
                              </div>

                              @if(!empty($entity->processed_data['classification_legend']))
                                <div class="mt-4 border-t border-gray-200 pt-4">
                                  <div class="text-sm font-semibold text-gray-900 mb-2">
                                    PBT/vPvB &amp; PMT/vPvM score(ii):
                                  </div>
                                  <div class="grid grid-cols-4 gap-x-4 gap-y-2 text-gray-700" style="font-size: 12px; line-height: 1.1;">
                                    <div>
                                      <div>if ((P or vP) and (B or vB) and (T or T+)):</div>
                                      <div class="pl-4">Classification: PBT</div>
                                      <div class="pl-4">PBT score = 1</div>
                                      <div>else:</div>
                                      <div class="pl-4">Classification: not PBT</div>
                                      <div class="pl-4">PBT score = 0</div>
                                    </div>
                                    <div>
                                      <div>if (vP and vB):</div>
                                      <div class="pl-4">Classification: vPvB</div>
                                      <div class="pl-4">vPvB score = 1</div>
                                      <div>else:</div>
                                      <div class="pl-4">Classification: not vPvB</div>
                                      <div class="pl-4">vPvB score = 0</div>
                                    </div>
                                    <div>
                                      <div>if ((P or vP) and (M or vM) and (T or T+)):</div>
                                      <div class="pl-4">Classification: PMT</div>
                                      <div class="pl-4">PMT score = 1</div>
                                      <div>else:</div>
                                      <div class="pl-4">Classification: not PMT</div>
                                      <div class="pl-4">PMT score = 0</div>
                                    </div>
                                    <div>
                                      <div>if (vP and vM):</div>
                                      <div class="pl-4">Classification: vPvM</div>
                                      <div class="pl-4">vPvM score = 1</div>
                                      <div>else:</div>
                                      <div class="pl-4">Classification: not vPvM</div>
                                      <div class="pl-4">vPvM score = 0</div>
                                    </div>
                                  </div>
                                </div>
                              @endif
                            </div>
                          @endif

                          @if(isset($entity->processed_data['pbmt_score']))
                            <div class="mt-6 border-t border-gray-200 pt-4">
                              <div class="text-sm font-semibold text-gray-900 mb-3">
                                Conclusion PBMT
                              </div>
                              <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                  <thead class="bg-gray-50">
                                    <tr>
                                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Conclusion
                                      </th>
                                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        PBMT score for prioritization(iii)
                                      </th>
                                    </tr>
                                  </thead>
                                  <tbody class="bg-white divide-y divide-gray-200">
                                    <tr class="hover:bg-gray-50">
                                      <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">
                                        PBMT
                                      </td>
                                      <td class="px-4 py-3 text-sm text-gray-700 font-mono whitespace-nowrap">
                                        {{ $entity->processed_data['pbmt_score'] }}
                                      </td>
                                    </tr>
                                  </tbody>
                                </table>
                              </div>

                              @if(!empty($entity->processed_data['pbmt_legend']))
                                <div class="mt-4 border-t border-gray-200 pt-4">
                                  <div class="text-sm font-semibold text-gray-900 mb-2">
                                    PBMT score for prioritization(iii):
                                  </div>
                                  <div class="text-gray-700" style="font-size: 12px; line-height: 1.1;">
                                    {{ $entity->processed_data['pbmt_legend'] }}
                                  </div>
                                </div>
                              @endif
                            </div>
                          @endif
                        </div>
                      @endif
                    @elseif(isset($entity->data['method_of_presentation']))
                      {{-- Fallback for unprocessed data --}}
                      @if($entity->data['method_of_presentation'] === 'database_table')
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                          <p class="text-sm text-slate-600 mb-2">Database table presentation</p>
                          <p class="text-xs text-slate-500">Model: {{ $entity->data['model'] ?? 'N/A' }}</p>
                          @if(isset($entity->data['fields']))
                            <p class="text-xs text-slate-500">Fields: {{ implode(', ', $entity->data['fields']) }}</p>
                          @endif
                        </div>
                      @elseif($entity->data['method_of_presentation'] === 'text')
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                          <p class="text-sm text-slate-600">{{ $entity->data['text'] ?? 'No text content available' }}</p>
                        </div>
                      @elseif($entity->data['method_of_presentation'] === 'banner')
                        {{-- Fallback banner display --}}
                        @php
                          $bannerColor = $entity->data['color'] ?? 'green';
                          $colorClasses = [
                            'green' => 'bg-green-100 border-l-green-600 text-green-800',
                            'light-green' => 'bg-green-50 border-l-green-500 text-green-700',
                            'dark-green' => 'bg-green-200 border-l-green-700 text-green-900',
                            'emerald' => 'bg-emerald-100 border-l-emerald-600 text-emerald-800',
                            'teal' => 'bg-teal-100 border-l-teal-600 text-teal-800'
                          ];
                          $colorClass = $colorClasses[$bannerColor] ?? $colorClasses['green'];
                        @endphp
                        <div class="border-l-4 p-4 {{ $colorClass }} rounded-r-lg">
                          <p class="text-sm font-medium leading-relaxed">{{ $entity->data['text'] ?? 'No banner text available' }}</p>
                        </div>
                      @endif
                    @else
                      <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                        <p class="text-sm text-slate-600">Content will be configured later</p>
                      </div>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-6 text-center">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">No Factsheet Sections Available</h3>
              <p class="text-gray-600">Factsheet sections have not been configured yet.</p>
            </div>
          @endif
          @else
            <div class="mb-6 bg-slate-50 border border-slate-200 rounded-lg p-6 text-center">
              <h2 class="text-lg font-semibold text-gray-900 mb-2">No Substances Selected</h2>
              <p class="text-gray-600 mb-4">Please search for substances to view their factsheet information.</p>
              <a href="{{ route('factsheets.search.filter') }}" class="btn-submit">
                Start Substance Search
              </a>
            </div>
          @endif

          {{-- end of main div --}}
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
