@if ($serverPaymentStatus && $serverPaymentStatus['payment'])
  @php
    $payment = $serverPaymentStatus['payment'];
    $daysRemaining = $serverPaymentStatus['days_remaining'];
    $renewalDue = $serverPaymentStatus['renewal_due'];
  @endphp
  <div class="flex flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3 rounded-lg border shadow-sm {{ $renewalDue ? 'bg-amber-50 border-amber-400' : 'bg-lime-50 border-lime-400' }}">
    <span class="font-semibold text-gray-800">
      <i class="fas fa-server mr-2 {{ $renewalDue ? 'text-amber-600' : 'text-lime-700' }}"></i>
      Server status
      <x-role-lock :roles="\App\Services\ServerPaymentStatusService::VIEWER_ROLES" class="ml-1" />
    </span>
    <span class="text-sm text-gray-700">
      Paid until <span class="font-medium text-gray-900">{{ $payment->period_end_date->format('Y-m-d') }}</span>
    </span>
    @if ($daysRemaining !== null)
      <span class="text-sm {{ $renewalDue ? 'font-semibold text-amber-700' : 'text-gray-700' }}">
        {{ number_format($daysRemaining, 0, '.', ' ') }} {{ $daysRemaining === 1 ? 'day' : 'days' }} left{{ $renewalDue ? ', renewal due soon' : '' }}
      </span>
    @endif
    <a href="{{ route('backend.server-payments.index') }}" class="link-lime-text text-sm sm:ml-auto">
      Server Payments
    </a>
  </div>
@endif
