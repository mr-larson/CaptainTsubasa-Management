<script setup>
import { computed } from 'vue';
import { usePlayerUtils } from './usePlayerUtils.js';
import TeamStyleBadges from "@/Pages/GameSaves/Play/components/TeamStyleBadges.vue";
import DashboardSquadCards from "@/Pages/GameSaves/Play/components/DashboardSquadCards.vue";
import DashboardSeasonCards from "@/Pages/GameSaves/Play/components/DashboardSeasonCards.vue";
import AssistantAdviceCard from "@/Pages/GameSaves/Play/components/AssistantAdviceCard.vue";

const props = defineProps({
    gameSave:      { type: Object, required: true },
    team:          { type: Object, default: null },
    season:        { type: Number, required: true },
    week:          { type: Number, required: true },
    roster:        { type: Array,  required: true },
    clubStanding:  { type: Object, default: null },
    standings:     { type: Array,  required: true },
    teamRecord:    { type: Object, required: true },
    teamBudget:    { type: Number, required: true },
    injuriesCount:    { type: Number, required: true },
    suspensionsCount: { type: Number, required: true },
    cardsCount:       { type: Number, required: true },
    averageAttack:  { type: Number, required: true },
    averageDefense: { type: Number, required: true },
    averageStamina: { type: Number, required: true },
    nextMatch:      { type: Object, default: null },
    nextMatchInfo:  { type: Object, default: null },
    isByeWeek:      { type: Boolean, required: true },
    teamById:       { type: Object, required: true },
    saving:         { type: Boolean, required: true },
    simulating:     { type: Boolean, default: false },
    matches:        { type: Array,   default: () => [] },
    tournament:         { type: Object, default: null },
    isWorldCup:         { type: Boolean, default: false },
    playerSeasonStats:  { type: Object,  default: () => ({}) },
    career:             { type: Object,  default: null },
    advice:             { type: Array,   default: () => [] },
    incomeModel:        { type: Object,  default: null },
    trainingCost:       { type: Number,  default: 0 },
    maxTrainings:       { type: Number,  default: 3 },
});

const emit = defineEmits(['play-next-match', 'simulate-week', 'save-game', 'quit', 'change-tab', 'open-player', 'apply-advice']);

// ── Coupe du Monde : contexte de poule de l'équipe contrôlée + étape ──
const wcStageLabel = computed(() => ({
    group: 'Phase de poules', semi: 'Demi-finales', final: 'Finale', done: 'Terminé',
}[props.tournament?.stage] ?? '—'));

const wcStanding = computed(() => {
    if (!props.isWorldCup || !props.tournament || !props.team) return null;
    for (const group of props.tournament.groups ?? []) {
        const idx = group.rows.findIndex(r => r.team_id === props.team.id);
        if (idx !== -1) {
            const row = group.rows[idx];
            return { group: group.key, position: idx + 1, total: group.rows.length, points: row.points, qualified: row.qualified };
        }
    }
    return null; // équipe non trouvée (ne devrait pas arriver)
});

const wcChampion = computed(() => props.tournament?.champion ?? null);

// ==========================
//   HELPERS
// ==========================
const periodLabel = (p) => ({ college: 'Collège', highschool: 'Lycée', pro: 'Professionnel' }[p] ?? p);

const { teamLogoUrl } = usePlayerUtils();

const opponentTeamIdFor = (match) => {
    if (!match || !props.team) return null;
    return match.home_team_id === props.team.id ? match.away_team_id : match.home_team_id;
};

const opponentTeam = computed(() => {
    if (!props.nextMatch) return null;
    const oppId = opponentTeamIdFor(props.nextMatch);
    return oppId ? props.teamById[oppId] ?? null : null;
});

const opponentContracts = computed(() => {
    if (!opponentTeam.value) return [];
    return opponentTeam.value.contracts ?? [];
});

const opponentAvgAttack = computed(() => {
    const players = opponentContracts.value.map(c => c.game_player ?? c.gamePlayer).filter(Boolean);
    if (!players.length) return 0;
    return Math.round(players.reduce((s, p) => s + Number(p.attack ?? 0), 0) / players.length);
});

