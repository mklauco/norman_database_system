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
            NORMAN Ecotoxicology Database
          </h1>
          
          <!-- Description -->
          <p class="text-gray-700 leading-relaxed mb-4">
            The Ecotoxicology Database contains – for substances listed in the NORMAN Substance Database – experimental endpoints from ecotox tests as well as quality targets from different regulatory contexts.
          </p>
          
          <!-- Subheading -->
          <h2 class="text-lg font-bold text-gray-800 mb-2">The EMPODAT Database consists of:</h2>
          
          <p class="text-gray-700 leading-relaxed mb-4">
            The term Lowest PNECs refers to quality targets which are suggested by experts for prioritisation purposes. They are obtained experimentally or predicted by QSAR models. The Ecotoxicology Database provides a transparent tool to help experts in:
          </p>
          
          <!-- Bulleted List -->
          <ul class="list-disc list-inside text-gray-700 mb-4">
            <li>the identification of the reliable ecotoxicity studies, based on the CRED (Criteria for Reporting and Evaluating ecotoxicity Data) classification system;</li>              
            <li>the online derivation of Quality Targets for each matrix and regulatory framework based on selected ‘reliable’ ecotoxicity studies, using a built-in software tool implementing the requirements of the EC guidelines;</li>
            <li>the compilation of all existing Quality Targets from different regulatory frameworks;</li>
            <li>the final selection of the Lowest PNEC value for substance prioritisation purposes, agreed upon as a result of Europe-wide expert consultations. </li>
          </ul>
          
          <!-- Additional Information -->
          <p class="text-gray-700 leading-relaxed mb-4">
            No. of substances in the database:
          </p>
          <ul class="list-disc list-inside text-gray-700 mb-4">
            <li>No. of substances with Lowest PNEC: <strong>93 579</strong></li>
            <li>No. of substances with verified(*) Lowest PNEC: <strong>3 290</strong></li>
            <li>No. of experimental endpoint values: <strong>81 430</strong></li>
            {{-- <li>No. of substances with experimental data: <strong>2 197</strong></li> --}}
          </ul>
          
          
        </div>
        
        
      </div>
    </div>
  </div>
  
</x-app-layout>