<x-app-layout>
  <x-slot name="header">
    @include('sle.header')
  </x-slot>
  
  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <!-- Title -->
          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            NORMAN Suspect List Exchange – Database Management (Development Only)
          </h1>
          
          <!-- Warning -->
          <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
            <div class="flex">
              <div class="shrink-0">
                <i class="fa fa-exclamation-triangle text-yellow-400"></i>
              </div>
              <div class="ml-3">
                <p class="text-sm text-yellow-700">
                  <strong>Development Interface:</strong> This page is for internal development and database management purposes only. 
                  Regular users should use the <a href="{{ route('sle.sources.index') }}" class="link-lime-text">main SLE interface</a>.
                </p>
              </div>
            </div>
          </div>

          <!-- Description -->
          <p class="text-gray-700 leading-relaxed mb-4">
            This interface allows administrators to manage the local database copy of SLE data. 
            The main SLE interface displays data directly from the online source in real-time.
          </p>

          <!-- Navigation -->
          <div class="mb-4 flex justify-between items-center">
            <a href="{{ route('sle.sources.index') }}" class="btn-submit">
              <i class="fa fa-arrow-left mr-2"></i>
              Back to Main SLE Interface
            </a>
            
            <div class="flex space-x-2">
              <a href="{{ route('sle.sources.refresh') }}" class="btn-submit" onclick="return confirm('Are you sure you want to refresh the data from the online source?')">
                <i class="fa fa-refresh mr-2"></i>
                Refresh from Online Source
              </a>
              <a href="{{ route('sle.sources.create') }}" class="btn-create">
                <i class="fa fa-plus mr-2"></i>
                Add new source
              </a>
            </div>
          </div>

          <!-- Database Data Table -->
          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
                <th>ID</th>
                <th>Code</th>
                <th>Name</th>
                <th>Description</th>
                <th>Link Full List</th>
                <th>Link InChI Key List</th>
                <th>Link References</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($sleSources as $sle)
              <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif">
                <td class="p-1 text-center">
                  {{ $sle->id }}
                </td>
                <td class="p-1 text-center">
                  {{ $sle->code }}
                </td>
                <td class="p-1 text-center">
                  {{ $sle->name }}
                </td>
                <td class="p-1 text-center">
                  {{ $sle->description }} 
                </td>
                <td class="p-1 text-left">
                  @if($sle->link_full_list)
                    <div class="prose max-w-none">
                      {!! preg_replace('/<a\s+href=/', '<a class="link-lime-text" href=', $sle->link_full_list) !!}
                    </div>
                  @else
                    <span class="text-gray-400">-</span>
                  @endif
                </td>
                <td class="p-1 text-left">
                  @if($sle->link_inchikey_list)
                    <div class="prose max-w-none">
                      {!! preg_replace('/<a\s+href=/', '<a class="link-lime-text" href=', $sle->link_inchikey_list) !!}
                    </div>
                  @else
                    <span class="text-gray-400">-</span>
                  @endif
                </td>
                <td class="p-1 text-left">
                  @if($sle->link_references)
                    <div class="prose max-w-none">
                      {!! preg_replace('/<a\s+href=/', '<a class="link-lime-text" href=', $sle->link_references) !!}
                    </div>
                  @else
                    <span class="text-gray-400">-</span>
                  @endif
                </td>
                <td class="p-1 text-center">
                  <div class="flex space-x-2 justify-center">
                    <a href="{{ route('sle.sources.show', $sle) }}" class="text-blue-600 hover:text-blue-900" title="View">
                      <i class="fa fa-eye"></i>
                    </a>
                    <a href="{{ route('sle.sources.edit', $sle) }}" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                      <i class="fa fa-edit"></i>
                    </a>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>

          <!-- Database Info -->
          <div class="mt-4 text-center text-sm text-gray-600">
            Database records: {{ count($sleSources) }}
          </div>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
