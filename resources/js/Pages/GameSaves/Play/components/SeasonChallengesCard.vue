<script setup>
import { computed } from 'vue';

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
    if (o.key === 'rank_max') return `${o.value}e / ${o.target}e`;
    if (o.key === 'budget_min') return `${o.value.toLocaleString()} / ${o.target.toLocaleString()} €`;
    return `${o.value} / ${o.target}`;
}

const rewardMax = computed(() => props.challenges.objectives.reduce((s, o) => s + o.reward, 0));
</script>

<template>
    <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">🎲 Défis de la saison</h3>
            <span class="text-[11px] text-slate-400">
                <span class="font-bold text-slate-700">{{ challenges.points_secured }}</span> / {{ challenges.points_max }} pts acquis
                · jusqu'à {{ rewardMax.toLocaleString() }} € de primes
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
                        <div class="h-full rounded-full transition-all" :class="barCls(o)" :style="{ width: progress(o) + '%' }"></div>
                    </div>
                    <span class="text-2xs text-slate-500 w-24 text-right tabular-nums">{{ valueText(o) }}</span>
                    <span class="text-2xs font-semibold w-24 text-right" :class="STATUS[o.status].cls">
                        {{ STATUS[o.status].icon }} {{ STATUS[o.status].text }}
                    </span>
                </div>
            </li>
        </ul>
        <p class="text-2xs text-slate-400 mt-3">
            Sans effet sur le championnat : chaque défi réussi rapporte une prime et des points pour le classement des défis de fin de saison. Toutes les équipes ont les leurs.
        </p>
    </div>
</template>
