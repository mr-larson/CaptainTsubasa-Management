<script setup>
import { computed } from 'vue';
import { positionColor, positionGroup } from './draftPositions.js';

// Les 20 derniers picks, du plus récent au plus ancien.
const props = defineProps({
    picks:       { type: Array,    default: () => [] },
    isHumanTeam: { type: Function, required: true },
});

const recent = computed(() => [...props.picks].reverse().slice(0, 20));
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-white p-3 max-h-[250px] overflow-y-auto">
        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Derniers picks</h4>
        <div v-if="recent.length" class="space-y-1">
            <div v-for="(pick, i) in recent" :key="i"
                 class="flex items-center gap-2 text-[11px] px-2 py-1 rounded"
                 :class="isHumanTeam(pick.team_id) ? 'bg-amber-50' : 'bg-slate-50'">
                <span class="font-bold text-slate-500 w-14 truncate">{{ pick.team_name }}</span>
                <span class="flex-1 text-slate-700 truncate">{{ pick.player_name }}</span>
                <span :class="positionColor(pick.position)" class="px-1.5 py-0.5 rounded text-[9px] font-bold">
                    {{ positionGroup(pick.position) }}
                </span>
            </div>
        </div>
        <p v-else class="text-[10px] text-slate-400 italic">Aucun pick encore</p>
    </div>
</template>
