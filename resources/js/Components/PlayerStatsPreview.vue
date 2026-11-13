<script setup>
import { computed } from 'vue';
import { PLAYER_STATS } from '@/Enums/playerStats.js';

// Aperçu visuel des statistiques d'un joueur (barres). `stats` = { speed: 53, … }.
const props = defineProps({
    stats: { type: Object, required: true },
});

const bars = computed(() =>
    PLAYER_STATS.map((def) => ({ ...def, value: Number(props.stats[def.key] ?? 0) })).filter((s) => s.value > 0),
);
</script>

<template>
    <div v-if="bars.length" class="mb-4 border border-slate-300 rounded-xl bg-slate-50 p-3">
        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Aperçu des statistiques</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1.5">
            <div v-for="stat in bars" :key="stat.key" class="flex items-center gap-2">
                <span class="w-16 text-[11px] text-slate-500 shrink-0">{{ stat.short ?? stat.label }}</span>
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all" :class="stat.color"
                         :style="{ width: Math.min(stat.value, 100) + '%' }"></div>
                </div>
                <span class="w-7 text-right text-[11px] font-bold text-slate-700">{{ stat.value }}</span>
            </div>
        </div>
    </div>
</template>
