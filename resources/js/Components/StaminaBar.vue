<script setup>
import { computed } from 'vue';

/**
 * Jauge d'endurance : barre colorée selon le niveau (vert dès 60, ambre dès 30, rouge sinon).
 * Taille, fond de la gauge et infobulle se règlent par les attributs du composant
 * (`class="w-8 h-[3px] bg-slate-300"`, `title="…"`).
 */
const props = defineProps({
    value: { type: Number, default: 0 },
});

const fill = computed(() => (props.value >= 60 ? 'bg-emerald-400' : props.value >= 30 ? 'bg-amber-400' : 'bg-rose-500'));
const width = computed(() => `${Math.max(0, Math.min(props.value, 100))}%`);
</script>

<template>
    <div class="overflow-hidden rounded-full">
        <div class="h-full rounded-full transition-all" :class="fill" :style="{ width }"></div>
    </div>
</template>
