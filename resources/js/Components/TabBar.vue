<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Barre d'onglets commune (rôles ARIA, tabindex itinérant, flèches ← → / Début / Fin).
// Un indicateur unique se déplace entre les onglets et suit leur largeur réelle.
const props = defineProps({
    // [{ key, label, count?, badge?, disabled?, disabledHint? }]
    tabs: { type: Array, required: true },
    modelValue: { type: [String, Number], default: null },
    size: { type: String, default: 'md' }, // md | sm
    // true : les onglets passent à la ligne ; false : la barre défile horizontalement.
    wrap: { type: Boolean, default: false },
    label: { type: String, default: null },
    outlined: { type: Boolean, default: false },
    // true : les onglets se partagent la largeur disponible sans comprimer leurs libellés.
    stretch: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
const buttons = ref([]);
const track = ref(null);
const indicator = ref(null);
const ready = ref(false);
const liquidShape = ref(null);
let stretchAnimation;
let observer;
let disposed = false;

const indicatorStyle = computed(() => indicator.value ? {
    width: `${indicator.value.width}px`,
    height: `${indicator.value.height}px`,
    transform: `translate(${indicator.value.left}px, ${indicator.value.top}px)`,
} : {});

function measure() {
    const active = buttons.value.find(button => button?.dataset.tab === String(props.modelValue));
    indicator.value = active && !active.disabled ? {
        left: active.offsetLeft - 8,
        top: active.offsetTop - 6,
        width: active.offsetWidth + 16,
        height: active.offsetHeight + 12,
    } : null;
}

function observe() {
    observer?.disconnect();
    if (track.value) observer?.observe(track.value);
    buttons.value.forEach(button => { if (button) observer?.observe(button); });
    measure();
}

watch(() => [props.modelValue, props.tabs, props.size, props.wrap, props.stretch], observe, { deep: true, flush: 'post' });
watch(() => props.modelValue, async (current, previous) => {
    if (current === previous) return;
    const before = buttons.value.find(button => button?.dataset.tab === String(previous));
    await nextTick();
    stretchAnimation?.cancel();
    if (disposed || !ready.value || !liquidShape.value
        || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const after = buttons.value.find(button => button?.dataset.tab === String(current));
    if (!before || !after || after.disabled) return;
    const distance = after.offsetLeft - before.offsetLeft;
    // Stretch only the decorative shape, towards the destination; labels stay still.
    const sameRow = Math.abs(after.offsetTop - before.offsetTop) < 2;
    if (!sameRow || !distance) return;
    const amount = Math.min(0.28, 0.16 + Math.abs(distance) / Math.max(after.offsetWidth, 1) * 0.045);
    stretchAnimation = liquidShape.value.animate([
        { transform: 'scale(1, 1)', easing: 'cubic-bezier(0.2, 0, 0.4, 1)' },
        { transform: `scale(${1 + amount}, ${1 - amount * 0.3})`, offset: 0.42, easing: 'cubic-bezier(0.2, 0, 0.2, 1)' },
        { transform: 'scale(1, 1)' },
    ], {
        duration: 460,
        easing: 'linear',
    });
    liquidShape.value.style.transformOrigin = distance > 0 ? 'left center' : 'right center';
});

onMounted(() => {
    observer = new ResizeObserver(measure);
    observe();
    requestAnimationFrame(() => { if (!disposed) ready.value = true; });
    document.fonts?.ready.then(() => { if (!disposed) measure(); });
});
onBeforeUnmount(() => { disposed = true; stretchAnimation?.cancel(); observer?.disconnect(); });

function select(tab) {
    if (!tab.disabled) emit('update:modelValue', tab.key);
}

function move(step) {
    const enabled = props.tabs.filter(t => !t.disabled);
    if (!enabled.length) return;
    const index = enabled.findIndex(t => t.key === props.modelValue);
    const next = step === 'first' ? enabled[0]
        : step === 'last' ? enabled[enabled.length - 1]
            : enabled[(index + step + enabled.length) % enabled.length];
    emit('update:modelValue', next.key);
    nextTick(() => buttons.value.find(b => b?.dataset.tab === String(next.key))?.focus());
}

const SIZES = { md: 'px-3 py-2 text-sm', sm: 'px-3 py-1.5 text-xs' };
</script>

<template>
    <nav ref="track" role="tablist" :aria-label="label"
         class="magic-tabs relative isolate flex gap-1 rounded-xl bg-slate-100 px-2 py-1.5"
         :class="[wrap ? 'flex-wrap' : 'overflow-x-auto', { 'magic-tabs--outlined': outlined }]"
         @keydown.right.prevent="move(1)" @keydown.left.prevent="move(-1)"
         @keydown.home.prevent="move('first')" @keydown.end.prevent="move('last')">
        <div v-if="indicator" aria-hidden="true" class="magic-indicator pointer-events-none absolute left-0 top-0"
             :class="{ 'magic-indicator--ready': ready }" :style="indicatorStyle">
            <div ref="liquidShape" class="absolute inset-0">
            <svg class="absolute inset-0 h-full w-full text-white overflow-visible" viewBox="0 0 200 60" preserveAspectRatio="none">
                <path fill="currentColor" transform="translate(0 60) scale(1 -1)" d="M0 0H200C182 0 190 30 180 46C175 54 168 58 156 58H44C32 58 25 54 20 46C10 30 18 0 0 0Z" />
                <path v-if="outlined" fill="none" stroke="#e2e8f0" stroke-width="1" vector-effect="non-scaling-stroke"
                      transform="translate(0 60) scale(1 -1)"
                      d="M0 1.5C18 1.5 10 30 20 46C25 54 32 58 44 58H156C168 58 175 54 180 46C190 30 182 1.5 200 1.5" />
            </svg>
            <span class="absolute inset-x-3 inset-y-2 magic-active-pill rounded-full border border-slate-300 bg-white"></span>
            </div>
        </div>
        <button v-for="t in tabs" :key="t.key" ref="buttons" type="button" role="tab"
                :data-tab="t.key"
                :aria-selected="modelValue === t.key"
                :tabindex="modelValue === t.key ? 0 : -1"
                :disabled="t.disabled"
                :title="t.disabled ? t.disabledHint : null"
                class="magic-tab relative z-10 shrink-0 whitespace-nowrap rounded-full font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-900"
                :class="[SIZES[size] ?? SIZES.md, stretch ? 'min-w-max flex-1 text-center' : '', t.disabled
                    ? 'text-slate-400 cursor-not-allowed'
                    : modelValue === t.key
                        ? 'text-teal-700'
                        : 'text-slate-500 hover:text-slate-800']"
                @click="select(t)">
            {{ t.label }}<span v-if="t.count !== undefined && t.count !== null" class="opacity-60"> ({{ t.count }})</span>
            <span v-if="t.badge" class="ml-1 rounded-full px-1.5 py-0.5 text-2xs font-bold"
                  :class="modelValue === t.key ? 'bg-teal-50 text-teal-700' : 'bg-slate-200 text-slate-600'">
                {{ t.badge }}
            </span>
        </button>
    </nav>
</template>

<style scoped>
.magic-tabs {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.magic-tabs--outlined::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    border: 1px solid #e2e8f0;
    border-top: 0;
    border-radius: inherit;
}

.magic-active-pill {
    box-shadow: 0 2px 6px rgb(15 23 42 / 12%), 0 1px 2px rgb(15 23 42 / 6%);
}

.magic-indicator--ready {
    transition: transform 340ms cubic-bezier(0.22, 1, 0.36, 1),
                width 340ms cubic-bezier(0.22, 1, 0.36, 1),
                height 340ms cubic-bezier(0.22, 1, 0.36, 1);
}

.magic-tab {
    transition: color 180ms ease;
}

@media (prefers-reduced-motion: reduce) {
    .magic-indicator--ready, .magic-tab {
        transition: none;
    }
    .magic-tab[aria-selected="true"] {
        transform: none;
    }
}
</style>
