<script setup>
import { computed } from 'vue';
import Panel from '@/Components/Panel.vue';
import PositionBadge from '@/Components/PositionBadge.vue';

// Les cinq derniers picks, du plus récent au plus ancien.
const props = defineProps({
    picks:       { type: Array,    default: () => [] },
    isHumanTeam: { type: Function, required: true },
});

const recent = computed(() => props.picks.slice(-5).reverse());
</script>

<template>
    <Panel padding="sm" title="Derniers picks">
        <div v-if="recent.length" class="space-y-1">
            <div v-for="(pick, i) in recent" :key="i"
                 class="flex items-center gap-2 text-[11px] px-2 py-1 rounded"
                 :class="isHumanTeam(pick.team_id) ? 'bg-teal-50' : 'bg-white'">
                <span class="font-bold text-slate-500 w-14 truncate">{{ pick.team_name }}</span>
                <span class="flex-1 text-slate-700 truncate">{{ pick.player_name }}</span>
                <PositionBadge :position="pick.position" />
            </div>
        </div>
        <p v-else class="text-2xs text-slate-500 italic">Aucun pick encore</p>
    </Panel>
</template>