const opponentAvgDefense = computed(() => {
    const players = opponentContracts.value.map(c => c.game_player ?? c.gamePlayer).filter(Boolean);
    if (!players.length) return 0;
    return Math.round(players.reduce((s, p) => s + Number(p.defense ?? 0), 0) / players.length);
});

const opponentAvgStamina = computed(() => {
    const players = opponentContracts.value.map(c => c.game_player ?? c.gamePlayer).filter(Boolean);
    if (!players.length) return 0;
    return Math.round(players.reduce((s, p) => s + Number(p.stamina ?? 0), 0) / players.length);
});

// ==========================
//   SAISON PROGRESSION
// ==========================
const totalWeeks = computed(() => {
    // Dernière semaine du calendrier (juste aussi en Coupe du Monde).
    const weeks = (props.matches ?? []).map(m => Number(m.week) || 0);
    if (weeks.length) return Math.max(...weeks);
    const n = props.standings.length;
    if (n < 2) return 28;
    return n % 2 === 1 ? n * 2 : (n - 1) * 2;
});

const seasonProgress = computed(() =>
    Math.round(((props.week - 1) / Math.max(totalWeeks.value, 1)) * 100)
);

// ==========================
//   FORME RÉCENTE
// ==========================
const FORM_STYLE = {
    V: 'bg-emerald-500 text-white',
    N: 'bg-slate-300 text-slate-700',
    D: 'bg-rose-500 text-white',
};

// Matchs joués d'une équipe, du plus ancien au plus récent.
const playedMatchesOf = (teamId) => (props.matches ?? [])
    .filter(m => m.status === 'played' && (m.home_team_id === teamId || m.away_team_id === teamId))
    .sort((a, b) => (Number(a.season ?? 0) - Number(b.season ?? 0)) || (Number(a.week) - Number(b.week)));

const resultFor = (m, teamId) => {
    const isHome   = m.home_team_id === teamId;
    const scored   = Number(isHome ? m.home_score : m.away_score) || 0;
    const conceded = Number(isHome ? m.away_score : m.home_score) || 0;
    return { label: scored > conceded ? 'V' : scored === conceded ? 'N' : 'D', scored, conceded };
};

// 5 derniers résultats (le plus récent à droite).
const formOf = (teamId) => teamId == null ? [] : playedMatchesOf(teamId).slice(-5).map(m => ({
    ...resultFor(m, teamId),
    title: `S${m.week} : ${resultFor(m, teamId).scored}-${resultFor(m, teamId).conceded}`,
}));

const myForm       = computed(() => formOf(props.team?.id));
const opponentForm = computed(() => formOf(opponentTeam.value?.id));

// Dernière confrontation avec l'adversaire du prochain match.
const lastHeadToHead = computed(() => {
    const me = props.team?.id, opp = opponentTeam.value?.id;
    if (me == null || opp == null) return null;
    const m = playedMatchesOf(me).filter(x => x.home_team_id === opp || x.away_team_id === opp).at(-1);
    return m ? { ...resultFor(m, me), week: m.week } : null;
});

// Forces face à face : écart en faveur de qui ?
const faceToFace = computed(() => [
    { label: 'Attaque', me: props.averageAttack,  opp: opponentAvgAttack.value,  color: 'bg-orange-400' },
    { label: 'Défense', me: props.averageDefense, opp: opponentAvgDefense.value, color: 'bg-blue-400' },
    { label: 'Stamina', me: props.averageStamina, opp: opponentAvgStamina.value, color: 'bg-emerald-400' },
]);
// Joueurs adverses à surveiller : meilleur buteur et meilleur duelliste.
const opponentThreats = computed(() => {
    const players = opponentContracts.value.map(c => c.game_player ?? c.gamePlayer).filter(Boolean);
    const statOf  = (p) => props.playerSeasonStats?.[p.id] ?? props.playerSeasonStats?.[String(p.id)] ?? null;
    const best = (pick) => players
        .map(p => ({ player: p, value: Number(pick(statOf(p)) ?? 0) }))
        .filter(r => r.value > 0)
        .sort((a, b) => b.value - a.value)[0] ?? null;
    const scorer  = best(s => s?.offense?.goals);
    const duelist = best(s => s?.duelsWon);
    return [
        scorer  && { key: 'goals', text: `⚽ ${scorer.player.lastname}`,  detail: `${scorer.value} but${scorer.value > 1 ? 's' : ''}` },
        duelist && { key: 'duels', text: `💪 ${duelist.player.lastname}`, detail: `${duelist.value} duels gagnés` },
    ].filter(Boolean);
});

