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
              @yield('page-title', 'EMPODAT Statistics Overview')
            </h2>
            @hasSection('page-subtitle')
              <p class="text-gray-600">@yield('page-subtitle')</p>
            @endif
          </div>

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

            // Determine current scope and admin access
            $currentScope = request()->input('scope', 'public');
            $canViewAll = auth()->check() && (
              auth()->user()->hasRole('super_admin') ||
              auth()->user()->hasRole('admin') ||
              auth()->user()->hasRole('empodat')
            );

            if ($currentScope === 'all' && !$canViewAll) {
              $currentScope = 'public';
            }
          @endphp

          <!-- Scope Selector for Admins -->
          @if($canViewAll)
            <div class="mb-6">
              <div class="bg-slate-100 border border-slate-300 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div>
                    <h3 class="text-sm font-semibold text-slate-700">Statistics View Mode</h3>
                    <p class="text-xs text-slate-500 mt-1">
                      @if($currentScope === 'public')
                        Showing public statistics (unprotected data only)
                      @else
                        Showing admin statistics (all data except deleted)
                      @endif
                    </p>
                  </div>
                  <div class="flex gap-2">
                    <a href="{{ request()->fullUrlWithQuery(['scope' => 'public']) }}"
                       class="px-4 py-2 text-sm rounded-sm transition-colors {{ $currentScope === 'public' ? 'bg-sky-600 text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                      Public Data
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['scope' => 'all']) }}"
                       class="px-4 py-2 text-sm rounded-sm transition-colors {{ $currentScope === 'all' ? 'bg-amber-600 text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                      All Data (Admin)
                    </a>
                  </div>
                </div>
              </div>
            </div>
          @endif

          <!-- First Row: Available Statistics -->
          <div class="mb-6">
            <div class="bg-zinc-50 border border-zinc-300 rounded-lg p-4">
              <h3 class="text-lg font-semibold text-zinc-800 mb-4">
                Available Statistics
                @if($currentScope === 'all')
                  <span class="ml-2 text-xs font-normal bg-amber-100 text-amber-700 px-2 py-1 rounded-sm">Admin View</span>
                @else
                  <span class="ml-2 text-xs font-normal bg-sky-100 text-sky-700 px-2 py-1 rounded-sm">Public View</span>
                @endif
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">

                @php
                  // Check for public stats
                  $hasPublicCountryYear = in_array('country_year', $availableStats);
                  $hasPublicMatrix = in_array('matrix', $availableStats);
                  $hasPublicSubstance = in_array('substance', $availableStats);
                  $hasPublicQuality = in_array('quality', $availableStats);

                  // Check for admin stats
                  $hasAdminCountryYear = in_array('country_year_all', $availableStats);
                  $hasAdminMatrix = in_array('matrix_all', $availableStats);
                  $hasAdminSubstance = in_array('substance_all', $availableStats);
                  $hasAdminQuality = in_array('quality_all', $availableStats);

                  // Determine which stats to show based on current scope
                  $showCountryYear = $currentScope === 'all' ? $hasAdminCountryYear : $hasPublicCountryYear;
                  $showMatrix = $currentScope === 'all' ? $hasAdminMatrix : $hasPublicMatrix;
                  $showSubstance = $currentScope === 'all' ? $hasAdminSubstance : $hasPublicSubstance;
                  $showQuality = $currentScope === 'all' ? $hasAdminQuality : $hasPublicQuality;
                @endphp

                @if($showCountryYear)
                  <a href="{{ route('empodat.statistics.countryYear', ['scope' => $currentScope]) }}" class="block p-3 bg-white border border-sky-700 rounded-sm hover:bg-sky-50 transition-colors">
                    <div class="font-medium text-sky-800">Country Year Statistics</div>
                    <div class="text-xs text-sky-700">Data per country across years</div>
                  </a>
                @else
                  <div class="p-3 bg-gray-100 border border-gray-400 rounded-sm text-gray-600">
                    <div class="font-medium">Country Year Statistics</div>
                    <div class="text-xs">Not yet generated for this view</div>
                  </div>
                @endif

                @if($showMatrix)
                  <a href="{{ route('empodat.statistics.matrix', ['scope' => $currentScope]) }}" class="block p-3 bg-white border border-sky-700 rounded-sm hover:bg-sky-50 transition-colors">
                    <div class="font-medium text-sky-800">Matrix Statistics</div>
                    <div class="text-xs text-sky-700">Data per environmental matrix</div>
                  </a>
                @else
                  <div class="p-3 bg-gray-100 border border-gray-400 rounded-sm text-gray-600">
                    <div class="font-medium">Matrix Statistics</div>
                    <div class="text-xs">Not yet generated for this view</div>
                  </div>
                @endif

                @if($showSubstance)
                  <a href="{{ route('empodat.statistics.substance', ['scope' => $currentScope]) }}" class="block p-3 bg-white border border-sky-700 rounded-sm hover:bg-sky-50 transition-colors">
                    <div class="font-medium text-sky-800">Substance Statistics</div>
                    <div class="text-xs text-sky-700">Data per chemical substance</div>
                  </a>
                @else
                  <div class="p-3 bg-gray-100 border border-gray-400 rounded-sm text-gray-600">
                    <div class="font-medium">Substance Statistics</div>
                    <div class="text-xs">Not yet generated for this view</div>
                  </div>
                @endif

                @if($showQuality)
                  <a href="{{ route('empodat.statistics.quality', ['scope' => $currentScope]) }}" class="block p-3 bg-white border border-sky-700 rounded-sm hover:bg-sky-50 transition-colors">
                    <div class="font-medium text-sky-800">QA/QC Statistics</div>
                    <div class="text-xs text-sky-700">Data per analytical rating </div>
                  </a>
                @else
                  <div class="p-3 bg-gray-100 border border-gray-400 rounded-sm text-gray-600">
                    <div class="font-medium">QA/QC Statistics</div>
                    <div class="text-xs">Not yet generated for this view</div>
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

                  <!-- Public Statistics Generation -->
                  <div class="mb-4">
                    <h4 class="text-sm font-medium text-amber-700 mb-2">Public Statistics (unprotected data only)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                      <form action="{{ route('empodat.statistics.generateCountry') }}" method="POST">
                        @csrf
                        <input type="hidden" name="scope" value="public">
                        <button type="submit"
                                class="w-full px-4 py-2 bg-sky-600 text-white rounded-sm hover:bg-sky-700 transition-colors text-sm">
                          Country Statistics
                        </button>
                      </form>

                      <form action="{{ route('empodat.statistics.generateMatrix') }}" method="POST">
                        @csrf
                        <input type="hidden" name="scope" value="public">
                        <button type="submit"
                                class="w-full px-4 py-2 bg-sky-600 text-white rounded-sm hover:bg-sky-700 transition-colors text-sm">
                          Matrix Statistics
                        </button>
                      </form>

                      <form action="{{ route('empodat.statistics.generateSubstance') }}" method="POST">
                        @csrf
                        <input type="hidden" name="scope" value="public">
                        <button type="submit"
                                class="w-full px-4 py-2 bg-sky-600 text-white rounded-sm hover:bg-sky-700 transition-colors text-sm">
                          Substance Statistics
                        </button>
                      </form>

                      <form action="{{ route('empodat.statistics.generateQuality') }}" method="POST">
                        @csrf
                        <input type="hidden" name="scope" value="public">
                        <button type="submit"
                                class="w-full px-4 py-2 bg-sky-600 text-white rounded-sm hover:bg-sky-700 transition-colors text-sm">
                          QA/QC Statistics
                        </button>
                      </form>
                    </div>
                  </div>

                  <!-- Admin Statistics Generation -->
                  <div>
                    <h4 class="text-sm font-medium text-amber-700 mb-2">Admin Statistics (all data except deleted)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                      <form action="{{ route('empodat.statistics.generateCountry') }}" method="POST">
                        @csrf
                        <input type="hidden" name="scope" value="all">
                        <button type="submit"
                                class="w-full px-4 py-2 bg-amber-600 text-white rounded-sm hover:bg-amber-700 transition-colors text-sm">
                          Country Statistics
                        </button>
                      </form>

                      <form action="{{ route('empodat.statistics.generateMatrix') }}" method="POST">
                        @csrf
                        <input type="hidden" name="scope" value="all">
                        <button type="submit"
                                class="w-full px-4 py-2 bg-amber-600 text-white rounded-sm hover:bg-amber-700 transition-colors text-sm">
                          Matrix Statistics
                        </button>
                      </form>

                      <form action="{{ route('empodat.statistics.generateSubstance') }}" method="POST">
                        @csrf
                        <input type="hidden" name="scope" value="all">
                        <button type="submit"
                                class="w-full px-4 py-2 bg-amber-600 text-white rounded-sm hover:bg-amber-700 transition-colors text-sm">
                          Substance Statistics
                        </button>
                      </form>

                      <form action="{{ route('empodat.statistics.generateQuality') }}" method="POST">
                        @csrf
                        <input type="hidden" name="scope" value="all">
                        <button type="submit"
                                class="w-full px-4 py-2 bg-amber-600 text-white rounded-sm hover:bg-amber-700 transition-colors text-sm">
                          QA/QC Statistics
                        </button>
                      </form>
                    </div>
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