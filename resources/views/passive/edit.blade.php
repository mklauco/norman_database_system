<x-app-layout>
  <x-slot name="header">
    @include('passive.header')
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
            <h3 class="text-lg font-semibold text-gray-800">Edit Record ID: {{ $passive->id }}</h3>
            <div class="flex space-x-2">
              <a href="{{ route('passive.search.show', ['search' => $passive->id] + $request->except(['search'])) }}" class="link-lime">
                <i class="fas fa-eye mr-1"></i> View
              </a>
              <a href="{{ route('passive.search.search', $request->except(['search'])) }}" class="link-lime">
                <i class="fas fa-arrow-left mr-1"></i> Back to Search
              </a>
            </div>
          </div>

          <form method="POST" action="{{ route('passive.search.update', ['search' => $passive->id] + $request->except(['search'])) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="overflow-x-auto">
              <table class="table-standard">
                <tbody>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Substance ID</td>
                    <td class="p-2">
                      <input type="number" name="sus_id" value="{{ $passive->sus_id }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Country ID</td>
                    <td class="p-2">
                      <input type="text" name="country_id" value="{{ $passive->country_id }}" class="form-text" maxlength="2">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Country Other</td>
                    <td class="p-2">
                      <input type="text" name="country_other" value="{{ $passive->country_other }}" class="form-text" maxlength="2">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Station Name</td>
                    <td class="p-2">
                      <input type="text" name="station_name" value="{{ $passive->station_name }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Short Sample Code</td>
                    <td class="p-2">
                      <input type="text" name="short_sample_code" value="{{ $passive->short_sample_code }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Sample Code</td>
                    <td class="p-2">
                      <input type="text" name="sample_code" value="{{ $passive->sample_code }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Provider Code</td>
                    <td class="p-2">
                      <input type="text" name="provider_code" value="{{ $passive->provider_code }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">National Code</td>
                    <td class="p-2">
                      <input type="text" name="national_code" value="{{ $passive->national_code }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">EC Code (WISE)</td>
                    <td class="p-2">
                      <input type="text" name="code_ec_wise" value="{{ $passive->code_ec_wise }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">EC Code (Other)</td>
                    <td class="p-2">
                      <input type="text" name="code_ec_other" value="{{ $passive->code_ec_other }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Other Code</td>
                    <td class="p-2">
                      <input type="text" name="code_other" value="{{ $passive->code_other }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Specific Locations</td>
                    <td class="p-2">
                      <textarea name="specific_locations" rows="3" class="form-text">{{ $passive->specific_locations }}</textarea>
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Longitude</td>
                    <td class="p-2">
                      <input type="text" name="longitude_decimal" value="{{ $passive->longitude_decimal }}" class="form-text" maxlength="20">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Latitude</td>
                    <td class="p-2">
                      <input type="text" name="latitude_decimal" value="{{ $passive->latitude_decimal }}" class="form-text" maxlength="20">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Precision Coordinates ID</td>
                    <td class="p-2">
                      <input type="number" name="dpc_id" value="{{ $passive->dpc_id }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Altitude</td>
                    <td class="p-2">
                      <input type="text" name="altitude" value="{{ $passive->altitude }}" class="form-text" maxlength="20">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Proxy Pressures ID</td>
                    <td class="p-2">
                      <input type="number" name="dpr_id" value="{{ $passive->dpr_id }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Proxy Pressures Other</td>
                    <td class="p-2">
                      <input type="text" name="dpr_other" value="{{ $passive->dpr_other }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Dynamic Sampling Stretch</td>
                    <td class="p-2">
                      <input type="text" name="ds_passive_sampling_stretch" value="{{ $passive->ds_passive_sampling_stretch }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Stretch Start and End</td>
                    <td class="p-2">
                      <textarea name="ds_stretch_start_and_end" rows="3" class="form-text">{{ $passive->ds_stretch_start_and_end }}</textarea>
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">DS Longitude Start</td>
                    <td class="p-2">
                      <input type="text" name="ds_longitude_start_point_decimal" value="{{ $passive->ds_longitude_start_point_decimal }}" class="form-text" maxlength="20">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">DS Latitude Start</td>
                    <td class="p-2">
                      <input type="text" name="ds_latitude_start_point_decimal" value="{{ $passive->ds_latitude_start_point_decimal }}" class="form-text" maxlength="20">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">DS Longitude End</td>
                    <td class="p-2">
                      <input type="text" name="ds_longitude_end_point_decimal" value="{{ $passive->ds_longitude_end_point_decimal }}" class="form-text" maxlength="20">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">DS Latitude End</td>
                    <td class="p-2">
                      <input type="text" name="ds_latitude_end_point_decimal" value="{{ $passive->ds_latitude_end_point_decimal }}" class="form-text" maxlength="20">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">DS Precision Coordinates ID</td>
                    <td class="p-2">
                      <input type="number" name="ds_dpc_id" value="{{ $passive->ds_dpc_id }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">DS Altitude</td>
                    <td class="p-2">
                      <input type="text" name="ds_altitude" value="{{ $passive->ds_altitude }}" class="form-text" maxlength="20">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">DS Proxy Pressures ID</td>
                    <td class="p-2">
                      <input type="number" name="ds_dpr_id" value="{{ $passive->ds_dpr_id }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">DS Proxy Pressures Other</td>
                    <td class="p-2">
                      <input type="text" name="ds_dpr_other" value="{{ $passive->ds_dpr_other }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Matrix ID</td>
                    <td class="p-2">
                      <input type="number" name="matrix_id" value="{{ $passive->matrix_id }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Matrix Other</td>
                    <td class="p-2">
                      <input type="text" name="matrix_other" value="{{ $passive->matrix_other }}" class="form-text" maxlength="30">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Type Sampling ID</td>
                    <td class="p-2">
                      <input type="number" name="type_sampling_id" value="{{ $passive->type_sampling_id }}" class="form-text" required>
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Type Sampling Other</td>
                    <td class="p-2">
                      <input type="text" name="type_sampling_other" value="{{ $passive->type_sampling_other }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Passive Sampler ID</td>
                    <td class="p-2">
                      <input type="number" name="passive_sampler_id" value="{{ $passive->passive_sampler_id }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Passive Sampler Other</td>
                    <td class="p-2">
                      <input type="text" name="passive_sampler_other" value="{{ $passive->passive_sampler_other }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Sampler Type ID</td>
                    <td class="p-2">
                      <input type="number" name="sampler_type_id" value="{{ $passive->sampler_type_id }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Sampler Type Other</td>
                    <td class="p-2">
                      <input type="text" name="sampler_type_other" value="{{ $passive->sampler_type_other }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Sampler Mass</td>
                    <td class="p-2">
                      <input type="text" name="sampler_mass" value="{{ $passive->sampler_mass }}" class="form-text" maxlength="20">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Sampler Surface Area</td>
                    <td class="p-2">
                      <input type="text" name="sampler_surface_area" value="{{ $passive->sampler_surface_area }}" class="form-text" maxlength="20">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Sampling Start Day</td>
                    <td class="p-2">
                      <input type="number" name="date_sampling_start_day" value="{{ $passive->date_sampling_start_day }}" class="form-text" min="1" max="31">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Sampling Start Month</td>
                    <td class="p-2">
                      <input type="number" name="date_sampling_start_month" value="{{ $passive->date_sampling_start_month }}" class="form-text" min="1" max="12">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Sampling Start Year</td>
                    <td class="p-2">
                      <input type="number" name="date_sampling_start_year" value="{{ $passive->date_sampling_start_year }}" class="form-text" min="1900" max="2100" required>
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Exposure Time (Days)</td>
                    <td class="p-2">
                      <input type="text" name="exposure_time_days" value="{{ $passive->exposure_time_days }}" class="form-text" maxlength="20">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Exposure Time (Hours)</td>
                    <td class="p-2">
                      <input type="text" name="exposure_time_hours" value="{{ $passive->exposure_time_hours }}" class="form-text" maxlength="20">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Date of Analysis</td>
                    <td class="p-2">
                      <input type="date" name="date_of_analysis" value="{{ $passive->date_of_analysis }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Time of Analysis</td>
                    <td class="p-2">
                      <input type="time" name="time_of_analysis" value="{{ $passive->time_of_analysis }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Name</td>
                    <td class="p-2">
                      <input type="text" name="name" value="{{ $passive->name }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Basin Name ID</td>
                    <td class="p-2">
                      <input type="number" name="basin_name_id" value="{{ $passive->basin_name_id }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Basin Name Other</td>
                    <td class="p-2">
                      <input type="text" name="basin_name_other" value="{{ $passive->basin_name_other }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Type Data Source ID</td>
                    <td class="p-2">
                      <input type="number" name="dts_id" value="{{ $passive->dts_id }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Type Data Source Other</td>
                    <td class="p-2">
                      <input type="text" name="dts_other" value="{{ $passive->dts_other }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Type Monitoring ID</td>
                    <td class="p-2">
                      <input type="number" name="dtm_id" value="{{ $passive->dtm_id }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Type Monitoring Other</td>
                    <td class="p-2">
                      <input type="text" name="dtm_other" value="{{ $passive->dtm_other }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Concentration ID</td>
                    <td class="p-2">
                      <input type="number" name="dic_id" value="{{ $passive->dic_id }}" class="form-text" required>
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Concentration Value</td>
                    <td class="p-2">
                      <input type="number" name="concentration_value" value="{{ $passive->concentration_value }}" class="form-text" step="0.000001" required>
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Unit</td>
                    <td class="p-2">
                      <input type="text" name="unit" value="{{ $passive->unit }}" class="form-text" maxlength="20" required>
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Title of Project</td>
                    <td class="p-2">
                      <input type="text" name="title_of_project" value="{{ $passive->title_of_project }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">pH</td>
                    <td class="p-2">
                      <input type="text" name="ph" value="{{ $passive->ph }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Temperature</td>
                    <td class="p-2">
                      <input type="text" name="temperature" value="{{ $passive->temperature }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">SPM Concentration</td>
                    <td class="p-2">
                      <input type="text" name="spm_conc" value="{{ $passive->spm_conc }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Salinity</td>
                    <td class="p-2">
                      <input type="text" name="salinity" value="{{ $passive->salinity }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">DOC</td>
                    <td class="p-2">
                      <input type="text" name="doc" value="{{ $passive->doc }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Hardness</td>
                    <td class="p-2">
                      <input type="text" name="hardness" value="{{ $passive->hardness }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">O2 1</td>
                    <td class="p-2">
                      <input type="text" name="o2_1" value="{{ $passive->o2_1 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">O2 2</td>
                    <td class="p-2">
                      <input type="text" name="o2_2" value="{{ $passive->o2_2 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">BOD5</td>
                    <td class="p-2">
                      <input type="text" name="bod5" value="{{ $passive->bod5 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">H2S</td>
                    <td class="p-2">
                      <input type="text" name="h2s" value="{{ $passive->h2s }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">P-PO4</td>
                    <td class="p-2">
                      <input type="text" name="p_po4" value="{{ $passive->p_po4 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">N-NO2</td>
                    <td class="p-2">
                      <input type="text" name="n_no2" value="{{ $passive->n_no2 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">TSS</td>
                    <td class="p-2">
                      <input type="text" name="tss" value="{{ $passive->tss }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">P Total</td>
                    <td class="p-2">
                      <input type="text" name="p_total" value="{{ $passive->p_total }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">N-NO3</td>
                    <td class="p-2">
                      <input type="text" name="n_no3" value="{{ $passive->n_no3 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">N Total</td>
                    <td class="p-2">
                      <input type="text" name="n_total" value="{{ $passive->n_total }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Remark 1</td>
                    <td class="p-2">
                      <input type="text" name="remark_1" value="{{ $passive->remark_1 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Remark 2</td>
                    <td class="p-2">
                      <input type="text" name="remark_2" value="{{ $passive->remark_2 }}" class="form-text">
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Analytical Method ID</td>
                    <td class="p-2">
                      <input type="number" name="am_id" value="{{ $passive->am_id }}" class="form-text" required>
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Organisation ID</td>
                    <td class="p-2">
                      <input type="number" name="org_id" value="{{ $passive->org_id }}" class="form-text" required>
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">Original Compound</td>
                    <td class="p-2">
                      <input type="text" name="orig_compound" value="{{ $passive->orig_compound }}" class="form-text" required>
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">Original CAS No</td>
                    <td class="p-2">
                      <input type="text" name="orig_cas_no" value="{{ $passive->orig_cas_no }}" class="form-text" required>
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">P Determinand ID</td>
                    <td class="p-2">
                      <input type="text" name="p_determinand_id" value="{{ $passive->p_determinand_id }}" class="form-text" required>
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">P/A Exposure Time</td>
                    <td class="p-2">
                      <textarea name="p_a_exposure_time" rows="3" class="form-text">{{ $passive->p_a_exposure_time }}</textarea>
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">P/A Cruise Dates</td>
                    <td class="p-2">
                      <textarea name="p_a_cruise_dates" rows="3" class="form-text">{{ $passive->p_a_cruise_dates }}</textarea>
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">P/A River KM</td>
                    <td class="p-2">
                      <textarea name="p_a_river_km" rows="3" class="form-text">{{ $passive->p_a_river_km }}</textarea>
                    </td>
                  </tr>
                  <tr class="bg-slate-100">
                    <td class="p-2 font-semibold">P/A Sampler Sheets/Disks Nr</td>
                    <td class="p-2">
                      <textarea name="p_a_sampler_sheets_disks_nr" rows="3" class="form-text">{{ $passive->p_a_sampler_sheets_disks_nr }}</textarea>
                    </td>
                  </tr>
                  <tr class="bg-slate-200">
                    <td class="p-2 font-semibold">P/A Sample Code</td>
                    <td class="p-2">
                      <textarea name="p_a_sample_code" rows="3" class="form-text">{{ $passive->p_a_sample_code }}</textarea>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
              <a href="{{ route('passive.search.show', ['search' => $passive->id] + $request->all()) }}" class="btn-clear">
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
