<x-app-layout>
  <div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb Navigation -->
    <nav class="mb-6">
      <ol class="flex items-center space-x-2 text-sm text-gray-500">
        <li>
          <a href="{{ route('ecotox.lowestpnec.index') }}" class="link-lime-text hover:text-lime-700">
            ← Back to Lowest PNEC Database
          </a>
        </li>
        <li>
          <span class="mx-2">/</span>
        </li>
        <li class="text-gray-800 font-medium">
          Record Details - {{ $substance->prefixed_code ?? 'Unknown' }}
        </li>
      </ol>
    </nav>

    <div>
      <!-- Primary information -->
      <div class="grid grid-cols-3 gap-4">
        <div class="col-span-1">
          <div class="mb-4">
            <a href="{{ route('ecotox.lowestpnec.index') }}"
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
              ← Go Back to Database
            </a>
          </div>
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
              Lowest PNEC Record Details
            </h1>
            <p class="text-gray-700">Viewing record for {{ $substance->name ?? 'Unknown Substance' }}</p>
          </div>
        </div>
        <div class="col-span-2">
          <div class="mb-6 bg-white border border-gray-200 rounded-lg p-6 shadow-xs">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Substance Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Norman SusDat ID</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $substance->prefixed_code ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Substance Name</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $substance->name ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">CAS Number</h3>
                <p class="text-sm text-teal-800 font-mono">{{ $substance->cas_number ?? 'N/A' }}</p>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">Data Type</h3>
                <p class="text-sm text-teal-800 font-mono">
                  {{ $lowestPnec->lowest_exp_pred == 1 ? 'Experimental' : 'Predicted' }}
                </p>
              </div>
              @if($lowestPnecMain)
              <div>
                <h3 class="text-sm font-medium text-gray-800 mb-1">NORMAN PNEC ID</h3>
                <p class="text-sm text-teal-800 font-mono">
                  {{ $lowestPnecMain->der_id ? 'NORMAN PNEC ' . $lowestPnecMain->der_id : ($pnec3->norman_pnec_id ?? 'N/A') }}
                </p>
              </div>
              @endif
            </div>
          </div>
        </div>
      </div>

      <!-- Detailed Information Table -->
      <div class="bg-white border border-gray-200 rounded-lg shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full border border-gray-300 text-sm">
            <thead>
              <tr class="bg-gray-100">
                <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                  Parameter Name
                </th>
                <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">
                  Value
                </th>
              </tr>
            </thead>
            <tbody>
              <!-- PNEC Values Section -->
              <tr class="bg-gray-50">
                <td colspan="2" class="border border-gray-300 px-3 py-2 font-semibold text-center text-gray-800 bg-lime-100">
                  PNEC Values
                </td>
              </tr>
              
              @if($lowestPnecMain && $lowestPnecMain->lowest_pnec_value)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Lowest PNECfw [µg/l]</td>
                <td class="border border-gray-300 px-3 py-2 font-semibold">{{ $lowestPnecMain->lowest_pnec_value }}</td>
              </tr>
              @endif
              
              @if($lowestPnec->lowest_pnec_value_1)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Freshwater PNECfw [µg/l]</td>
                <td class="border border-gray-300 px-3 py-2 font-semibold">{{ $lowestPnec->lowest_pnec_value_1 }}</td>
              </tr>
              @endif
              
              @if($lowestPnec->lowest_pnec_value_2)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Marine water PNECmarine [µg/l]</td>
                <td class="border border-gray-300 px-3 py-2 font-semibold">{{ $lowestPnec->lowest_pnec_value_2 }}</td>
              </tr>
              @endif
              
              @if($lowestPnec->lowest_pnec_value_3)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Sediments [µg/kg dw]</td>
                <td class="border border-gray-300 px-3 py-2 font-semibold">{{ $lowestPnec->lowest_pnec_value_3 }}</td>
              </tr>
              @endif
              
              @if($lowestPnec->lowest_pnec_value_4)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Biota (fish) [µg/kg ww]</td>
                <td class="border border-gray-300 px-3 py-2 font-semibold">{{ $lowestPnec->lowest_pnec_value_4 }}</td>
              </tr>
              @endif
              
              @if($lowestPnec->lowest_pnec_value_5)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Marine biota (fish) [µg/kg ww]</td>
                <td class="border border-gray-300 px-3 py-2 font-semibold">{{ $lowestPnec->lowest_pnec_value_5 }}</td>
              </tr>
              @endif
              
              @if($lowestPnec->lowest_pnec_value_6)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Biota (mollusc) [µg/kg ww]</td>
                <td class="border border-gray-300 px-3 py-2 font-semibold">{{ $lowestPnec->lowest_pnec_value_6 }}</td>
              </tr>
              @endif
              
              @if($lowestPnec->lowest_pnec_value_7)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Marine biota (mollusc) [µg/kg ww]</td>
                <td class="border border-gray-300 px-3 py-2 font-semibold">{{ $lowestPnec->lowest_pnec_value_7 }}</td>
              </tr>
              @endif
              
              @if($lowestPnec->lowest_pnec_value_8)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Biota (WFD) [µg/kg ww]</td>
                <td class="border border-gray-300 px-3 py-2 font-semibold">{{ $lowestPnec->lowest_pnec_value_8 }}</td>
              </tr>
              @endif

              <!-- Detailed Information Section -->
              <tr class="bg-gray-50">
                <td colspan="2" class="border border-gray-300 px-3 py-2 font-semibold text-center text-gray-800 bg-lime-100">
                  Detailed Information
                </td>
              </tr>

              @if($pnec3 && $pnec3->norman_dataset_id)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">NORMAN Dataset ID</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->norman_dataset_id }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->data_source_name)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Data source name</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->data_source_name }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->data_source_link)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Data source link</td>
                <td class="border border-gray-300 px-3 py-2">
                  @if(filter_var($pnec3->data_source_link, FILTER_VALIDATE_URL))
                    <a href="{{ $pnec3->data_source_link }}" target="_blank" class="link-lime-text">{{ $pnec3->data_source_link }}</a>
                  @else
                    {{ $pnec3->data_source_link }}
                  @endif
                </td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->data_source_id)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Data source ID</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->data_source_id }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->study_title)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Study title</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->study_title }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->authors)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Author(s)</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->authors }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->year)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Date</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->year }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->bibliographic_source)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Bibliographic source</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->bibliographic_source }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->dossier_available)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Dossier available?</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->dossier_available }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->country_or_region)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Country or Region</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->country_or_region }}</td>
              </tr>
              @endif

              @if($lowestPnecMain && $lowestPnecMain->lowest_institution)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Institution (PNEC)</td>
                <td class="border border-gray-300 px-3 py-2">{{ $lowestPnecMain->lowest_institution }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->matrix_habitat)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Environmental medium</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->matrix_habitat }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->legal_status)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Legal status</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->legal_status }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->protected_asset)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Protected asset</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->protected_asset }}</td>
              </tr>
              @endif

              @if($lowestPnecMain && $lowestPnecMain->lowest_pnec_type)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">PNEC type</td>
                <td class="border border-gray-300 px-3 py-2">{{ $lowestPnecMain->lowest_pnec_type }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->pnec_type_country)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">PNEC type (country specific name)</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->pnec_type_country }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->monitoring_frequency)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Monitoring frequency</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->monitoring_frequency }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->taxonomic_group)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Taxonomic group</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->taxonomic_group }}</td>
              </tr>
              @endif

              @if($lowestPnecMain && $lowestPnecMain->lowest_AF)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Applied AF</td>
                <td class="border border-gray-300 px-3 py-2">{{ $lowestPnecMain->lowest_AF }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->justification)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Justification</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->justification }}</td>
              </tr>
              @endif

              @if($lowestPnecMain && $lowestPnecMain->lowest_derivation_method)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Derivation method</td>
                <td class="border border-gray-300 px-3 py-2">{{ $lowestPnecMain->lowest_derivation_method }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->ecotox_id)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Biotest ID</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->ecotox_id }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->remarks)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Remarks</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->remarks }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->reliability_study)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Reliability of the key study</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->reliability_study }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->reliability_score)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Reliability score system used</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->reliability_score }}</td>
              </tr>
              @endif

              @if($pnec3 && $pnec3->institution_study)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Institution (key study)</td>
                <td class="border border-gray-300 px-3 py-2">{{ $pnec3->institution_study }}</td>
              </tr>
              @endif

              @if($lowestPnecMain && $lowestPnecMain->lowest_year)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Date</td>
                <td class="border border-gray-300 px-3 py-2">{{ \Carbon\Carbon::parse($lowestPnecMain->lowest_year)->format('d.m.Y') }}</td>
              </tr>
              @endif

              @if($lowestPnecMain && $lowestPnecMain->lowest_sum_vote)
              <tr class="bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Sum of votes</td>
                <td class="border border-gray-300 px-3 py-2">{{ $lowestPnecMain->lowest_sum_vote }}</td>
              </tr>
              @endif

              @if($lowestPnecMain && $lowestPnecMain->editor)
              <tr>
                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">Expert</td>
                <td class="border border-gray-300 px-3 py-2">{{ $lowestPnecMain->editor->name }}</td>
              </tr>
              @endif

            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Go Back Button -->
    <div class="mt-6 flex justify-center">
      <a href="{{ route('ecotox.lowestpnec.index') }}" 
         class="inline-flex items-center px-6 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
        ← Go Back to Database
      </a>
    </div>
  </div>
</x-app-layout>
