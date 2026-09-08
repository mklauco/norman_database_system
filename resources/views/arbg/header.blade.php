<div class="px-4 sm:px-6 lg:px-8">
  <span class="mr-12 font-bold text-lime-700">
    ARB&ARG Database
  </span> 

  <x-nav-link-header :href="route('arbghome.index')" :active="request()->is('*home*')">
    Home
  </x-nav-link-header>

  @if(request()->routeIs('arbg.bacteria.show'))
  <span class="inline-flex py-4 mr-5 items-center border-b-2 border-transparent text-sm font-medium leading-5 text-gray-400 cursor-not-allowed"
        title="You are viewing a record in a new tab. To return to search results, close this tab or use your browser's back button."
        aria-label="Search Bacteria is disabled on this page">
    Search Bacteria
  </span>
  @else
  <x-nav-link-header :href="route('arbg.bacteria.search.filter')" :active="request()->is('arbg/bacteria*') && !request()->is('arbg/bacteria/statistics*')">
    Search Bacteria
  </x-nav-link-header>
  @endif

  @if(request()->routeIs('arbg.gene.show'))
  <span class="inline-flex py-4 mr-5 items-center border-b-2 border-transparent text-sm font-medium leading-5 text-gray-400 cursor-not-allowed"
        title="You are viewing a record in a new tab. To return to search results, close this tab or use your browser's back button."
        aria-label="Search Genes is disabled on this page">
    Search Genes
  </span>
  @else
  <x-nav-link-header :href="route('arbg.gene.search.filter')" :active="request()->is('arbg/gene*') && !request()->is('arbg/gene/statistics*')">
    Search Genes
  </x-nav-link-header>
  @endif

  <x-nav-link-header :href="route('arbg.bacteria.statistics.index')" :active="request()->is('arbg/bacteria/statistics*')">
    Bacteria Statistics
  </x-nav-link-header>

  <x-nav-link-header :href="route('arbg.gene.statistics.index')" :active="request()->is('arbg/gene/statistics*')">
    Gene Statistics
  </x-nav-link-header>

  <x-nav-link-header :href="route('templates.specific.index', ['code' => 'arbg'])" :active="request()->is('backend/templates/entity/arbg*')">
    DCT Download
  </x-nav-link-header>

  <x-nav-link-header :href="route('arbg.files.index')" :active="request()->is('arbg/uploaded-files*')">
    Uploaded Files
  </x-nav-link-header>

  @role('super_admin')
  <x-nav-link-header :href="route('querylog.index', ['module' => 'arbg'])" :active="request()->is('*querylog*')">
    History of search
  </x-nav-link-header>
  @else
  <x-nav-link-header>
    History of search <i class="fas fa-lock ml-2"></i>
  </x-nav-link-header>
  @endrole

  {{-- @if(request()->is('*filter') == true)
  <x-nav-link-header :href="route('substances.index')" :active="request()->is('*search')">
    Full View
  </x-nav-link-header>
  @else
  <x-nav-link-header :href="request()->fullUrl()" :active="request()->is('*search')">
    Current View
  </x-nav-link-header>
  @endif   --}}

  {{-- <x-nav-link-header :href="route('duplicates.index')" :active="request()->is('*duplicates*')">
    Duplicates
  </x-nav-link-header>

  @if(request()->routeIs('substances.show') == true)
  <x-nav-link-header :href="route('substances.show', $substance->id)" :active="request()->routeIs('substances.show')">
    Showing Specific Substance
  </x-nav-link-header>
  @endif --}}
</div>