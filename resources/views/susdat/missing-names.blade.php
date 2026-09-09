<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <div class="mb-6 flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold">Substances Missing Names</h2>
              <p class="text-gray-600">{{ $substances->count() }} substances have a code but no name</p>
            </div>

            @if($substances->count() > 0)
              <form method="POST" action="{{ route('substances.fetch-missing-names') }}"
                    onsubmit="return confirm('This will fetch data from the NORMAN API for {{ $substances->count() }} substances. Continue?')">
                @csrf
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-lime-600 text-white text-sm font-medium rounded-md hover:bg-lime-700 focus:outline-none focus:ring-2 focus:ring-lime-500">
                  <i class="fas fa-download mr-2"></i>
                  Fetch from NORMAN API
                </button>
              </form>
            @endif
          </div>

          @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md">
              {{ session('success') }}
            </div>
          @endif

          @if(session('fetch_errors'))
            <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-md">
              <p class="font-semibold mb-1">Errors:</p>
              <ul class="list-disc list-inside text-sm">
                @foreach(session('fetch_errors') as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          @if($substances->count() > 0)
            <table class="table-standard">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="px-4 py-2">ID</th>
                  <th class="px-4 py-2">NORMAN SusDat ID</th>
                  <th class="px-4 py-2">CAS RN</th>
                  <th class="px-4 py-2">StdInChIKey</th>
                  <th class="px-4 py-2">Molecular Formula</th>
                  <th class="px-4 py-2">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($substances as $substance)
                <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                  <td class="px-4 py-2 font-mono">{{ $substance->id }}</td>
                  <td class="px-4 py-2 font-mono">NS{{ $substance->code }}</td>
                  <td class="px-4 py-2">{{ $substance->cas_number }}</td>
                  <td class="px-4 py-2 font-mono text-xs">{{ $substance->stdinchikey }}</td>
                  <td class="px-4 py-2">{{ $substance->molecular_formula }}</td>
                  <td class="px-4 py-2">
                    <a href="{{ route('substances.show', $substance->id) }}" class="link-lime-text">View</a>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          @else
            <p class="text-gray-500">All substances with codes have names assigned.</p>
          @endif

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
