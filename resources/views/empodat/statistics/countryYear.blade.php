@extends('empodat.statistics.layout')

@section('page-title', 'Country Year Statistics')
@section('page-subtitle', 'Number of data per country across years')

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
  @elseif(empty($countryStats))
    <!-- Empty Data -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
      <div class="text-gray-600 text-lg mb-2">No country statistics available for the selected year range.</div>
      <div class="text-sm text-gray-500">Try adjusting the year range or generate new statistics.</div>
    </div>
  @else
    <!-- Year Range Selection -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
      <form id="yearRangeForm" method="GET" action="{{ route('empodat.statistics.countryYear') }}" class="flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
          <label for="minYear" class="text-sm font-medium text-gray-700">From Year:</label>
          <select 
            id="minYear" 
            name="min_year" 
            class="border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            @for($year = $minYear; $year <= $maxYear; $year++)
              <option value="{{ $year }}" {{ ($displayMinYear == $year) ? 'selected' : '' }}>
                {{ $year }}
              </option>
            @endfor
          </select>
        </div>
        
        <div class="flex items-center gap-2">
          <label for="maxYear" class="text-sm font-medium text-gray-700">To Year:</label>
          <select 
            id="maxYear" 
            name="max_year" 
            class="border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            @for($year = $minYear; $year <= $maxYear; $year++)
              <option value="{{ $year }}" {{ ($displayMaxYear == $year) ? 'selected' : '' }}>
                {{ $year }}
              </option>
            @endfor
          </select>
        </div>
        
        <button 
          type="submit" 
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors text-sm font-medium"
        >
          Update View
        </button>
        
        <button 
          type="button" 
          id="resetRange"
          class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors text-sm font-medium"
        >
          Reset to Default
        </button>
      </form>
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
            placeholder="Filter countries..."
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
<div class="overflow-x-auto overflow-y-visible border border-gray-200 rounded-lg">
  <table id="statisticsTable" class="w-full table-fixed border-collapse">
    <thead>
      <tr class="bg-gray-600 text-white">
        <th class="border border-gray-300 px-4 py-2 text-left sticky left-0 bg-gray-600 z-20 w-48 min-w-[12rem]">
          <button type="button" class="flex items-center gap-1 font-medium hover:text-gray-200">
            Country
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
            </svg>
          </button>
        </th>
        @php
          $displayYears = range($displayMinYear, $displayMaxYear);
        @endphp
        @foreach($displayYears as $year)
          <th class="year-column border border-gray-300 px-4 py-2 text-center w-24 min-w-[6rem]" data-year="{{ $year }}">
            <button type="button" class="flex items-center gap-1 font-medium hover:text-gray-200 mx-auto whitespace-nowrap">
              {{ $year }}
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
              </svg>
            </button>
          </th>
        @endforeach
      </tr>
    </thead>
    <tbody id="statisticsTableBody">
      @foreach($countryStats as $country => $yearData)
        <tr class="country-row hover:bg-gray-50 transition-colors" data-country="{{ strtolower($country) }}">
          <td class="border border-gray-300 px-4 py-2 font-medium sticky left-0 bg-white z-10 w-48 min-w-[12rem]">
            <div class="truncate" title="{{ $country }}">{{ $country }}</div>
          </td>
          @foreach($displayYears as $year)
            <td class="year-column border border-gray-300 px-4 py-2 text-right w-24 min-w-[6rem]" data-year="{{ $year }}">
              @if(isset($yearData[$year]))
                <span class="font-medium text-blue-600">{{ number_format($yearData[$year]) }}</span>
              @else
                <span class="text-gray-400">-</span>
              @endif
            </td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

    <!-- Table Info -->
    <div class="mt-4 flex justify-between items-center text-sm text-gray-600">
      <div id="tableInfo">
        Showing {{ count($countryStats) }} countries with data from {{ $displayMinYear }} to {{ $displayMaxYear }}
      </div>
      <div>
        Total records in database: <span class="font-medium">{{ number_format($totalRecords) }}</span>
      </div>
    </div>
  @endif
@endsection

