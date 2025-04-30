<span class="mr-12 font-bold text-lime-700">
  Prioritisation module:
</span>

<x-nav-link-header :href="route('prioritisation.monitoring-scarce.index')" 
:active="request()->routeIs('prioritisation.monitoring-scarce.index')">
Monitoring Scarce
</x-nav-link-header>

<x-nav-link-header :href="route('prioritisation.monitoring-danube.index')" 
:active="request()->routeIs('prioritisation.monitoring-danube.index')">
Monitoring Danube
</x-nav-link-header>

<x-nav-link-header :href="route('prioritisation.modelling-scarce.index')" 
:active="request()->routeIs('prioritisation.modelling-scarce.index')">
Modelling Scarce
</x-nav-link-header>

<x-nav-link-header :href="route('prioritisation.modelling-danube.index')" 
:active="request()->routeIs('prioritisation.modelling-danube.index')">
Modelling Danube
</x-nav-link-header>


{{-- <x-nav-link-header :href="route('prioritisation.dctitems.index')" :active="request()->is('*dctitems*')">
  DCT Download
</x-nav-link-header> --}}

@role('super_admin')
{{-- <x-nav-link-header :href="route('prioritisation.querylog.index')" :active="request()->is('*querylog*')">
  History of search
</x-nav-link-header> --}}
@else
{{-- <x-nav-link-header>
  History of search <i class="fas fa-lock ml-2"></i>
</x-nav-link-header> --}}

@endrole

