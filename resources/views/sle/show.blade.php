<x-app-layout>
  <x-slot name="header">
    @include('sle.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <!-- Header with Actions -->
          <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
              SLE Source Details
            </h1>
            <div class="flex space-x-2">
              <a href="{{ route('sle.sources.edit', $sleSource) }}" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:outline-hidden focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition">
                <i class="fa fa-edit mr-2"></i>
                Edit
              </a>
              <a href="{{ route('sle.sources.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-hidden focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                <i class="fa fa-list mr-2"></i>
                Back to List
              </a>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column - Basic Information -->
            <div class="space-y-6">
              <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                  <i class="fa fa-info-circle mr-2 text-blue-600"></i>
                  Basic Information
                </h3>
                
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Source ID</label>
                    <p class="mt-1 text-sm text-gray-900">#{{ $sleSource->id }}</p>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Code</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $sleSource->code ?? 'Not specified' }}</p>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $sleSource->name ?? 'Not specified' }}</p>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $sleSource->description ?? 'No description provided' }}</p>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Display Order</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $sleSource->order ?? 'Not specified' }}</p>
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <div class="mt-1">
                      @if($sleSource->show)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          Visible
                        </span>
                      @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                          Hidden
                        </span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>

              <!-- Timestamps -->
              <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                  <i class="fa fa-clock mr-2 text-gray-600"></i>
                  Timestamps
                </h3>
                
                <div class="space-y-3 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-600">Created:</span>
                    <span class="font-medium">{{ $sleSource->created_at ? $sleSource->created_at->format('M j, Y \a\t g:i A') : 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Last Updated:</span>
                    <span class="font-medium">{{ $sleSource->updated_at ? $sleSource->updated_at->format('M j, Y \a\t g:i A') : 'N/A' }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column - Link Information -->
            <div class="space-y-6">
              <!-- Link Full List -->
              <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                  <i class="fa fa-link mr-2 text-green-600"></i>
                  Full List Link
                </h3>
                <div>
                  @if($sleSource->link_full_list)
                    <div class="prose max-w-none">
                      {!! $sleSource->link_full_list !!}
                    </div>
                  @else
                    <p class="text-gray-500 italic">No full list link provided</p>
                  @endif
                </div>
              </div>

              <!-- Link InChI Key List -->
              <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                  <i class="fa fa-key mr-2 text-purple-600"></i>
                  InChI Key List Link
                </h3>
                <div>
                  @if($sleSource->link_inchikey_list)
                    <div class="prose max-w-none">
                      {!! $sleSource->link_inchikey_list !!}
                    </div>
                  @else
                    <p class="text-gray-500 italic">No InChI key list link provided</p>
                  @endif
                </div>
              </div>

              <!-- Link References -->
              <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                  <i class="fa fa-book mr-2 text-orange-600"></i>
                  References Link
                </h3>
                <div>
                  @if($sleSource->link_references)
                    <div class="prose max-w-none">
                      {!! $sleSource->link_references !!}
                    </div>
                  @else
                    <p class="text-gray-500 italic">No references link provided</p>
                  @endif
                </div>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
            <a href="{{ route('sle.sources.index') }}" class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-xs text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
              <i class="fa fa-arrow-left mr-2"></i>
              Back to List
            </a>
            <a href="{{ route('sle.sources.edit', $sleSource) }}" class="inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-xs text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition">
              <i class="fa fa-edit mr-2"></i>
              Edit Source
            </a>
          </div>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
