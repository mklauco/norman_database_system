
@if (is_string($active_ids))
@php
$active_ids = json_decode($active_ids, true);
@endphp
@endif

{{-- @if (is_null($active_ids))
@php
$active_ids = [];
@endphp
@endif --}}

{{-- {{ var_dump($list) }} --}}
<div class="w-full max-w-lg">
    <!-- Start Component -->
    <div
    x-data="multiselect(
      { 
        items: [
    @foreach ($list as $key => $value)
    @if(is_array($value))
      { label: '{!! preg_replace("/'/", '`', $value) !!}', value: '{{$value['id']}}' @if (in_array($value['id'], $active_ids)) , selected: true @endif},
    @else
    { label: '{!! preg_replace("/'/", '`', $value) !!}', value: '{{$key}}'@if (in_array($key, $active_ids)) , selected: true @endif},
    @endif
    @endforeach
    ],
    size: 6,
})"
    x-init="onInit"
    @focusout="handleBlur"
    class="relative"
    >
    
    <input type="hidden" name="{{$tag}}" :value="JSON.stringify(selectedItems.map(item => item.value))">
    
    <!-- Start Item Tags And Input Field -->
    <div
    class="flex items-center justify-between px-1 border border-2 rounded-md relative pr-8 bg-white"
    >
    <ul class="flex flex-wrap items-center w-full">
        <!-- Tags (Selected) -->
        <template x-for="(selectedItem, idx) in selectedItems">
            
            <li
            x-text="shortenedLabel(selectedItem.label, maxTagChars)"
            @click="removeElementByIdx(idx)"
            @keyup.backspace="removeElementByIdx(idx)"
            @keyup.delete="removeElementByIdx(idx)"
            tabindex="0"
            class="relative m-1 px-2 py-1.5 border rounded-md cursor-pointer hover:bg-red-100 after:content-['x'] after:ml-1.5 after:text-red-300 outline-none focus:outline-none ring-0 focus:ring-2 focus:ring-amber-300 ring-inset transition-all"
            ></li>
            
        </template>
        
        <!-- Search Input -->
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
        class="flex-grow py-2 px-2 mx-1 my-1.5 outline-none focus:outline-none focus:ring-amber-300 focus:ring-2 ring-inset transition-all rounded-md w-24"
        />
        
        <!-- Arrow Icon -->
        <svg
        @click="expanded = !expanded; expanded && $refs.searchInput.focus()"
        xmlns="http://www.w3.org/2000/svg"
        width="24"
        height="24"
        stroke-width="0"
        fill="#ccc"
        :class="expanded && 'rotate-180'"
        class="absolute right-2 top-1/2 -translate-y-1/2 cursor-pointer focus:outline-none"
        tabindex="-1"
        >
        <path
        d="M12 17.414 3.293 8.707l1.414-1.414L12 14.586l7.293-7.293 1.414 1.414L12 17.414z"
        />
    </svg>
</ul>
</div>
<!-- End Item Tags And Input Field -->

<!-- Start Items List -->
<template x-if="expanded">
    <ul
    x-ref="listBox"
    class="w-full list-none border border-2 border-t-0 rounded-md focus:outline-none overflow-y-auto outline-none focus:outline-none bg-blue-100 absolute left-0 bottom-100 z-10"
    tabindex="0"
    :style="listBoxStyle"
    >
    <!-- Item Element -->
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
    
    <!-- Empty Text -->
    <template x-if="!filteredItems.length">
        <li
        x-text="emptyText"
        class="cursor-pointer px-2 py-2 text-gray-400"
        ></li>
    </template>
</ul>
</template>
<!-- End Items List -->
</div>
<!-- End Component -->
</div>