<x-app-layout>
  <x-slot name="header">
    @include('microplastics.header')
  </x-slot>

  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            NORMAN Microplastics Database
          </h1>

          <p class="text-gray-700 leading-relaxed mb-4">
            Microplastics Database is a specialised platform for storing and sharing microplastics (MPs) analytical data across various environmental matrices. Within the module, microplastics are defined as synthetic polymer particles smaller than 5 mm. Analytical results can be searched and filtered based on the methodology applied:
          </p>
          <ul class="list-disc pl-6 text-gray-700 leading-relaxed mb-4 space-y-1">
            <li><span class="font-semibold">Visual methods</span> (e.g. naked eye, electron/optical microscopy) – particle numbers, shapes, colours and sizes.</li>
            <li><span class="font-semibold">Spectroscopic methods</span> (e.g. FTIR, Raman microscopy) – particle numbers, shapes, colours, sizes and polymer category.</li>
            <li><span class="font-semibold">Thermoanalytical methods</span> (e.g. Py-GC/MS, TED-GC/MS) – total polymer mass fractions.</li>
          </ul>

          <h2 class="text-lg font-bold text-gray-800 mb-2">
            Matrices covered
          </h2>
          <hr class="border-t-2 border-lime-500 mb-4">
          <p class="text-gray-700 leading-relaxed mb-4">
            Surface water, waste water, SPM (suspended particulate matter) and macrozoobenthos.
          </p>

          <h2 class="text-lg font-bold text-gray-800 mb-2">
            How to submit data
          </h2>
          <hr class="border-t-2 border-lime-500 mb-4">
          <p class="text-gray-700 leading-relaxed mb-4">
            Data Collection Templates (DCT) for the individual methods and matrices are being prepared. Once released, they will be available on the <a href="{{ route('templates.specific.index', ['code' => 'microplastics']) }}" class="link-lime-text">DCT Download</a> page of this module.
          </p>
          <p class="text-gray-700 leading-relaxed mb-4">
            Data for visual and spectroscopic methods are sent to the NORMAN (EI) team in XLS or CSV format.
          </p>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
