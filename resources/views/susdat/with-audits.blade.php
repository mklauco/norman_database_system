<x-app-layout>
  <x-slot name="header">
    @include('susdat.header')
  </x-slot>
  
  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <div class="mb-6">
            <h2 class="text-xl font-semibold">Substances with Audit History</h2>
            <p class="text-gray-600">{{ $substances->total() }} substances have audit records</p>
          </div>

          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
                <th class="px-4 py-2">ID</th>
                <th class="px-4 py-2">Code</th>
                <th class="px-4 py-2">Name</th>
                <th class="px-4 py-2">Audit Count</th>
                <th class="px-4 py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($substances as $substance)
              <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                <td class="px-4 py-2">{{ $substance->id }}</td>
                <td class="px-4 py-2">NS{{ $substance->code }}</td>
                <td class="px-4 py-2">{{ $substance->name }}</td>
                <td class="px-4 py-2 text-center">
                  <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                    {{ $substance->audits_count }}
                  </span>
                </td>
                <td class="px-4 py-2">
                  <div class="flex space-x-2">
                    <a href="{{ route('substances.show', $substance) }}" class="btn-link-lime">View</a>
                    <a href="{{ route('substances.audits', $substance) }}" class="btn-link-lime">Audits</a>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
          
          {{ $substances->links() }}
          
        </div>
      </div>
    </div>
  </div>
</x-app-layout>