const statMax = computed(() => Math.max(1, ...faceToFace.value.flatMap(s => [s.me, s.opp])));

// ==========================
//   RANK STYLE
// ==========================
const rankStyle = computed(() => {
    const pos = props.clubStanding?.position ?? 99;
    if (pos === 1) return 'text-yellow-500';
    if (pos <= 3)  return 'text-amber-500';
    if (pos <= 6)  return 'text-teal-500';
    return 'text-slate-500';
});

const matchesPlayed = computed(() =>
    (props.teamRecord.wins ?? 0) + (props.teamRecord.draws ?? 0) + (props.teamRecord.losses ?? 0)
);
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto min-h-0 pr-1 [&>*]:shrink-0">

        <!-- ============================================ -->
        <!-- LIGNE 1 : Contexte + progression saison      -->
        <!-- ============================================ -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl overflow-hidden shrink-0">
                        <img v-if="teamLogoUrl(team)" :src="teamLogoUrl(team)" class="w-full h-full object-contain" alt=""/>
                        <span v-else class="w-full h-full flex items-center justify-center text-lg">🏟️</span>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-700">{{ team?.name ?? '—' }}</div>
                        <div class="text-xs text-slate-400 mb-1">{{ periodLabel(gameSave.period) }}</div>
                        <TeamStyleBadges :team="team" size="sm" />
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="text-center">
                        <div class="text-lg font-black text-slate-700">{{ season }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold">Saison</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-black text-teal-600">{{ week }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold">Semaine</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-black text-slate-700">{{ matchesPlayed }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold">Matchs joués</div>
                    </div>
                </div>

                <!-- Boutons actions -->
                <div class="flex gap-2 shrink-0">
                    <button type="button"
                            class="px-4 py-1.5 text-xs rounded-full font-semibold border transition-all disabled:opacity-40"
                            :class="saving ? 'bg-slate-100 text-slate-400 border-slate-200' : 'bg-cyan-50 text-cyan-600 border-cyan-300 hover:bg-cyan-100'"
                            :disabled="saving" @click="emit('save-game')">
                        {{ saving ? '...' : '💾 Sauvegarder' }}
                    </button>
                    <button type="button"
                            class="px-4 py-1.5 text-xs rounded-full font-semibold border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 transition-all"
                            @click="emit('quit')">
                        Quitter
                    </button>
                </div>
            </div>

            <!-- Barre progression saison -->
            <div>
                <div class="flex justify-between text-[10px] text-slate-400 mb-1">
                    <span>Semaine 1</span>
                    <span class="font-semibold text-teal-500">{{ seasonProgress }}% de la saison</span>
                    <span>Semaine {{ totalWeeks }}</span>
                </div>
                <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-teal-400 to-teal-500 rounded-full transition-all"
                         :style="{ width: seasonProgress + '%' }">
                    </div>
                </div>
            </div>
        </div>

        <!-- Coupe du Monde : bannière champion -->
        <div v-if="isWorldCup && wcChampion"
             class="flex items-center justify-center gap-3 px-5 py-4 rounded-2xl bg-gradient-to-r from-amber-100 to-yellow-50 border border-amber-300">
            <span class="text-4xl">🏆</span>
            <div class="text-center">
                <div class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Champion du Monde</div>
                <div class="text-xl font-extrabold text-amber-800">{{ wcChampion.flag }} {{ wcChampion.name }}</div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- LIGNE 2 : 4 tuiles                          -->
        <!-- ============================================ -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

            <!-- Classement — Coupe du Monde : position dans la poule -->
            <div v-if="isWorldCup" class="border border-slate-200 rounded-xl bg-slate-50 p-2 flex flex-col items-center gap-1">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    {{ wcStanding ? 'Poule ' + wcStanding.group : 'Tournoi' }}
                </div>
                <div class="text-3xl font-black mt-1 text-indigo-600">
                    {{ wcStanding ? wcStanding.position : '—' }}<sup v-if="wcStanding" class="text-base">e</sup>
                </div>
                <div class="text-[10px] text-slate-400">{{ wcStanding ? '/ ' + wcStanding.total : wcStageLabel }}</div>
                <div v-if="wcStanding"
                     class="text-[10px] font-bold px-2 py-0.5 rounded-full mt-1"
                     :class="wcStanding.qualified ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-200 text-slate-500'">
                    {{ wcStanding.qualified ? 'Qualifié' : wcStanding.points + ' pts' }}
                </div>
            </div>

            <!-- Classement — Ligue -->
            <div v-else class="border border-slate-200 rounded-xl bg-slate-50 p-2 flex flex-col items-center gap-1">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Classement</div>
                <div class="text-3xl font-black mt-1" :class="rankStyle">
                    {{ clubStanding?.position ?? '—' }}<sup v-if="clubStanding" class="text-base">e</sup>
                </div>
                <div class="text-[10px] text-slate-400">/ {{ standings.length }} équipes</div>
                <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden mt-1">
                    <div class="h-full bg-teal-500 rounded-full"
                         :style="{ width: clubStanding ? (100 - ((clubStanding.position - 1) / Math.max(standings.length - 1, 1)) * 100) + '%' : '0%' }">
                    </div>
                </div>
            </div>

            <!-- Bilan -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-2">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Bilan</div>
                <div class="flex gap-2">
                    <div class="flex-1 text-center bg-emerald-50 rounded-lg py-1.5">
                        <div class="text-xl font-black text-emerald-600">{{ teamRecord.wins ?? 0 }}</div>
                        <div class="text-[9px] text-emerald-500 font-semibold">V</div>
                    </div>
                    <div class="flex-1 text-center bg-slate-100 rounded-lg py-1.5">
                        <div class="text-xl font-black text-slate-500">{{ teamRecord.draws ?? 0 }}</div>
                        <div class="text-[9px] text-slate-400 font-semibold">N</div>
                    </div>
                    <div class="flex-1 text-center bg-rose-50 rounded-lg py-1.5">
                        <div class="text-xl font-black text-rose-500">{{ teamRecord.losses ?? 0 }}</div>
                        <div class="text-[9px] text-rose-400 font-semibold">D</div>
                    </div>
                </div>
                <div class="text-center mt-1.5">
                    <span class="text-xs font-black text-teal-600">{{ clubStanding?.points ?? 0 }} pts</span>
                </div>
            </div>

            <!-- Budget -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-2 flex flex-col items-center gap-1">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Budget</div>
                <div class="text-2xl font-black text-teal-600 mt-1">{{ teamBudget }}</div>
                <div class="text-[10px] text-slate-500 mt-1">{{ roster.length }} joueur(s)</div>
            </div>

            <!-- Effectif -->
            <div class="border border-slate-200 rounded-xl bg-slate-50 p-2">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Effectif</div>
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Blessés</span>
                        <span class="font-black" :class="injuriesCount > 0 ? 'text-rose-500' : 'text-slate-400'">
                            {{ injuriesCount }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Suspensions</span>
                        <span class="font-black" :class="suspensionsCount > 0 ? 'text-amber-500' : 'text-slate-400'">
                            {{ suspensionsCount }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Cartons</span>
                        <span class="font-black" :class="cardsCount > 0 ? 'text-yellow-500' : 'text-slate-400'">
                            {{ cardsCount }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- LIGNE 3 : Prochain match (hero)              -->
        <!-- ============================================ -->
        <div class="rounded-xl overflow-hidden bg-slate-50"
             :class="{
         'border-2 border-teal-400':   !isByeWeek && nextMatchInfo?.isHome,
         'border-2 border-orange-400': !isByeWeek && !nextMatchInfo?.isHome,
         'border border-slate-200':    isByeWeek,
     }">

            <div v-if="!isByeWeek && nextMatch && nextMatchInfo" class="p-1.5">
                <!-- Label -->
                <div class="text-center">
            <span class="text-[10px] font-bold uppercase tracking-widest"
                  :class="nextMatchInfo.isHome ? 'text-teal-500' : 'text-orange-500'">
                {{ nextMatchInfo.isHome ? '🏠 Domicile' : '✈️ Extérieur' }}
                &nbsp;•&nbsp; Semaine {{ nextMatchInfo.week }}
            </span>
                </div>

                <!-- Affrontement -->
                <div class="flex flex-wrap items-center justify-around gap-3 sm:gap-4">
                    <!-- Mon équipe -->
                    <div class="flex flex-col items-center gap-2 flex-1">
                        <div class="w-14 h-14 rounded-2xl overflow-hidden flex items-center justify-center">
                            <img v-if="teamLogoUrl(team)" :src="teamLogoUrl(team)" class="w-full h-full object-contain" alt=""/>
                            <span v-else class="text-2xl">🏟️</span>
                        </div>
                        <div class="text-sm font-bold text-slate-700 text-center">{{ team?.name ?? '—' }}</div>
                        <div class="text-[10px] text-slate-400">{{ clubStanding ? `${clubStanding.position}e au classement` : '' }}</div>
                        <div v-if="myForm.length" class="flex gap-1" aria-label="Forme récente">
                            <span v-for="(r, i) in myForm" :key="i" :title="r.title"
                                  class="w-5 h-5 rounded text-[10px] font-black flex items-center justify-center" :class="FORM_STYLE[r.label]">{{ r.label }}</span>
                        </div>
                    </div>

                    <!-- VS + Bouton central -->
                    <div class="text-center shrink-0 flex flex-col items-center gap-3">
                        <div class="text-3xl font-black text-slate-300">VS</div>
                        <div v-if="lastHeadToHead" class="text-[10px] text-slate-400 -mt-2">
                            Dernier duel (S{{ lastHeadToHead.week }}) :
                            <span class="font-bold" :class="{ 'text-emerald-600': lastHeadToHead.label === 'V', 'text-rose-500': lastHeadToHead.label === 'D', 'text-slate-500': lastHeadToHead.label === 'N' }">
                                {{ lastHeadToHead.scored }}-{{ lastHeadToHead.conceded }}
                            </span>
                        </div>
                        <button v-if="isByeWeek" type="button"
                                class="px-5 py-2 rounded-full font-bold text-xs bg-slate-200 hover:bg-slate-300 text-slate-600 transition-all disabled:opacity-50 disabled:cursor-wait"
                                :disabled="simulating"
                                @click="emit('simulate-week')">
                            ⏭ Simuler S{{ week }}
                        </button>
                        <button v-else type="button"
                                class="px-6 py-1.5 rounded-full font-bold text-sm text-white shadow-sm transition-all hover:scale-105 active:scale-95"
                                :class="nextMatchInfo.isHome ? 'bg-teal-500 hover:bg-teal-400' : 'bg-orange-500 hover:bg-orange-400'"
                                @click="emit('play-next-match')">
                            ▶ Jouer
                        </button>
                    </div>

                    <!-- Adversaire -->
                    <div class="flex flex-col items-center gap-2 flex-1">
                        <div class="w-16 h-16 rounded-2xl overflow-hidden flex items-center justify-center">
                            <img v-if="teamLogoUrl(opponentTeam)" :src="teamLogoUrl(opponentTeam)" class="w-full h-full object-contain" alt=""/>
                            <span v-else class="text-2xl">⚽</span>
                        </div>
                        <div class="text-sm font-bold text-slate-700 text-center">{{ nextMatchInfo.opponentName }}</div>
                        <div class="text-[10px] text-slate-400">
                            {{ standings.findIndex(r => r.id === opponentTeam?.id) + 1 > 0
                            ? `${standings.findIndex(r => r.id === opponentTeam?.id) + 1}e au classement`
                            : '' }}
                        </div>
                        <div v-if="opponentForm.length" class="flex gap-1" aria-label="Forme récente de l'adversaire">
                            <span v-for="(r, i) in opponentForm" :key="i" :title="r.title"
                                  class="w-5 h-5 rounded text-[10px] font-black flex items-center justify-center" :class="FORM_STYLE[r.label]">{{ r.label }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Semaine sans match -->
            <div v-else class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold text-slate-600">Semaine {{ week }} — Pas de match</div>
                    <div class="text-xs text-slate-400 mt-0.5">Semaine de repos ou en attente du calendrier</div>
                </div>
                <button type="button"
                        class="px-6 py-2 rounded-full font-semibold text-sm bg-slate-200 hover:bg-slate-300 text-slate-600 transition-all disabled:opacity-50 disabled:cursor-wait"
                        :disabled="simulating"
                        @click="emit('simulate-week')">
                    ⏭ Simuler la semaine
                </button>
            </div>
        </div>

        <!-- Conseils de l'adjoint -->
        <AssistantAdviceCard :advice="advice" @apply="a => emit('apply-advice', a)" />

        <!-- Cartes de la colonne gauche (rappels, mandat, récap) sur petit écran -->
        <slot name="side-cards" />

        <!-- ============================================ -->
        <!-- LIGNE 4 : Forces face à face                -->
        <!-- ============================================ -->
        <div class="border border-slate-200 rounded-xl bg-slate-50 p-4">
            <div class="flex items-center justify-between gap-2 mb-3">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider truncate">{{ team?.name ?? 'Mon équipe' }}</h4>
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider shrink-0">Forces</h4>
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider truncate text-right">
                    {{ opponentTeam && !isByeWeek ? nextMatchInfo?.opponentName : 'Pas d\'adversaire' }}
                </h4>
            </div>
            <div class="space-y-2">
                <div v-for="stat in faceToFace" :key="stat.label" class="grid grid-cols-[2rem_1fr_4.5rem_1fr_2rem] items-center gap-2">
                    <span class="text-xs font-black text-right"
                          :class="opponentTeam && !isByeWeek && stat.me > stat.opp ? 'text-emerald-600' : 'text-slate-700'">{{ stat.me }}</span>
                    <!-- Ma barre (croît vers la gauche) -->
                    <div class="h-2 bg-slate-200 rounded-full overflow-hidden flex justify-end">
                        <div class="h-full rounded-full" :class="stat.color" :style="{ width: (stat.me / statMax * 100) + '%' }"></div>
                    </div>
                    <span class="text-[11px] text-slate-500 text-center">{{ stat.label }}</span>
                    <!-- Barre adverse (croît vers la droite) -->
                    <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                        <div v-if="opponentTeam && !isByeWeek" class="h-full rounded-full opacity-60" :class="stat.color"
                             :style="{ width: (stat.opp / statMax * 100) + '%' }"></div>
                    </div>
                    <span class="text-xs font-black"
                          :class="opponentTeam && !isByeWeek && stat.opp > stat.me ? 'text-rose-500' : 'text-slate-700'">
                        {{ opponentTeam && !isByeWeek ? stat.opp : '—' }}
                    </span>
                </div>
            </div>
            <div v-if="opponentTeam && !isByeWeek && opponentThreats.length"
                 class="mt-3 pt-3 border-t border-slate-200 flex flex-wrap items-center justify-end gap-x-3 gap-y-1 text-xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">À surveiller</span>
                <span v-for="t in opponentThreats" :key="t.key" class="text-slate-700">
                    <span class="font-semibold">{{ t.text }}</span>
                    <span class="text-slate-400"> · {{ t.detail }}</span>
                </span>
            </div>
            <div v-if="team?.description || (opponentTeam && !isByeWeek && opponentTeam.description)"
                 class="mt-3 pt-3 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <p class="text-xs text-slate-400 italic leading-relaxed line-clamp-2">{{ team?.description }}</p>
                <p v-if="opponentTeam && !isByeWeek" class="text-xs text-slate-400 italic leading-relaxed line-clamp-2 sm:text-right">{{ opponentTeam.description }}</p>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- LIGNE 5 : Vestiaire, contrats, meilleurs joueurs -->
        <!-- ============================================ -->
        <DashboardSquadCards :roster="roster" :week="week" :playerSeasonStats="playerSeasonStats"
                             @open-player="p => emit('open-player', p)" />

        <!-- ============================================ -->
        <!-- LIGNE 6 : Trajectoire + finances            -->
        <!-- ============================================ -->
        <DashboardSeasonCards :team="clubStanding ?? team" :teams="standings" :matches="matches"
                              :season="season" :week="week" :career="career" :isWorldCup="isWorldCup"
                              :budget="teamBudget" :incomeModel="incomeModel"
                              :trainingCost="trainingCost" :maxTrainings="maxTrainings" />

    </div>
</template>
