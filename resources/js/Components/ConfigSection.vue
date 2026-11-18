<script setup>
import { provide } from 'vue';

/**
 * Bloc de réglages (« Cartes », « Match », « Entraînement »…) partagé par
 * l'écran de création de partie (compact) et l'onglet Gestion (confortable).
 * Il transmet sa densité aux ConfigRange / ConfigToggle qu'il contient.
 */
const props = defineProps({
    icon:    { type: String, default: '' },
    title:   { type: String, required: true },
    compact: { type: Boolean, default: false },
});

provide('configCompact', props.compact);
</script>

<template>
    <div class="border border-slate-200 rounded-lg flex flex-col"
         :class="compact ? 'bg-slate-50 p-3 gap-2' : 'bg-white p-4 gap-3'">
        <h4 class="font-bold text-slate-700 flex items-center gap-1.5" :class="compact ? 'text-xs' : 'text-sm'">
            <span :class="compact ? '' : 'text-base'">{{ icon }}</span> {{ title }}
        </h4>
        <slot />
    </div>
</template>
