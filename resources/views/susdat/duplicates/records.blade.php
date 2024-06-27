<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <div class="flex space-x-2 items-center">
            <span>Duplicate records for: <span class="font-bold">{{$pivot}}: {{$pivot_value}}</span></span>
          </div>

          <div id="displaySubstancesDiv">
            @include('susdat.display-substances')
          </div>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>