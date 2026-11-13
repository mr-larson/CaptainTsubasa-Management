<script setup>
import { computed } from 'vue';
import { useAnimatedStats } from '@/Composables/useAnimatedStats.js';

const props = defineProps({
    challenges: { type: Object, required: true },
});

const STATUS = {
    done:      { icon: '✅', text: 'Réussi',     cls: 'text-emerald-600' },
    failed:    { icon: '❌', text: 'Raté',        cls: 'text-rose-500' },
    on_track:  { icon: '🟢', text: 'En bonne voie', cls: 'text-emerald-600' },
    off_track: { icon: '⏳', text: 'En cours',    cls: 'text-slate-400' },
};

const TIER_CLS = {
    easy:   'bg-emerald-50 text-emerald-600 border-emerald-200',
    medium: 'bg-amber-50 text-amber-600 border-amber-200',
    hard:   'bg-rose-50 text-rose-600 border-rose-200',
};

const MAX_KEYS = ['goals_against_max', 'rank_max'];

// Progression (0–100) pour la barre : plafonnée, inversée pour les « au plus ».
function progress(o) {
    if (o.status === 'done') return 100;
    if (MAX_KEYS.includes(o.key)) {
        if (o.key === 'rank_max') return o.value <= o.target ? 100 : Math.max(5, Math.round(o.target / o.value * 100));
        return o.status === 'failed' ? 100 : Math.round(Math.min(1, o.value / Math.max(1, o.target)) * 100);
    }
    return Math.round(Math.min(1, o.value / Math.max(1, o.target)) * 100);
}

function barCls(o) {
    if (o.status === 'done') return 'bg-emerald-500';
    if (o.status === 'failed') return 'bg-rose-400';
    if (o.key === 'goals_against_max') return 'bg-amber-400'; // jauge de « budget » de buts encaissés
    return 'bg-teal-400';
}

function valueText(o) {
    if (o.key === 'rank_max') return `${challengeValue(`${o.key}:value`)}e / ${o.target}e`;
    if (o.key === 'budget_min') return `${challengeValue(`${o.key}:value`).toLocaleString()} / ${o.target.toLocaleString()} billets`;
    return `${challengeValue(`${o.key}:value`)} / ${o.target}`;
}

const rewardMax = computed(() => props.challenges.objectives.reduce((s, o) => s + o.reward, 0));
const { root, value: challengeValue, width: challengeWidth } = useAnimatedStats(
    () => Object.fromEntries(props.challenges.objectives.flatMap(objective => [
        [`${objective.key}:progress`, progress(objective)], [`${objective.key}:value`, objective.value],
    ])),
);
</script>

<template>
    <div ref="root" class="border border-slate-200 rounded-xl bg-slate-50 p-4">
        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">🎲 Défis de la saison</h3>
            <span class="text-[11px] text-slate-400">
                <span class="font-bold text-slate-700">{{ challenges.points_secured }}</span> / {{ challenges.points_max }} pts acquis
                · {{ (challenges.reward_paid ?? 0).toLocaleString() }} / {{ rewardMax.toLocaleString() }} <GameBanknote /> versés
            </span>
        </div>

        <ul class="flex flex-col gap-2.5">
            <li v-for="o in challenges.objectives" :key="o.key" class="flex flex-col gap-1">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="shrink-0" :title="o.family_label">{{ o.family_icon }}</span>
                    <span class="text-xs font-semibold text-slate-700 leading-snug flex-1 min-w-0">{{ o.label }}</span>
                    <span class="shrink-0 text-2xs font-bold px-1.5 py-0.5 rounded-full border" :class="TIER_CLS[o.tier]">
                        {{ o.tier_label }} · {{ o.points }} pt{{ o.points > 1 ? 's' : '' }}
                    </span>
                </div>
                <div class="flex items-center gap-2 pl-6">
                    <div class="flex-1 h-1.5 rounded-full bg-slate-200 overflow-hidden">
                        <div class="h-full rounded-full" :class="barCls(o)" :style="{ width: challengeWidth(`${o.key}:progress`) }"></div>
                    </div>
                    <span class="text-2xs text-slate-500 w-24 text-right tabular-nums">{{ valueText(o) }}</span>
                    <span class="text-2xs font-semibold w-24 text-right" :class="STATUS[o.status].cls">
                        {{ STATUS[o.status].icon }} {{ STATUS[o.status].text }}
                        <span v-if="o.reward_paid" class="block">+{{ o.reward_paid }} <GameBanknote /> versés</span>
                    </span>
                </div>
            </li>
        </ul>
        <p class="text-2xs text-slate-400 mt-3">
            Les primes sont versées dès que le résultat est définitif. Les défis qui dépendent du bilan final sont validés en fin de saison.
        </p>
    </div>
</template>
