<script setup>
import { inject } from 'vue';

/**
 * Curseur de réglage numérique : libellé, glissière et valeur courante.
 * Le libellé peut contenir du balisage via le slot par défaut (ex. icône de monnaie).
 * `valueWidth` règle la largeur réservée à la valeur affichée.
 */
const model = defineModel({ type: Number, required: true });

defineProps({
    label:      { type: String, default: '' },
    min:        { type: Number, default: 0 },
    max:        { type: Number, required: true },
    step:       { type: Number, default: 1 },
    id:         { type: String, default: undefined },
    valueWidth: { type: String, default: null },   // classe Tailwind (« w-10 »…), sinon selon la densité
});

const compact = inject('configCompact', false);
</script>

<template>
    <div class="flex flex-col gap-1 min-w-0">
        <label :for="id" :class="compact ? 'text-2xs text-slate-500' : 'text-xs text-slate-600'">
            <slot>{{ label }}</slot>
        </label>
        <div class="flex items-center gap-2">
            <input :id="id" v-model.number="model" type="range" :min="min" :max="max" :step="step"
                   class="flex-1 accent-teal-500" :class="compact ? 'h-1' : 'h-1.5'" />
            <span class="text-xs font-bold text-slate-700 text-right shrink-0"
                  :class="valueWidth ?? (compact ? 'w-5' : 'w-6')"><slot name="value">{{ model }}</slot></span>
        </div>
    </div>
</template>
