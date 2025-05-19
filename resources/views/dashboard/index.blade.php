<x-app-layout>
  <x-slot name="header">
    @include('dashboard.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <!-- Welcome Section -->
      {{-- <div class="bg-gradient-to-r from-blue-600 to-indigo-800 rounded-lg shadow-md mb-6 overflow-hidden">
        <div class="p-6 text-white">
          <h2 class="text-2xl font-bold">Welcome, {{ $user->name }}</h2>
          <p class="mt-2 text-blue-100">{{ $currentDate->format('l, F j, Y') }}</p>
        </div>
      </div> --}}
      
      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Left Column: Combined Quick Access, API Tokens, and Recent Activity -->
        <div class="space-y-6">
          <!-- Quick Access Panel -->
          <div class="bg-white overflow-hidden shadow-md rounded-lg">
            <div class="p-4 bg-gray-50 border-b">
              <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-bolt mr-2 text-yellow-500"></i>Quick Access
              </h3>
            </div>
            <div class="p-5">
              <div class="grid grid-cols-2 gap-3">
                @foreach($quickAccessLinks as $link)
                  <a href="{{ route($link['route']) }}" class="flex flex-col items-center p-3 bg-{{ $link['color'] }}-50 rounded-md hover:bg-{{ $link['color'] }}-100 transition">
                    <i class="{{ $link['icon'] }} text-xl text-{{ $link['color'] }}-600 mb-2"></i>
                    <span class="text-sm text-center font-medium">{{ $link['name'] }}</span>
                  </a>
                @endforeach
              </div>
            </div>
          </div>
          
          <!-- API Tokens Panel -->
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
          
          <!-- Recent Activity Panel -->
          <div class="bg-white overflow-hidden shadow-md rounded-lg">
            <div class="p-4 bg-gray-50 border-b">
              <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-history mr-2 text-orange-500"></i>Recent Activity
              </h3>
            </div>
            <div class="p-5">
              @if(count($statistics['recent_activity']) > 0)
                <div class="space-y-4">
                  @foreach($statistics['recent_activity'] as $activity)
                    <div class="border-b border-gray-100 pb-3">
                      <div class="flex items-start">
                        @if($activity['type'] == 'file_upload')
                          <div class="bg-green-100 p-2 rounded-full mr-3">
                            <i class="fas fa-upload text-green-600"></i>
                          </div>
                        @else
                          <div class="bg-blue-100 p-2 rounded-full mr-3">
                            <i class="fas fa-file-alt text-blue-600"></i>
                          </div>
                        @endif
                        <div>
                          <a href="{{ $activity['url'] }}" class="font-medium text-gray-900 hover:text-indigo-600">
                            {{ Str::limit($activity['title'], 30) }}
                          </a>
                          <p class="text-xs text-gray-500 mt-1">
                            @if($activity['type'] == 'file_upload')
                              File uploaded by {{ $activity['user'] }}
                            @else
                              Template for {{ $activity['entity'] }} by {{ $activity['user'] }}
                            @endif
                          </p>
                          <p class="text-xs text-gray-400 mt-1">
                            {{ \Carbon\Carbon::parse($activity['date'])->diffForHumans() }}
                          </p>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              @else
                <div class="text-center py-6 text-gray-500">
                  No recent activity
                </div>
              @endif
            </div>
          </div>
        </div>
        
        <!-- Middle Column: System Statistics (Tall) -->
        <div class="bg-white overflow-hidden shadow-md rounded-lg h-full">
          <div class="p-4 bg-gray-50 border-b">
            <h3 class="text-lg font-semibold text-gray-800">
              <i class="fas fa-chart-line mr-2 text-green-500"></i>System Statistics
            </h3>
          </div>
          <div class="p-5">
            <div class="space-y-3">
              @foreach($databaseEntities as $entity)
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                  <span class="text-gray-700">{{ $entity->name }}</span>
                  <span class="font-semibold">{{ number_format($entity->number_of_records ?? 0) }}</span>
                </div>
              @endforeach
              
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
            
            <div class="mt-8 pt-4 border-t border-gray-200">
              <div class="flex justify-between items-center mb-3">
                <h4 class="font-medium text-gray-800">Recent Templates</h4>
                <a href="{{ route('templates.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">View All</a>
              </div>
              
              <div class="space-y-3">
                @php
                $recentTemplateEntries = array_filter($statistics['recent_activity'], function($item) {
                    return $item['type'] == 'template_create';
                });
                $recentTemplateEntries = array_slice($recentTemplateEntries, 0, 3);
                @endphp
                
                @forelse($recentTemplateEntries as $template)
                <a href="{{ $template['url'] }}" class="block p-2 border rounded hover:bg-gray-50">
                  <div class="font-medium text-sm">{{ Str::limit($template['title'], 25) }}</div>
                  <div class="text-xs text-gray-500">{{ $template['entity'] }}</div>
                </a>
                @empty
                <p class="text-sm text-gray-500">No recent templates</p>
                @endforelse
              </div>
            </div>
          </div>
        </div>
        
        <!-- Right Column: Admin Tools (only for admin) -->
        @role('super_admin')
        <div class="bg-white overflow-hidden shadow-md rounded-lg h-full">
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
        <!-- Placeholder for non-admin users to maintain grid layout -->
        <div class="bg-white overflow-hidden shadow-md rounded-lg h-full">
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
</x-app-layout>