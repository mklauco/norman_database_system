{{-- <span class="mr-12 font-bold text-lime-700">
  Substance Database:
</span> --}}

<x-nav-link-header :href="route('substances.filter')" :active="request()->is('*filter')">
  Filter
</x-nav-link-header>

@if(request()->is('*filter') == true)
<x-nav-link-header :href="route('substances.index')" :active="request()->is('*search')">
  Full View
</x-nav-link-header>
@else
<x-nav-link-header :href="request()->fullUrl()" :active="request()->is('*search')">
  Current View
</x-nav-link-header>
@endif  

@auth
<x-nav-link-header :href="route('duplicates.index')" :active="request()->is('*duplicates*')">
  Duplicates
</x-nav-link-header>
@endauth

@if(request()->routeIs('substances.show') == true)
<x-nav-link-header :href="route('substances.show', $substance->id)" :active="request()->routeIs('substances.show')">
  Showing Specific Substance
</x-nav-link-header>
@endif