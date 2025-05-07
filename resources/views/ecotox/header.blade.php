<span class="mr-12 font-bold text-lime-700">
  Ecotox
</span>

<x-nav-link-header :href="route('ecotoxhome.index')" :active="request()->is('ecotox/ecotoxhome*')">
  Home
</x-nav-link-header>

<x-nav-link-header :href="route('ecotox.lowestpnec.index')" :active="request()->is('ecotox/lowestpnec*')">
  Lowest PNEC Search
</x-nav-link-header>


@role('super_admin')
{{-- <x-nav-link-header :href="route('querylog.index')" :active="request()->is('*querylog*')">
  History of search
</x-nav-link-header> --}}
@else
{{-- <x-nav-link-header>
  History of search <i class="fas fa-lock ml-2"></i>
</x-nav-link-header> --}}

@endrole