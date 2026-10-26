<x-app-layout>
  <x-slot name="header">
    @include('prioritisation.header')
  </x-slot>


  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <!-- Title -->
          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            NORMAN Prioritisation Results
          </h1>

          <!-- Introduction Section -->
          <div class="mb-8 text-gray-700">
            <p class="mb-4">This part of the NORMAN Database System presents results of prioritisation of:</p>

            <ol class="list-decimal list-outside ml-6 space-y-4">
              <li>
                <strong>Chemical occurrence data</strong> from <a href="{{ route('codsearch.filter') }}" class="link-lime-text">EMPODAT database</a> using <a href="https://www.norman-network.net/sites/default/files/files/Publications/NORMAN_prioritisation_Manual_15%20April2013_final%20for%20website-f.pdf" target="_blank" class="link-lime-text">NORMAN Prioritisation Framework</a>. The substances are ranked based on the availability and quality of occurrence data in <strong>six Categories</strong> and ranked within each category based on their <strong>Risk Score</strong> (<strong>Final score</strong>).
                <ol class="list-[lower-alpha] list-outside ml-6 mt-2 space-y-2">
                  <li>
                    <strong>NORMAN 2017</strong>: Europe-wide prioritisation of 966 <strong>NORMAN substances as of 25 July 2016</strong> – combined freshwater/marine water with data from 2009 – 2016.
                  </li>
                  <li>
                    <strong>DANUBE 2018</strong>: Prioritisation of the <strong>Danube River Basin Specific Pollutants</strong> (RBSPs) within the <a href="https://www.solutions-project.eu/" target="_blank" class="link-lime-text">SOLUTIONS project</a> <strong>as of 14 April 2018</strong> – data obtained from Joint Danube Surveys 1/2/3 and other monitoring data in the Water Quality Database of the <a href="http://www.icpdr.org/wq-db/" target="_blank" class="link-lime-text">ICPDR</a>.
                  </li>
                  <li>
                    <strong>SCARCE 2018</strong>: Prioritisation of the <strong>Iberian Peninsula River Basin Specific Pollutants</strong> (RBSPs) within the <a href="https://www.solutions-project.eu/" target="_blank" class="link-lime-text">SOLUTIONS project</a> <strong>as of 20 April 2018</strong> – data obtained from the <a href="https://www.idaea.csic.es/scarceconsolider/publica/P000Main.php" target="_blank" class="link-lime-text">SCARCE project</a>; Llobregat and Ebro River Basins.
                  </li>
                </ol>
              </li>
              <li>
                <strong>Simulated Predicted Environmental Concentrations</strong> (PEC) provided by the Model Train developed within the SOLUTIONS project (<a href="https://www.solutions-project.eu/results-products/#article-52" target="_blank" class="link-lime-text">https://www.solutions-project.eu/results-products/#article-52</a>). <strong>The NORMAN Prioritisation Framework Risk Score based on the Frequency of Exceedance and Extent of Exceedance of PNECs (the same as for Measured Environmental Concentrations (MEC)) was used</strong>.
                <p class="mt-2">A model based prioritisation of chemicals that can serve as one of the Lines of Evidence in an overall prioritisation exercise to define RBSPs.</p>
                <ol class="list-[lower-alpha] list-outside ml-6 mt-2 space-y-2">
                  <li><strong>DANUBE 2018</strong>: Simulations of the combined emission and fate and transport models were carried out for 1788 chemicals.</li>
                  <li><strong>SCARCE 2018</strong>: Simulations of the combined emission and fate and transport models were carried out for 1811 chemicals.</li>
                </ol>
              </li>
            </ol>
          </div>

          <!-- 2x2 Navigation Structure -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Monitoring Danube -->
            <div class="bg-slate-50 rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
              <h2 class="text-xl font-semibold text-gray-700 mb-3 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Monitoring Danube
              </h2>
              <p class="text-gray-600 mb-4">
                Explore monitoring data for the Danube river basin priority substances, including measurement data and exceedance scores.
              </p>
              <a href="{{ route('prioritisation.monitoring-danube.index') }}" class="inline-flex items-center px-4 py-2 bg-sky-600 text-white rounded-md hover:bg-sky-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                View Data
                <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
              </a>
            </div>
            
            <!-- Monitoring Scarce -->
            <div class="bg-slate-50 rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
              <h2 class="text-xl font-semibold text-gray-700 mb-3 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Monitoring Scarce
              </h2>
              <p class="text-gray-600 mb-4">
                Access monitoring data for the Scarce river basin priority substances, with detailed measurement and exceedance information.
              </p>
              <a href="{{ route('prioritisation.monitoring-scarce.index') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                View Data
                <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
              </a>
            </div>
            
            <!-- Modelling Danube -->
            <div class="bg-slate-50 rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
              <h2 class="text-xl font-semibold text-gray-700 mb-3 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                </svg>
                Modelling Danube
              </h2>
              <p class="text-gray-600 mb-4">
                Review modelling results for the Danube basin, including emissions estimates, corrections, and calculated priority scores.
              </p>
              <a href="{{ route('prioritisation.modelling-danube.index') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                View Models
                <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
              </a>
            </div>
            
            <!-- Modelling Scarce -->
            <div class="bg-slate-50 rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
              <h2 class="text-xl font-semibold text-gray-700 mb-3 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                </svg>
                Modelling Scarce
              </h2>
              <p class="text-gray-600 mb-4">
                Examine modelling results for the Scarce basin, including substance emissions, corrections, and priority scoring metrics.
              </p>
              <a href="{{ route('prioritisation.modelling-scarce.index') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                View Models
                <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
              </a>
            </div>
            
          </div>
          
        </div>
      </div>
    </div>
  </div>
  
</x-app-layout>