@section('side-tools')
  <!-- Year Range Quick Actions -->
  <div class="space-y-2">
    <button 
      type="button" 
      onclick="setYearRange(2020, 2024)"
      class="w-full px-3 py-2 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 transition-colors text-sm"
    >
      Last 5 Years (2020-2024)
    </button>
    
    <button 
      type="button" 
      onclick="setYearRange(2015, 2024)"
      class="w-full px-3 py-2 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 transition-colors text-sm"
    >
      Last 10 Years (2015-2024)
    </button>
    
    <button 
      type="button" 
      onclick="setYearRange(2000, 2024)"
      class="w-full px-3 py-2 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 transition-colors text-sm"
    >
      Since 2000
    </button>
  </div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const countryRows = document.querySelectorAll('.country-row');
    const entriesSelect = document.getElementById('entriesSelect');
    const resetRangeBtn = document.getElementById('resetRange');
    const downloadCsvBtn = document.getElementById('downloadCsv');
    const minYearSelect = document.getElementById('minYear');
    const maxYearSelect = document.getElementById('maxYear');

    // Only add event listeners if elements exist
    if (searchInput && countryRows.length > 0) {
      // Search functionality
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let visibleRows = 0;
        
        countryRows.forEach(row => {
          const countryName = row.dataset.country;
          if (countryName.includes(searchTerm)) {
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
          
          countryRows.forEach((row, index) => {
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

    // Reset range functionality
    if (resetRangeBtn && minYearSelect && maxYearSelect) {
      resetRangeBtn.addEventListener('click', function() {
        const currentYear = new Date().getFullYear();
        minYearSelect.value = currentYear - 10;
        maxYearSelect.value = currentYear;
      });

      // Year range validation
      minYearSelect.addEventListener('change', function() {
        const minYear = parseInt(this.value);
        const maxYear = parseInt(maxYearSelect.value);
        
        if (minYear > maxYear) {
          maxYearSelect.value = minYear;
        }
      });

      maxYearSelect.addEventListener('change', function() {
        const maxYear = parseInt(this.value);
        const minYear = parseInt(minYearSelect.value);
        
        if (maxYear < minYear) {
          minYearSelect.value = maxYear;
        }
      });
    }

    // CSV Download functionality
    if (downloadCsvBtn) {
      downloadCsvBtn.addEventListener('click', function() {
        downloadTableAsCSV();
      });
    }

    function updateTableInfo(visibleRows) {
      const tableInfo = document.getElementById('tableInfo');
      if (tableInfo) {
        const displayMinYear = {{ $displayMinYear ?? 'null' }};
        const displayMaxYear = {{ $displayMaxYear ?? 'null' }};
        tableInfo.textContent = `Showing ${visibleRows} countries with data from ${displayMinYear} to ${displayMaxYear}`;
      }
    }

    function downloadTableAsCSV() {
      const table = document.getElementById('statisticsTable');
      if (!table) return;
      
      const rows = table.querySelectorAll('tr');
      const csvContent = [];
      
      // Process each row
      rows.forEach((row, rowIndex) => {
        const cells = row.querySelectorAll('th, td');
        const rowData = [];
        
        // Skip hidden rows (except header)
        if (rowIndex > 0 && row.style.display === 'none') {
          return;
        }
        
        cells.forEach(cell => {
          let cellValue = cell.textContent.trim();
          // Clean up the cell value (remove extra whitespace and normalize)
          cellValue = cellValue.replace(/\s+/g, ' ');
          // Escape quotes and wrap in quotes if necessary
          if (cellValue.includes(',') || cellValue.includes('"') || cellValue.includes('\n')) {
            cellValue = '"' + cellValue.replace(/"/g, '""') + '"';
          }
          rowData.push(cellValue);
        });
        
        if (rowData.length > 0) {
          csvContent.push(rowData.join(','));
        }
      });
      
      // Create and download the CSV file
      const csvString = csvContent.join('\n');
      const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      
      if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `empodat_country_year_statistics_${new Date().toISOString().slice(0, 10)}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }
    }
  });

  // Quick year range setter function
  function setYearRange(minYear, maxYear) {
    const minYearSelect = document.getElementById('minYear');
    const maxYearSelect = document.getElementById('maxYear');
    
    if (minYearSelect && maxYearSelect) {
      minYearSelect.value = minYear;
      maxYearSelect.value = maxYear;
    }
  }
</script>
@endpush