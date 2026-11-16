@props(['text', 'label' => 'Copy'])

<button
  type="button"
  x-data="{
    copied: false,
    copy(text) {
      const done = () => { this.copied = true; setTimeout(() => this.copied = false, 1500); };
      const fallback = () => {
        const area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        const ok = document.execCommand('copy');
        document.body.removeChild(area);
        if (ok) { done(); }
      };
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(done, fallback);
        return;
      }
      fallback();
    },
  }"
  x-on:click="copy(@js($text))"
  {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-xs text-gray-500 hover:text-lime-700']) }}
  title="{{ $label }}"
  aria-label="{{ $label }}"
>
  <i class="fas" :class="copied ? 'fa-check text-lime-600' : 'fa-copy'" aria-hidden="true"></i>
  <span x-show="copied" x-cloak class="text-lime-700">Copied</span>
</button>
