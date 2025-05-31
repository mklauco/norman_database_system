@extends('empodat.statistics.layout')

@section('page-title', 'Quality Statistics')
@section('page-subtitle', 'Data quality based on analytical method ratings')

@section('main-content')
  @if(isset($generatedAt))
    <div class="mb-4 text-sm text-gray-600">
      Data generated: {{ \Carbon\Carbon::parse($generatedAt)->format('Y-m-d H:i:s') }}
    </div>
  @endif

  @if(isset($message))
    <!-- No Data Message -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
      <div class="text-yellow-800">{{ $message }}</div>
      <a href="{{ route('empodat.statistics.index') }}" class="text-blue-600 hover:text-blue-800 underline text-sm">
        Go back to statistics overview
      </a>
    </div>
  @elseif(empty($qualityStats))
    <!-- Empty Data -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
      <div class="text-gray-600 text-lg mb-2">No quality statistics available.</div>
      <div class="text-sm text-gray-500">Generate new statistics to see data.</div>
    </div>
  @else
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="font-semibold text-blue-800 mb-2">Quality Categories</h4>
        <div class="text-2xl font-bold text-blue-900">{{ $totalCategories }}</div>
        <div class="text-sm text-blue-600">rating categories</div>
      </div>

      @if(!empty($qualityStats))
        @php
          // Find highest and lowest quality categories
          $highestQuality = collect($qualityStats)->filter(function($item) {
            return !is_null($item['min_rating']);
          })->sortByDesc('min_rating')->first();
          
          $lowestQuality = collect($qualityStats)->filter(function($item) {
            return !is_null($item['min_rating']);
          })->sortBy('min_rating')->first();
          
          // Calculate high quality percentage (rating >= 68)
          $highQualityCount = collect($qualityStats)->filter(function($item) {
            return !is_null($item['min_rating']) && $item['min_rating'] >= 68;
          })->sum('record_count');
          
          $highQualityPercentage = $totalRecords > 0 ? ($highQualityCount / $totalRecords) * 100 : 0;
        @endphp

        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
          <h4 class="font-semibold text-green-800 mb-2">Highest Quality Data</h4>
          @if($highestQuality)
            <div class="text-lg font-bold text-green-900">{{ number_format($highestQuality['record_count']) }}</div>
            <div class="text-sm text-green-600">{{ $highestQuality['rating_range'] }} rating</div>
          @else
            <div class="text-sm text-green-600">No rated data available</div>
          @endif
        </div>

        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
          <h4 class="font-semibold text-orange-800 mb-2">Lowest Quality Data</h4>
          @if($lowestQuality)
            <div class="text-lg font-bold text-orange-900">{{ number_format($lowestQuality['record_count']) }}</div>
            <div class="text-sm text-orange-600">{{ $lowestQuality['rating_range'] }} rating</div>
          @else
            <div class="text-sm text-orange-600">No rated data available</div>
          @endif
        </div>

        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
          <h4 class="font-semibold text-purple-800 mb-2">High Quality Coverage</h4>
          <div class="text-2xl font-bold text-purple-900">{{ number_format($highQualityPercentage, 1) }}%</div>
          <div class="text-sm text-purple-600">adequately supported data</div>
        </div>
      @else
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <h4 class="font-semibold text-gray-600 mb-2">Quality Distribution</h4>
          <div class="text-sm text-gray-500">No data available</div>
        </div>
      @endif
    </div>

    <!-- Data Table Controls -->
    <div class="mb-4 flex justify-between items-center">
      <div class="flex items-center gap-2">
        <label for="entriesSelect" class="text-sm text-gray-700">Show</label>
        <select id="entriesSelect" class="border border-gray-300 rounded px-2 py-1 text-sm">
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100" selected>100</option>
          <option value="-1">All</option>
        </select>
        <span class="text-sm text-gray-700">entries</span>
      </div>
      
      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
          <label for="searchInput" class="text-sm text-gray-700">Search:</label>
          <input 
            type="text" 
            id="searchInput" 
            class="border border-gray-300 rounded px-3 py-1 text-sm w-64"
            placeholder="Filter quality categories..."
          >
        </div>
        
        <button 
          type="button" 
          id="downloadCsv"
          class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors text-sm font-medium flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
          </svg>
          Download CSV
        </button>
      </div>
    </div>

    <!-- Statistics Table -->
    <div class="overflow-x-auto border border-gray-200 rounded-lg">
      <table id="statisticsTable" class="w-full table-auto border-collapse">
        <thead>
          <tr class="bg-gray-600 text-white">
            <th class="border border-gray-300 px-4 py-2 text-left">
              <button type="button" class="flex items-center gap-1 font-medium hover:text-gray-200">
                Quality Category
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                </svg>
              </button>
            </th>
            <th class="border border-gray-300 px-4 py-2 text-center w-32">
              <button type="button" class="flex items-center gap-1 font-medium hover:text-gray-200 mx-auto">
                Rating Range
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                </svg>
              </button>
            </th>
            <th class="border border-gray-300 px-4 py-2 text-right w-32">
              <button type="button" class="flex items-center gap-1 font-medium hover:text-gray-200 ml-auto">
                Record Count
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                </svg>
              </button>
            </th>
            <th class="border border-gray-300 px-4 py-2 text-right w-24">
              <button type="button" class="flex items-center gap-1 font-medium hover:text-gray-200 ml-auto">
                Percentage
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                </svg>
              </button>
            </th>
            <th class="border border-gray-300 px-4 py-2 text-center w-24">
              <button type="button" class="flex items-center gap-1 font-medium hover:text-gray-200 mx-auto">
                Quality Level
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                </svg>
              </button>
            </th>
          </tr>
        </thead>
        <tbody id="statisticsTableBody">
          @foreach($qualityStats as $quality)
            <tr class="quality-row hover:bg-gray-50 transition-colors" data-quality="{{ strtolower($quality['category_name']) }}">
              <td class="border border-gray-300 px-4 py-2">
                <div class="font-medium text-gray-900">{{ $quality['category_name'] }}</div>
              </td>
              <td class="border border-gray-300 px-4 py-2 text-center">
                <span class="px-2 py-1 bg-gray-100 rounded text-sm font-mono">
                  {{ $quality['rating_range'] }}
                </span>
              </td>
              <td class="border border-gray-300 px-4 py-2 text-right">
                <span class="font-medium text-blue-600">{{ number_format($quality['record_count']) }}</span>
              </td>
              <td class="border border-gray-300 px-4 py-2 text-right">
                <span class="text-gray-600">
                  {{ $totalRecords > 0 ? number_format(($quality['record_count'] / $totalRecords) * 100, 2) : 0 }}%
                </span>
              </td>
              <td class="border border-gray-300 px-4 py-2 text-center">
                @if(is_null($quality['min_rating']))
                  <span class="px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs font-medium">N/A</span>
                @elseif($quality['min_rating'] >= 68)
                  <span class="px-2 py-1 bg-green-200 text-green-800 rounded text-xs font-medium">High</span>
                @elseif($quality['min_rating'] >= 52)
                  <span class="px-2 py-1 bg-yellow-200 text-yellow-800 rounded text-xs font-medium">Medium</span>
                @elseif($quality['min_rating'] >= 22)
                  <span class="px-2 py-1 bg-orange-200 text-orange-800 rounded text-xs font-medium">Low</span>
                @else
                  <span class="px-2 py-1 bg-red-200 text-red-800 rounded text-xs font-medium">Very Low</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <!-- Table Info -->
    <div class="mt-4 flex justify-between items-center text-sm text-gray-600">
      <div id="tableInfo">
        Showing {{ count($qualityStats) }} quality categories
      </div>
      <div>
        Total records in database: <span class="font-medium">{{ number_format($totalRecords) }}</span>
      </div>
    </div>
  @endif
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const qualityRows = document.querySelectorAll('.quality-row');
    const entriesSelect = document.getElementById('entriesSelect');
    const downloadCsvBtn = document.getElementById('downloadCsv');

    // Only add event listeners if elements exist
    if (searchInput && qualityRows.length > 0) {
      // Search functionality
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let visibleRows = 0;
        
        qualityRows.forEach(row => {
          const qualityName = row.dataset.quality;
          if (qualityName.includes(searchTerm)) {
            row.style.display = '';
            visibleRows++;
          } else {
            row.style.display = 'none';
          }
        });
        
        updateTableInfo(visibleRows);
      });

      // Entries per page functionality
      if (entriesSelect) {
        entriesSelect.addEventListener('change', function() {
          const limit = parseInt(this.value);
          let visibleCount = 0;
          
          qualityRows.forEach((row, index) => {
            if (row.style.display !== 'none') {
              if (limit === -1 || visibleCount < limit) {
                row.style.display = '';
                visibleCount++;
              } else {
                row.style.display = 'none';
              }
            }
          });
        });
      }
    }

    // CSV Download functionality
    if (downloadCsvBtn) {
      downloadCsvBtn.addEventListener('click', function() {
        // Redirect to download endpoint
        window.location.href = '{{ route("empodat.statistics.download") }}?type=quality';
      });
    }

    function updateTableInfo(visibleRows) {
      const tableInfo = document.getElementById('tableInfo');
      if (tableInfo) {
        tableInfo.textContent = `Showing ${visibleRows} quality categories`;
      }
    }
  });
</script>
@endpush