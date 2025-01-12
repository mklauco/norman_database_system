<x-app-layout>
  <x-slot name="header">
    @include('passive.header')
  </x-slot>
  
  
  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <!-- Title -->
          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            NORMAN Passive Sampling Database
          </h1>
          
          <!-- Description -->
          <p class="text-gray-700 leading-relaxed mb-4">
            Passive Sampling is based on free flow of analyte molecules from the sampled medium to a collecting medium as a result of a difference in chemical potentials. It can be used for the determination of both inorganic and organic compounds in a variety of matrices, including air, water and soil. At this stage, DCT consists of data from passive samplers and is focused on to bring the data from water (surface, ground, lake, sea......) and air.
          </p>
          <p class="text-gray-700 leading-relaxed mb-4">
            DCT for Passive sampling is designed to:
          </p>
          <ul class="list-disc list-inside text-gray-700 mb-4">
            <li>record information about detected compounds with details on GPS location, exposure time, type of samplers, and estimation on free concentration in the selected matrices, </li>
            <li>access to the information about pollutants from dynamic or static samplers,</li>
            <li>data from regulatory monitoring, surveys, domestic or international campaigns, projects etc.,</li>
            <li>wide scope screening of different groups of compounds with different polarities, and physical-chemical properties.</li>
          </ul>

          
          <!-- Subheading -->
          <h2 class="text-lg font-bold text-gray-800 mb-2">
            How to submit data
          </h2>
          <hr class="border-t-2 border-lime-500 mb-4">
          
          <p class="text-gray-700 leading-relaxed mb-4">
            For information and conditions for the inclusion of your data in the NORMAN Database System, please contact <strong>Dr. Jaroslav SLOBODNIK</strong>.
          </p>
          <p class="text-gray-700 leading-relaxed mb-4">
            To include data into the NORMAN database, DATA COLLECTION TEMPLATES (DCT) in excel was developed. These DCTs can be downloaded at <a href="https://www.norman-network.com/nds/passive" class="link-lime-text">https://www.norman-network.com/nds/passive</a>.
          </p>
          <p class="text-gray-700 leading-relaxed mb-4">
            Large datasets, available in other then excel format can be uploaded as well, after communication with the NORMAN team – to check the structure of data, availability of obligatory information and to agree on the optimal way of the data transfer.
          </p>
          
          
          
        </div>
        
        
      </div>
    </div>
  </div>
  
</x-app-layout>