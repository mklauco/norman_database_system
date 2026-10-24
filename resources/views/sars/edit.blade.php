<x-app-layout>
  <x-slot name="header">
    @include('sars.header')
  </x-slot>

  <div class="py-4">
    <div class="w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">
          
          @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-sm">
              {{ session('success') }}
            </div>
          @endif

                      <div class="mb-6 flex justify-between items-center">
              <h3 class="text-lg font-semibold text-gray-800">Edit Record ID: {{ $sars->id }}</h3>
              <div class="flex space-x-2">
                <a href="{{ route('sars.search.show', $sars->id) }}" class="link-lime">
                  <i class="fas fa-eye mr-1"></i> View
                </a>
                <a href="{{ route('sars.search.search') }}" class="link-lime">
                  <i class="fas fa-arrow-left mr-1"></i> Back to Search
                </a>
              </div>
            </div>

          <form method="POST" action="{{ route('sars.search.update', $sars->id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="overflow-x-auto">
              <table class="table-standard">
                <tbody>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Type of Data</td>
                    <td class="p-2">
                      <input type="text" name="type_of_data" value="{{ $sars->type_of_data }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Data Provider</td>
                    <td class="p-2">
                      <input type="text" name="data_provider" value="{{ $sars->data_provider }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Contact Person</td>
                    <td class="p-2">
                      <input type="text" name="contact_person" value="{{ $sars->contact_person }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Address of Contact</td>
                    <td class="p-2">
                      <input type="text" name="address_of_contact" value="{{ $sars->address_of_contact }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Email</td>
                    <td class="p-2">
                      <input type="email" name="email" value="{{ $sars->email }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Laboratory</td>
                    <td class="p-2">
                      <input type="text" name="laboratory" value="{{ $sars->laboratory }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Country</td>
                    <td class="p-2">
                      <input type="text" name="name_of_country" value="{{ $sars->name_of_country }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">City</td>
                    <td class="p-2">
                      <input type="text" name="name_of_city" value="{{ $sars->name_of_city }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Station Name</td>
                    <td class="p-2">
                      <input type="text" name="station_name" value="{{ $sars->station_name }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">National Code</td>
                    <td class="p-2">
                      <input type="text" name="national_code" value="{{ $sars->national_code }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Relevant EC Code (WISE)</td>
                    <td class="p-2">
                      <input type="text" name="relevant_ec_code_wise" value="{{ $sars->relevant_ec_code_wise }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Relevant EC Code (Other)</td>
                    <td class="p-2">
                      <input type="text" name="relevant_ec_code_other" value="{{ $sars->relevant_ec_code_other }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Other Code</td>
                    <td class="p-2">
                      <input type="text" name="other_code" value="{{ $sars->other_code }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Latitude</td>
                    <td class="p-2">
                      <input type="text" name="latitude" value="{{ $sars->latitude }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Longitude</td>
                    <td class="p-2">
                      <input type="text" name="longitude" value="{{ $sars->longitude }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Altitude</td>
                    <td class="p-2">
                      <input type="text" name="altitude" value="{{ $sars->altitude }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Design Capacity</td>
                    <td class="p-2">
                      <input type="text" name="design_capacity" value="{{ $sars->design_capacity }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Population Served</td>
                    <td class="p-2">
                      <input type="text" name="population_served" value="{{ $sars->population_served }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Catchment Size</td>
                    <td class="p-2">
                      <input type="text" name="catchment_size" value="{{ $sars->catchment_size }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">GDP</td>
                    <td class="p-2">
                      <input type="text" name="gdp" value="{{ $sars->gdp }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">People Positive</td>
                    <td class="p-2">
                      <input type="text" name="people_positive" value="{{ $sars->people_positive }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">People Recovered</td>
                    <td class="p-2">
                      <input type="text" name="people_recovered" value="{{ $sars->people_recovered }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">People Positive Past</td>
                    <td class="p-2">
                      <input type="text" name="people_positive_past" value="{{ $sars->people_positive_past }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">People Recovered Past</td>
                    <td class="p-2">
                      <input type="text" name="people_recovered_past" value="{{ $sars->people_recovered_past }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Sample Matrix</td>
                    <td class="p-2">
                      <input type="text" name="sample_matrix" value="{{ $sars->sample_matrix }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Sample From Year</td>
                    <td class="p-2">
                      <input type="number" name="sample_from_year" value="{{ $sars->sample_from_year }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Sample From Month</td>
                    <td class="p-2">
                      <input type="number" name="sample_from_month" value="{{ $sars->sample_from_month }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Sample From Day</td>
                    <td class="p-2">
                      <input type="number" name="sample_from_day" value="{{ $sars->sample_from_day }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Sample From Hour</td>
                    <td class="p-2">
                      <input type="text" name="sample_from_hour" value="{{ $sars->sample_from_hour }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Type of Sample</td>
                    <td class="p-2">
                      <input type="text" name="type_of_sample" value="{{ $sars->type_of_sample }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Type of Composite Sample</td>
                    <td class="p-2">
                      <input type="text" name="type_of_composite_sample" value="{{ $sars->type_of_composite_sample }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Sample Interval</td>
                    <td class="p-2">
                      <input type="text" name="sample_interval" value="{{ $sars->sample_interval }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Flow Total</td>
                    <td class="p-2">
                      <input type="text" name="flow_total" value="{{ $sars->flow_total }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Flow Minimum</td>
                    <td class="p-2">
                      <input type="text" name="flow_minimum" value="{{ $sars->flow_minimum }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Flow Maximum</td>
                    <td class="p-2">
                      <input type="text" name="flow_maximum" value="{{ $sars->flow_maximum }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Temperature</td>
                    <td class="p-2">
                      <input type="text" name="temperature" value="{{ $sars->temperature }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">COD</td>
                    <td class="p-2">
                      <input type="text" name="cod" value="{{ $sars->cod }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Total N / NH4-N</td>
                    <td class="p-2">
                      <input type="text" name="total_n_nh4_n" value="{{ $sars->total_n_nh4_n }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">TSS</td>
                    <td class="p-2">
                      <input type="text" name="tss" value="{{ $sars->tss }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Dry Weather Conditions</td>
                    <td class="p-2">
                      <input type="text" name="dry_weather_conditions" value="{{ $sars->dry_weather_conditions }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Last Rain Event</td>
                    <td class="p-2">
                      <input type="text" name="last_rain_event" value="{{ $sars->last_rain_event }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Associated Phenotype</td>
                    <td class="p-2">
                      <input type="text" name="associated_phenotype" value="{{ $sars->associated_phenotype }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Genetic Marker</td>
                    <td class="p-2">
                      <input type="text" name="genetic_marker" value="{{ $sars->genetic_marker }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Date of Sample Preparation</td>
                    <td class="p-2">
                      <input type="text" name="date_of_sample_preparation" value="{{ $sars->date_of_sample_preparation }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Storage of Sample</td>
                    <td class="p-2">
                      <input type="text" name="storage_of_sample" value="{{ $sars->storage_of_sample }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Volume of Sample</td>
                    <td class="p-2">
                      <input type="text" name="volume_of_sample" value="{{ $sars->volume_of_sample }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Internal Standard Used 1</td>
                    <td class="p-2">
                      <input type="text" name="internal_standard_used1" value="{{ $sars->internal_standard_used1 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Method Used for Sample Preparation</td>
                    <td class="p-2">
                      <input type="text" name="method_used_for_sample_preparation" value="{{ $sars->method_used_for_sample_preparation }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Date of RNA Extraction</td>
                    <td class="p-2">
                      <input type="text" name="date_of_rna_extraction" value="{{ $sars->date_of_rna_extraction }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Method Used for RNA Extraction</td>
                    <td class="p-2">
                      <input type="text" name="method_used_for_rna_extraction" value="{{ $sars->method_used_for_rna_extraction }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Internal Standard Used 2</td>
                    <td class="p-2">
                      <input type="text" name="internal_standard_used2" value="{{ $sars->internal_standard_used2 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">RNA 1</td>
                    <td class="p-2">
                      <input type="text" name="rna1" value="{{ $sars->rna1 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">RNA 2</td>
                    <td class="p-2">
                      <input type="text" name="rna2" value="{{ $sars->rna2 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Replicates 1</td>
                    <td class="p-2">
                      <input type="text" name="replicates1" value="{{ $sars->replicates1 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Analytical Method Type</td>
                    <td class="p-2">
                      <input type="text" name="analytical_method_type" value="{{ $sars->analytical_method_type }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Analytical Method Type Other</td>
                    <td class="p-2">
                      <input type="text" name="analytical_method_type_other" value="{{ $sars->analytical_method_type_other }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Date of Analysis</td>
                    <td class="p-2">
                      <input type="text" name="date_of_analysis" value="{{ $sars->date_of_analysis }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">LoD 1</td>
                    <td class="p-2">
                      <input type="text" name="lod1" value="{{ $sars->lod1 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">LoD 2</td>
                    <td class="p-2">
                      <input type="text" name="lod2" value="{{ $sars->lod2 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">LoQ 1</td>
                    <td class="p-2">
                      <input type="text" name="loq1" value="{{ $sars->loq1 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">LoQ 2</td>
                    <td class="p-2">
                      <input type="text" name="loq2" value="{{ $sars->loq2 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Uncertainty of the Quantification</td>
                    <td class="p-2">
                      <input type="text" name="uncertainty_of_the_quantification" value="{{ $sars->uncertainty_of_the_quantification }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Efficiency</td>
                    <td class="p-2">
                      <input type="text" name="efficiency" value="{{ $sars->efficiency }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">RNA 3</td>
                    <td class="p-2">
                      <input type="text" name="rna3" value="{{ $sars->rna3 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Pos Control Used</td>
                    <td class="p-2">
                      <input type="text" name="pos_control_used" value="{{ $sars->pos_control_used }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Replicates 2</td>
                    <td class="p-2">
                      <input type="text" name="replicates2" value="{{ $sars->replicates2 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Ct #</td>
                    <td class="p-2">
                      <input type="text" name="ct" value="{{ $sars->ct }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Gene 1</td>
                    <td class="p-2">
                      <input type="text" name="gene1" value="{{ $sars->gene1 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Gene 2</td>
                    <td class="p-2">
                      <input type="text" name="gene2" value="{{ $sars->gene2 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Comment</td>
                    <td class="p-2">
                      <textarea name="comment" rows="3" class="form-text">{{ $sars->comment }}</textarea>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
              <a href="{{ route('sars.search.show', $sars->id) }}" class="btn-clear">
                <i class="fas fa-times mr-1"></i> Cancel
              </a>
              <button type="submit" class="btn-submit">
                <i class="fas fa-save mr-1"></i> Update Record
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
