<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-lime-700">
    Microplastics
  </span>

  <x-nav-link-header :href="route('microplastics.home.index')" :active="request()->routeIs('microplastics.home.index')">
    Home
  </x-nav-link-header>

  <x-nav-link-header :href="route('templates.specific.index', ['code' => 'microplastics'])" :active="request()->is('backend/templates/entity/microplastics*')">
    DCT Download
  </x-nav-link-header>

  <span class="inline-flex py-4 mr-5 items-center border-b-2 border-transparent text-sm font-medium leading-5 text-gray-400 cursor-not-allowed" title="Coming soon">
    Visual methods
  </span>

  <span class="inline-flex py-4 mr-5 items-center border-b-2 border-transparent text-sm font-medium leading-5 text-gray-400 cursor-not-allowed" title="Coming soon">
    Spectroscopic methods
  </span>

  <span class="inline-flex py-4 mr-5 items-center border-b-2 border-transparent text-sm font-medium leading-5 text-gray-400 cursor-not-allowed" title="Coming soon">
    Thermoanalytical methods
  </span>
</div>
