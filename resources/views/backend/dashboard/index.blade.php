<x-app-layout>
  <x-slot name="header">
    @include('backend.dashboard.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
      @include('backend.dashboard.partials.server_payment_status')

      @if ($serverStats)
        @include('backend.partials.server_statistics')
      @endif

      @include('backend.dashboard.partials.databases')
    </div>
  </div>
</x-app-layout>
