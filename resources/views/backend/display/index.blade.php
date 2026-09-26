<x-app-layout>
  <x-slot name="header">
    @include('backend.system-settings.header')
  </x-slot>

  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">

          @if (session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
              {{ session('success') }}
            </div>
          @endif

          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-medium text-gray-900">Display Configuration</h3>
          </div>

          <p class="text-sm text-gray-600 mb-6">
            Configure how records are displayed in the "Show" views for each module.
            Select a module to manage its sections and columns.
          </p>

          @if($modules->count() > 0)
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Module</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sections</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  @foreach($modules as $module)
                    <tr>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $module->name }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                        {{ $module->code }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($module->display_sections_count > 0)
                          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            {{ $module->display_sections_count }} sections
                          </span>
                        @else
                          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                            Not configured
                          </span>
                        @endif
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        @if($module->display_sections_count > 0)
                          <a href="{{ route('backend.display.sections', $module->code) }}"
                             class="text-teal-600 hover:text-teal-900">
                            Manage Sections
                          </a>
                        @else
                          <span class="text-gray-400">No sections</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="text-center py-8">
              <p class="text-gray-500">No modules found.</p>
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
