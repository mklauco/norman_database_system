<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <!-- Summary Section -->
          <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Summary</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div class="flex justify-between items-center py-3 border-b border-gray-200">
                <span class="text-lg text-gray-700">Total no. of data</span>
                <span class="text-xl font-bold text-gray-900">{{ number_format($totalRecords) }}</span>
              </div>
              
              <div class="flex justify-between items-center py-3 border-b border-gray-200">
                <span class="text-lg text-gray-700">No. of substances</span>
                <span class="text-xl font-bold text-gray-900">{{ number_format($substanceCount) }}</span>
              </div>
            </div>
            
            <div class="mt-4">
              <a href="{{ route('substances.filter') }}" class="text-blue-600 hover:text-blue-800 font-medium underline">
                Full list of substances
              </a>
            </div>
          </div>

          <!-- Number of Data per Ecosystem/Matrix Section -->
          <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Number of Data per Ecosystem/Matrix</h2>
            
            <div class="space-y-3">
              <a href="{{ route('empodat.statistics.matrix') }}" 
                 class="block text-green-600 hover:text-green-800 font-medium text-lg underline">
                No. of data per matrix
              </a>
              
              <a href="{{ route('empodat.statistics.submatrix') }}" 
                 class="block text-green-600 hover:text-green-800 font-medium text-lg underline">
                No. of data per matrix/sub-matrix
              </a>
            </div>
          </div>

          <!-- Number of Data per QA/QC Information Category Section -->
          <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Number of Data per QA/QC Information Category</h2>
            
            <div class="space-y-3">
              <a href="{{ route('empodat.statistics.qaqc') }}" 
                 class="block text-green-600 hover:text-green-800 font-medium text-lg underline">
                No. of data per category
              </a>
            </div>
          </div>

          <!-- Number of Data per Country/Method Section -->
          <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Number of Data per Country/Method</h2>
            
            <div class="space-y-3">
              <a href="{{ route('empodat.statistics.method') }}" 
                 class="block text-green-600 hover:text-green-800 font-medium text-lg underline">
                No. of data per data collection method
              </a>
              
              <a href="{{ route('empodat.statistics.country') }}" 
                 class="block text-green-600 hover:text-green-800 font-medium text-lg underline">
                No. of data per country
              </a>
              
              <a href="{{ route('empodat.statistics.countryYear') }}" 
                 class="block text-green-600 hover:text-green-800 font-medium text-lg underline">
                No. of data per country (per years)
              </a>
            </div>
          </div>

          <!-- Additional Statistics Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
            
            <!-- Countries Card -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg border border-blue-200">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-blue-600">Countries</p>
                  <p class="text-2xl font-bold text-blue-900">{{ $countryCount }}</p>
                </div>
                <div class="p-3 bg-blue-200 rounded-full">
                  <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"/>
                  </svg>
                </div>
              </div>
            </div>

            <!-- Matrices Card -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg border border-green-200">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-green-600">Matrices</p>
                  <p class="text-2xl font-bold text-green-900">{{ $matrixCount }}</p>
                </div>
                <div class="p-3 bg-green-200 rounded-full">
                  <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                  </svg>
                </div>
              </div>
            </div>

            <!-- Year Range Card -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-lg border border-purple-200">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-purple-600">Year Range</p>
                  <p class="text-2xl font-bold text-purple-900">{{ $minYear }} - {{ $maxYear }}</p>
                </div>
                <div class="p-3 bg-purple-200 rounded-full">
                  <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                  </svg>
                </div>
              </div>
            </div>

          </div>

        </div>
      </div>
    </div>
  </div>
  
</x-app-layout>