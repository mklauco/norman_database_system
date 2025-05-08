<!-- Modal Window -->
<div class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50" x-show="showModal" x-transition>
  <div class="bg-white w-11/12 md:w-3/4 lg:w-3/4 xl:w-2/3 rounded shadow-lg relative" x-trap.inert="showModal">
    <!-- Modal Header -->
    <div class="flex justify-between items-center border-b px-4 py-2 bg-lime-600 text-white">
      <h3 class="text-lg font-semibold">Lowest PNEC Record: <span x-text="record?.id"></span></h3>
      <button @click="closeModal()" class="text-white hover:text-gray-200 text-xl">
        &times;
      </button>
    </div>
    
    <!-- Modal Content -->
    <div class="p-4 max-h-[70vh] overflow-y-auto">
      <!-- Substance Information -->
      <div class="mb-4">
        <h4 class="text-lg font-semibold mb-2 border-b pb-1 bg-gray-100 px-2">Substance Information</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 px-2">
          <div>
            <p class="font-semibold">SusDat ID:</p>
            <p x-text="record?.sus_id"></p>
          </div>
          <div>
            <p class="font-semibold">Substance:</p>
            <p x-text="record?.substance?.name"></p>
          </div>
        </div>
      </div>
      
      <!-- PNEC Values -->
      <div class="mb-4">
        <h4 class="text-lg font-semibold mb-2 border-b pb-1 bg-gray-100 px-2">PNEC Values</h4>
        <div class="overflow-x-auto px-2">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-100">
                <th class="p-2 text-left">Parameter</th>
                <th class="p-2 text-right">Value</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="p-2 text-left">Lowest PNECfw [µg/l]</td>
                <td class="p-2 text-right font-semibold" x-text="record?.main_record?.lowest_pnec_value"></td>
              </tr>
              <tr class="bg-gray-50">
                <td class="p-2 text-left">Freshwater PNECfw [µg/l]</td>
                <td class="p-2 text-right font-semibold" x-text="record?.lowest_pnec_value_1"></td>
              </tr>
              <tr>
                <td class="p-2 text-left">Marine water PNECmarine [µg/l]</td>
                <td class="p-2 text-right font-semibold" x-text="record?.lowest_pnec_value_2"></td>
              </tr>
              <tr class="bg-gray-50">
                <td class="p-2 text-left">Sediments [µg/kg dw]</td>
                <td class="p-2 text-right font-semibold" x-text="record?.lowest_pnec_value_3"></td>
              </tr>
              <tr>
                <td class="p-2 text-left">Biota (fish) [µg/kg ww]</td>
                <td class="p-2 text-right font-semibold" x-text="record?.lowest_pnec_value_4"></td>
              </tr>
              <tr class="bg-gray-50">
                <td class="p-2 text-left">Marine biota (fish) [µg/kg ww]</td>
                <td class="p-2 text-right font-semibold" x-text="record?.lowest_pnec_value_5"></td>
              </tr>
              <tr>
                <td class="p-2 text-left">Biota (mollusc) [µg/kg ww]</td>
                <td class="p-2 text-right font-semibold" x-text="record?.lowest_pnec_value_6"></td>
              </tr>
              <tr class="bg-gray-50">
                <td class="p-2 text-left">Marine biota (mollusc) [µg/kg ww]</td>
                <td class="p-2 text-right font-semibold" x-text="record?.lowest_pnec_value_7"></td>
              </tr>
              <tr>
                <td class="p-2 text-left">Biota (WFD) [µg/kg ww]</td>
                <td class="p-2 text-right font-semibold" x-text="record?.lowest_pnec_value_8"></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Detailed Information -->
      <div class="mb-4">
        <h4 class="text-lg font-semibold mb-2 border-b pb-1 bg-gray-100 px-2">Detailed Information</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2 px-2">
          <div class="mb-1">
            <p class="font-semibold">NORMAN PNEC ID:</p>
            <p x-text="record?.main_record?.der_id ? 'NORMAN PNEC ' + record.main_record.der_id : record?.pnec3?.norman_pnec_id"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">NORMAN Dataset ID:</p>
            <p x-text="record?.pnec3?.norman_dataset_id"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Data source name:</p>
            <p x-text="record?.pnec3?.data_source_name"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Data source link:</p>
            <p x-text="record?.pnec3?.data_source_link"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Data source ID:</p>
            <p x-text="record?.pnec3?.data_source_id"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Study title:</p>
            <p x-text="record?.pnec3?.study_title"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Author(s):</p>
            <p x-text="record?.pnec3?.authors"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Date:</p>
            <p x-text="record?.pnec3?.year"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Bibliographic source:</p>
            <p x-text="record?.pnec3?.bibliographic_source"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Dossier available?:</p>
            <p x-text="record?.pnec3?.dossier_available"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Country or Region:</p>
            <p x-text="record?.pnec3?.country_or_region"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Institution (PNEC):</p>
            <p x-text="record?.main_record?.lowest_institution"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Environmental medium:</p>
            <p x-text="record?.pnec3?.matrix_habitat"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Legal status:</p>
            <p x-text="record?.pnec3?.legal_status"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Protected asset:</p>
            <p x-text="record?.pnec3?.protected_asset"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">PNEC type:</p>
            <p x-text="record?.main_record?.lowest_pnec_type"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">PNEC type (country specific name):</p>
            <p x-text="record?.pnec3?.pnec_type_country"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Monitoring frequency:</p>
            <p x-text="record?.pnec3?.monitoring_frequency"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Taxonomic group:</p>
            <p x-text="record?.pnec3?.taxonomic_group"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Applied AF:</p>
            <p x-text="record?.main_record?.lowest_AF"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Justification:</p>
            <p x-text="record?.pnec3?.justification"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Derivation method:</p>
            <p x-text="record?.main_record?.lowest_derivation_method"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Biotest ID:</p>
            <p x-text="record?.pnec3?.ecotox_id"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Remarks:</p>
            <p x-text="record?.pnec3?.remarks"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Reliability of the key study:</p>
            <p x-text="record?.pnec3?.reliability_study"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Reliability score system used:</p>
            <p x-text="record?.pnec3?.reliability_score"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Institution (key study):</p>
            <p x-text="record?.pnec3?.institution_study"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Date:</p>
            <p x-text="record?.main_record?.lowest_year ? new Date(record.main_record.lowest_year).toLocaleDateString('de-DE') : ''"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Sum of votes:</p>
            <p x-text="record?.main_record?.lowest_sum_vote"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Expert:</p>
            <p x-text="record?.editor?.name"></p>
          </div>
          <div class="mb-1">
            <p class="font-semibold">Data Type:</p>
            <p x-text="record?.lowest_exp_pred ? 'Experimental' : 'Predicted'"></p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="flex justify-end border-t px-4 py-2">
      <button @click="closeModal()" class="px-4 py-2 bg-lime-600 text-white rounded hover:bg-lime-700">
        Close
      </button>
    </div>
  </div>
</div>