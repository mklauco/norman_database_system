@extends('arbg.bacteria.statistics.layout')

@section('page-title', 'ARBG Bacteria Statistics Overview')

@section('main-content')
  <!-- Database Overview Card -->
  <div class="bg-slate-600 text-white rounded-lg p-6 mb-6">
    <div class="flex justify-between items-center">
      <div>
        <h3 class="text-2xl font-bold mb-2">ARBG Bacteria Database</h3>
        <p class="text-slate-200">Antibiotic Resistant Bacteria monitoring data</p>
      </div>
      <div class="text-right">
        <div class="text-3xl font-bold">{{ number_format($totalRecords, 0, '.', ' ') }}</div>
        <div class="text-slate-200">Total Records</div>
        @if($bacteriaEntity && $bacteriaEntity->last_update)
          <div class="text-xs text-slate-300 mt-1">
            Updated: {{ \Carbon\Carbon::parse($bacteriaEntity->last_update)->format('Y-m-d') }}
          </div>
        @endif
      </div>
    </div>
  </div>

  @if(!empty($allStats))
    <!-- Statistics Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

      <!-- Totals -->
      @if(isset($allStats['arbg.bacteria.totals']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Totals</h4>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Records:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['arbg.bacteria.totals']['total_records'], 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Countries:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['arbg.bacteria.totals']['total_countries'], 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Matrices:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['arbg.bacteria.totals']['total_matrices'], 0, '.', ' ') }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Years:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['arbg.bacteria.totals']['total_years'], 0, '.', ' ') }}</span>
            </div>
          </div>
        </div>
      @endif

      <!-- Per Country -->
      @if(isset($allStats['arbg.bacteria.per_country']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Country</h4>
            <a href="{{ route('arbg.bacteria.statistics.perCountry') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Countries:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['arbg.bacteria.per_country']['total_countries'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topCountry = collect($allStats['arbg.bacteria.per_country']['data'])
                ->sortByDesc(function($item) { return $item['count']; })
                ->first();
              $topCountryKey = collect($allStats['arbg.bacteria.per_country']['data'])
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

      <!-- Per Year -->
      @if(isset($allStats['arbg.bacteria.per_year']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Year</h4>
            <a href="{{ route('arbg.bacteria.statistics.perYear') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Years:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['arbg.bacteria.per_year']['total_years'], 0, '.', ' ') }}</span>
            </div>
            @if(isset($allStats['arbg.bacteria.per_year']['year_range']))
              <div class="text-xs text-slate-500 mt-2">
                Range: {{ $allStats['arbg.bacteria.per_year']['year_range']['min_year'] }} - {{ $allStats['arbg.bacteria.per_year']['year_range']['max_year'] }}
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Per Matrix -->
      @if(isset($allStats['arbg.bacteria.per_matrix']))
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-3">
            <h4 class="font-semibold text-slate-800">Records by Matrix</h4>
            <a href="{{ route('arbg.bacteria.statistics.perMatrix') }}"
               class="text-slate-600 hover:text-slate-800 text-xs underline">
              View Details
            </a>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-slate-600">Matrices:</span>
              <span class="font-medium text-slate-900">{{ number_format($allStats['arbg.bacteria.per_matrix']['total_matrices'], 0, '.', ' ') }}</span>
            </div>
            @php
              $topMatrix = collect($allStats['arbg.bacteria.per_matrix']['data'])
                ->sortByDesc(function($item) { return $item['count']; })
                ->first();
              $topMatrixKey = collect($allStats['arbg.bacteria.per_matrix']['data'])
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
