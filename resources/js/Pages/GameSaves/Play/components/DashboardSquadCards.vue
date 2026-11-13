<script setup>
// Dashboard — pilotage de l'effectif : vestiaire (moral/relations), contrats
// qui arrivent à échéance, meilleurs joueurs de la saison. Un clic sur un
// joueur ouvre sa fiche dans Mon équipe.
import { computed } from 'vue';
import { usePlayerUtils } from '../usePlayerUtils.js';

const props = defineProps({
    roster:            { type: Array,  required: true },
    week:              { type: Number, required: true },
    playerSeasonStats: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['open-player']);

const { moraleState } = usePlayerUtils();

// Fenêtre d'alerte contrats : identique au raccourci de l'onglet Gestion.
const CONTRACT_WINDOW = 4;
const MAX_ROWS = 4;

// ── Vestiaire ────────────────────────────────────────────────
const averageMorale = computed(() => {
    if (!props.roster.length) return 0;
    return Math.round(props.roster.reduce((s, p) => s + Number(p.morale ?? 60), 0) / props.roster.length);
});

const affinityLabel = (a) => a <= -50 ? 'rupture' : 'relation tendue';

// Joueurs à surveiller : moral ≤ 40 (malus en match) ou relation coach ≤ -30.
const lockerRoomWatch = computed(() => props.roster
    .map(p => {
        const morale   = Number(p.morale ?? 60);
        const affinity = Number(p.coach_affinity ?? 0);
        const reasons  = [];
        if (morale <= 40)   reasons.push(moraleState(morale).label.toLowerCase());
        if (affinity <= -30) reasons.push(affinityLabel(affinity));
        return reasons.length ? { player: p, morale, reasons, severity: Math.min(morale, 60 + affinity) } : null;
    })
    .filter(Boolean)
    .sort((a, b) => a.severity - b.severity));

// ── Contrats ─────────────────────────────────────────────────
const expiring = computed(() => props.roster
    .filter(p => p.contract_end_week != null
        && Number(p.contract_end_week) >= props.week
        && Number(p.contract_end_week) <= props.week + CONTRACT_WINDOW)
    .map(p => ({ player: p, endWeek: Number(p.contract_end_week), left: Number(p.contract_end_week) - props.week }))
    .sort((a, b) => a.endWeek - b.endWeek));

const leftLabel = (left) => left <= 0 ? 'cette semaine' : `dans ${left} sem.`;

// ── Meilleurs joueurs ────────────────────────────────────────
const statOf = (p) => props.playerSeasonStats?.[p.id] ?? props.playerSeasonStats?.[String(p.id)] ?? null;

const topBy = (pick, min = 1) => props.roster
    .map(p => ({ player: p, value: Number(pick(statOf(p)) ?? 0) }))
    .filter(r => r.value >= min)
    .sort((a, b) => b.value - a.value)
    .slice(0, 3);

const topScorers  = computed(() => topBy(s => s?.offense?.goals));
const topDuelists = computed(() => topBy(s => s?.duelsWon));

const ROW = 'w-full flex items-center justify-between gap-2 px-2 py-1 rounded-lg text-xs text-left hover:bg-white transition-colors';
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <!-- Vestiaire -->
        <div class="border border-slate-300 rounded-xl bg-slate-50 p-4">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Vestiaire</h4>
                <span class="text-xs font-bold" :class="moraleState(averageMorale).text"
                      title="Moral moyen de l'effectif">
                    {{ moraleState(averageMorale).emoji }} {{ averageMorale }}
                </span>
            </div>
            <div v-if="lockerRoomWatch.length" class="flex flex-col -mx-2">
                <button v-for="w in lockerRoomWatch.slice(0, MAX_ROWS)" :key="w.player.id" type="button"
                        :class="ROW" @click="emit('open-player', w.player)" title="Voir la fiche du joueur">
                    <span class="font-semibold text-slate-700 truncate">{{ moraleState(w.morale).emoji }} {{ w.player.lastname }}</span>
                    <span class="text-[11px] text-orange-600 shrink-0">{{ w.reasons.join(' · ') }}</span>
                </button>
                <span v-if="lockerRoomWatch.length > MAX_ROWS" class="px-2 text-[11px] text-slate-400">
                    + {{ lockerRoomWatch.length - MAX_ROWS }} autre(s)
                </span>
            </div>
            <p v-else class="text-xs text-emerald-600">✓ Aucun joueur mécontent ni en froid avec toi</p>
        </div>

        <!-- Contrats -->
        <div class="border border-slate-300 rounded-xl bg-slate-50 p-4">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Contrats</h4>
                <span class="text-2xs text-slate-400">d'ici la semaine {{ week + CONTRACT_WINDOW }}</span>
            </div>
            <div v-if="expiring.length" class="flex flex-col -mx-2">
                <button v-for="c in expiring.slice(0, MAX_ROWS)" :key="c.player.id" type="button"
                        :class="ROW" @click="emit('open-player', c.player)" title="Voir la fiche du joueur">
                    <span class="font-semibold text-slate-700 truncate">{{ c.player.lastname }}</span>
                    <span class="text-[11px] shrink-0 font-semibold"
                          :class="c.left <= 1 ? 'text-rose-600' : 'text-amber-600'">
                        fin S{{ c.endWeek }} · {{ leftLabel(c.left) }}
                    </span>
                </button>
                <span v-if="expiring.length > MAX_ROWS" class="px-2 text-[11px] text-slate-400">
                    + {{ expiring.length - MAX_ROWS }} autre(s)
                </span>
            </div>
            <p v-else class="text-xs text-emerald-600">✓ Aucun contrat n'arrive à échéance</p>
        </div>

        <!-- Meilleurs joueurs -->
        <div class="border border-slate-300 rounded-xl bg-slate-50 p-4">
            <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider mb-2">Meilleurs joueurs</h4>
            <div v-if="topScorers.length || topDuelists.length" class="grid grid-cols-2 gap-3">
                <div v-for="col in [
                        { title: '⚽ Buteurs', rows: topScorers },
                        { title: '💪 Duels gagnés', rows: topDuelists },
                    ]" :key="col.title" class="min-w-0">
                    <div class="text-[11px] font-semibold text-slate-500 mb-1">{{ col.title }}</div>
                    <button v-for="r in col.rows" :key="r.player.id" type="button"
                            class="w-full flex items-center justify-between gap-1 text-xs py-0.5 hover:text-teal-600 transition-colors"
                            @click="emit('open-player', r.player)" title="Voir la fiche du joueur">
                        <span class="truncate text-slate-700">{{ r.player.lastname }}</span>
                        <span class="font-black text-slate-700 tabular-nums shrink-0">{{ r.value }}</span>
                    </button>
                    <span v-if="!col.rows.length" class="text-[11px] text-slate-400">—</span>
                </div>
            </div>
            <p v-else class="text-xs text-slate-400 italic">Pas encore de match joué cette saison</p>
        </div>
    </div>
</template>
