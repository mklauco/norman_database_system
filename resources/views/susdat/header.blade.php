<div class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 sm:px-6 lg:px-8">
  <span class="mr-8 font-bold text-lime-700">
    NORMAN Substance Database &ndash; NORMAN SusDat
  </span>

  <x-nav-link-header :href="route('substances.search.filter')" :active="request()->routeIs('substances.search.filter')" class="mr-0! whitespace-nowrap">
    Search
  </x-nav-link-header>

  {{-- Show Results only when a filter is active --}}
  @if (request()->routeIs('substances.search.search') || 
       (request()->has('searchCategory') && request()->input('searchCategory') == 1) ||
       (request()->has('searchSource') && request()->input('searchSource') == 1) ||
       (request()->has('searchSubstance') && request()->input('searchSubstance') == 1))
    <x-nav-link-header :href="request()->fullUrl()" :active="request()->routeIs('substances.search.search')" class="mr-0! whitespace-nowrap">
      Results
    </x-nav-link-header>
  @else
    {{-- Show List of All Substances when no filter is active --}}
    <x-nav-link-header :href="route('substances.index')" :active="request()->routeIs('substances.index')" class="mr-0! whitespace-nowrap">
      List of All Substances
    </x-nav-link-header>
  @endif

  @role('super_admin')
    <x-nav-link-header :href="route('querylog.index', ['module' => 'susdat'])" :active="request()->is('*querylog*')" class="mr-0! whitespace-nowrap">
      History of search
    </x-nav-link-header>
  @else
    <span class="inline-flex items-center py-4 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 cursor-not-allowed whitespace-nowrap" aria-disabled="true">
      History of search <i class="fas fa-lock ml-2"></i>
    </span>
  @endrole

  @auth
    @if (auth()->user()->hasRole(['super_admin', 'admin', 'susdat']))
      <x-nav-link-header :href="route('substances.audited')" :active="request()->routeIs('substances.audited')" class="mr-0! whitespace-nowrap">
        Audit Log
      </x-nav-link-header>
    @endif
  @endauth

  @auth
    @if (auth()->user()->hasRole(['super_admin', 'admin', 'susdat']))
      <x-nav-link-header :href="route('duplicates.index')" :active="request()->is('*duplicates*')" class="mr-0! whitespace-nowrap">
        Duplicates
      </x-nav-link-header>
    @endif
  @endauth

  @auth
    @if (auth()->user()->hasRole(['super_admin', 'admin', 'susdat']))
      <x-nav-link-header :href="route('substances.create')" :active="request()->routeIs('substances.create')" class="mr-0! whitespace-nowrap">
        Create New Substance
      </x-nav-link-header>
    @endif
  @endauth

  <x-nav-link-header :href="route('susdat.batch.index')" :active="request()->is('*batch*')" class="mr-0! whitespace-nowrap">
    Batch Conversion
  </x-nav-link-header>

  @role('super_admin')
    <x-nav-link-header :href="route('substances.missing-names')" :active="request()->routeIs('substances.missing-names')" class="mr-0! whitespace-nowrap">
      Fetch Missing Names
    </x-nav-link-header>
  @endrole


</div>
