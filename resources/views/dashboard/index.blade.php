<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="grid lg:grid-cols-3 gap-6">
        <!-- First Column: System Statistics (spanning 2 columns) -->
        <div class="lg:col-span-2 bg-white overflow-hidden shadow-md rounded-lg h-full">
          <div class="p-4 bg-gray-50 border-b">
            <h3 class="text-lg font-semibold text-gray-800">
              <i class="fas fa-chart-line mr-2 text-green-500"></i>System Statistics
            </h3>
          </div>
          <div class="p-5">
            <div class="space-y-3">
              <div class="mt-4 overflow-hidden bg-white rounded-lg shadow">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                  <div class="grid grid-cols-3 font-medium text-sm text-gray-600">
                    <div>Database Name</div>
                    <div class="text-right">Total Records</div>
                    <div class="text-right">Search Count</div>
                  </div>
                </div>
                
                <div class="divide-y divide-gray-100">
                  @foreach($databaseEntities as $entity)
                  <div class="grid grid-cols-3 px-4 py-3 hover:bg-gray-50">
                    <div class="text-gray-800 font-medium">{{ $entity->name }}</div>
                    <div class="text-right font-mono text-gray-700">{{ number_format($entity->number_of_records ?? 0) }}</div>
                    <div class="text-right font-mono text-gray-700">{{ number_format($entity->query_log_count ?? 0) }}</div>
                  </div>
                  @endforeach
                </div>
              </div>
              
              <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-700">Templates</span>
                <span class="font-semibold">{{ number_format($statistics['total_templates']) }}</span>
              </div>
              
              <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-700">Files</span>
                <span class="font-semibold">{{ number_format($statistics['total_files']) }}</span>
              </div>
              
              <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-700">Projects</span>
                <span class="font-semibold">{{ number_format($statistics['total_projects']) }}</span>
              </div>
              
              <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-700">Your Files</span>
                <span class="font-semibold">{{ number_format($statistics['user_files']) }}</span>
              </div>
            </div>
            <div class="mt-4 text-center">
              <a href="{{ route('landing.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm">
                View All Databases
              </a>
            </div>
            

          </div>
        </div>
        
        <!-- Third Column: API Tokens and Admin Tools -->
        <div class="flex flex-col space-y-6">
          <!-- API Tokens Section -->
          <div class="bg-white overflow-hidden shadow-md rounded-lg">
            <div class="p-4 bg-gray-50 border-b">
              <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-key mr-2 text-indigo-500"></i>API Tokens
              </h3>
            </div>
            <div class="p-5">
              @if($user->tokens->count() == 0)
              <div class="text-center py-2">
                <i class="fas fa-exclamation-circle text-2xl text-red-500 mb-2"></i>
                <p class="text-gray-700">No API tokens created yet</p>
                <a href="{{ route('apiresources.index') }}" class="mt-2 inline-block px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                  Create API Token
                </a>
              </div>
              @else
              <div class="text-center py-2">
                <i class="fas fa-check-circle text-2xl text-green-500 mb-2"></i>
                <p class="text-gray-700">You have {{ $user->tokens->count() }} active API token(s)</p>
                <a href="{{ route('apiresources.index') }}" class="mt-2 inline-block px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                  Manage Tokens
                </a>
              </div>
              @endif
            </div>
          </div>
          
          <!-- Admin Tools Section (for admins) or Data Templates (for non-admins) -->
          @role('super_admin')
          <div class="bg-white overflow-hidden shadow-md rounded-lg flex-grow">
            <div class="p-4 bg-gray-50 border-b">
              <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-tools mr-2 text-gray-700"></i>Admin Tools
              </h3>
            </div>
            <div class="p-5">
              <div class="space-y-6">
                @foreach($adminProcessGroups as $group)
                <div>
                  <h4 class="font-semibold text-sm uppercase text-gray-500 mb-3">{{ $group['name'] }}</h4>
                  <div class="flex flex-wrap gap-2">
                    @foreach($group['processes'] as $process)
                    <form action="{{ route($process['route']) }}" method="{{ $process['method'] }}">
                      @csrf
                      <button type="submit" class="btn-submit text-xs">{{ $process['name'] }}</button>
                    </form>
                    @endforeach
                  </div>
                </div>
                @endforeach
              </div>
              
              <div class="mt-8 pt-4 border-t border-gray-200">
                <h4 class="font-semibold text-sm uppercase text-gray-500 mb-3">Admin Actions</h4>
                <div class="grid grid-cols-1 gap-3">
                  <a href="{{ route('querylog.index') }}" class="flex items-center p-3 bg-gray-50 rounded-md hover:bg-gray-100 transition">
                    <i class="fas fa-clipboard-list text-gray-600 mr-3"></i>
                    <span class="text-sm font-medium">View System Logs</span>
                  </a>
                  <a href="{{ route('templates.create') }}" class="flex items-center p-3 bg-blue-50 rounded-md hover:bg-blue-100 transition">
                    <i class="fas fa-plus text-blue-600 mr-3"></i>
                    <span class="text-sm font-medium">Create Template</span>
                  </a>
                  <a href="{{ route('files.create') }}" class="flex items-center p-3 bg-green-50 rounded-md hover:bg-green-100 transition">
                    <i class="fas fa-upload text-green-600 mr-3"></i>
                    <span class="text-sm font-medium">Upload File</span>
                  </a>
                </div>
              </div>
            </div>
          </div>
          @else
          <!-- Data Templates Section for non-admin users -->
          <div class="bg-white overflow-hidden shadow-md rounded-lg flex-grow">
            <div class="p-4 bg-gray-50 border-b">
              <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-file-download mr-2 text-blue-500"></i>Data Templates
              </h3>
            </div>
            <div class="p-5">
              <div class="grid grid-cols-1 gap-4">
                @foreach($entitiesWithTemplates as $entity)
                <a href="{{ route('templates.specific.index', ['code' => $entity->code]) }}" 
                  class="flex items-center p-3 border rounded-lg hover:bg-blue-50 transition">
                  <div class="bg-blue-100 p-2 rounded-full mr-3">
                    <i class="fas fa-file-alt text-blue-600"></i>
                  </div>
                  <div>
                    <h4 class="font-medium text-gray-900">{{ $entity->name }}</h4>
                    <p class="text-xs text-gray-600">Download templates</p>
                  </div>
                </a>
                @endforeach
              </div>
            </div>
          </div>
          @endrole
        </div>
      </div>
    </div>
  </div>
</x-app-layout>