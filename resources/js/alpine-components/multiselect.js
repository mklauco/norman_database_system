/**
 * Multi-select combobox used by resources/views/components/multiselect.blade.php.
 * Options are read from the <script type="application/json" x-ref="options"> element.
 */
const MAX_VISIBLE = 200;
const MAX_LIST_HEIGHT = 242;
const GAP = 8;

export default function multiselect(config = {}) {
    return {
        items: [],
        labels: new Map(),
        selected: config.selected ?? [],
        placeholder: config.placeholder ?? 'Type here...',
        search: '',
        open: false,
        activeIndex: -1,
        uid: null,
        position: { top: null, bottom: null, left: 0, width: 0, maxHeight: MAX_LIST_HEIGHT },
        reposition: null,

        init() {
            try {
                this.items = JSON.parse(this.$refs.options.textContent || '[]');
            } catch (e) {
                this.items = [];
            }
            this.labels = new Map(this.items.map((item) => [item.value, item.label]));
            this.uid = this.$id('multiselect');
            this.reposition = () => this.updatePosition();

            this.$watch('activeIndex', () => this.$nextTick(() => this.scrollActiveIntoView()));
            this.$watch('selected', () => this.$nextTick(() => {
                this.$root.querySelector('input[type=hidden]')?.dispatchEvent(new Event('change', { bubbles: true }));
            }));
        },

        /**
         * Replaces the options (e.g. a list that depends on another field) and drops
         * selected values that are no longer offered.
         */
        setItems(items) {
            this.items = items.map((item) => ({ value: String(item.value), label: item.label }));
            this.labels = new Map(this.items.map((item) => [item.value, item.label]));

            const offered = new Set(this.items.map((item) => item.value));
            this.selected = this.selected.filter((value) => offered.has(value));
        },

        destroy() {
            this.stopTracking();
        },

        get listId() {
            return this.uid + '-list';
        },

        optionId(idx) {
            return this.uid + '-opt-' + idx;
        },

        get matchingItems() {
            const term = this.search.trim().toLowerCase();
            const chosen = new Set(this.selected);

            return this.items.filter((item) =>
                !chosen.has(item.value) && (term === '' || (item.label ?? '').toLowerCase().includes(term))
            );
        },

        get visibleItems() {
            return this.matchingItems.slice(0, MAX_VISIBLE);
        },

        get hiddenCount() {
            return Math.max(0, this.matchingItems.length - MAX_VISIBLE);
        },

        get listStyle() {
            const p = this.position;

            return {
                left: p.left + 'px',
                width: p.width + 'px',
                top: p.top === null ? 'auto' : p.top + 'px',
                bottom: p.bottom === null ? 'auto' : p.bottom + 'px',
                maxHeight: p.maxHeight + 'px',
            };
        },

        labelOf(value) {
            return this.labels.get(value) ?? value;
        },

        shortLabel(label, maxChars) {
            label = String(label ?? '');

            return label.length <= maxChars ? label : label.substring(0, maxChars) + '...';
        },

        openList() {
            if (this.open) {
                return;
            }
            this.open = true;
            this.updatePosition();
            window.addEventListener('scroll', this.reposition, true);
            window.addEventListener('resize', this.reposition);
        },

        close() {
            this.open = false;
            this.search = '';
            this.activeIndex = -1;
            this.stopTracking();
        },

        toggle() {
            if (this.open) {
                this.close();
            } else {
                this.$refs.input.focus();
                this.openList();
            }
        },

        stopTracking() {
            window.removeEventListener('scroll', this.reposition, true);
            window.removeEventListener('resize', this.reposition);
        },

        updatePosition() {
            const rect = this.$refs.field.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const spaceBelow = viewportHeight - rect.bottom - GAP;
            const spaceAbove = rect.top - GAP;
            const openAbove = spaceBelow < MAX_LIST_HEIGHT && spaceAbove > spaceBelow;

            this.position = {
                left: rect.left,
                width: rect.width,
                top: openAbove ? null : rect.bottom,
                bottom: openAbove ? viewportHeight - rect.top : null,
                maxHeight: Math.max(80, Math.min(MAX_LIST_HEIGHT, openAbove ? spaceAbove : spaceBelow)),
            };
        },

        listElement() {
            return document.getElementById(this.listId);
        },

        onClickOutside(event) {
            const list = this.listElement();
            if (list && list.contains(event.target)) {
                return;
            }
            if (this.open) {
                this.close();
            }
        },

        onFocusOut(event) {
            const next = event.relatedTarget;
            const list = this.listElement();
            if (next && (this.$el.contains(next) || (list && list.contains(next)))) {
                return;
            }
            this.close();
        },

        move(step) {
            this.openList();
            const count = this.visibleItems.length;
            if (!count) {
                this.activeIndex = -1;
                return;
            }
            if (this.activeIndex < 0) {
                this.activeIndex = step > 0 ? 0 : count - 1;
                return;
            }
            this.activeIndex = (this.activeIndex + step + count) % count;
        },

        selectActive() {
            const item = this.visibleItems[this.activeIndex];
            if (item) {
                this.choose(item);
            }
        },

        choose(item) {
            if (!this.selected.includes(item.value)) {
                this.selected.push(item.value);
            }
            this.search = '';
            this.activeIndex = -1;
            this.$refs.input.focus();
            this.$nextTick(() => this.updatePosition());
        },

        removeAt(idx) {
            this.selected.splice(idx, 1);
            this.$nextTick(() => {
                if (this.open) {
                    this.updatePosition();
                }
            });
        },

        onBackspace() {
            if (this.search === '' && this.selected.length) {
                this.selected.pop();
                this.$nextTick(() => {
                    if (this.open) {
                        this.updatePosition();
                    }
                });
            }
        },

        scrollActiveIntoView() {
            if (this.activeIndex < 0 || !this.open) {
                return;
            }
            const list = this.listElement();
            const option = document.getElementById(this.optionId(this.activeIndex));
            if (!list || !option) {
                return;
            }
            const top = option.offsetTop;
            const bottom = top + option.offsetHeight;
            if (top < list.scrollTop) {
                list.scrollTop = top;
            } else if (bottom > list.scrollTop + list.clientHeight) {
                list.scrollTop = bottom - list.clientHeight;
            }
        },
    };
}

window.multiselect = multiselect;
