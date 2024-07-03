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

          <form action="{{route('duplicates.handleDuplicates')}}" method="POST">
            @csrf
          <div id="displaySubstancesDiv">
            @include('susdat.display-substances', ['show' => ['substances' => false, 'sources' => false, 'duplicates' => true] ])
          </div>
          <div class="flex justify-end mt-4">
            <button type="submit" class="btn-submit">Submit</button>
          </div>
          <div class="flex space-x-2 items-center mt-2">
            <span class="">
              Information from external sources:
            </span>
          </div>
          </form>

          <div class="bg-sky-200 shadow-md mt-5">
            <span class="text-sm font-bold"> Comptox Database: </span>
            @livewire('susdat.duplicate-load-comptox', ['dtxsid' => $dtxsIds])
          </div>

          <div class="bg-emerald-100 shadow-md mt-5">
            <span class="text-sm font-bold"> Pubchem Database: </span>
            @livewire('susdat.duplicate-load-pubchem', ['pubchemIds' => $pubchemIds])
          </div>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>