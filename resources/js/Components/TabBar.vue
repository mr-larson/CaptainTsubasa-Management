<script setup>
import { nextTick, ref } from 'vue';

// Barre d'onglets commune (rôles ARIA, tabindex itinérant, flèches ← → / Début / Fin).
// Le parent fournit la bordure basse (border-b) : l'onglet actif la recouvre grâce au -mb-px.
const props = defineProps({
    // [{ key, label, count?, badge?, disabled?, disabledHint? }]
    tabs: { type: Array, required: true },
    modelValue: { type: [String, Number], default: null },
    size: { type: String, default: 'md' }, // md | sm
    // true : les onglets passent à la ligne ; false : la barre défile horizontalement.
    wrap: { type: Boolean, default: false },
    label: { type: String, default: null },
    // true : les onglets se partagent la largeur disponible sans comprimer leurs libellés.
    stretch: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
const buttons = ref([]);

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
    <nav role="tablist" :aria-label="label"
         class="-mb-px flex gap-1"
         :class="wrap ? 'flex-wrap' : 'overflow-x-auto'"
         @keydown.right.prevent="move(1)" @keydown.left.prevent="move(-1)"
         @keydown.home.prevent="move('first')" @keydown.end.prevent="move('last')">
        <button v-for="t in tabs" :key="t.key" ref="buttons" type="button" role="tab"
                :data-tab="t.key"
                :aria-selected="modelValue === t.key"
                :tabindex="modelValue === t.key ? 0 : -1"
                :disabled="t.disabled"
                :title="t.disabled ? t.disabledHint : null"
                class="whitespace-nowrap border-b-2 font-semibold transition-colors"
                :class="[SIZES[size] ?? SIZES.md, stretch ? 'min-w-max flex-1 text-center' : '', t.disabled
                    ? 'border-transparent text-slate-300 cursor-not-allowed'
                    : modelValue === t.key
                        ? 'border-teal-500 text-teal-700'
                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700']"
                @click="select(t)">
            {{ t.label }}<span v-if="t.count !== undefined && t.count !== null" class="opacity-60"> ({{ t.count }})</span>
            <span v-if="t.badge" class="ml-1 rounded-full px-1.5 py-0.5 text-2xs font-bold"
                  :class="modelValue === t.key ? 'bg-teal-50 text-teal-700' : 'bg-slate-100 text-slate-500'">
                {{ t.badge }}
            </span>
        </button>
    </nav>
</template>
