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
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
              <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-lime-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h2 class="text-xl font-semibold text-gray-800">
                  Merge History
                </h2>
              </div>
              <a href="{{ route('duplicates.index') }}" class="link-lime-text text-sm">
                ← Back to Duplicates
              </a>
            </div>
            
            <div class="bg-lime-50 border border-lime-200 text-lime-900 rounded-sm p-4">
              <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-lime-700 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                  <h3 class="text-lg font-semibold text-lime-900">
                    Canonical Reference System Merge History
                  </h3>
                  <p class="mt-1">
                    View all substance merge operations and audit trail. Merged substances are preserved but hidden from normal searches. 
                    Use the <span class="font-medium">Restore</span> button to reactivate merged substances if needed.
                  </p>
                </div>
              </div>
            </div>
          </div>

          {{-- Filters Section --}}
          <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Merge History</h3>
            <form method="GET" action="{{ route('duplicates.mergeHistory') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" 
                       id="date_from" 
                       name="date_from" 
                       value="{{ $filters['date_from'] ?? '' }}"
                       class="form-text rounded-md">
              </div>
              
              <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" 
                       id="date_to" 
                       name="date_to" 
                       value="{{ $filters['date_to'] ?? '' }}"
                       class="form-text rounded-md">
              </div>
              
              <div>
                <label for="merged_by" class="block text-sm font-medium text-gray-700 mb-1">Merged By</label>
                <select id="merged_by" 
                        name="merged_by" 
                        class="form-select rounded-md">
                  <option value="">All Users</option>
                  @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ ($filters['merged_by'] ?? '') == $user->id ? 'selected' : '' }}>
                      {{ $user->formatted_name }}
                    </option>
                  @endforeach
                </select>
              </div>
              
              <div class="flex items-end">
                <button type="submit" class="btn-submit w-full">
                  Apply Filters
                </button>
              </div>
            </form>
            
            @if(!empty(array_filter($filters)))
              <div class="mt-4 pt-4 border-t border-gray-200">
                <a href="{{ route('duplicates.mergeHistory') }}" class="link-lime-text text-sm">
                  Clear All Filters
                </a>
              </div>
            @endif
          </div>

          {{-- Merge History Table --}}
          <div class="overflow-x-auto">
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="py-1 px-2 text-left">Merged Substance</th>
                  <th class="py-1 px-2 text-left">Canonical Substance</th>
                  <th class="py-1 px-2 text-left">Merge Reason</th>
                  <th class="py-1 px-2 text-left">Merged By</th>
                  <th class="py-1 px-2 text-left">Merged At</th>
                  <th class="py-1 px-2 text-left">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($mergedSubstances as $mergedSubstance)
                  <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                    <td class="py-1 px-2 align-top">
                      <div class="font-medium font-mono">{{ $mergedSubstance->prefixed_code }}</div>
                      @if($mergedSubstance->name)
                        <div class="break-words">{{ $mergedSubstance->name }}</div>
                      @endif
                      <div class="text-gray-600">ID: <span class="font-mono">{{ $mergedSubstance->id }}</span></div>
                    </td>

                    <td class="py-1 px-2 align-top">
                      @if($mergedSubstance->canonical)
                        <div class="font-medium font-mono">{{ $mergedSubstance->canonical->prefixed_code }}</div>
                        @if($mergedSubstance->canonical->name)
                          <div class="break-words">{{ $mergedSubstance->canonical->name }}</div>
                        @endif
                        <div class="text-gray-600">ID: <span class="font-mono">{{ $mergedSubstance->canonical->id }}</span></div>
                      @else
                        <span class="text-gray-500">{{ \App\Services\Susdat\SusdatDisplay::EMPTY }}</span>
                      @endif
                    </td>

                    <td class="py-1 px-2 align-top break-words">
                      {{ $mergedSubstance->merge_reason }}
                    </td>

                    <td class="py-1 px-2 align-top">
                      {{ $mergedSubstance->mergedBy->formatted_name ?? 'Unknown' }}
                    </td>

                    <td class="py-1 px-2 align-top whitespace-nowrap">
                      {{ \App\Services\Susdat\SusdatDisplay::date($mergedSubstance->merged_at) }}
                    </td>

                    <td class="py-1 px-2 align-top">
                      <form method="POST" action="{{ route('duplicates.restore', $mergedSubstance->id) }}" class="inline">
                        @csrf
                        <button type="button"
                                onclick="confirmRestore('{{ $mergedSubstance->prefixed_code }}', '{{ $mergedSubstance->name ?? 'N/A' }}', this.closest('form'))"
                                class="btn-submit-danger">
                          Restore
                        </button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="py-2 px-2 text-center text-gray-500">
                      No merge history found.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          {{-- Pagination --}}
          @if($mergedSubstances->hasPages())
            <div class="mt-6">
              {{ $mergedSubstances->links('pagination::tailwind') }}
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>

  {{-- Restore Confirmation Modal --}}
  <div id="restoreModal" role="dialog" aria-modal="true" aria-labelledby="restoreModalTitle" class="fixed inset-0 bg-gray-600/50 overflow-y-auto h-full w-full z-50 hidden">
    
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-lg bg-white">
      
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L5.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <div>
            <h3 id="restoreModalTitle" class="text-lg font-semibold text-gray-800">Confirm Substance Restoration</h3>
            <p class="text-sm text-gray-500">This action will reactivate the merged substance</p>
          </div>
        </div>
        <button type="button" onclick="cancelRestore()" aria-label="Close" class="text-gray-500 hover:text-gray-700 rounded-sm focus:outline-hidden focus-visible:ring-2 focus-visible:ring-lime-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      
      <!-- Body -->
      <div class="mb-6">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
          <p class="text-yellow-800">
            You are about to restore substance <strong id="modalSubstanceCode"></strong> 
            (<span id="modalSubstanceName"></span>). This action cannot be undone.
          </p>
        </div>
        
        <div class="bg-lime-50 border border-lime-200 text-lime-900 rounded-sm p-3">
          <h5 class="font-medium text-sm mb-2">This restoration will:</h5>
          <ul class="text-sm space-y-1">
            <li>• Make the substance active again</li>
            <li>• Remove it from the merge history</li>
            <li>• Allow it to appear in searches again</li>
            <li>• Reset its canonical reference status</li>
          </ul>
        </div>
      </div>
      
      <!-- Footer -->
      <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
        <button type="button" onclick="cancelRestore()" class="btn-clear">
          Cancel
        </button>
        <button type="button" onclick="executeRestore()" class="btn-submit-danger">
          Restore Substance
        </button>
      </div>
    </div>
  </div>

  <script>
    let currentRestoreForm = null;
    
    // Restore confirmation functions
    function confirmRestore(code, name, formElement) {
      currentRestoreForm = formElement;
      document.getElementById('modalSubstanceCode').textContent = code;
      document.getElementById('modalSubstanceName').textContent = name;
      document.getElementById('restoreModal').classList.remove('hidden');
    }
    
    function executeRestore() {
      if (currentRestoreForm) {
        currentRestoreForm.submit();
      }
      cancelRestore();
    }
    
    function cancelRestore() {
      document.getElementById('restoreModal').classList.add('hidden');
      currentRestoreForm = null;
    }
    
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && !document.getElementById('restoreModal').classList.contains('hidden')) {
        cancelRestore();
      }
    });

    // Close restore modal when clicking outside
    document.getElementById('restoreModal').addEventListener('click', function(e) {
      if (e.target === this) {
        cancelRestore();
      }
    });
  </script>
</x-app-layout>
