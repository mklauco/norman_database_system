<x-app-layout>
  <x-slot name="header">
    @include('hazards.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            NORMAN Hazards and Properties Database
          </h1>

          <p class="text-gray-700 leading-relaxed mb-4">
            The Hazards module combines CompTox, JANUS, and PIKME substance data for search, review, and expert derivation workflows.
          </p>

          <p class="text-gray-700 leading-relaxed mb-4">
            Search and review of Hazards records is available under <span class="font-semibold">Search Data</span>.
            The derivation workspace for P, B, M, and T buckets is available under <span class="font-semibold">Derivation</span> for admin users.
          </p>

          <h2 class="text-lg font-bold text-gray-800 mb-2">Current status</h2>
          <ul class="list-disc list-inside text-gray-700">
            <li>Hazards API fetch, parse, and fill pipeline is available.</li>
            <li>JANUS and PIKME data are included in Hazards substance data.</li>
            <li>Derivation buckets are available through the Hazards navigation.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
