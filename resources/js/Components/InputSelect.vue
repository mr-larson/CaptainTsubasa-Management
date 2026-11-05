<script setup>
import { computed } from 'vue';
import { fieldClass } from './fieldStyles';

// Les attributs passés (class, required, aria-*, @change…) tombent directement sur le <select>.
const props = defineProps({
    modelValue: { type: [String, Number, Boolean], default: '' },
    // Option désactivée affichée tant que rien n'est choisi ; '' pour ne pas en afficher.
    placeholder: { type: String, default: 'Choisir…' },
    // md : formulaire (pilule) ; sm : contrôle compact des écrans de jeu (rectangle, text-xs).
    size: { type: String, default: 'md' },
});

const emit = defineEmits(['update:modelValue']);

const internalValue = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});
</script>

<template>
    <select v-model="internalValue" :class="['w-full', size === 'sm'
        ? ['py-1.5 pl-2.5 pr-8', fieldClass({ shape: 'rect', size: 'sm' })]
        : fieldClass()]">
        <option v-if="placeholder" disabled value="">
            {{ placeholder }}
        </option>
        <slot />
    </select>
</template>
