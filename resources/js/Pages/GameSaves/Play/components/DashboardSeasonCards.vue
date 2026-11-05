<script setup>
// Dashboard — trajectoire de la saison (position semaine par semaine, écart avec
// l'objectif du mandat) et projection budgétaire.
import { computed } from 'vue';

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

const myId = computed(() => props.team?.id ?? null);

const seasonMatches = computed(() => (props.matches ?? [])
    .filter(m => m.season == null || Number(m.season) === Number(props.season)));

const isMine = (m) => m.home_team_id === myId.value || m.away_team_id === myId.value;

// ── Position semaine par semaine ─────────────────────────────
// Rejoue le classement après chaque semaine avec le même départage que
// useDashboard : points → diff. de buts → buts marqués → victoires → nom.
const positionHistory = computed(() => {
    if (myId.value == null || !props.teams.length) return [];
    const played = seasonMatches.value.filter(m => m.status === 'played');
    const weeks  = [...new Set(played.map(m => Number(m.week)))].sort((a, b) => a - b);
    const rows   = Object.fromEntries(props.teams.map(t => [t.id, { id: t.id, name: t.name ?? '', pts: 0, gf: 0, ga: 0, w: 0 }]));

    return weeks.map(w => {
        played.filter(m => Number(m.week) === w).forEach(m => {
            const h = rows[m.home_team_id], a = rows[m.away_team_id];
            if (!h || !a) return;
            const hs = Number(m.home_score) || 0, as = Number(m.away_score) || 0;
            h.gf += hs; h.ga += as; a.gf += as; a.ga += hs;
            if (hs > as)      { h.pts += 3; h.w++; }
            else if (hs < as) { a.pts += 3; a.w++; }
            else              { h.pts++; a.pts++; }
        });
        const order = Object.values(rows).sort((x, y) =>
            (y.pts - x.pts) || ((y.gf - y.ga) - (x.gf - x.ga)) || (y.gf - x.gf) || (y.w - x.w) || x.name.localeCompare(y.name));
        return { week: w, position: order.findIndex(r => r.id === myId.value) + 1 };
    });
});

const teamCount = computed(() => Math.max(props.teams.length, 2));

// ── Objectif du mandat ───────────────────────────────────────
const targetRank = computed(() => props.career?.mandate?.target_rank ?? null);

const remainingMatches = computed(() =>
    seasonMatches.value.filter(m => isMine(m) && m.status !== 'played'
        && m.home_team_id != null && m.away_team_id != null).length);

const objective = computed(() => {
    const target = targetRank.value;
    if (!target || myId.value == null) return null;
    const pts   = (t) => (t?.wins ?? 0) * 3 + (t?.draws ?? 0);
    const myPos = props.teams.findIndex(t => t.id === myId.value) + 1;
    const me    = props.teams[myPos - 1];
    if (!me) return null;

    if (myPos <= target) {
        const chaser = props.teams[target]; // (target+1)e
        return {
            ok: true,
            text: chaser ? `+${pts(me) - pts(chaser)} pt(s) sur la ${target + 1}e place (${chaser.name})` : 'Objectif atteint',
        };
    }
    const holder = props.teams[target - 1];
    const gap    = pts(holder) - pts(me);
    return {
        ok: false,
        out: gap > remainingMatches.value * 3,
        text: `À ${gap} pt(s) de la ${target}e place (${holder.name})`,
    };
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

// ── Finances ─────────────────────────────────────────────────
const totalWeeks = computed(() => Math.max(props.week, ...seasonMatches.value.map(m => Number(m.week) || 0)));
// Le revenu de la semaine en cours n'est versé qu'à sa clôture : elle compte.
const weeksLeft  = computed(() => Math.max(0, totalWeeks.value - props.week + 1));

const record = computed(() => ({
    w: props.team?.wins ?? 0, d: props.team?.draws ?? 0, l: props.team?.losses ?? 0,
}));

// Bonus moyen par match au rythme actuel (victoires/nuls déjà obtenus).
const avgBonus = computed(() => {
    const m = props.incomeModel;
    const played = record.value.w + record.value.d + record.value.l;
    if (!m || !played) return 0;
    return (record.value.w * m.win_bonus + record.value.d * m.draw_bonus) / played;
});

const projection = computed(() => {
    const m = props.incomeModel;
    if (!m) return null;
    const base = props.budget + weeksLeft.value * m.base;
    return { min: base, likely: Math.round(base + remainingMatches.value * avgBonus.value) };
});

const weeklyTrainingCost = computed(() => props.trainingCost * props.maxTrainings);

const fmt = (n) => Number(n ?? 0).toLocaleString('fr-FR');
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <!-- Trajectoire -->
        <div v-if="!isWorldCup" class="md:col-span-2 border border-slate-200 rounded-xl bg-slate-50 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Trajectoire</h4>
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
                      class="absolute w-2.5 h-2.5 -ml-[5px] -mt-[5px] rounded-full ring-2 ring-white pointer-events-auto"
                      :class="dotClass(p.position)"
                      :style="{ left: (p.x / W * 100) + '%', top: (p.y / H * 100) + '%' }"
                      :title="`Semaine ${p.week} : ${p.position}e`"></span>
                <span class="absolute -left-1 text-[9px] text-slate-400" :style="{ top: (yFor(1) / H * 100) + '%', transform: 'translateY(-50%)' }">1</span>
                <span class="absolute -left-1 text-[9px] text-slate-400" :style="{ top: (yFor(teamCount) / H * 100) + '%', transform: 'translateY(-50%)' }">{{ teamCount }}</span>
            </div>
            <div v-if="points.length" class="flex justify-between text-[10px] text-slate-400 mt-1 px-1">
                <span>S{{ points[0].week }}</span>
                <span v-if="targetRank" class="text-emerald-600">zone verte = objectif</span>
                <span>S{{ points.at(-1).week }} · {{ points.at(-1).position }}e</span>
            </div>
            <p v-else class="text-xs text-slate-400 italic">La courbe apparaîtra après le premier match.</p>
        </div>

        <!-- Finances -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-4" :class="isWorldCup ? 'md:col-span-3' : ''">
            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Finances</h4>
            <div class="space-y-1.5 text-xs text-slate-600">
                <div class="flex justify-between"><span>Budget</span><span class="font-black text-slate-700 tabular-nums">{{ fmt(budget) }}</span></div>
                <div v-if="incomeModel" class="flex justify-between gap-2">
                    <span>Revenu / semaine</span>
                    <span class="text-right tabular-nums">
                        <span class="font-bold text-slate-700">+{{ fmt(incomeModel.base) }}</span>
                        <span class="text-slate-400"> · V +{{ fmt(incomeModel.win_bonus) }} · N +{{ fmt(incomeModel.draw_bonus) }}</span>
                    </span>
                </div>
                <div v-if="weeklyTrainingCost > 0" class="flex justify-between">
                    <span>Entraînements / sem.</span>
                    <span class="font-bold text-slate-700 tabular-nums">−{{ fmt(weeklyTrainingCost) }}</span>
                </div>
                <div v-if="projection" class="pt-2 mt-1 border-t border-slate-200">
                    <div class="flex justify-between">
                        <span>Fin de saison (S{{ totalWeeks }})</span>
                        <span class="font-black text-teal-600 tabular-nums">≈ {{ fmt(projection.likely) }}</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-0.5 leading-snug">
                        Au rythme actuel, hors achats ({{ weeksLeft }} sem. de revenus). Minimum garanti : {{ fmt(projection.min) }}.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
