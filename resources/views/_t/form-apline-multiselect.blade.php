{{--
  Alpine.js Multi-Select Component

  A reusable multi-select dropdown component using Alpine.js

  Parameters:
  - $tag (string, required): The name attribute for the hidden input field
  - $list (array, required): Array of items to display. Can be:
      * Simple array: ['key1' => 'Label 1', 'key2' => 'Label 2']
      * Array of arrays: [['id' => 1, 'label' => 'Item 1'], ['id' => 2, 'name' => 'Item 2']]
  - $active_ids (array, optional): Array of pre-selected item IDs/keys

  Usage:
  @include('_t.form-apline-multiselect', [
    'tag' => 'myField',
    'list' => $myList,
    'active_ids' => $selectedIds ?? [],
  ])

  Important Notes:
  - This component is used across the entire project. Any changes may affect multiple modules.
  - Uses Laravel's Js::from() helper to safely convert PHP strings to JavaScript
  - Js::from() properly escapes quotes, apostrophes, line breaks, and special characters
  - Preserves parentheses (), brackets [], and other safe characters in labels
--}}

{{-- Normalize active_ids to array if passed as JSON string --}}
@if (is_string($active_ids))
  @php
    $active_ids = json_decode($active_ids, true);
  @endphp
@endif

<div class="w-full">
  <!-- Alpine.js Multi-Select Component -->
  <div
    x-data="multiselect({
      items: [
        @foreach ($list as $key => $value)
          @if(is_array($value))
            {{-- Handle array values: extract label from common keys --}}
            @php
              $labelText = $value['label'] ?? $value['name'] ?? $value['title'] ?? '';
            @endphp
            {
              label: {{ Js::from($labelText) }},
              value: '{{ $value['id'] }}'
              @if (in_array($value['id'], $active_ids)), selected: true @endif
            },
          @else
            {{--
              Handle simple string values
              Note: Js::from() replaces the old preg_replace() approach
              It safely escapes special characters while preserving () [] and other safe chars
            --}}
            {
              label: {{ Js::from($value) }},
              value: '{{ $key }}'
              @if (in_array($key, $active_ids)), selected: true @endif
            },
          @endif
        @endforeach
      ],
      size: 6,
    })"
    x-init="onInit"
    @focusout="handleBlur"
    class="relative"
  >

    {{-- Hidden input that stores selected values as JSON array --}}
    <input
      type="hidden"
      name="{{ $tag }}"
      :value="JSON.stringify(selectedItems.map(item => item.value))"
    >

    <!-- Selected Items Tags and Search Input Container -->
    <div class="flex items-center justify-between px-1 border border-2 rounded-0 relative pr-8 bg-white">
      <ul class="flex flex-wrap items-center w-full">

        <!-- Selected Items as Removable Tags -->
        <template x-for="(selectedItem, idx) in selectedItems">
          <li
            x-text="shortenedLabel(selectedItem.label, maxTagChars)"
            @click="removeElementByIdx(idx)"
            @keyup.backspace="removeElementByIdx(idx)"
            @keyup.delete="removeElementByIdx(idx)"
            tabindex="0"
            class="relative m-1 px-2 py-1.5 border rounded-0 cursor-pointer hover:bg-red-100 after:content-['x'] after:ml-1.5 after:text-red-300 outline-hidden focus:outline-hidden ring-0 focus:ring-2 focus:ring-amber-300 ring-inset transition-all"
          ></li>
        </template>

        <!-- Search Input Field -->
        <input
          x-ref="searchInput"
          x-model="search"
          @click="expanded = true"
          @focusin="expanded = true"
          @input="expanded = true"
          @keyup.arrow-down="expanded = true; selectNextItem()"
          @keyup.arrow-up="expanded = true; selectPrevItem()"
          @keyup.escape="reset"
          @keyup.enter="addActiveItem"
          :placeholder="searchPlaceholder"
          type="text"
          class="grow py-2 px-2 mx-1 my-1.5 outline-hidden focus:outline-hidden focus:ring-lime-300 focus:ring-2 ring-inset transition-all rounded-0 w-24 text-sm"
        />

        <!-- Dropdown Toggle Arrow Icon -->
        <svg
          @click="expanded = !expanded; expanded && $refs.searchInput.focus()"
          xmlns="http://www.w3.org/2000/svg"
          width="24"
          height="24"
          stroke-width="0"
          fill="#ccc"
          :class="expanded && 'rotate-180'"
          class="absolute right-2 top-1/2 -translate-y-1/2 cursor-pointer focus:outline-hidden"
          tabindex="-1"
        >
          <path d="M12 17.414 3.293 8.707l1.414-1.414L12 14.586l7.293-7.293 1.414 1.414L12 17.414z" />
        </svg>
      </ul>
    </div>
    <!-- End Selected Items Tags and Search Input Container -->

    <!-- Dropdown List (shown when expanded) -->
    <template x-if="expanded">
      <ul
        x-ref="listBox"
        class="w-full list-none border border-2 border-t-0 rounded-0 focus:outline-hidden overflow-y-auto outline-hidden focus:outline-hidden bg-stone-100 absolute left-0 bottom-100 z-10 text-sm"
        tabindex="0"
        :style="listBoxStyle"
      >
        <!-- Available Items (filtered by search) -->
        <template x-if="filteredItems.length">
          <template x-for="(filteredItem, idx) in filteredItems">
            <li
              x-text="shortenedLabel(filteredItem.label, maxItemChars)"
              @click="handleItemClick(filteredItem)"
              :class="idx === activeIndex && 'bg-amber-200'"
              :title="filteredItem.label"
              class="hover:bg-amber-200 cursor-pointer px-2 py-2"
            ></li>
          </template>
        </template>

        <!-- Empty State Message -->
        <template x-if="!filteredItems.length">
          <li
            x-text="emptyText"
            class="cursor-pointer px-2 py-2 text-gray-400"
          ></li>
        </template>
      </ul>
    </template>
    <!-- End Dropdown List -->
  </div>
  <!-- End Alpine.js Multi-Select Component -->
</div>
