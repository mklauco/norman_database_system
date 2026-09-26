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
                <h3 class="ml-3 flex items-center gap-2 text-base font-semibold text-gray-800">Users <x-role-lock :roles="['admin', 'user_manager']" /></h3>
              </div>
              <div class="space-y-1">
                <div class="flex justify-between text-xs">
                  <span class="text-gray-600">Total:</span>
                  <span class="font-semibold text-gray-900 font-mono">{{ number_format($statistics['total_users'], 0, '.', ' ') }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-gray-600">Active:</span>
                  <span class="font-semibold text-lime-600 font-mono">{{ number_format($statistics['active_users'], 0, '.', ' ') }}</span>
                </div>
              </div>
            </div>
          </div>
        </a>
        @endhasanyrole

        @hasanyrole('super_admin|admin')

          <!-- API Tokens Card -->
          <a href="{{ route('apiresources.index') }}" class="block">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
              <div class="p-4">
                <div class="flex items-center mb-3">
                  <div class="p-2 bg-zinc-100 rounded-lg">
                    <i class="fas fa-key text-zinc-600 text-xl"></i>
                  </div>
                  <h3 class="ml-3 flex items-center gap-2 text-base font-semibold text-gray-800">API Tokens <x-role-lock :roles="['super_admin', 'admin']" /></h3>
                </div>
                <div class="space-y-1">
                  <div class="flex justify-between text-xs">
                    <span class="text-gray-600">Total:</span>
                    <span class="font-semibold text-gray-900 font-mono">{{ number_format($statistics['total_api_tokens'], 0, '.', ' ') }}</span>
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
                  <h3 class="ml-3 flex items-center gap-2 text-base font-semibold text-gray-800">Login History <x-role-lock :roles="['super_admin']" /></h3>
                </div>
                <div>
                  <p class="text-xs text-gray-500">Track user activity</p>
                </div>
              </div>
            </div>
          </a>
          @endrole

        @endhasanyrole

      </div>

      @hasanyrole('super_admin|admin')

        <!-- Server Related Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

          <!-- Server Payments Section -->
          @if ($canViewServerPayments)
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 bg-slate-800 border-b border-slate-700">
              <h3 class="flex items-center gap-2 text-base font-semibold text-white">
                <i class="fas fa-server text-slate-300"></i>
                Server Payments
                <x-role-lock :roles="\App\Services\ServerPaymentStatusService::VIEWER_ROLES" color="text-slate-300" />
              </h3>
              <p class="text-xs text-slate-300 mt-1">Payment status and timeline</p>
            </div>
            <div class="p-4 text-gray-900">
              @if ($paymentStatus['payment'])
                <div class="space-y-4">
                  <div class="flex justify-between items-center">
                    <div>
                      <p class="text-sm text-gray-600">Current Period:</p>
                      <p class="font-medium">{{ $paymentStatus['payment']->formatted_period }}</p>
                    </div>
                    <div class="text-right">
                      <p class="text-sm text-gray-600">Status:</p>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if ($paymentStatus['payment']->status === 'paid' && ! $paymentStatus['renewal_due']) bg-lime-100 text-lime-800
                        @elseif ($paymentStatus['renewal_due']) bg-amber-100 text-amber-800
                        @else bg-zinc-200 text-zinc-800
                        @endif">
                        {{ ucfirst(str_replace('_', ' ', $paymentStatus['payment']->status)) }}
                      </span>
                    </div>
                  </div>

                  @if ($paymentStatus['payment']->status === 'paid' && $paymentStatus['days_remaining'] !== null)
                    <div>
                      <div class="flex flex-col gap-1 mb-1">
                        <div class="flex justify-between text-sm text-gray-600">
                          <span>Days Remaining</span>
                          <span class="font-medium font-mono">{{ number_format($paymentStatus['days_remaining'], 0, '.', ' ') }} days</span>
                        </div>
                        <span class="text-xs text-gray-500">until {{ \Illuminate\Support\Carbon::parse($paymentStatus['payment']->period_end_date)->format('Y-m-d') }}</span>
                      </div>
                      <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full @if ($paymentStatus['renewal_due']) bg-amber-500 @else bg-lime-500 @endif"
                             style="width: {{ max(0, min(100, 100 - $paymentStatus['progress_percentage'])) }}%"></div>
                      </div>
                      @if ($paymentStatus['renewal_due'])
                        <p class="text-xs text-amber-700 mt-1">Payment renewal needed soon</p>
                      @endif
                    </div>
                  @endif
                </div>
              @else
                <div class="text-center py-4">
                  <p class="text-gray-500 text-sm">No server payment data available</p>
                </div>
              @endif
            </div>
          </div>
          @endif

          <!-- Server Statistics Section -->
          @if ($canViewServerStats)
            @include('backend.partials.server_statistics')
          @endif

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
                <x-role-lock :roles="['admin', 'user_manager']" class="ml-auto" />
              </a>
              @endhasanyrole

              @hasanyrole('super_admin|server_payment_admin')
              <a href="{{ route('backend.server-payments.create') }}" class="flex items-center p-4 bg-lime-50 rounded-lg hover:bg-lime-100 transition">
                <i class="fas fa-plus-circle text-lime-600 text-xl mr-3"></i>
                <span class="text-sm font-medium text-gray-700">Add Payment Record</span>
                <x-role-lock :roles="['super_admin', 'server_payment_admin']" class="ml-auto" />
              </a>
              @endhasanyrole
            </div>
          </div>
        </div>

      @endhasanyrole

    </div>
  </div>
</x-app-layout>
