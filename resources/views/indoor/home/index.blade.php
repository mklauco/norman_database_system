<x-app-layout>
  <x-slot name="header">
    @include('indoor.header')
  </x-slot>
  
  
  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          <!-- Title -->
          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            NORMAN Indoor Environment Database
          </h1>
          
          <!-- Description -->
          <p class="text-gray-700 leading-relaxed mb-4">
            Indoor Environment Database is a database of geo-referenced monitoring on emerging substances coming from different types of indoor environment samples. The main purpose of using the database is to address the crucial issue of chemicals present in the indoor environment across Europe and beyond. Toxic chemicals may accumulate in indoor spaces without our awareness, leading to chronic exposure. Interconnection of this database with other NORMAN database modules has a significant role in unraveling the complexities of indoor exposure and its link to potential health risks.
          </p>
          
          <!-- Subheading -->
          <h2 class="text-lg font-bold text-gray-800 mb-2">
            How to submit data
          </h2>
          <hr class="border-t-2 border-lime-500 mb-4">
          
          <p class="text-gray-700 leading-relaxed mb-4">
            For information and conditions for the inclusion of your data in the NORMAN Database System, please contact <strong>Dr. Jaroslav SLOBODNIK</strong>.
          </p>
          <p class="text-gray-700 leading-relaxed mb-4">
            To include data into the NORMAN Database System, DATA COLLECTION TEMPLATE (DCT) in excel was developed for the indoor environment samples. This DCT can be downloaded at <a href="https://www.norman-network.com/nds/indoor/downloadDCT.php" class="link-lime-text">https://www.norman-network.com/nds/indoor/downloadDCT.php</a>.
          </p>
          <p class="text-gray-700 leading-relaxed mb-4">
            The completed DCT should be sent to the NORMAN Database development team: norman@ei.sk with copy to slobodnik@ei.sk, for further processing and upload to the web-database.
          </p>
          <p class="text-gray-700 leading-relaxed mb-4">
            Large datasets, available in other then excel format can be uploaded as well, after communication with the NORMAN team – to check the structure of data, availability of obligatory information and to agree on the optimal way of the data transfer.
          </p>
          
          
          
        </div>
        
        
      </div>
    </div>
  </div>
  
</x-app-layout>