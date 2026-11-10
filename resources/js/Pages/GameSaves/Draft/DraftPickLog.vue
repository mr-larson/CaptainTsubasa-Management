<script setup>
import { computed } from 'vue';
import { positionColor, positionGroup } from './draftPositions.js';

// Les cinq derniers picks, du plus récent au plus ancien.
const props = defineProps({
    picks:       { type: Array,    default: () => [] },
    isHumanTeam: { type: Function, required: true },
});

const recent = computed(() => props.picks.slice(-5).reverse());
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-3">
        <h4 class="text-2xs font-bold text-slate-700 uppercase tracking-wider mb-2">Derniers picks</h4>
        <div v-if="recent.length" class="space-y-1">
            <div v-for="(pick, i) in recent" :key="i"
                 class="flex items-center gap-2 text-[11px] px-2 py-1 rounded"
                 :class="isHumanTeam(pick.team_id) ? 'bg-teal-50' : 'bg-white'">
                <span class="font-bold text-slate-500 w-14 truncate">{{ pick.team_name }}</span>
                <span class="flex-1 text-slate-700 truncate">{{ pick.player_name }}</span>
                <span :class="positionColor(pick.position)" class="px-1.5 py-0.5 rounded text-3xs font-bold">
                    {{ positionGroup(pick.position) }}
                </span>
            </div>
        </div>
        <p v-else class="text-2xs text-slate-400 italic">Aucun pick encore</p>
    </div>
</template>
