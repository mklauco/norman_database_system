<x-app-layout>
  <x-slot name="header">
    @include('literature.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <!-- Header -->
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Species</h2>
            <div class="flex space-x-3">
              <a href="{{ route('literature.species.download') }}" class="btn-submit">
                Download CSV
              </a>
              <a href="{{ route('literature.species.create') }}" class="btn-create">
                Add New Species
              </a>
            </div>
          </div>

          <!-- Success Message -->
          @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-md">
              {{ session('success') }}
            </div>
          @endif

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="table-standard w-full">
              <thead>
                <tr class="bg-gray-600 text-white">
                  <th class="py-2 px-4 text-left">ID</th>
                  <th class="py-2 px-4 text-left">Name</th>
                  <th class="py-2 px-4 text-left">Latin Name</th>
                  <th class="py-2 px-4 text-left">Kingdom</th>
                  <th class="py-2 px-4 text-left">Phylum</th>
                  <th class="py-2 px-4 text-left">Class</th>
                  <th class="py-2 px-4 text-left">Order</th>
                  <th class="py-2 px-4 text-left">Genus</th>
                  <th class="py-2 px-4 text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($species as $index => $sp)
                  <tr class="hover:bg-slate-300 transition {{ $index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-200' }}">
                    <td class="py-2 px-4">
                      <span class="font-mono text-xs font-semibold text-gray-800">{{ $sp->id }}</span>
                    </td>
                    <td class="py-2 px-4">
                      <span class="font-medium text-gray-900">{{ $sp->name ?: 'N/A' }}</span>
                    </td>
                    <td class="py-2 px-4">
                      <span class="text-sm text-gray-700 italic">{{ $sp->name_latin ?: 'N/A' }}</span>
                    </td>
                    <td class="py-2 px-4">
                      <span class="text-sm text-gray-700">{{ $sp->kingdom ?: 'N/A' }}</span>
                    </td>
                    <td class="py-2 px-4">
                      <span class="text-sm text-gray-700">{{ $sp->phylum ?: 'N/A' }}</span>
                    </td>
                    <td class="py-2 px-4">
                      <span class="text-sm text-gray-700">{{ $sp->class ?: 'N/A' }}</span>
                    </td>
                    <td class="py-2 px-4">
                      <span class="text-sm text-gray-700">{{ $sp->order ?: 'N/A' }}</span>
                    </td>
                    <td class="py-2 px-4">
                      <span class="text-sm text-gray-700">{{ $sp->genus ?: 'N/A' }}</span>
                    </td>
                    <td class="py-2 px-4 text-center">
                      <div class="flex justify-center space-x-2">
                        <a href="{{ route('literature.species.edit', $sp) }}" class="text-gray-600 hover:text-gray-900 text-sm px-2 py-1">
                          Edit
                        </a>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr class="bg-slate-100">
                    <td colspan="9" class="py-6 px-4 text-center text-gray-500">
                      <p class="text-base">No species found</p>
                      <p class="text-sm mt-1">Click "Add New Species" to create one.</p>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="mt-4">
            {{ $species->links('pagination::tailwind') }}
          </div>

          <div class="mt-2 text-sm text-gray-700 text-center">
            @if($species->total() > 0)
              Showing {{ $species->firstItem() }} to {{ $species->lastItem() }} of {{ $species->total() }} species
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
