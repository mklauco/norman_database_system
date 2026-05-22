<x-app-layout>
  <x-slot name="header">
    @include('empodat_suspect.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <!-- Header -->
          <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">
              @yield('page-title', 'EMPODAT Suspect Statistics')
            </h2>
            @hasSection('page-subtitle')
              <p class="text-gray-600">@yield('page-subtitle')</p>
            @endif
          </div>

          @auth
            @if(auth()->user()->hasRole('super_admin'))
              <!-- Admin Tools -->
              <div class="mb-6">
                <div class="bg-amber-50 border border-amber-600 rounded-lg p-4">
                  <h3 class="text-lg font-semibold text-amber-800 mb-4">
                    <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"/>
                      <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6.5a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                    Generate Statistics (Admin Only)
                  </h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <form action="{{ route('empodat_suspect.statistics.generate') }}" method="POST">
                      @csrf
                      <button type="submit"
                              class="w-full px-4 py-2 bg-zinc-700 text-white rounded hover:bg-zinc-800 transition-colors text-sm">
                        Generate All Statistics
                      </button>
                    </form>
                  </div>
                  <div class="mt-3 text-sm text-amber-700">
                    <strong>Note:</strong> Click queues a background job and returns immediately. Generation against the full dataset (~40M rows) typically takes <strong>up to 5 minutes</strong> — refresh this page to see updated "Updated:" timestamps on each card. Concurrent runs are deduplicated automatically.
                  </div>
                </div>
              </div>
            @endif
          @endauth

          <!-- Flash Messages -->
          @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-400 text-green-700 px-4 py-3 rounded">
              {{ session('success') }}
            </div>
          @endif

          @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded">
              {{ session('error') }}
            </div>
          @endif

          <!-- Main Content -->
          <div class="w-full">
            @yield('main-content')
          </div>

        </div>
      </div>
    </div>
  </div>

</x-app-layout>
