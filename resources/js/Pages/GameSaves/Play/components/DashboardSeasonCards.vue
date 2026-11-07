<script setup>
// Dashboard — trajectoire de la saison (position semaine par semaine, écart avec
// l'objectif du mandat) et projection budgétaire.
import { computed, ref } from 'vue';
import { useSeasonTrajectory } from '../useSeasonTrajectory.js';

const props = defineProps({
    team:          { type: Object,  default: null },
    teams:         { type: Array,   default: () => [] },   // classement courant (useDashboard.standings)
    matches:       { type: Array,   default: () => [] },
    season:        { type: Number,  required: true },
    week:          { type: Number,  required: true },
    career:        { type: Object,  default: null },
    isWorldCup:    { type: Boolean, default: false },
    budget:        { type: Number,  default: 0 },
    incomeModel:   { type: Object,  default: null },       // { base, win_bonus, draw_bonus }
    trainingCost:  { type: Number,  default: 0 },
    maxTrainings:  { type: Number,  default: 3 },
});

const {
    positionHistory, teamCount, targetRank, remainingMatches, objective,
    totalWeeks, weeksLeft, projection,
} = useSeasonTrajectory({
    team: () => props.team, teams: () => props.teams, matches: () => props.matches,
    season: () => props.season, week: () => props.week, career: () => props.career,
    budget: () => props.budget, incomeModel: () => props.incomeModel,
});

