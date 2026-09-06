<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-lime-700">
    Ecotoxicology Database
  </span>

  <x-nav-link-header :href="route('ecotoxhome.index')" :active="request()->is('*home*')">
    Home
  </x-nav-link-header>

  <x-nav-link-header :href="route('ecotox.data.search.filter')" :active="request()->is('*data*')">
    Search Ecotox data
  </x-nav-link-header>

  @role('super_admin|admin|ecotox')
  <x-nav-link-header :href="route('ecotox.credevaluation.search.filter')" :active="request()->is('ecotox/credevaluation*')">
    CRED Evaluation
  </x-nav-link-header>
  

  <x-nav-link-header :href="route('ecotox.pnecderivation.index')" :active="request()->is('ecotox/pnecderivation*')">
    PNEC Derivation
  </x-nav-link-header>
  @endrole
  
  <x-nav-link-header :href="route('ecotox.quality.search.filter')" :active="request()->is('ecotox/quality*')">
    Search Quality Target
  </x-nav-link-header>

  <x-nav-link-header :href="route('ecotox.lowestpnec.index')" :active="request()->is('ecotox/lowestpnec*')">
    Lowest PNECs
  </x-nav-link-header>

  <x-nav-link-header :href="route('templates.specific.index', ['code' => 'ecotox'])" :active="request()->is('backend/templates/entity/ecotox*')">
    DCT Download
  </x-nav-link-header>



  @role('super_admin')
  <x-nav-link-header :href="route('querylog.index', ['module' => 'ecotox'])" :active="request()->is('*querylog*')">
    History of search
  </x-nav-link-header>
  @else
  <x-nav-link-header>
    History of search <i class="fas fa-lock ml-2"></i>
  </x-nav-link-header>
  @endrole
</div>