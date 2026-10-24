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
            <h2 class="text-xl font-semibold text-gray-800">Tissues</h2>
            <div class="flex space-x-3">
              <a href="{{ route('literature.tissues.download_categories') }}" class="btn-submit">
                Download Categories CSV
              </a>
              <a href="{{ route('literature.tissues.download_subcategories') }}" class="btn-submit">
                Download Subcategories CSV
              </a>
              <a href="{{ route('literature.tissues.create') }}" class="btn-create">
                Add New Tissue
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
                  <th class="py-2 px-4 text-left">Subcategories</th>
                  <th class="py-2 px-4 text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($tissues as $index => $tissue)
                  <tr class="hover:bg-slate-300 transition {{ $index % 2 === 0 ? 'bg-slate-100' : 'bg-slate-200' }}">
                    <td class="py-2 px-4">
                      <span class="font-mono text-xs font-semibold text-gray-800">{{ $tissue->id }}</span>
                    </td>
                    <td class="py-2 px-4">
                      <span class="font-medium text-gray-900">{{ $tissue->name }}</span>
                    </td>
                    <td class="py-2 px-4">
                      <span class="text-sm text-gray-700">
                        @if($tissue->subcategories && $tissue->subcategories->count() > 0)
                          {{ $tissue->subcategories->pluck('name')->implode('; ') }}
                        @else
                          <span class="text-gray-400 italic">No subcategories</span>
                        @endif
                      </span>
                    </td>
                    <td class="py-2 px-4 text-center">
                      <div class="flex justify-center space-x-2">
                        <a href="{{ route('literature.tissues.edit', $tissue) }}" class="text-gray-600 hover:text-gray-900 text-sm px-2 py-1">
                          Edit
                        </a>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr class="bg-slate-100">
                    <td colspan="4" class="py-6 px-4 text-center text-gray-500">
                      <p class="text-base">No tissues found</p>
                      <p class="text-sm mt-1">Click "Add New Tissue" to create one.</p>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="mt-4">
            {{ $tissues->links('pagination::tailwind') }}
          </div>

          <div class="mt-2 text-sm text-gray-700 text-center">
            @if($tissues->total() > 0)
              Showing {{ $tissues->firstItem() }} to {{ $tissues->lastItem() }} of {{ $tissues->total() }} tissues
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