// ── Graphe SVG ───────────────────────────────────────────────
const W = 320, H = 110, PAD_X = 18, PAD_Y = 10;
const yFor = (pos) => PAD_Y + (pos - 1) * (H - 2 * PAD_Y) / (teamCount.value - 1);
const points = computed(() => {
    const h = positionHistory.value;
    const step = h.length > 1 ? (W - 2 * PAD_X) / (h.length - 1) : 0;
    return h.map((p, i) => ({ ...p, x: h.length > 1 ? PAD_X + i * step : W / 2, y: yFor(p.position) }));
});
const linePath = computed(() => points.value.map((p, i) => `${i ? 'L' : 'M'}${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' '));
const dotClass = (pos) => targetRank.value == null
    ? 'bg-teal-500'
    : pos <= targetRank.value ? 'bg-emerald-500' : 'bg-rose-500';

// ── Infobulle des pastilles (survol, focus clavier ou tap) ─────
const hoveredWeek = ref(null);
const hovered = computed(() => points.value.find(p => p.week === hoveredWeek.value) ?? null);
const RESULT_STYLE = {
    V: { icon: '✅', text: 'text-emerald-300' },
    N: { icon: '🤝', text: 'text-slate-300' },
    D: { icon: '❌', text: 'text-rose-300' },
};
// Bulle centrée sur la pastille, recalée aux bords ; sous la pastille si elle est en haut.
const tooltipStyle = computed(() => {
    const p = hovered.value;
    if (!p) return {};
    const xPct = p.x / W * 100, yPct = p.y / H * 100;
    const tx = xPct < 20 ? '0%' : xPct > 80 ? '-100%' : '-50%';
    const below = yPct < 35;
    return {
        left: `${xPct}%`,
        top: `${yPct}%`,
        transform: `translate(${tx}, ${below ? '12px' : 'calc(-100% - 12px)'})`,
    };
});

// ── Finances ─────────────────────────────────────────────────
const weeklyTrainingCost = computed(() => props.trainingCost * props.maxTrainings);

const fmt = (n) => Number(n ?? 0).toLocaleString('fr-FR');
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <!-- Trajectoire -->
        <div v-if="!isWorldCup" class="md:col-span-2 border border-slate-200 rounded-xl bg-slate-50 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Trajectoire</h4>
                <div v-if="objective" class="flex flex-wrap items-center gap-2 text-[11px]">
                    <span class="font-semibold text-slate-500">🎯 {{ career.mandate.label }}</span>
                    <span class="px-2 py-0.5 rounded-full border font-semibold"
                          :class="objective.ok ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                                : objective.out ? 'bg-rose-50 border-rose-200 text-rose-700'
                                : 'bg-amber-50 border-amber-200 text-amber-700'">
                        {{ objective.ok ? '✓' : objective.out ? '✗' : '↗' }} {{ objective.text }}
                    </span>
                    <span class="text-slate-400">{{ remainingMatches }} match(s) restant(s)</span>
                </div>
            </div>

            <svg v-if="points.length" :viewBox="`0 0 ${W} ${H}`" class="w-full h-32" preserveAspectRatio="none"
                 role="img" :aria-label="`Position au classement : ${points.map(p => 'S' + p.week + ' ' + p.position + 'e').join(', ')}`">
                <!-- Zone objectif -->
                <rect v-if="targetRank" :x="PAD_X" :y="0" :width="W - 2 * PAD_X"
                      :height="yFor(targetRank) + (H - 2 * PAD_Y) / (teamCount - 1) / 2"
                      class="fill-emerald-100/60" />
                <line v-for="pos in [1, teamCount]" :key="pos" :x1="PAD_X" :x2="W - PAD_X" :y1="yFor(pos)" :y2="yFor(pos)"
                      class="stroke-slate-200" stroke-width="1" vector-effect="non-scaling-stroke" />
                <path :d="linePath" fill="none" class="stroke-slate-400" stroke-width="2"
                      vector-effect="non-scaling-stroke" stroke-linejoin="round" />
            </svg>
            <!-- Points et libellés en HTML (le SVG est étiré, le texte ne doit pas l'être) -->
            <div v-if="points.length" class="relative -mt-32 h-32 pointer-events-none">
                <span v-for="p in points" :key="p.week"
                      tabindex="0" role="button"
                      :aria-label="`Semaine ${p.week} : ${p.position}e`"
                      class="absolute w-2.5 h-2.5 -ml-[5px] -mt-[5px] rounded-full ring-2 ring-white pointer-events-auto cursor-pointer transition-transform focus:outline-none"
                      :class="[dotClass(p.position), hoveredWeek === p.week ? 'scale-150 z-10' : 'hover:scale-125']"
                      :style="{ left: (p.x / W * 100) + '%', top: (p.y / H * 100) + '%' }"
                      @mouseenter="hoveredWeek = p.week" @mouseleave="hoveredWeek = null"
                      @focus="hoveredWeek = p.week" @blur="hoveredWeek = null"
                      @click="hoveredWeek = hoveredWeek === p.week ? null : p.week"></span>

                <!-- Infobulle -->
                <div v-if="hovered" role="tooltip"
                     class="absolute z-20 whitespace-nowrap rounded-lg bg-slate-700 px-2.5 py-1.5 text-[11px] text-white shadow-lg"
                     :style="tooltipStyle">
                    <div class="font-bold">
                        Semaine {{ hovered.week }} · {{ hovered.position }}<sup>e</sup>
                        <span v-if="hovered.delta" class="ml-1" :class="hovered.delta > 0 ? 'text-emerald-300' : 'text-rose-300'">
                            {{ hovered.delta > 0 ? '▲' : '▼' }} {{ Math.abs(hovered.delta) }}
                        </span>
                        <span v-else-if="hovered.delta === 0" class="ml-1 text-slate-400">=</span>
                    </div>
                    <div class="text-slate-300">
                        <template v-if="hovered.match">
                            {{ RESULT_STYLE[hovered.match.result].icon }}
                            <span class="font-bold" :class="RESULT_STYLE[hovered.match.result].text">{{ hovered.match.result }} {{ hovered.match.score }}</span>
                            {{ hovered.match.home ? 'contre' : 'à' }} {{ hovered.match.opponent }}
                            <span class="text-slate-400">({{ hovered.match.home ? 'dom.' : 'ext.' }})</span>
                        </template>
                        <template v-else>💤 Repos</template>
                        <span class="text-slate-400"> · {{ hovered.points }} pt{{ hovered.points > 1 ? 's' : '' }}</span>
                    </div>
                </div>
                <span class="absolute -left-1 text-3xs text-slate-400" :style="{ top: (yFor(1) / H * 100) + '%', transform: 'translateY(-50%)' }">1</span>
                <span class="absolute -left-1 text-3xs text-slate-400" :style="{ top: (yFor(teamCount) / H * 100) + '%', transform: 'translateY(-50%)' }">{{ teamCount }}</span>
            </div>
            <div v-if="points.length" class="flex justify-between text-2xs text-slate-400 mt-1 px-1">
                <span>S{{ points[0].week }}</span>
                <span v-if="targetRank" class="text-emerald-600">zone verte = objectif</span>
                <span>S{{ points.at(-1).week }} · {{ points.at(-1).position }}e</span>
            </div>
            <p v-else class="text-xs text-slate-400 italic">La courbe apparaîtra après le premier match.</p>
        </div>

        <!-- Finances -->
        <div id="dashboard-finances" class="border border-slate-200 rounded-xl bg-slate-50 p-4 scroll-mt-4" :class="isWorldCup ? 'md:col-span-3' : ''">
            <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-2">Finances</h4>
            <div class="space-y-1.5 text-xs text-slate-600">
                <div class="flex justify-between"><span>Budget</span><span class="font-black text-slate-700 tabular-nums">{{ fmt(budget) }} <GameBanknote /></span></div>
                <div v-if="incomeModel" class="flex justify-between gap-2">
                    <span>Revenu / semaine</span>
                    <span class="text-right tabular-nums">
                        <span class="font-bold text-slate-700">+{{ fmt(incomeModel.base) }} <GameBanknote /></span>
                        <span class="text-slate-400"> · V +{{ fmt(incomeModel.win_bonus) }} <GameBanknote /> · N +{{ fmt(incomeModel.draw_bonus) }} <GameBanknote /></span>
                    </span>
                </div>
                <div v-if="weeklyTrainingCost > 0" class="flex justify-between">
                    <span>Entraînements / sem.</span>
                    <span class="font-bold text-slate-700 tabular-nums">−{{ fmt(weeklyTrainingCost) }} <GameBanknote /></span>
                </div>
                <div v-if="projection" class="pt-2 mt-1 border-t border-slate-200">
                    <div class="flex justify-between">
                        <span>Fin de saison (S{{ totalWeeks }})</span>
                        <span class="font-black text-teal-600 tabular-nums">≈ {{ fmt(projection.likely) }} <GameBanknote /></span>
                    </div>
                    <p class="text-2xs text-slate-400 mt-0.5 leading-snug">
                        Au rythme actuel, hors achats ({{ weeksLeft }} sem. de revenus). Minimum garanti : {{ fmt(projection.min) }} <GameBanknote />.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
