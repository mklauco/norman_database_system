<tr x-bind:class="row.type === 'header' ? 'bg-gray-50' : (row.isOdd ? 'bg-gray-50' : '')">
  <!-- Section Header -->
  <template x-if="row.type === 'header'">
      <td colspan="4" 
          class="border border-gray-300 px-3 py-2 font-semibold text-center text-gray-800 bg-lime-100"
          x-text="row.title">
      </td>
  </template>

  <!-- Data Row Cells -->
  <template x-if="row.type === 'data'">
      <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">
          @if($isSuperAdmin)
              <div>
                  <div class="font-semibold" x-text="row.key"></div>
                  <div class="text-xs text-gray-500">
                      ID: <span x-text="row.columnId || 'N/A'"></span>
                  </div>
              </div>
          @else
              <span x-text="row.key"></span>
          @endif
      </td>
  </template>

  <template x-if="row.type === 'data'">
      <td class="border border-gray-300 px-3 py-2" x-text="row.original || 'N/A'"></td>
  </template>

  <template x-if="row.type === 'data'">
      <td class="border border-gray-300 px-3 py-2" x-text="row.harmonised || 'N/A'"></td>
  </template>

  <template x-if="row.type === 'data'">
      <td class="border border-gray-300 px-3 py-2">
          <!-- Editable Fields -->
          <template x-if="row.isEditable">
              <div>
                  <!-- Text Input -->
                  <template x-if="row.inputType === 'text'">
                      <input type="text" 
                             x-model="row.final"
                             @input="updateField(row.id, $event.target.value)"
                             class="w-full px-2 py-1 border border-gray-300 rounded-sm focus:outline-hidden focus:ring-2 focus:ring-sky-500"
                             placeholder="Enter text">
                  </template>

                  <!-- Numeric Input -->
                  <template x-if="row.inputType === 'numeric'">
                      <input type="number" 
                             step="any"
                             x-model="row.final"
                             @input="updateField(row.id, $event.target.value)"
                             class="w-full px-2 py-1 border border-gray-300 rounded-sm focus:outline-hidden focus:ring-2 focus:ring-sky-500"
                             placeholder="Enter number">
                  </template>

                  <!-- Dropdown -->
                  <template x-if="row.inputType === 'dropdown'">
                      <select x-model="row.final"
                              @change="updateField(row.id, $event.target.value)"
                              class="w-full px-2 py-1 border border-gray-300 rounded-sm focus:outline-hidden focus:ring-2 focus:ring-sky-500">
                          <option value="">Select option</option>
                          <template x-for="(option, index) in row.dropdownOptions" :key="`${row.key}-${index}-${option}`">
                              <option x-bind:value="option" 
                                      x-text="option"
                                      x-bind:selected="row.final === option">
                              </option>
                          </template>
                      </select>
                  </template>
              </div>
          </template>

          <!-- Non-Editable Fields -->
          <template x-if="!row.isEditable">
              <span x-text="row.final || 'N/A'"></span>
          </template>
      </td>
  </template>
</tr>