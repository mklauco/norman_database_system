@php(view()->share('navSection', 'system-settings'))
<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-purple-700">
    System Settings
  </span>

  <x-nav-link-header :href="route('backend.system-settings.index')" :active="request()->routeIs('backend.system-settings.index')">
    Overview
  </x-nav-link-header>

  @role('super_admin')
  <x-nav-link-header :href="route('backend.user-login-retention.filter')" :active="request()->is('*user-login-retention*')">
    User Login Retention
  </x-nav-link-header>
  @endrole

  @hasanyrole('super_admin|server_payment_admin|server_payment_viewer')
  <x-nav-link-header :href="route('backend.server-payments.index')" :active="request()->is('*server-payments*')">
    Server Payments
  </x-nav-link-header>
  @endhasanyrole
</div>
