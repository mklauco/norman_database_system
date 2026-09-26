@props(['roles', 'color' => 'text-gray-400'])

@php
  $label = 'Visible to: '.collect($roles)
      ->map(fn (string $role): string => ucfirst(str_replace('_', ' ', $role)))
      ->implode(', ');
@endphp

{{-- The tooltip is position: fixed so it is never clipped by a card's overflow-hidden. --}}
<span
  x-data="{
    open: false,
    placed: false,
    top: 0,
    left: 0,
    show() {
      this.open = true;
      this.placed = false;
      this.$nextTick(() => {
        const anchor = this.$el.getBoundingClientRect();
        const tip = this.$refs.tip.getBoundingClientRect();
        this.left = Math.max(8, Math.min(anchor.left + anchor.width / 2 - tip.width / 2, window.innerWidth - tip.width - 8));
        this.top = anchor.bottom + 6;
        this.placed = true;
      });
    },
    hide() {
      this.open = false;
    },
  }"
  @mouseenter="show()"
  @mouseleave="hide()"
  @focus="show()"
  @blur="hide()"
  @scroll.window="hide()"
  tabindex="0"
  aria-label="{{ $label }}"
  {{ $attributes->merge(['class' => 'inline-flex items-center cursor-help rounded focus:outline-none focus:ring-2 focus:ring-lime-400 '.$color]) }}
>
  <i class="fas fa-lock-open text-xs" aria-hidden="true"></i>
  <span
    x-ref="tip"
    x-show="open"
    x-cloak
    role="tooltip"
    :style="`top: ${top}px; left: ${left}px; visibility: ${placed ? 'visible' : 'hidden'}`"
    class="fixed z-50 whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1.5 text-xs font-medium normal-case tracking-normal text-white shadow-lg pointer-events-none"
  >{{ $label }}</span>
</span>
