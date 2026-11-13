{{--
  Multi-select combobox (Alpine logic: resources/js/alpine-components/multiselect.js)

  Props:
  - name (string, required): name of the hidden input; its value is a JSON array of the selected values as strings
  - options (array|Collection, required): either ['key' => 'Label', ...]
      or [['id' => 1, 'label' => 'Item'], ['id' => 2, 'name' => 'Item'], ...] (label, name or title)
  - selected (array|string|null): pre-selected values, as an array or a JSON string
  - label (string|null): accessible name of the search input; defaults to one derived from name
  - placeholder (string): placeholder of the search input

  Usage:
  <x-multiselect name="countrySearch" :options="$countryList" :selected="$request->countrySearch ?? []" />
--}}
@props([
    'name',
    'options' => [],
    'selected' => [],
    'label' => null,
    'placeholder' => 'Type here...',
])

@php
    $selectedIds = $selected;
    if (is_string($selectedIds)) {
        $selectedIds = json_decode($selectedIds, true);
    }
    if (! is_array($selectedIds)) {
        $selectedIds = [];
    }

    $items = [];
    $selectedValues = [];
    foreach ($options as $key => $value) {
        if (is_array($value)) {
            $itemValue = $value['id'];
            $itemLabel = $value['label'] ?? $value['name'] ?? $value['title'] ?? '';
        } else {
            $itemValue = $key;
            $itemLabel = $value;
        }
        $items[] = ['value' => (string) $itemValue, 'label' => (string) $itemLabel];
        if (in_array($itemValue, $selectedIds)) {
            $selectedValues[] = (string) $itemValue;
        }
    }

    $accessibleName = $label ?: ucfirst(strtolower(trim(preg_replace('/(?<!^)[A-Z]/', ' $0', preg_replace('/Search$/', '', $name)))));
@endphp

<div
  x-data="multiselect({ selected: @js($selectedValues), placeholder: @js($placeholder) })"
  @click.outside="onClickOutside($event)"
  @focusout="onFocusOut($event)"
  @keydown.escape="close()"
  {{ $attributes->merge(['class' => 'relative w-full']) }}
>
  <script type="application/json" x-ref="options">@json($items)</script>

  <input type="hidden" name="{{ $name }}" value="{{ json_encode($selectedValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}" :value="JSON.stringify(selected)">

  <div
    x-ref="field"
    @mousedown="if ($event.target !== $refs.input) $event.preventDefault()"
    @click="$refs.input.focus(); openList()"
    class="relative flex flex-wrap items-center gap-1 border-2 bg-white px-1 py-1 pr-8 cursor-text"
  >
    <template x-for="(value, idx) in selected" :key="value">
      <span class="inline-flex items-center gap-1 border px-2 py-1 text-sm">
        <span x-text="shortLabel(labelOf(value), 25)" :title="labelOf(value)"></span>
        <button
          type="button"
          @click.stop="removeAt(idx)"
          class="text-red-300 hover:text-red-600 focus:outline-hidden focus:ring-2 focus:ring-amber-300"
          :aria-label="'Remove ' + labelOf(value)"
        >&times;</button>
      </span>
    </template>

    <input
      x-ref="input"
      x-model="search"
      type="text"
      role="combobox"
      autocomplete="off"
      aria-autocomplete="list"
      aria-label="{{ $accessibleName }}"
      :aria-expanded="open.toString()"
      :aria-controls="listId"
      :aria-activedescendant="activeIndex >= 0 ? optionId(activeIndex) : null"
      :placeholder="placeholder"
      @focus="openList()"
      @input="openList(); activeIndex = -1"
      @keydown.arrow-down.prevent="move(1)"
      @keydown.arrow-up.prevent="move(-1)"
      @keydown.enter.prevent="selectActive()"
      @keydown.backspace="onBackspace()"
      class="grow w-24 min-w-0 border-0 px-2 py-1.5 text-sm outline-hidden ring-inset focus:outline-hidden focus:ring-2 focus:ring-lime-300"
    >

    <svg
      @click.stop="toggle()"
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      fill="#ccc"
      aria-hidden="true"
      :class="open && 'rotate-180'"
      class="absolute right-2 top-1/2 -translate-y-1/2 cursor-pointer"
    >
      <path d="M12 17.414 3.293 8.707l1.414-1.414L12 14.586l7.293-7.293 1.414 1.414L12 17.414z" />
    </svg>
  </div>

  <template x-teleport="body">
    <ul
      x-show="open"
      x-ref="list"
      :id="listId"
      role="listbox"
      aria-multiselectable="true"
      aria-label="{{ $accessibleName }}"
      @mousedown.prevent
      :style="listStyle"
      class="fixed z-50 list-none overflow-y-auto border-2 bg-stone-100 text-sm shadow-lg"
    >
      <template x-for="(item, idx) in visibleItems" :key="item.value">
        <li
          :id="optionId(idx)"
          role="option"
          aria-selected="false"
          @click="choose(item)"
          @mousemove="activeIndex = idx"
          :title="item.label"
          :class="idx === activeIndex && 'bg-amber-200'"
          class="cursor-pointer px-2 py-2 hover:bg-amber-200"
          x-text="shortLabel(item.label, 50)"
        ></li>
      </template>
      <li x-show="!visibleItems.length" class="px-2 py-2 text-gray-400" role="presentation">No items found...</li>
      <li x-show="hiddenCount > 0" class="px-2 py-2 text-gray-500 italic" role="presentation" x-text="hiddenCount + ' more – type to narrow'"></li>
    </ul>
  </template>
</div>
