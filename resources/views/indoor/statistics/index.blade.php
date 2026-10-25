@extends('indoor.statistics.layout')

@section('page-title', 'Indoor Environment Statistics Overview')

@section('main-content')
  <!-- Database Overview Card -->
  <div class="bg-slate-600 text-white rounded-lg p-6 mb-6">
    <div class="flex justify-between items-center">
      <div>
        <h3 class="text-2xl font-bold mb-2">Indoor Environment Database</h3>
        <p class="text-slate-200">Indoor environment chemical occurrence data</p>
      </div>
      <div class="text-right">
        <div class="text-3xl font-bold">{{ number_format($totalRecords, 0, '.', ' ') }}</div>
        <div class="text-slate-200">Total Records</div>
        @if($indoorEntity && $indoorEntity->last_update)
          <div class="text-xs text-slate-300 mt-1">
            Updated: {{ \Carbon\Carbon::parse($indoorEntity->last_update)->format('Y-m-d') }}
          </div>
        @endif
      </div>
    </div>
  </div>

  @if(!empty($allStats))
    <!-- Statistics Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">

      <!-- Totals -->
      @if(isset($allStats['indoor.totals']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Totals</h4>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Records:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['indoor.totals']['total_records'], 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Countries:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['indoor.totals']['total_countries'], 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Matrices:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['indoor.totals']['total_matrices'], 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Environment Types:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['indoor.totals']['total_environment_types'], 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Environment Categories:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['indoor.totals']['total_environment_categories'], 0, '.', ' ') }}</span>
            </div>
          </div>
        </div>
      @endif

      <!-- Per Country -->
      @if(isset($allStats['indoor.per_country']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Country</h4>
            <a href="{{ route('indoor.statistics.perCountry') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Countries:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['indoor.per_country']['total_countries'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topCountry = collect($allStats['indoor.per_country']['data'])
                ->sortByDesc(function($item) { return $item['count']; })
                ->first();
              $topCountryKey = collect($allStats['indoor.per_country']['data'])
                ->sortByDesc(function($item) { return $item['count']; })
                ->keys()
                ->first();
            @endphp
            @if($topCountry)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ $topCountryKey }}<br>{{ number_format($topCountry['count'], 0, '.', ' ') }} records
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Per Matrix -->
      @if(isset($allStats['indoor.per_matrix']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Matrix</h4>
            <a href="{{ route('indoor.statistics.perMatrix') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Matrices:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['indoor.per_matrix']['total_matrices'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topMatrix = collect($allStats['indoor.per_matrix']['data'])
                ->sortByDesc(function($item) { return $item['count']; })
                ->first();
              $topMatrixKey = collect($allStats['indoor.per_matrix']['data'])
                ->sortByDesc(function($item) { return $item['count']; })
                ->keys()
                ->first();
            @endphp
            @if($topMatrix)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ Str::limit($topMatrixKey, 20) }}<br>{{ number_format($topMatrix['count'], 0, '.', ' ') }} records
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Per Environment Type -->
      @if(isset($allStats['indoor.per_environment_type']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Environment Type</h4>
            <a href="{{ route('indoor.statistics.perEnvironmentType') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Types:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['indoor.per_environment_type']['total_types'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topType = collect($allStats['indoor.per_environment_type']['data'])
                ->sortByDesc(function($item) { return $item['count']; })
                ->first();
              $topTypeKey = collect($allStats['indoor.per_environment_type']['data'])
                ->sortByDesc(function($item) { return $item['count']; })
                ->keys()
                ->first();
            @endphp
            @if($topType)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ Str::limit($topTypeKey, 20) }}<br>{{ number_format($topType['count'], 0, '.', ' ') }} records
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Per Environment Category -->
      @if(isset($allStats['indoor.per_environment_category']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Environment Category</h4>
            <a href="{{ route('indoor.statistics.perEnvironmentCategory') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Categories:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['indoor.per_environment_category']['total_categories'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topCategory = collect($allStats['indoor.per_environment_category']['data'])
                ->sortByDesc(function($item) { return $item['count']; })
                ->first();
              $topCategoryKey = collect($allStats['indoor.per_environment_category']['data'])
                ->sortByDesc(function($item) { return $item['count']; })
                ->keys()
                ->first();
            @endphp
            @if($topCategory)
              <div class="text-xs text-slate-500 mt-2">
                Top: {{ Str::limit($topCategoryKey, 20) }}<br>{{ number_format($topCategory['count'], 0, '.', ' ') }} records
              </div>
            @endif
          </div>
        </div>
      @endif

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
        @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
          <div class="bg-sky-50 border border-sky-200 rounded-lg p-4 max-w-md mx-auto">
            <div class="text-sm text-sky-800 font-medium mb-2">Admin Quick Start:</div>
            <div class="text-sm text-sky-600">
              Use the "Generate All Statistics" button above to create your first statistics overview.
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
