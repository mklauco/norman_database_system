@php(view()->share('navSection', 'system-settings'))
<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-purple-700">
    System Settings
  </span>

  <x-nav-link-header :href="route('backend.system-settings.index')" :active="request()->routeIs('backend.system-settings.index')">
    Overview
  </x-nav-link-header>

  @role('super_admin')
  <x-nav-link-header :href="route('backend.notifications.index')" :active="request()->routeIs('backend.notifications.*')">
    Notifications
  </x-nav-link-header>
  @endrole

  @hasanyrole('super_admin|admin')
  <x-nav-link-header :href="route('backend.display.index')" :active="request()->routeIs('backend.display.*')">
    Display Config
  </x-nav-link-header>
  @endhasanyrole

  @role('super_admin')
  <x-nav-link-header :href="route('backend.system-settings.maintenance')" :active="request()->routeIs('backend.system-settings.maintenance')">
    Maintenance
  </x-nav-link-header>
  @endrole

  @role('super_admin')
  <x-nav-link-header :href="route('querylog.index')" :active="request()->routeIs('querylog.*')">
    Search log
  </x-nav-link-header>
  @endrole

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
