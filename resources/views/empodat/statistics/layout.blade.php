<x-app-layout>
  <x-slot name="header">
    @include('empodat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <!-- Header -->
          <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">
              @yield('page-title', 'Empodat Statistics Overview')
            </h2>
            @hasSection('page-subtitle')
              <p class="text-gray-600">@yield('page-subtitle')</p>
            @endif
          </div>

          <!-- First Row: Available Statistics -->
          <div class="mb-6">
            <div class="bg-zinc-50 border border-zinc-300 rounded-lg p-4">
              <h3 class="text-lg font-semibold text-zinc-800 mb-4">Available Statistics</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                
                @php
                  // Get empodat database entity
                  $empodatEntity = \App\Models\DatabaseEntity::where('code', 'empodat')->first();
                  $availableStats = [];
                  
                  if ($empodatEntity) {
                    // Get all unique keys for this database entity
                    $availableStats = \App\Models\Statistic::where('database_entity_id', $empodatEntity->id)
                      ->distinct()
                      ->pluck('key')
                      ->toArray();
                  }
                @endphp

                @if(in_array('country_year', $availableStats))
                  <a href="{{ route('empodat.statistics.countryYear') }}" class="block p-3 bg-white border border-sky-700 rounded hover:bg-sky-50 transition-colors">
                    <div class="font-medium text-sky-800">Country Year Statistics</div>
                    <div class="text-xs text-sky-700">Data per country across years</div>
                  </a>
                @endif
                
                @if(in_array('matrix', $availableStats))
                  <a href="{{ route('empodat.statistics.matrix') }}" class="block p-3 bg-white border border-sky-700 rounded hover:bg-sky-50 transition-colors">
                    <div class="font-medium text-sky-800">Matrix Statistics</div>
                    <div class="text-xs text-sky-700">Data per environmental matrix</div>
                  </a>
                @endif
                
                @if(in_array('substance', $availableStats))
                  <a href="{{ route('empodat.statistics.substance') }}" class="block p-3 bg-white border border-sky-700 rounded hover:bg-sky-50 transition-colors">
                    <div class="font-medium text-sky-800">Substance Statistics</div>
                    <div class="text-xs text-sky-700">Data per chemical substance</div>
                  </a>
                @else
                  <div class="p-3 bg-gray-100 border border-gray-400 rounded text-gray-600">
                    <div class="font-medium">Substance Statistics</div>
                    <div class="text-xs">Coming soon...</div>
                  </div>
                @endif
                
                @if(in_array('quality', $availableStats))
                  <a href="{{ route('empodat.statistics.quality') }}" class="block p-3 bg-white border border-sky-700 rounded hover:bg-sky-50 transition-colors">
                    <div class="font-medium text-sky-800">QA/QC Statistics</div>
                    <div class="text-xs text-sky-700">Data per analytical rating </div>
                  </a>
                @else
                  <div class="p-3 bg-gray-100 border border-gray-400 rounded text-gray-600">
                    <div class="font-medium">QA/QC Statistics</div>
                    <div class="text-xs">Coming soon...</div>
                  </div>
                @endif
              </div>
            </div>
          </div>

          @auth
            @if(auth()->user()->hasRole('super_admin'))
              <!-- Second Row: Admin Tools -->
              <div class="mb-6">
                <div class="bg-amber-50 border border-amber-600 rounded-lg p-4">
                  <h3 class="text-lg font-semibold text-amber-800 mb-4">
                    <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"/>
                      <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6.5a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                    Generate Statistics (Admin Only)
                  </h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3">
                    <form action="{{ route('empodat.statistics.generateCountry') }}" method="POST">
                      @csrf
                      <button type="submit" 
                              class="w-full px-4 py-2 bg-zinc-700 text-white rounded hover:bg-zinc-800 transition-colors text-sm">
                        Generate Country Statistics
                      </button>
                    </form>
                    
                    <form action="{{ route('empodat.statistics.generateMatrix') }}" method="POST">
                      @csrf
                      <button type="submit" 
                              class="w-full px-4 py-2 bg-zinc-700 text-white rounded hover:bg-zinc-800 transition-colors text-sm">
                        Generate Matrix Statistics
                      </button>
                    </form>
                    
                    <form action="{{ route('empodat.statistics.generateSubstance') }}" method="POST">
                      @csrf
                      <button type="submit" 
                              class="w-full px-4 py-2 bg-zinc-700 text-white rounded hover:bg-zinc-800 transition-colors text-sm">
                        Generate Substance Statistics
                      </button>
                    </form>
                    
                    
                    <form action="{{ route('empodat.statistics.generateQuality') }}" method="POST">
                      @csrf
                      <button type="submit" 
                              class="w-full px-4 py-2 bg-zinc-700 text-white rounded hover:bg-zinc-800 transition-colors text-sm">
                        Generate QA/QC Statistics
                      </button>
                    </form>
                    
                  </div>
                </div>
              </div>
            @endif
          @endauth

          <!-- Third Row: Main Content -->
          <div class="w-full">
            @yield('main-content')
          </div>

        </div>
      </div>
    </div>
  </div>
  
</x-app-layout>