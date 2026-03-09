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
            This module will provide hazard and property information sourced from CompTox API integrations.
          </p>

          <h2 class="text-lg font-bold text-gray-800 mb-2">Current status</h2>
          <ul class="list-disc list-inside text-gray-700 mb-4">
            <li>Hazards API fetch and parse pipeline is available for manual triggering.</li>
            <li>Module UI and search pages will be added in next steps.</li>
          </ul>

          <p class="text-gray-700 leading-relaxed">
            This is a placeholder home page following the Ecotoxicology module structure.
          </p>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
