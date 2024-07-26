<x-nav-link-header :href="route('dashboard')" :active="request()->routeIs('dashboard')">
  Main panel
</x-nav-link-header>

@role('admin')
<x-nav-link-header :href="route('users.index')" :active="request()->routeIs('users.index')">
  Users
</x-nav-link-header>
@endrole

<x-nav-link-header :href="route('apiresources.index')" :active="request()->routeIs('apiresources.index')">
    API Tokens
</x-nav-link-header>
