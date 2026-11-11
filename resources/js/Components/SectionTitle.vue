<script setup>
// Petit titre de section en majuscules (« Effectif », « Proposer un contrat »…).
defineProps({
    as: { type: String, default: 'h4' },
});
</script>

<template>
    <component :is="as" class="text-2xs font-bold uppercase tracking-wider text-slate-400">
        <slot />
    </component>
</template>
