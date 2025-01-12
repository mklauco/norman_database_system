<x-nav-link-header :href="route('dashboard')" :active="request()->routeIs('dashboard')">
  Main panel
</x-nav-link-header>

@role('user_manager')
<x-nav-link-header :href="route('users.index')" :active="request()->routeIs('users.index')">
  Users
</x-nav-link-header>
@endrole

@role('project_manager')
<x-nav-link-header :href="route('projects.index')" :active="request()->routeIs('projects.index')">
  Projects
</x-nav-link-header>
@endrole

<x-nav-link-header :href="route('apiresources.index')" :active="request()->routeIs('apiresources.index')">
    API Tokens
</x-nav-link-header>
