<x-app-layout>
  <x-slot name="header">
    @include('empodat_suspect.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-[100rem] mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">EMPODAT Suspect Command Center</h2>
            <p class="text-gray-600">Queue the materialized-view refresh commands that keep search, exports, and prioritisation up to date.</p>
          </div>

          @livewire('empodat-suspect.command-center')

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
