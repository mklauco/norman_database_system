<x-app-layout>
  <x-slot name="header">
    @include('hazards.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-lg rounded-0">
        <form
          x-data="{
            loading: false,
            submitForm(event) {
              event.preventDefault();
              this.loading = true;
              event.target.submit();
            }
          }"
          @submit="submitForm($event)"
          name="searchHazardsDerivation"
          id="searchHazardsDerivation"
          action="{{ route('hazards.derivation.search.search') }}"
          method="GET">

          <div class="p-4 text-gray-900 grid grid-cols-1 gap-4">
            <div class="bg-gray-100 p-2">
              <div class="font-bold mb-2">Substance:</div>
              @livewire('hazards.substance-search', ['existingSubstances' => $request->substances, 'formId' => 'searchHazardsDerivation'])
            </div>

            <div class="flex justify-end m-2">
              <a href="{{ route('hazards.derivation.search.filter') }}" class="btn-clear mx-2">Reset</a>
              <button type="submit" class="btn-submit" :disabled="loading" :class="{ 'opacity-50 cursor-not-allowed': loading }">
                <span x-text="loading ? 'Opening...' : 'Open Derivation'"></span>
              </button>
            </div>

            <div class="m-2">
              <ul class="list-disc list-inside text-gray-700 text-sm">
                <li>Select one Hazards substance to open the derivation workspace.</li>
                <li>The derivation page groups data into P, B, M, and T buckets with Predicted and Experimental sections.</li>
                <li>Shared votes are visible to all admins and super admins.</li>
              </ul>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</x-app-layout>
