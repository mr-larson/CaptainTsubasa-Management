<script setup>
import { computed } from 'vue';
import { usePlayerUtils } from '../usePlayerUtils.js';

/**
 * Affiche les postes secondaires d'un joueur — les postes où il peut aussi
 * évoluer (bonus de poste réduit en match). Rien n'est rendu s'il n'en a pas.
 */
const props = defineProps({
    player: { type: Object, required: true },
});

const { positionGroup, positionGroupColor, positionLabel } = usePlayerUtils();

const secondaries = computed(() => {
    const list = props.player?.secondary_positions ?? [];
    return (Array.isArray(list) ? list : []).filter(Boolean);
});
</script>

<template>
    <div v-if="secondaries.length" class="flex flex-wrap items-center gap-1">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mr-0.5"
              title="Postes où ce joueur peut aussi évoluer (bonus de poste réduit)">
            Aussi
        </span>
        <span v-for="pos in secondaries" :key="pos"
              class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold"
              :class="positionGroupColor(pos)"
              :title="positionLabel(pos)">
            {{ positionGroup(pos) }}
        </span>
    </div>
</template>
