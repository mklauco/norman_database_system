@extends('empodat.statistics.layout')

@section('page-title', 'Empodat Statistics Overview')

@section('main-content')
  <!-- Database Overview Card -->
  <div class="bg-gradient-to-r from-slate-600 to-slate-700 text-white rounded-lg p-6 mb-6">
    <div class="flex justify-between items-center">
      <div>
        <h3 class="text-2xl font-bold mb-2">Empodat Database</h3>
        <p class="text-slate-200">Chemical occurrence monitoring data</p>
      </div>
      <div class="text-right">
        <div class="text-3xl font-bold">{{ number_format($totalRecords) }}</div>
        <div class="text-slate-200">Total Records</div>
        @if($empodatEntity && $empodatEntity->last_update)
          <div class="text-xs text-slate-300 mt-1">
            Updated: {{ \Carbon\Carbon::parse($empodatEntity->last_update)->format('Y-m-d') }}
          </div>
        @endif
      </div>
    </div>
  </div>

  @if(!empty($allStats))
    <!-- Statistics Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      
      <!-- Country Year Statistics -->
      @if(isset($allStats['country_year']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Geographic Coverage</h4>
            <a href="{{ route('empodat.statistics.countryYear') }}" 
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Countries:</span>
              <span class="font-medium text-slate-900">{{ $allStats['country_year']['total_countries'] }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Time Span:</span>
              <span class="font-medium text-slate-900">
                {{ $allStats['country_year']['year_range']['max_year'] - $allStats['country_year']['year_range']['min_year'] + 1 }} years
              </span>
            </div>
            <div class="text-xs text-slate-500 mt-2">
              {{ $allStats['country_year']['year_range']['min_year'] }} - {{ $allStats['country_year']['year_range']['max_year'] }}
            </div>
          </div>
        </div>
      @endif

      <!-- Matrix Statistics -->
      @if(isset($allStats['matrix']))
        <div class="bg-stone-50 border border-stone-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-stone-800">Environmental Matrices</h4>
            <a href="{{ route('empodat.statistics.matrix') }}" 
               class="text-stone-600 hover:text-stone-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-stone-600">Total Matrices:</span>
              <span class="font-medium text-stone-900">{{ $allStats['matrix']['total_matrices'] }}</span>
            </div>
            @php
              $topMatrix = collect($allStats['matrix']['data'])->sortByDesc('record_count')->first();
            @endphp
            @if($topMatrix)
              <div class="text-xs text-stone-500 mt-2">
                Most common: {{ $topMatrix['title'] ?? 'Unknown' }}
                <br>{{ number_format($topMatrix['record_count']) }} records
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Substance Statistics -->
      @if(isset($allStats['substance']))
        <div class="bg-zinc-50 border border-zinc-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-zinc-800">Chemical Substances</h4>
            <a href="{{ route('empodat.statistics.substance') }}" 
               class="text-zinc-600 hover:text-zinc-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-zinc-600">Total Substances:</span>
              <span class="font-medium text-zinc-900">{{ number_format($allStats['substance']['total_substances']) }}</span>
            </div>
            @php
              $topSubstance = collect($allStats['substance']['data'])->first(); // Already sorted by record_count desc
              $avgRecordsPerSubstance = $allStats['substance']['total_substances'] > 0 ? 
                round($allStats['substance']['total_records'] / $allStats['substance']['total_substances']) : 0;
            @endphp
            @if($topSubstance)
              <div class="text-xs text-zinc-500 mt-2">
                Most monitored: {{ Str::limit($topSubstance['substance_name'], 20) }}
                <br>Avg: {{ $avgRecordsPerSubstance }} records/substance
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Quality Statistics -->
      @if(isset($allStats['quality']))
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-gray-800">Data Quality</h4>
            <a href="{{ route('empodat.statistics.quality') }}" 
               class="text-gray-600 hover:text-gray-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            @php
              // Calculate high quality percentage (rating >= 68)
              $highQualityCount = collect($allStats['quality']['data'])->filter(function($item) {
                return !is_null($item['min_rating']) && $item['min_rating'] >= 68;
              })->sum('record_count');
              
              $highQualityPercentage = $allStats['quality']['total_records'] > 0 ? 
                round(($highQualityCount / $allStats['quality']['total_records']) * 100, 1) : 0;
              
              $noRatingCount = collect($allStats['quality']['data'])->filter(function($item) {
                return is_null($item['min_rating']);
              })->sum('record_count');
              
              $noRatingPercentage = $allStats['quality']['total_records'] > 0 ? 
                round(($noRatingCount / $allStats['quality']['total_records']) * 100, 1) : 0;
            @endphp
            <div class="flex justify-between">
              <span class="text-sm text-gray-600">High Quality:</span>
              <span class="font-medium text-gray-900">{{ $highQualityPercentage }}%</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-gray-600">No Rating:</span>
              <span class="font-medium text-gray-900">{{ $noRatingPercentage }}%</span>
            </div>
            <div class="text-xs text-gray-500 mt-2">
              {{ $allStats['quality']['total_categories'] }} quality categories
            </div>
          </div>
        </div>
      @endif

      <!-- Dynamic sections for any other statistics -->
      @foreach($allStats as $key => $data)
        @if(!in_array($key, ['country_year', 'matrix', 'substance', 'quality']))
          <div class="bg-neutral-50 border border-neutral-200 rounded-lg p-4">
            <div class="flex justify-between items-start mb-3">
              <h4 class="font-semibold text-neutral-800">{{ ucfirst(str_replace('_', ' ', $key)) }} Statistics</h4>
              @if(Route::has('empodat.statistics.' . $key))
                <a href="{{ route('empodat.statistics.' . $key) }}" 
                   class="text-neutral-600 hover:text-neutral-800 text-xs underline">
                  View Details
                </a>
              @endif
            </div>
            <div class="space-y-2">
              <div class="text-sm text-neutral-600">
                Generated: {{ \Carbon\Carbon::parse($data['generated_at'])->format('M d, Y') }}
              </div>
              @if(isset($data['total_records']))
                <div class="flex justify-between">
                  <span class="text-sm text-neutral-600">Records:</span>
                  <span class="font-medium text-neutral-900">{{ number_format($data['total_records']) }}</span>
                </div>
              @endif
            </div>
          </div>
        @endif
      @endforeach

    </div>

    <!-- Key Insights -->
    <div class="bg-stone-50 border border-stone-200 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-stone-800 mb-4">Key Insights</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        @if(isset($allStats['country_year']) && isset($allStats['substance']))
          <div class="flex items-start space-x-2">
            <div class="w-2 h-2 bg-stone-500 rounded-full mt-2"></div>
            <div>
              <strong>Data Density:</strong> 
              {{ round($totalRecords / $allStats['country_year']['total_countries']) }} records per country on average
            </div>
          </div>
        @endif
        
        @if(isset($allStats['substance']) && isset($allStats['matrix']))
          <div class="flex items-start space-x-2">
            <div class="w-2 h-2 bg-stone-500 rounded-full mt-2"></div>
            <div>
              <strong>Monitoring Scope:</strong> 
              {{ number_format($allStats['substance']['total_substances']) }} substances across {{ $allStats['matrix']['total_matrices'] }} matrix types
            </div>
          </div>
        @endif
        
        @if(isset($allStats['quality']))
          <div class="flex items-start space-x-2">
            <div class="w-2 h-2 bg-stone-500 rounded-full mt-2"></div>
            <div>
              <strong>Quality Assessment:</strong> 
              {{ $highQualityPercentage }}% of data has adequate quality support
            </div>
          </div>
        @endif
        
        @if(isset($allStats['country_year']))
          <div class="flex items-start space-x-2">
            <div class="w-2 h-2 bg-stone-500 rounded-full mt-2"></div>
            <div>
              <strong>Temporal Coverage:</strong> 
              {{ $allStats['country_year']['year_range']['max_year'] - $allStats['country_year']['year_range']['min_year'] + 1 }} years of monitoring data
            </div>
          </div>
        @endif
      </div>
    </div>

  @else
    <!-- No Statistics Available -->
    <div class="text-center py-12">
      <div class="text-gray-500 text-xl mb-4">
        No statistics generated yet
      </div>
      <div class="text-sm text-gray-400 mb-8">
        Generate statistics to see comprehensive data insights and coverage analysis.
      </div>
      
      @auth
        @if(auth()->user()->hasRole('super_admin'))
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 max-w-md mx-auto">
            <div class="text-sm text-blue-800 font-medium mb-2">Admin Quick Start:</div>
            <div class="text-sm text-blue-600">
              Use the generation tools above to create your first statistics overview.
            </div>
          </div>
        @else
          <div class="text-sm text-gray-500">
            Statistics generation is available for administrators only.
          </div>
        @endif
      @else
        <div class="text-sm text-gray-500">
          Please log in to access statistics generation.
        </div>
      @endauth
    </div>
  @endif
@endsection