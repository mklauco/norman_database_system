<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-lime-700">
    Hazards and Properties Database
  </span>

  <x-nav-link-header :href="route('hazardshome.index')" :active="request()->is('hazards/hazardshome*')">
    Home
  </x-nav-link-header>

  <x-nav-link-header :href="route('hazards.data.search.filter')" :active="request()->is('hazards/data*')">
    Search Data
  </x-nav-link-header>

  @role('super_admin|admin')
  <x-nav-link-header :href="route('hazards.derivation.search.filter')" :active="request()->is('hazards/derivation*')">
    Derivation
  </x-nav-link-header>
  @endrole

  @role('super_admin')
  <x-nav-link-header :href="route('querylog.index', ['module' => 'hazards'])" :active="request()->is('*querylog*')">
    History of search
  </x-nav-link-header>
  @else
  <x-nav-link-header>
    History of search <i class="fas fa-lock ml-2"></i>
  </x-nav-link-header>
  @endrole
</div>
