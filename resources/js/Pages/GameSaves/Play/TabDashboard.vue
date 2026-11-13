<script setup>
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import { computed } from 'vue';
import { STAT_COLORS, usePlayerUtils } from './usePlayerUtils.js';
import { averageTeamStat, TEAM_STATS } from './teamStats.js';
import TeamStyleBadges from "@/Pages/GameSaves/Play/components/TeamStyleBadges.vue";
import DashboardSquadCards from "@/Pages/GameSaves/Play/components/DashboardSquadCards.vue";
import DashboardSeasonCards from "@/Pages/GameSaves/Play/components/DashboardSeasonCards.vue";
import AssistantAdviceCard from "@/Pages/GameSaves/Play/components/AssistantAdviceCard.vue";
import { useSeasonTrajectory } from "@/Pages/GameSaves/Play/useSeasonTrajectory.js";
import SeasonChallengesCard from "@/Pages/GameSaves/Play/components/SeasonChallengesCard.vue";

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
    nextMatch:      { type: Object, default: null },
    nextMatchInfo:  { type: Object, default: null },
    isByeWeek:      { type: Boolean, required: true },
    midseasonBreakRecovery: { type: Number, default: null },
    teamById:       { type: Object, required: true },
    saving:         { type: Boolean, required: true },
    simulating:     { type: Boolean, default: false },
    launchingMatch: { type: Boolean, default: false },
    canSimulateMatch: { type: Boolean, default: false },
    matches:        { type: Array,   default: () => [] },
    tournament:         { type: Object, default: null },
    isWorldCup:         { type: Boolean, default: false },
    playerSeasonStats:  { type: Object,  default: () => ({}) },
    career:             { type: Object,  default: null },
    seasonChallenges:   { type: Object,  default: null },
    advice:             { type: Array,   default: () => [] },
    incomeModel:        { type: Object,  default: null },
    trainingCost:       { type: Number,  default: 0 },
    maxTrainings:       { type: Number,  default: 3 },
});

const emit = defineEmits(['play-next-match', 'simulate-match', 'simulate-week', 'save-game', 'quit', 'change-tab', 'open-player', 'apply-advice']);

// Guide « Comment jouer » dans un nouvel onglet, pour ne pas quitter la partie.
const openHelp = () => window.open(route('guide') + '#preparer', '_blank', 'noopener');

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

const opponentPlayers = computed(() => (opponentTeam.value?.contracts ?? [])
    .map(c => c.game_player ?? c.gamePlayer ?? c.player)
    .filter(Boolean));

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

const faceToFace = computed(() => TEAM_STATS.map(({ key, label, color }) => ({
    key, label,
    color: color ?? STAT_COLORS[key],
    me: averageTeamStat(props.roster, key),
    opp: props.isByeWeek ? null : averageTeamStat(opponentPlayers.value, key),
})));

