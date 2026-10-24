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
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h2 class="text-xl font-semibold text-gray-900">
                  Merge History
                </h2>
              </div>
              <a href="{{ route('duplicates.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                ← Back to Duplicates
              </a>
            </div>
            
            {{-- Flash Messages --}}
            @if(session('success'))
              <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center space-x-3">
                  <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  <div class="text-green-800">{{ session('success') }}</div>
                </div>
              </div>
            @endif
            
            @if(session('error'))
              <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center space-x-3">
                  <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                  <div class="text-red-800">{{ session('error') }}</div>
                </div>
              </div>
            @endif
            
            @if(session('info'))
              <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center space-x-3">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  <div class="text-blue-800">{{ session('info') }}</div>
                </div>
              </div>
            @endif
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                  <h3 class="font-medium text-blue-800">
                    Canonical Reference System Merge History
                  </h3>
                  <p class="text-blue-700 mt-1">
                    View all substance merge operations and audit trail. Merged substances are preserved but hidden from normal searches. 
                    Use the <span class="font-medium">Restore</span> button to reactivate merged substances if needed.
                  </p>
                </div>
              </div>
            </div>
          </div>

          {{-- Filters Section --}}
          <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Merge History</h3>
            <form method="GET" action="{{ route('duplicates.mergeHistory') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" 
                       id="date_from" 
                       name="date_from" 
                       value="{{ $filters['date_from'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              </div>
              
              <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" 
                       id="date_to" 
                       name="date_to" 
                       value="{{ $filters['date_to'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              </div>
              
              <div>
                <label for="merged_by" class="block text-sm font-medium text-gray-700 mb-1">Merged By</label>
                <select id="merged_by" 
                        name="merged_by" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                <a href="{{ route('duplicates.mergeHistory') }}" class="text-sm text-blue-600 hover:text-blue-800">
                  Clear All Filters
                </a>
              </div>
            @endif
          </div>

          {{-- Merge History Table --}}
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Merged Substance
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Canonical Substance
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Merge Reason
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Merged By
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Merged At
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  @forelse($mergedSubstances as $mergedSubstance)
                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                          {{ $mergedSubstance->prefixed_code }}
                        </div>
                        @if($mergedSubstance->name)
                          <div class="text-sm text-gray-500">
                            {{ $mergedSubstance->name }}
                          </div>
                        @endif
                        <div class="text-xs text-gray-400">
                          ID: {{ $mergedSubstance->id }}
                        </div>
                      </td>
                      
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-green-900">
                          {{ $mergedSubstance->canonical->prefixed_code ?? 'N/A' }}
                        </div>
                        @if($mergedSubstance->canonical && $mergedSubstance->canonical->name)
                          <div class="text-sm text-green-700">
                            {{ $mergedSubstance->canonical->name }}
                          </div>
                        @endif
                        @if($mergedSubstance->canonical)
                          <div class="text-xs text-green-600">
                            ID: {{ $mergedSubstance->canonical->id }}
                          </div>
                        @endif
                      </td>
                      
                      <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $mergedSubstance->merge_reason }}">
                          {{ $mergedSubstance->merge_reason }}
                        </div>
                      </td>
                      
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                          {{ $mergedSubstance->mergedBy->formatted_name ?? 'Unknown' }}
                        </div>
                      </td>
                      
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                          {{ $mergedSubstance->merged_at ? $mergedSubstance->merged_at->format('M j, Y') : 'N/A' }}
                        </div>
                        <div class="text-xs text-gray-500">
                          {{ $mergedSubstance->merged_at ? $mergedSubstance->merged_at->format('g:i A') : '' }}
                        </div>
                      </td>
                      
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">

                          <form method="POST" action="{{ route('duplicates.restore', $mergedSubstance->id) }}" class="inline">
                            @csrf
                            <button type="button" 
                                    onclick="confirmRestore('{{ $mergedSubstance->prefixed_code }}', '{{ $mergedSubstance->name ?? 'N/A' }}', this.closest('form'))"
                                    class="btn-submit-danger">
                              Restore
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        No merge history found.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

          {{-- Pagination --}}
          @if($mergedSubstances->hasPages())
            <div class="mt-6">
              {{ $mergedSubstances->links() }}
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>

  {{-- Restore Confirmation Modal --}}
  <div id="restoreModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    
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
            <h3 class="text-lg font-medium text-gray-900">Confirm Substance Restoration</h3>
            <p class="text-sm text-gray-500">This action will reactivate the merged substance</p>
          </div>
        </div>
        <button onclick="cancelRestore()" class="text-gray-400 hover:text-gray-600">
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
        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
          <h5 class="font-medium text-blue-800 text-sm mb-2">This restoration will:</h5>
          <ul class="text-blue-700 text-sm space-y-1">
            <li>• Make the substance active again</li>
            <li>• Remove it from the merge history</li>
            <li>• Allow it to appear in searches again</li>
            <li>• Reset its canonical reference status</li>
          </ul>
        </div>
      </div>
      
      <!-- Footer -->
      <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
        <button onclick="cancelRestore()" 
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
          Cancel
        </button>
        <button onclick="executeRestore()" 
                class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700">
          Restore Substance
        </button>
      </div>
    </div>
  </div>

  {{-- Substance Details Modal --}}
  <div id="substanceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Substance Details</h3>
          <button onclick="closeSubstanceModal()" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <div id="substanceDetails" class="text-sm text-gray-600">
          Loading...
        </div>
      </div>
    </div>
  </div>

  <script>
    let currentRestoreForm = null;
    
    function showSubstanceDetails(substanceId) {
      // Show modal
      document.getElementById('substanceModal').classList.remove('hidden');
      
      // Load substance details via AJAX (you can implement this endpoint)
      document.getElementById('substanceDetails').innerHTML = 'Substance ID: ' + substanceId + '<br>Details would be loaded here...';
    }
    
    function closeSubstanceModal() {
      document.getElementById('substanceModal').classList.add('hidden');
    }
    
    // Close modal when clicking outside
    document.getElementById('substanceModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeSubstanceModal();
      }
    });
    
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
    
    // Close restore modal when clicking outside
    document.getElementById('restoreModal').addEventListener('click', function(e) {
      if (e.target === this) {
        cancelRestore();
      }
    });
  </script>
</x-app-layout>
