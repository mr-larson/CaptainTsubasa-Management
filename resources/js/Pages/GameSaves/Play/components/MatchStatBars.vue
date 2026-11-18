<script setup>
import SectionTitle from '@/Components/SectionTitle.vue';
import { useAnimatedStats } from '@/Composables/useAnimatedStats.js';

const props = defineProps({
    bars: { type: Array, default: () => [] },
    matchId: { type: [Number, String], default: null },
    homeTeamName: { type: String, default: '' },
    awayTeamName: { type: String, default: '' },
});
const { root, value: animatedValue } = useAnimatedStats(
    () => Object.fromEntries(props.bars.map(bar => [bar.label, 100])),
    () => props.matchId,
);
const animatedCount = (bar, side) => Math.round(bar[side] * animatedValue(bar.label) / 100);
const statBarWidth = (value, other) => value + other ? value / (value + other) * 100 : 50;
</script>

<template>
            <div ref="root" class="border border-slate-200 rounded-xl bg-slate-50 p-4 shadow-card">
                <SectionTitle class="mb-3">Comparatif équipes</SectionTitle>
                <div class="space-y-2">
                    <div v-for="bar in bars" :key="bar.label">
                        <div class="text-2xs text-slate-400 text-center mb-0.5">{{ bar.label }}</div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 text-right text-xs font-black"
                                 :class="bar.home > bar.away ? 'text-blue-600' : 'text-slate-500'">{{ animatedCount(bar, 'home') }}</div>
                            <div class="flex-1 h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full flex origin-center" :style="{ transform: `scaleX(${animatedValue(bar.label) / 100})` }">
                                <div class="h-full bg-blue-500"
                                     :style="{ width: statBarWidth(bar.home, bar.away) + '%' }"></div>
                                <div class="h-full bg-orange-400"
                                     :style="{ width: statBarWidth(bar.away, bar.home) + '%' }"></div>
                                </div>
                            </div>
                            <div class="w-8 text-left text-xs font-black"
                                 :class="bar.away > bar.home ? 'text-orange-500' : 'text-slate-500'">{{ animatedCount(bar, 'away') }}</div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between mt-2 text-[11px] font-semibold">
                    <span class="text-blue-600">🏠 {{ homeTeamName }}</span>
                    <span class="text-orange-500">{{ awayTeamName }} ✈️</span>
                </div>
            </div>
</template>
