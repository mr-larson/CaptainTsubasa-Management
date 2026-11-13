<script setup>
import { computed } from 'vue';

// Bilan d'une équipe (victoires / nuls / défaites) en barres.
const props = defineProps({
    wins: { type: [Number, String], default: 0 },
    draws: { type: [Number, String], default: 0 },
    losses: { type: [Number, String], default: 0 },
});

const bars = computed(() => {
    const w = Number(props.wins ?? 0);
    const d = Number(props.draws ?? 0);
    const l = Number(props.losses ?? 0);
    const total = Math.max(w + d + l, 1);
    return [
        { key: 'wins', label: 'Victoires', value: w, color: 'bg-emerald-400' },
        { key: 'draws', label: 'Nuls', value: d, color: 'bg-amber-400' },
        { key: 'losses', label: 'Défaites', value: l, color: 'bg-rose-400' },
    ].map((b) => ({ ...b, percent: Math.round((b.value / total) * 100) }));
});
</script>

<template>
    <div class="mb-4 border border-slate-200 rounded-xl bg-slate-50 p-3">
        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Bilan</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-1.5">
            <div v-for="stat in bars" :key="stat.key" class="flex items-center gap-2">
                <span class="w-16 text-[11px] text-slate-500 shrink-0">{{ stat.label }}</span>
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all" :class="stat.color" :style="{ width: stat.percent + '%' }"></div>
                </div>
                <span class="w-7 text-right text-[11px] font-bold text-slate-700">{{ stat.value }}</span>
            </div>
        </div>
    </div>
</template>
