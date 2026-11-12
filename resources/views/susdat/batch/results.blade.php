<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
        
        <div class="p-6 text-gray-900">
          
          <!-- Page Header -->
          <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-2">Batch Conversion Results</h2>
            <p class="text-gray-600">
              Input type: <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $inputType)) }}</span>
            </p>
            <p class="text-gray-600">
              Match mode: <span class="font-medium">{{ $exactMatch ? 'Exact match only' : 'Partial matching allowed' }}</span>
            </p>
            <p class="text-gray-600">
              Total identifiers processed: <span class="font-medium">{{ \App\Services\Susdat\SusdatDisplay::count(count($results)) }}</span>
            </p>
          </div>
          
          <!-- Back Button -->
          <div class="mb-6">
            <div class="flex flex-wrap gap-3">
              <a href="{{ route('susdat.batch.index') }}" 
                 class="btn-clear inline-flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                New Batch Conversion
              </a>
              
              <a href="{{ route('susdat.batch.update') }}" 
                 class="btn-clear inline-flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Update Batch Conversion
              </a>
            </div>
          </div>
          
          <!-- Results Summary -->
          @php
            $foundCount = collect($results)->where('found', true)->count();
            $notFoundCount = collect($results)->where('found', false)->count();
          @endphp
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
              <div class="flex items-center">
                <div class="shrink-0">
                  <svg class="h-5 w-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-gray-700">Found</p>
                  <p class="text-2xl font-bold text-gray-900">{{ \App\Services\Susdat\SusdatDisplay::count($foundCount) }}</p>
                </div>
              </div>
            </div>
            
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
              <div class="flex items-center">
                <div class="shrink-0">
                  <svg class="h-5 w-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-gray-700">Not Found</p>
                  <p class="text-2xl font-bold text-gray-900">{{ \App\Services\Susdat\SusdatDisplay::count($notFoundCount) }}</p>
                </div>
              </div>
            </div>
            
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
              <div class="flex items-center">
                <div class="shrink-0">
                  <svg class="h-5 w-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-gray-700">Success Rate</p>
                  <p class="text-2xl font-bold text-gray-900">{{ count($results) > 0 ? round(($foundCount / count($results)) * 100, 1) : 0 }}%</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Download Buttons -->
          <div class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <h3 class="text-lg font-semibold text-gray-800">Export Results</h3>
              <div class="flex flex-wrap gap-3">
                <a href="{{ route('susdat.batch.download.csv') }}"
                   class="btn-clear inline-flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  Download CSV
                </a>
                <a href="{{ route('susdat.batch.download.xlsx') }}"
                   class="btn-clear inline-flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  Download XLSX
                </a>
              </div>
            </div>
            <p class="mt-2 text-sm text-gray-600">
              Export your batch conversion results as CSV or XLSX for further analysis or reporting.
            </p>
          </div>
          
          <!-- Results Table -->
          <div class="overflow-x-auto">
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="py-1 px-2 text-left">Input Identifier</th>
                  <th class="py-1 px-2 text-left">SUSDAT ID</th>
                  <th class="py-1 px-2 text-left">Substance Name</th>
                  <th class="py-1 px-2 text-left">CAS No.</th>
                  <th class="py-1 px-2 text-left">StdInChIKey</th>
                  <th class="py-1 px-2 text-left">Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($results as $result)
                <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="py-1 px-2 font-medium font-mono break-all">
                    {{ $result['input'] }}
                  </td>
                  <td class="py-1 px-2 font-mono">
                    @if($result['found'])
                      <a href="{{ route('substances.show', $result['substance_id']) }}" class="link-lime-text">NS{{ $result['susdat_id'] }}</a>
                    @else
                      <span class="text-gray-500">{{ \App\Services\Susdat\SusdatDisplay::EMPTY }}</span>
                    @endif
                  </td>
                  <td class="py-1 px-2 break-words">
                    @if(\App\Services\Susdat\SusdatDisplay::isEmpty($result['substance_name'] ?? null))
                      <span class="text-gray-500">{{ \App\Services\Susdat\SusdatDisplay::EMPTY }}</span>
                    @else
                      {{ $result['substance_name'] }}
                    @endif
                  </td>
                  <td class="py-1 px-2 font-mono">
                    @if(\App\Services\Susdat\SusdatDisplay::isEmpty($result['cas_no'] ?? null))
                      <span class="text-gray-500">{{ \App\Services\Susdat\SusdatDisplay::EMPTY }}</span>
                    @else
                      {{ $result['cas_no'] }}
                    @endif
                  </td>
                  <td class="py-1 px-2 font-mono break-all">
                    @if(\App\Services\Susdat\SusdatDisplay::isEmpty($result['std_inchikey'] ?? null))
                      <span class="text-gray-500">{{ \App\Services\Susdat\SusdatDisplay::EMPTY }}</span>
                    @else
                      {{ $result['std_inchikey'] }}
                    @endif
                  </td>
                  <td class="py-1 px-2">
                    @if($result['found'])
                      <span class="inline-block rounded-sm px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800">Found</span>
                    @else
                      <span class="inline-block rounded-sm px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800">Not Found</span>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
