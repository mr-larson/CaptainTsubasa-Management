<script setup>
import { computed } from 'vue';
import { BUTTON_BASE, buttonSize } from './buttonStyles';

const props = defineProps({
    // 'submit' par défaut : les formulaires BDD comptent dessus.
    type: { type: String, default: 'submit' },
    size: { type: String, default: 'md' }, // sm | md | lg
    // teal = action principale ; indigo = Coupe du Monde ; rose = confirmation destructive ; emerald = succès.
    tone: { type: String, default: 'teal' },
});

const TONES = {
    teal: 'bg-teal-500 hover:bg-teal-600 focus-visible:ring-teal-400',
    indigo: 'bg-indigo-500 hover:bg-indigo-600 focus-visible:ring-indigo-400',
    rose: 'bg-rose-500 hover:bg-rose-600 focus-visible:ring-rose-400',
    // Retour de succès (ex. « Enregistré ! »).
    emerald: 'bg-emerald-500 hover:bg-emerald-600 focus-visible:ring-emerald-400',
};

const classes = computed(() => [
    BUTTON_BASE,
    buttonSize(props.size),
    TONES[props.tone] ?? TONES.teal,
    'font-bold text-white',
    props.size === 'lg' ? '' : 'shadow-sm',
]);
</script>

<template>
    <button :type="type" :class="classes">
        <slot />
    </button>
</template>
