<div>
  <form name="searchSpecificSubstanceForm" id="searchSpecificSubstanceForm" action="{{route('substances.search.search')}}" method="GET">
    <input type="hidden" value="1" name="searchSubstance">
    <input type="text" wire:model.live.debounce.300ms="search" name="searchSubstanceString" id="searchSubstanceString" class="w-full px-4 py-2 border border-gray-300 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-lime-500 focus:border-transparent transition duration-200 ease-in-out">
    <div class="mt-4">
      <span class="text-gray-700">Search by:</span>
      <div class="mt-2 flex flex-wrap gap-x-6 gap-y-2">
        <label class="inline-flex items-center">
          <input wire:model.live.debounce.100ms="searchType" type="radio" name="searchType" value="cas_number" class="text-lime-600 border-gray-300 rounded-sm focus:ring-lime-500 focus:ring-2">
          <span class="ml-2">CAS</span>
        </label>
        <label class="inline-flex items-center">
          <input wire:model.live.debounce.100ms="searchType" type="radio" name="searchType" value="name" checked class="text-lime-600 border-gray-300 rounded-sm focus:ring-lime-500 focus:ring-2">
          <span class="ml-2">Name</span>
        </label>
        <label class="inline-flex items-center">
          <input wire:model.live.debounce.100ms="searchType" type="radio" name="searchType" value="stdinchikey" class="text-lime-600 border-gray-300 rounded-sm focus:ring-lime-500 focus:ring-2">
          <span class="ml-2">StdInChIKey</span>
        </label>
        <label class="inline-flex items-center">
          <input wire:model.live.debounce.100ms="searchType" type="radio" name="searchType" value="code" class="text-lime-600 border-gray-300 rounded-sm focus:ring-lime-500 focus:ring-2">
          <span class="ml-2">NORMAN SusDat ID</span>
        </label>
      </div>
    </div>
    <div class="mt-2 text-sm text-gray-500 space-y-1">
      <p>Append <code class="font-mono bg-gray-100 px-1 rounded-sm">%</code> to search names starting with your term, e.g. <code class="font-mono bg-gray-100 px-1 rounded-sm">Mercury%</code> finds "Mercury chloride" but not "Dimethylmercury".</p>
      <p>Limited to 50 results. Mode: {{ str_ends_with($search, '%') ? 'starts with' : 'contains' }}.</p>
    </div>
    <div>
      @foreach ($results as $result)
      <div class="block p-1">
        <label class="flex items-center cursor-pointer">
          <input type="checkbox" name="substancesSearch[]" value="{{$result->id}}" class="text-lime-600 border-gray-300 rounded-sm focus:ring-lime-500 focus:ring-2">
          <div class="ml-2 grow">
            <div class="font-medium">{{$result->name}}</div>
            <div class="text-sm text-gray-500">
              @if($result->cas_number)
                CAS: {{ $result->cas_number }}
              @endif
              @if($result->code)
                @if($result->cas_number) | @endif
                NORMAN SusDat ID: NS{{ $result->code }}
              @endif
              @if($result->stdinchikey)
                @if($result->cas_number || $result->code) | @endif
                {{ $result->stdinchikey }}
              @endif
            </div>
          </div>
        </label>
      </div>
      @endforeach
      @if($resultsAvailable == true)
      @if($results->count() > 0)
      <div class="flex justify-end m-2">
        <button type="submit" class="btn-submit"> Apply Substance Filter</button>
      </div>
      @else
      <div class="flex justify-end m-2">
        <span class="text-red-500"> No substances found</span>
      </div>
      @endif
      @endif
    </div>
  </form>
</div>
