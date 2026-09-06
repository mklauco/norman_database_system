<x-app-layout>
  <x-slot name="header">
    @include('backend.system-settings.header')
  </x-slot>

  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

      <!-- Welcome Section -->
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">System Settings</h2>
        <p class="text-gray-600">Manage system-wide settings and configurations</p>
      </div>

      <!-- System Information Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

        <!-- Users Card -->
        @hasanyrole('admin|user_manager')
        <a href="{{ route('users.index') }}" class="block">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
            <div class="p-4">
              <div class="flex items-center mb-3">
                <div class="p-2 bg-slate-100 rounded-lg">
                  <i class="fas fa-users text-slate-600 text-xl"></i>
                </div>
                <h3 class="ml-3 text-base font-semibold text-gray-800">Users</h3>
              </div>
              <div class="space-y-1">
                <div class="flex justify-between text-xs">
                  <span class="text-gray-600">Total:</span>
                  <span class="font-semibold text-gray-900">{{ $statistics['total_users'] }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-gray-600">Active:</span>
                  <span class="font-semibold text-lime-600">{{ $statistics['active_users'] }}</span>
                </div>
              </div>
            </div>
          </div>
        </a>
        @endhasanyrole

        <!-- API Tokens Card -->
        <a href="{{ route('apiresources.index') }}" class="block">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
            <div class="p-4">
              <div class="flex items-center mb-3">
                <div class="p-2 bg-zinc-100 rounded-lg">
                  <i class="fas fa-key text-zinc-600 text-xl"></i>
                </div>
                <h3 class="ml-3 text-base font-semibold text-gray-800">API Tokens</h3>
              </div>
              <div class="space-y-1">
                <div class="flex justify-between text-xs">
                  <span class="text-gray-600">Total:</span>
                  <span class="font-semibold text-gray-900">{{ $statistics['total_api_tokens'] }}</span>
                </div>
                <p class="text-xs text-gray-500">External integrations</p>
              </div>
            </div>
          </div>
        </a>

        <!-- User Login Retention Card -->
        @role('super_admin')
        <a href="{{ route('backend.user-login-retention.filter') }}" class="block">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
            <div class="p-4">
              <div class="flex items-center mb-3">
                <div class="p-2 bg-gray-100 rounded-lg">
                  <i class="fas fa-clock text-gray-600 text-xl"></i>
                </div>
                <h3 class="ml-3 text-base font-semibold text-gray-800">Login History</h3>
              </div>
              <div>
                <p class="text-xs text-gray-500">Track user activity</p>
              </div>
            </div>
          </div>
        </a>
        @endrole

        <!-- Server Payments Card -->
        @hasanyrole('super_admin|server_payment_admin|server_payment_viewer')
        <a href="{{ route('backend.server-payments.index') }}" class="block">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
            <div class="p-4">
              <div class="flex items-center mb-3">
                <div class="p-2 bg-slate-100 rounded-lg">
                  <i class="fas fa-credit-card text-slate-600 text-xl"></i>
                </div>
                <h3 class="ml-3 text-base font-semibold text-gray-800">Payments</h3>
              </div>
              <div class="space-y-1">
                @if($serverPayment)
                  <div class="flex justify-between text-xs">
                    <span class="text-gray-600">Status:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                      @if($serverPayment->status === 'paid') bg-lime-100 text-lime-800
                      @elseif($serverPayment->status === 'pending') bg-amber-100 text-amber-800
                      @else bg-zinc-200 text-zinc-800
                      @endif">
                      {{ ucfirst(str_replace('_', ' ', $serverPayment->status)) }}
                    </span>
                  </div>
                  @if($daysRemaining !== null)
                  <div class="flex justify-between text-xs">
                    <span class="text-gray-600">Days Left:</span>
                    <span class="font-semibold text-lime-600">{{ $daysRemaining }}</span>
                  </div>
                  @endif
                @else
                  <p class="text-xs text-gray-500">No active payment</p>
                @endif
              </div>
            </div>
          </div>
        </a>
        @endhasanyrole

      </div>

      <!-- Server Related Sections -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <!-- Server Payments Section -->
        @hasanyrole('super_admin|server_payment_admin|server_payment_viewer')
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-4 bg-slate-800 border-b border-slate-700">
            <h3 class="text-base font-semibold text-white">
              <i class="fas fa-server mr-2 text-slate-300"></i>
              Server Payments
            </h3>
            <p class="text-xs text-slate-300 mt-1">Payment status and timeline</p>
          </div>
          <div class="p-4 text-gray-900">
            @include('backend.dashboard.partials.server_payment_status')
          </div>
        </div>
        @endhasanyrole

        <!-- Server Statistics Section -->
        @role('super_admin')
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-4 bg-zinc-800 border-b border-zinc-700">
            <h3 class="text-base font-semibold text-white">
              <i class="fas fa-chart-bar mr-2 text-zinc-300"></i>
              Server Statistics
            </h3>
            <p class="text-xs text-zinc-300 mt-1">Resource usage</p>
          </div>
          <div class="p-4">
            <div class="grid grid-cols-2 gap-4">
              <!-- Disk Space -->
              <div class="border border-gray-200 rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs text-gray-600">Disk Space</span>
                  <i class="fas fa-hdd text-gray-400 text-sm"></i>
                </div>
                @if($serverStats['disk'])
                  <div class="text-xl font-semibold text-gray-900">{{ number_format($serverStats['disk']['used_percentage'], 1, '.', ' ') }} %</div>
                  <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                    <div class="h-1.5 rounded-full @if($serverStats['disk']['used_percentage'] >= 90) bg-red-500 @elseif($serverStats['disk']['used_percentage'] >= 75) bg-amber-500 @else bg-lime-500 @endif"
                         style="width: {{ min($serverStats['disk']['used_percentage'], 100) }}%"></div>
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    {{ $serverStats['disk']['used_human'] }} of {{ $serverStats['disk']['total_human'] }} used
                  </div>
                  <div class="text-xs text-gray-500">{{ $serverStats['disk']['free_human'] }} free</div>
                @else
                  <div class="text-xl font-semibold text-gray-900">--</div>
                  <div class="text-xs text-gray-500 mt-1">Unavailable</div>
                @endif
              </div>

              <!-- Uptime -->
              <div class="border border-gray-200 rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs text-gray-600">Uptime</span>
                  <i class="fas fa-clock text-gray-400 text-sm"></i>
                </div>
                @if($serverStats['uptime'])
                  <div class="text-xl font-semibold text-lime-600">{{ $serverStats['uptime']['host_human'] }}</div>
                  <div class="text-xs text-gray-500 mt-1">Host system</div>
                  @if($serverStats['uptime']['container_human'])
                    <div class="text-xs text-gray-500 mt-1">Container: {{ $serverStats['uptime']['container_human'] }}</div>
                  @endif
                @else
                  <div class="text-xl font-semibold text-lime-600">--</div>
                  <div class="text-xs text-gray-500 mt-1">Unavailable</div>
                @endif
              </div>
            </div>
          </div>
        </div>
        @endrole

        <!-- Backup Information Section -->
        @role('super_admin')
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-4 bg-gray-800 border-b border-gray-700">
            <h3 class="text-base font-semibold text-white">
              <i class="fas fa-database mr-2 text-gray-300"></i>
              Backup Information
            </h3>
            <p class="text-xs text-gray-300 mt-1">Database backup status</p>
          </div>
          <div class="p-4">
            <p class="text-sm text-gray-500 text-center py-8">Backup information will be available soon</p>
          </div>
        </div>
        @endrole

      </div>

      <!-- Quick Actions -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @hasanyrole('admin|user_manager')
            <a href="{{ route('users.create') }}" class="flex items-center p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
              <i class="fas fa-user-plus text-slate-600 text-xl mr-3"></i>
              <span class="text-sm font-medium text-gray-700">Add New User</span>
            </a>
            @endhasanyrole

            <a href="{{ route('apiresources.index') }}" class="flex items-center p-4 bg-zinc-50 rounded-lg hover:bg-zinc-100 transition">
              <i class="fas fa-key text-zinc-600 text-xl mr-3"></i>
              <span class="text-sm font-medium text-gray-700">Manage API Tokens</span>
            </a>

            @hasanyrole('super_admin|server_payment_admin')
            <a href="{{ route('backend.server-payments.create') }}" class="flex items-center p-4 bg-lime-50 rounded-lg hover:bg-lime-100 transition">
              <i class="fas fa-plus-circle text-lime-600 text-xl mr-3"></i>
              <span class="text-sm font-medium text-gray-700">Add Payment Record</span>
            </a>
            @endhasanyrole

            <a href="{{ route('dashboard') }}" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
              <i class="fas fa-home text-gray-600 text-xl mr-3"></i>
              <span class="text-sm font-medium text-gray-700">Back to Dashboard</span>
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</x-app-layout>
