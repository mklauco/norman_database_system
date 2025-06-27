<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          {{-- Header Section --}}
          <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-3">
                <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                <h2 class="text-xl font-semibold text-gray-900">
                  Duplicate Records Management
                </h2>
              </div>
              <a href="{{ route('duplicates.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                ← Back to Duplicates
              </a>
            </div>
            
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
              <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <div>
                  <h3 class="font-medium text-amber-800">
                    Duplicates found for: {{ ucfirst(str_replace('_', ' ', $pivot)) }}
                  </h3>
                  <p class="text-amber-700 mt-1">
                    <span class="font-semibold">{{ $pivot_value }}</span> 
                    <span class="text-sm">({{ $substancesCount }} records)</span>
                  </p>
                </div>
              </div>
            </div>
          </div>

          {{-- Duplicate Resolution Form --}}
          <form action="{{route('duplicates.handleDuplicates')}}" method="POST" class="mb-8">
            @csrf
            
            <div class="mb-4">
              <h3 class="text-lg font-medium text-gray-900 mb-3">
                Select Actions for Duplicate Records
              </h3>
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                <p class="text-sm text-blue-800">
                  <strong>Instructions:</strong> Review the duplicate records below and choose which ones to keep or remove. 
                  You can also restore previously deleted records.
                </p>
              </div>
            </div>

            <div class="border border-gray-200 rounded-lg">
              <div class="w-full overflow-x-scroll">
                <div id="displaySubstancesDiv">
                  <div style="min-width: 800px;">
                    @include('susdat.display-substances', ['show' => ['substances' => false, 'sources' => false, 'duplicates' => true] ])
                  </div>
                </div>
              </div>
            </div>
            
            <div class="flex justify-between items-center mt-6 pt-4 border-t border-gray-200">
              <div class="text-sm text-gray-600">
                Make your selections above and click submit to apply changes.
              </div>
              <button type="submit" class="btn-submit flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Apply Changes</span>
              </button>
            </div>
          </form>

          {{-- External Data Sources Section --}}
          <div class="space-y-6">
            <div class="border-t border-gray-200 pt-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center space-x-2">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                </svg>
                <span>External Database Information</span>
              </h3>
              <p class="text-sm text-gray-600 mb-4">
                Additional information from external sources to help identify the correct record to keep.
              </p>
            </div>

            {{-- Comptox Database Section --}}
            <div class="bg-sky-50 border border-sky-200 rounded-lg overflow-hidden">
              <div class="bg-sky-100 px-4 py-3 border-b border-sky-200">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-sky-600 rounded-full"></div>
                  <span class="font-semibold text-sky-900">CompTox Dashboard</span>
                  <span class="text-xs text-sky-700 bg-sky-200 px-2 py-1 rounded">External Source</span>
                </div>
              </div>
              <div class="p-4">
                @livewire('susdat.duplicate-load-comptox', ['dtxsid' => $dtxsIds])
              </div>
            </div>

            {{-- PubChem Database Section --}}
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg overflow-hidden">
              <div class="bg-emerald-100 px-4 py-3 border-b border-emerald-200">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-emerald-600 rounded-full"></div>
                  <span class="font-semibold text-emerald-900">PubChem Database</span>
                  <span class="text-xs text-emerald-700 bg-emerald-200 px-2 py-1 rounded">External Source</span>
                </div>
              </div>
              <div class="p-4">
                @livewire('susdat.duplicate-load-pubchem', ['pubchemIds' => $pubchemIds])
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>