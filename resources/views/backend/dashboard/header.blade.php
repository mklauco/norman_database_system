@php(view()->share('navSection', 'dashboard'))
<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-lime-700">
    Dashboard
  </span>

  <x-nav-link-header :href="route('dashboard')" :active="request()->routeIs('dashboard')">
    Main panel
  </x-nav-link-header>
  @role('admin')
  <x-nav-link-header :href="route('templates.index')" :active="request()->routeIs('templates.*')">
    Templates
  </x-nav-link-header>
  @endrole
  @role('admin')
  <x-nav-link-header :href="route('files.index')" :active="request()->routeIs('files.*')">
    Uploaded DCT Files
  </x-nav-link-header>
  @endrole
  
  @role('user_manager')
  <x-nav-link-header :href="route('users.index')" :active="request()->routeIs('users.*')">
    Users
  </x-nav-link-header>
  @endrole

  @role('project_manager')
  <x-nav-link-header :href="route('projects.index')" :active="request()->routeIs('projects.*')">
    Projects
  </x-nav-link-header>
  @endrole

  <x-nav-link-header :href="route('apiresources.index')" :active="request()->routeIs('apiresources.*')">
      API Tokens
  </x-nav-link-header>

  @role('super_admin')
  <x-nav-link-header :href="route('backend.notifications.index')" :active="request()->is('*notifications*')">
    Notifications
  </x-nav-link-header>
  @endrole

  @role('super_admin|admin')
  <x-nav-link-header :href="route('backend.display.index')" :active="request()->routeIs('backend.display.*')">
    Display Config
  </x-nav-link-header>
  @endrole
</div>