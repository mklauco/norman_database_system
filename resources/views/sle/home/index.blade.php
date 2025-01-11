<x-app-layout>
  <x-slot name="header">
    @include('ecotox.header')
  </x-slot>
  
  
  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <!-- Title -->
          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            NORMAN Suspect List Exchange – NORMAN SLE
          </h1>
          
          <!-- Description -->
          <p class="text-gray-700 leading-relaxed mb-4">
            The NORMAN Suspect List Exchange (NORMAN-SLE) was established in 2015 as a central access point for NORMAN members (and others) to find suspect lists relevant for their environmental monitoring questions. The NORMAN-SLE documents all individual collections that form a part of the merged collection NORMAN SusDat. The original SLE lists should be consulted to verify SusDat information if necessary (see Source column in SusDat). NORMAN-SLE versions are tracked on Zenodo. 
          </p>

          <p class="text-gray-700 leading-relaxed mb-4">
            NEW: Check out our NORMAN-SLE publication in ESEU @ DOI: 10.1186/s12302-022-00680-6 
          </p>
          
          <p class="text-gray-700 leading-relaxed mb-4">
            Comments and contributions are welcome - please email us at suspects@normandata.eu.
          </p>

          <p class="text-gray-700 leading-relaxed mb-4">
            Please refer to our documentation pages for: citation instructions, credits, updates, license details, SDFs and other useful tips!
          </p>
          

          <table class="table-standard">
            <thead>
              <tr class="bg-gray-600 text-white">
                <th>ID</th>
                <th>Code</th>
                <th>Name</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($sleSources as $sle)
              <tr class="@if($loop->odd) bg-slate-100 @else bg-slate-200 @endif ">
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
                <td class="p-1 text-center">
                  {{-- <a href="{{ route('sle.show', $sle->id) }}" class="text-blue-500 hover:text-blue-700">Edit</a> --}}
                  <a href="" class="link-lime">Edit <i class="fa fas-lock"></i></a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>

        </div>
        
        
      </div>
    </div>
  </div>
  
</x-app-layout>