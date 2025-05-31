@extends('empodat.statistics.layout')

@section('page-title', 'Matrix Statistics')
@section('page-subtitle', 'Number of data per environmental matrix')

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
  @elseif(empty($matrixStats))
    <!-- Empty Data -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
      <div class="text-gray-600 text-lg mb-2">No matrix statistics available.</div>
      <div class="text-sm text-gray-500">Generate new statistics to see data.</div>
    </div>
  @else
    <!-- Summary Cards -->
    {{-- <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="font-semibold text-blue-800 mb-2">Total Matrices</h4>
        <div class="text-2xl font-bold text-blue-900">{{ $totalMatrices }}</div>
        <div class="text-sm text-blue-600">environmental matrices</div>
      </div>

      <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <h4 class="font-semibold text-green-800 mb-2">Total Records</h4>
        <div class="text-2xl font-bold text-green-900">{{ number_format($totalRecords) }}</div>
        <div class="text-sm text-green-600">data points</div>
      </div>

      <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
        <h4 class="font-semibold text-purple-800 mb-2">Average per Matrix</h4>
        <div class="text-2xl font-bold text-purple-900">
          {{ $totalMatrices > 0 ? number_format($totalRecords / $totalMatrices) : 0 }}
        </div>
        <div class="text-sm text-purple-600">records per matrix</div>
      </div>
    </div> --}}

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
            placeholder="Filter matrices..."
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
            <th class="border border-gray-300 px-4 py-2 text-left w-8">
              <button type="button" class="flex items-center gap-1 font-medium hover:text-gray-200">
                Level
              </button>
            </th>
            <th class="border border-gray-300 px-4 py-2 text-left">
              <button type="button" class="flex items-center gap-1 font-medium hover:text-gray-200">
                Matrix Hierarchy
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                </svg>
              </button>
            </th>
            <th class="border border-gray-300 px-4 py-2 text-left">
              <button type="button" class="flex items-center gap-1 font-medium hover:text-gray-200">
                Matrix Name
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
          </tr>
        </thead>
        <tbody id="statisticsTableBody">
          @foreach($matrixStats as $matrix)
            <tr class="matrix-row hover:bg-gray-50 transition-colors" data-matrix="{{ strtolower($matrix['matrix_name']) }}">
              <td class="border border-gray-300 px-4 py-2 text-center">
                <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-medium text-white bg-gray-500 rounded-full">
                  {{ $matrix['hierarchy_level'] }}
                </span>
              </td>
              <td class="border border-gray-300 px-4 py-2">
                <div class="flex items-center">
                  <!-- Indentation based on hierarchy level -->
                  <div style="margin-left: {{ ($matrix['hierarchy_level'] - 1) * 1.5 }}rem;">
                    <div class="font-medium text-gray-900">{{ $matrix['hierarchy_path'] }}</div>
                    @if($matrix['hierarchy_level'] >= 2)
                      <div class="text-xs text-gray-500">
                        @if($matrix['title'])Title: {{ $matrix['title'] }}@endif
                        @if($matrix['subtitle']), Subtitle: {{ $matrix['subtitle'] }}@endif
                        @if($matrix['type']), Type: {{ $matrix['type'] }}@endif
                      </div>
                    @endif
                  </div>
                </div>
              </td>
              <td class="border border-gray-300 px-4 py-2">
                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm font-medium">
                  {{ $matrix['matrix_name'] }}
                </span>
              </td>
              <td class="border border-gray-300 px-4 py-2 text-right">
                <span class="font-medium text-blue-600">{{ number_format($matrix['record_count']) }}</span>
              </td>
              <td class="border border-gray-300 px-4 py-2 text-right">
                <span class="text-gray-600">
                  {{ $totalRecords > 0 ? number_format(($matrix['record_count'] / $totalRecords) * 100, 2) : 0 }}%
                </span>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <!-- Table Info -->
    <div class="mt-4 flex justify-between items-center text-sm text-gray-600">
      <div id="tableInfo">
        Showing {{ count($matrixStats) }} matrices
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
    const matrixRows = document.querySelectorAll('.matrix-row');
    const entriesSelect = document.getElementById('entriesSelect');
    const downloadCsvBtn = document.getElementById('downloadCsv');

    // Only add event listeners if elements exist
    if (searchInput && matrixRows.length > 0) {
      // Search functionality
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let visibleRows = 0;
        
        matrixRows.forEach(row => {
          const matrixName = row.dataset.matrix;
          if (matrixName.includes(searchTerm)) {
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
          
          matrixRows.forEach((row, index) => {
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
        window.location.href = '{{ route("empodat.statistics.download") }}?type=matrix';
      });
    }

    function updateTableInfo(visibleRows) {
      const tableInfo = document.getElementById('tableInfo');
      if (tableInfo) {
        tableInfo.textContent = `Showing ${visibleRows} matrices`;
      }
    }
  });
</script>
@endpush