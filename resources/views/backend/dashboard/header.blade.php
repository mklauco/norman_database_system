@php(view()->share('navSection', 'dashboard'))
<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-lime-700">
    Dashboard
  </span>

  <x-nav-link-header :href="route('dashboard')" :active="request()->routeIs('dashboard')">
    Main panel
  </x-nav-link-header>
  @hasanyrole('super_admin|admin')
  <x-nav-link-header :href="route('templates.index')" :active="request()->routeIs('templates.*')">
    DCT Templates
  </x-nav-link-header>
  @endhasanyrole
  @hasanyrole('super_admin|admin')
  <x-nav-link-header :href="route('files.index')" :active="request()->routeIs('files.*')">
    Uploaded DCT Files
  </x-nav-link-header>
  @endhasanyrole

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
</div>