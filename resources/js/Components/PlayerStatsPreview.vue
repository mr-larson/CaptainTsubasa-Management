<script setup>
import { computed } from 'vue';
import { useAnimatedStats } from '@/Composables/useAnimatedStats.js';
import { PLAYER_STATS } from '@/Enums/playerStats.js';

// Aperçu visuel des statistiques d'un joueur (barres). `stats` = { speed: 53, … }.
const props = defineProps({
    stats: { type: Object, required: true },
    playerId: { type: [Number, String], default: null },
});

const bars = computed(() =>
    PLAYER_STATS.map((def) => ({ ...def, value: Number(props.stats[def.key] ?? 0) })).filter((s) => s.value > 0),
);
const { root, value: animatedValue, width: animatedWidth } = useAnimatedStats(
    () => Object.fromEntries(bars.value.map(stat => [stat.key, stat.value])),
    () => props.playerId,
);
</script>

<template>
    <div ref="root" v-if="bars.length" class="mb-4 border border-slate-200 rounded-xl bg-white p-3">
        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Aperçu des statistiques</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1.5">
            <div v-for="stat in bars" :key="stat.key" class="flex items-center gap-2">
                <span class="w-16 text-[11px] text-slate-500 shrink-0">{{ stat.short ?? stat.label }}</span>
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full rounded-full" :class="stat.color"
                         :style="{ width: animatedWidth(stat.key) }"></div>
                </div>
                <span class="w-7 text-right text-[11px] font-bold text-slate-700">{{ animatedValue(stat.key) }}</span>
            </div>
        </div>
    </div>
</template>
