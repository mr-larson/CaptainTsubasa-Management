<script setup>
// Bouton principal des pages d'authentification : même turquoise que l'accueil.
defineProps({
    processing: { type: Boolean, default: false },
});
</script>

<template>
    <button type="submit" :disabled="processing"
            class="inline-flex min-h-11 w-full items-center justify-center rounded-full bg-teal-500 px-6 text-sm font-bold text-slate-700 transition-colors hover:bg-teal-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
        <slot />
    </button>
</template>
