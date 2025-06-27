{{-- Enhanced Substance Database Header --}}
<div class="flex items-center space-x-6 border-b border-gray-200 pb-2">
  {{-- Module Title (uncommented and styled) --}}
  <span class="text-lg font-bold text-emerald-700 border-r border-gray-300 pr-6">
    Substance Database
  </span>
  
  {{-- Navigation Links --}}
  <div class="flex space-x-4">
    {{-- Filter Link --}}
    <x-nav-link-header 
      :href="route('substances.filter')" 
      :active="request()->is('*filter')"
      class="flex items-center space-x-1"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"></path>
      </svg>
      <span>Filter</span>
    </x-nav-link-header>

    {{-- View Toggle Link --}}
    @if(request()->is('*filter'))
      <x-nav-link-header 
        :href="route('substances.index')" 
        :active="request()->is('*search')"
        class="flex items-center space-x-1"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
        </svg>
        <span>Full View</span>
      </x-nav-link-header>
    @else
      <x-nav-link-header 
        :href="request()->fullUrl()" 
        :active="request()->is('*search')"
        class="flex items-center space-x-1"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        </svg>
        <span>Current View</span>
      </x-nav-link-header>
    @endif

    {{-- Duplicates Link (Auth Required) --}}
    @auth
      <x-nav-link-header 
        :href="route('duplicates.index')" 
        :active="request()->is('*duplicates*')"
        class="flex items-center space-x-1"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
        </svg>
        <span>Duplicates</span>
      </x-nav-link-header>
    @endauth

    {{-- Audited Substances Link --}}
    <x-nav-link-header 
      :href="route('substances.audited')" 
      :active="request()->routeIs('substances.audited')"
      class="flex items-center space-x-1"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
      </svg>
      <span>Audited</span>
    </x-nav-link-header>

  </div>

  {{-- Substance Detail Indicator --}}
  @if(request()->routeIs('substances.show'))
    <div class="ml-auto flex items-center space-x-2 text-gray-600">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <span class="text-sm">Viewing: {{ $substance->display_name ?? $substance->name }}</span>
      @if($substance->prefixed_code)
        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">{{ $substance->prefixed_code }}</span>
      @endif
    </div>
  @endif
</div>