<script setup>
/** Bouton « Afficher / Masquer le mot de passe » : `v-model` sur l'état visible. */
defineProps({
    modelValue: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <button type="button"
            class="mt-1 text-xs font-semibold text-teal-600 hover:text-teal-700"
            :aria-pressed="modelValue"
            @click="$emit('update:modelValue', !modelValue)">
        {{ modelValue ? 'Masquer le mot de passe' : 'Afficher le mot de passe' }}
    </button>
</template>
