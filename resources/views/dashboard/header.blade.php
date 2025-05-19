<x-nav-link-header :href="route('dashboard')" :active="request()->routeIs('dashboard')">
  Main panel
</x-nav-link-header>

<x-nav-link-header :href="route('templates.index')" :active="request()->routeIs('templates.*')">
  Templates
</x-nav-link-header>

<x-nav-link-header :href="route('files.index')" :active="request()->routeIs('files.*')">
  Files
</x-nav-link-header>

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