// Joueurs adverses à surveiller : meilleur buteur et meilleur duelliste.
const opponentThreats = computed(() => {
    const players = opponentPlayers.value;
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

// Indicateurs du haut : évolution au classement, objectif, budget de fin de saison.
const { positionDelta, objective, projection, totalWeeks: seasonEndWeek } = useSeasonTrajectory({
    team: () => props.clubStanding ?? props.team, teams: () => props.standings,
    matches: () => props.matches, season: () => props.season, week: () => props.week,
    career: () => props.career, budget: () => props.teamBudget, incomeModel: () => props.incomeModel,
});

const scrollToFinances = () =>
    document.getElementById('dashboard-finances')?.scrollIntoView({ behavior: 'smooth', block: 'start' });

const KPI = 'border border-slate-300 rounded-xl bg-slate-50 p-2 text-left transition-colors hover:bg-slate-100 hover:border-slate-300';

const matchesPlayed = computed(() =>
    (props.teamRecord.wins ?? 0) + (props.teamRecord.draws ?? 0) + (props.teamRecord.losses ?? 0)
);
const hasPlayed = computed(() => matchesPlayed.value > 0);
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto min-h-0 pr-1 [&>*]:shrink-0">

        <!-- ============================================ -->
        <!-- LIGNE 1 : Contexte + progression saison      -->
        <!-- ============================================ -->
        <div class="border border-slate-300 rounded-xl bg-slate-50 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl overflow-hidden shrink-0">
                        <TeamLogoImage v-if="teamLogoUrl(team)" :src="teamLogoUrl(team)" class="w-full h-full" alt=""/>
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
                        <div class="text-2xs text-slate-400 font-semibold">Saison</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-black text-teal-600">{{ week }}</div>
                        <div class="text-2xs text-slate-400 font-semibold">Semaine</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-black text-slate-700">{{ matchesPlayed }}</div>
                        <div class="text-2xs text-slate-400 font-semibold">Matchs joués</div>
                    </div>
                </div>

                <!-- Boutons actions -->
                <div class="flex gap-2 shrink-0">
                    <ButtonSecondary size="sm" :disabled="saving" @click="emit('save-game')">
                        {{ saving ? '...' : '💾 Sauvegarder' }}
                    </ButtonSecondary>
                    <ButtonSecondary size="sm" @click="emit('quit')">
                        Quitter
                    </ButtonSecondary>
                    <!-- Aide consultable au moment du doute, sans quitter la partie -->
                    <ButtonSecondary size="sm" title="Comment jouer (nouvel onglet)" @click="openHelp">
                        ❔ Aide
                    </ButtonSecondary>
                </div>
            </div>

            <!-- Barre progression saison -->
            <div>
                <div class="flex justify-between text-2xs text-slate-400 mb-1">
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
        <!-- LIGNE 2 : 3 indicateurs (clic → détail)     -->
        <!-- ============================================ -->
        <!-- Téléphone : Classement + Budget côte à côte, Bilan en dessous -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">

            <!-- Classement — Coupe du Monde : position dans la poule -->
            <button v-if="isWorldCup" type="button" :class="KPI" class="flex flex-col items-center gap-1"
                    @click="emit('change-tab', 'standings')" title="Voir le tournoi">
                <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">
                    {{ wcStanding ? 'Poule ' + wcStanding.group : 'Tournoi' }}
                </span>
                <span class="text-3xl font-black text-indigo-600">
                    {{ wcStanding ? wcStanding.position : '—' }}<sup v-if="wcStanding" class="text-base">e</sup>
                </span>
                <span class="text-2xs text-slate-400">{{ wcStanding ? '/ ' + wcStanding.total : wcStageLabel }}</span>
                <span v-if="wcStanding" class="text-2xs font-bold px-2 py-0.5 rounded-full"
                      :class="wcStanding.qualified ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-200 text-slate-500'">
                    {{ wcStanding.qualified ? 'Qualifié' : wcStanding.points + ' pts' }}
                </span>
            </button>

            <!-- Classement — Ligue : position, points, évolution, objectif -->
            <button v-else type="button" :class="KPI" class="flex flex-col items-center gap-1"
                    @click="emit('change-tab', 'standings')" title="Voir le classement">
                <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Classement</span>
                <span class="flex items-baseline gap-2">
                    <span class="text-3xl font-black" :class="rankStyle">
                        <!-- Avant le 1er match, le rang n'est que l'ordre alphabétique : on affiche « — ». -->
                        {{ hasPlayed ? (clubStanding?.position ?? '—') : '—' }}<sup v-if="clubStanding && hasPlayed" class="text-base">e</sup>
                    </span>
                    <span v-if="positionDelta !== null" class="text-xs font-bold"
                          :class="positionDelta > 0 ? 'text-emerald-600' : positionDelta < 0 ? 'text-rose-500' : 'text-slate-400'"
                          :title="'Évolution depuis la semaine précédente'">
                        {{ positionDelta > 0 ? '↑' + positionDelta : positionDelta < 0 ? '↓' + (-positionDelta) : '=' }}
                    </span>
                </span>
                <span class="text-2xs text-slate-400">/ {{ standings.length }} · <span class="font-bold text-teal-600">{{ clubStanding?.points ?? 0 }} pts</span></span>
                <span v-if="objective" class="text-2xs font-bold px-2 py-0.5 rounded-full border"
                      :class="objective.ok ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                            : objective.out ? 'bg-rose-50 border-rose-200 text-rose-700'
                            : 'bg-amber-50 border-amber-200 text-amber-700'"
                      :title="'🎯 ' + career.mandate.label + ' — ' + objective.text">
                    🎯 {{ objective.ok ? '✓' : objective.out ? '✗' : '↗' }} {{ objective.short }}
                </span>
            </button>

            <!-- Bilan -->
            <button type="button" :class="KPI" class="col-span-2 order-last sm:col-span-1 sm:order-none"
                    @click="emit('change-tab', 'calendar')" title="Voir le calendrier">
                <span class="block text-2xs font-bold text-slate-400 uppercase tracking-wider mb-2 text-center">Bilan</span>
                <span class="flex gap-2">
                    <span class="flex-1 text-center bg-emerald-50 rounded-lg py-1.5">
                        <span class="block text-xl font-black text-emerald-600">{{ teamRecord.wins ?? 0 }}</span>
                        <span class="block text-3xs text-emerald-500 font-semibold">V</span>
                    </span>
                    <span class="flex-1 text-center bg-slate-100 rounded-lg py-1.5">
                        <span class="block text-xl font-black text-slate-500">{{ teamRecord.draws ?? 0 }}</span>
                        <span class="block text-3xs text-slate-400 font-semibold">N</span>
                    </span>
                    <span class="flex-1 text-center bg-rose-50 rounded-lg py-1.5">
                        <span class="block text-xl font-black text-rose-500">{{ teamRecord.losses ?? 0 }}</span>
                        <span class="block text-3xs text-rose-400 font-semibold">D</span>
                    </span>
                </span>
            </button>

            <!-- Budget -->
            <button type="button" :class="KPI" class="flex flex-col items-center gap-1"
                    @click="scrollToFinances" title="Voir le détail des finances">
                <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Budget</span>
                <span class="text-3xl font-black text-teal-600 tabular-nums">{{ Number(teamBudget).toLocaleString('fr-FR') }}</span>
                <span v-if="projection" class="text-2xs text-slate-400">
                    ≈ {{ projection.likely.toLocaleString('fr-FR') }} en S{{ seasonEndWeek }}
                </span>
            </button>
        </div>

        <!-- ============================================ -->
        <!-- LIGNE 3 : Prochain match (hero)              -->
        <!-- ============================================ -->
        <div class="rounded-xl overflow-hidden bg-slate-50"
             :class="{
         'border-2 border-teal-400':   midseasonBreakRecovery === null && !isByeWeek && nextMatchInfo?.isHome,
         'border-2 border-orange-400': midseasonBreakRecovery === null && !isByeWeek && !nextMatchInfo?.isHome,
         'border border-slate-300':    isByeWeek || midseasonBreakRecovery !== null,
     }">

            <div v-if="midseasonBreakRecovery !== null" class="p-5 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="text-sm font-bold text-slate-700">Trêve de mi-saison</div>
                    <div class="mt-1 text-xs text-slate-600">
                        Une semaine sans match. Tous les joueurs récupèrent jusqu’à {{ midseasonBreakRecovery }} points
                        d’endurance et le compteur de cartons jaunes repart à zéro.
                    </div>
                </div>
                <ButtonSecondary :disabled="simulating" @click="emit('simulate-week')">
                    Terminer la trêve
                </ButtonSecondary>
            </div>
            <div v-else-if="!isByeWeek && nextMatch && nextMatchInfo" class="p-1.5">
                <!-- Label -->
                <div class="text-center">
            <span class="text-2xs font-bold uppercase tracking-widest"
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
                            <TeamLogoImage v-if="teamLogoUrl(team)" :src="teamLogoUrl(team)" class="w-full h-full" alt=""/>
                            <span v-else class="text-2xl">🏟️</span>
                        </div>
                        <div class="text-sm font-bold text-slate-700 text-center">{{ team?.name ?? '—' }}</div>
                        <div class="text-2xs text-slate-400">{{ clubStanding ? `${clubStanding.position}e au classement` : '' }}</div>
                        <div v-if="myForm.length" class="flex gap-1" aria-label="Forme récente">
                            <span v-for="(r, i) in myForm" :key="i" :title="r.title"
                                  class="w-5 h-5 rounded text-2xs font-black flex items-center justify-center" :class="FORM_STYLE[r.label]">{{ r.label }}</span>
                        </div>
                    </div>

                    <!-- VS + Bouton central -->
                    <div class="text-center shrink-0 flex flex-col items-center gap-3">
                        <div class="text-3xl font-black text-slate-300">VS</div>
                        <div v-if="lastHeadToHead" class="text-2xs text-slate-400 -mt-2">
                            Dernier duel (S{{ lastHeadToHead.week }}) :
                            <span class="font-bold" :class="{ 'text-emerald-600': lastHeadToHead.label === 'V', 'text-rose-500': lastHeadToHead.label === 'D', 'text-slate-500': lastHeadToHead.label === 'N' }">
                                {{ lastHeadToHead.scored }}-{{ lastHeadToHead.conceded }}
                            </span>
                        </div>
                        <ButtonSecondary size="sm" v-if="isByeWeek" :disabled="simulating" @click="emit('simulate-week')">
                            ⏭ Simuler S{{ week }}
                        </ButtonSecondary>
                        <div v-else class="flex flex-wrap items-center justify-center gap-2">
                            <button type="button"
                                    class="px-6 py-1.5 rounded-full font-bold text-sm text-white shadow-sm transition-all hover:scale-105 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="nextMatchInfo.isHome ? 'bg-teal-500 hover:bg-teal-600' : 'bg-orange-500 hover:bg-orange-600'"
                                    :disabled="simulating || launchingMatch"
                                    @click="emit('play-next-match')">
                                ▶ Jouer
                            </button>
                            <ButtonSecondary v-if="canSimulateMatch" size="sm"
                                             :disabled="simulating || launchingMatch" @click="emit('simulate-match')">
                                ⏩ Simuler le match
                            </ButtonSecondary>
                        </div>
                    </div>

                    <!-- Adversaire -->
                    <div class="flex flex-col items-center gap-2 flex-1">
                        <div class="w-16 h-16 rounded-2xl overflow-hidden flex items-center justify-center">
                            <TeamLogoImage v-if="teamLogoUrl(opponentTeam)" :src="teamLogoUrl(opponentTeam)" class="w-full h-full" alt=""/>
                            <span v-else class="text-2xl">⚽</span>
                        </div>
                        <div class="text-sm font-bold text-slate-700 text-center">{{ nextMatchInfo.opponentName }}</div>
                        <div class="text-2xs text-slate-400">
                            {{ standings.findIndex(r => r.id === opponentTeam?.id) + 1 > 0
                            ? `${standings.findIndex(r => r.id === opponentTeam?.id) + 1}e au classement`
                            : '' }}
                        </div>
                        <div v-if="opponentForm.length" class="flex gap-1" aria-label="Forme récente de l'adversaire">
                            <span v-for="(r, i) in opponentForm" :key="i" :title="r.title"
                                  class="w-5 h-5 rounded text-2xs font-black flex items-center justify-center" :class="FORM_STYLE[r.label]">{{ r.label }}</span>
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
                <ButtonSecondary size="md" :disabled="simulating" @click="emit('simulate-week')">
                    ⏭ Simuler la semaine
                </ButtonSecondary>
            </div>
        </div>

        <!-- Conseils de l'adjoint -->
        <AssistantAdviceCard :advice="advice" @apply="a => emit('apply-advice', a)" />

        <!-- Cartes de la colonne gauche (rappels, mandat, récap) sur petit écran -->
        <slot name="side-cards" />

        <!-- ============================================ -->
        <!-- LIGNE 4 : Forces face à face                -->
        <!-- ============================================ -->
        <div class="border border-slate-300 rounded-xl bg-slate-50 p-4">
            <div class="flex items-center justify-between gap-2 mb-3">
                <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider truncate">{{ team?.name ?? 'Mon équipe' }}</h4>
                <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider shrink-0">Forces</h4>
                <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider truncate text-right">
                    {{ opponentTeam && !isByeWeek ? nextMatchInfo?.opponentName : 'Pas d\'adversaire' }}
                </h4>
            </div>
            <div class="space-y-2">
                <div v-for="stat in faceToFace" :key="stat.key" class="grid grid-cols-[1.75rem_minmax(0,1fr)_4.5rem_minmax(0,1fr)_1.75rem] items-center gap-2">
                    <span class="text-xs font-black text-right"
                          :class="stat.me != null && stat.opp != null && stat.me > stat.opp ? 'text-emerald-600' : 'text-slate-700'">{{ stat.me ?? '—' }}</span>
                    <!-- Ma barre (croît vers la gauche) -->
                    <div class="h-2 bg-slate-200 rounded-full overflow-hidden flex justify-end">
                        <div class="h-full rounded-full" :class="stat.color" :style="{ width: (stat.me ?? 0) + '%' }"></div>
                    </div>
                    <span class="text-[11px] text-slate-500 text-center">{{ stat.label }}</span>
                    <!-- Barre adverse (croît vers la droite) -->
                    <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                        <div v-if="stat.opp != null" class="h-full rounded-full opacity-60" :class="stat.color"
                             :style="{ width: stat.opp + '%' }"></div>
                    </div>
                    <span class="text-xs font-black"
                          :class="stat.me != null && stat.opp != null && stat.opp > stat.me ? 'text-rose-500' : 'text-slate-700'">
                        {{ stat.opp ?? '—' }}
                    </span>
                </div>
            </div>
            <p class="mt-2 text-2xs text-slate-500">Moyennes des effectifs sur 100 · Arrêt main et Poings calculés sur les gardiens.</p>
            <div v-if="opponentTeam && !isByeWeek && opponentThreats.length"
                 class="mt-3 pt-3 border-t border-slate-300 flex flex-wrap items-center justify-end gap-x-3 gap-y-1 text-xs">
                <span class="text-2xs font-bold text-slate-400 uppercase tracking-wider">À surveiller</span>
                <span v-for="t in opponentThreats" :key="t.key" class="text-slate-700">
                    <span class="font-semibold">{{ t.text }}</span>
                    <span class="text-slate-400"> · {{ t.detail }}</span>
                </span>
            </div>
            <div v-if="team?.description || (opponentTeam && !isByeWeek && opponentTeam.description)"
                 class="mt-3 pt-3 border-t border-slate-300 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <p class="text-xs text-slate-400 italic leading-relaxed line-clamp-2">{{ team?.description }}</p>
                <p v-if="opponentTeam && !isByeWeek" class="text-xs text-slate-400 italic leading-relaxed line-clamp-2 sm:text-right">{{ opponentTeam.description }}</p>
            </div>
        </div>

        <!-- Défis de la saison (second classement, sans effet sur le championnat) -->
        <SeasonChallengesCard v-if="seasonChallenges?.objectives?.length" :challenges="seasonChallenges" />

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
