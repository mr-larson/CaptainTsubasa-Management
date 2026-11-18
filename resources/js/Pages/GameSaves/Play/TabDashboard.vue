<script setup>
import NationFlag from '@/Components/NationFlag.vue';
import { Link } from '@inertiajs/vue3';
import TeamLogoImage from '@/Components/TeamLogoImage.vue';
import SectionTitle from '@/Components/SectionTitle.vue';
import Panel from '@/Components/Panel.vue';
import { remainingWeeks } from './worldCup.js';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import { computed } from 'vue';
import { useAnimatedStats } from '@/Composables/useAnimatedStats.js';
import { usePlayerUtils } from './usePlayerUtils.js';
import TeamStyleBadges from "@/Pages/GameSaves/Play/components/TeamStyleBadges.vue";
import DashboardSquadCards from "@/Pages/GameSaves/Play/components/DashboardSquadCards.vue";
import DashboardSeasonCards from "@/Pages/GameSaves/Play/components/DashboardSeasonCards.vue";
import AssistantAdviceCard from "@/Pages/GameSaves/Play/components/AssistantAdviceCard.vue";
import { useSeasonTrajectory } from "@/Pages/GameSaves/Play/useSeasonTrajectory.js";
import SeasonChallengesCard from "@/Pages/GameSaves/Play/components/SeasonChallengesCard.vue";
import PlayerRequestsCard from "@/Pages/GameSaves/Play/components/PlayerRequestsCard.vue";
import DashboardNextMatchCard from "@/Pages/GameSaves/Play/components/DashboardNextMatchCard.vue";
import DashboardForcesCard from "@/Pages/GameSaves/Play/components/DashboardForcesCard.vue";
import DashboardWorldCupTile from "@/Pages/GameSaves/Play/components/DashboardWorldCupTile.vue";

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
    playerRequests:     { type: Array,   default: () => [] },
    respondingToRequest: { type: Boolean, default: false },
    advice:             { type: Array,   default: () => [] },
    incomeModel:        { type: Object,  default: null },
    trainingCost:       { type: Number,  default: 0 },
    maxTrainings:       { type: Number,  default: 3 },
});

const emit = defineEmits(['play-next-match', 'simulate-match', 'simulate-week', 'save-game', 'quit', 'change-tab', 'open-player', 'apply-advice', 'respond-request']);

// Guide « Comment jouer » dans un nouvel onglet, pour ne pas quitter la partie.
const openHelp = () => window.open(route('guide') + '#preparer', '_blank', 'noopener');

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

// ==========================
//   SAISON PROGRESSION
// ==========================
const totalWeeks = computed(() => {
    // Dernière semaine du calendrier (juste aussi en Coupe du Monde).
    const weeks = (props.matches ?? []).map(m => Number(m.week) || 0);
    if (weeks.length) {
        // Coupe du Monde : les demies et la finale sont créées au fil du tournoi.
        // On ajoute les tours restants pour que la barre ne retombe pas à chaque phase.
        return Math.max(...weeks) + (props.isWorldCup ? remainingWeeks(props.tournament) : 0);
    }
    const n = props.standings.length;
    if (n < 2) return 28;
    return n % 2 === 1 ? n * 2 : (n - 1) * 2;
});

const seasonProgress = computed(() =>
    props.isWorldCup && props.tournament?.stage === 'done'
        ? 100
        : Math.min(100, Math.round(((props.week - 1) / Math.max(totalWeeks.value, 1)) * 100))
);

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

const KPI = 'border border-slate-200 rounded-xl bg-slate-50 p-2 text-left transition-colors hover:bg-slate-100 hover:border-slate-200';

const matchesPlayed = computed(() =>
    (props.teamRecord.wins ?? 0) + (props.teamRecord.draws ?? 0) + (props.teamRecord.losses ?? 0)
);
const hasPlayed = computed(() => matchesPlayed.value > 0);
const { root: seasonProgressRoot, value: animatedSeasonProgress, width: seasonProgressWidth } = useAnimatedStats(
    () => ({ progress: seasonProgress.value }),
    () => props.season,
);
</script>

<template>
    <div class="flex-1 flex flex-col gap-4 overflow-y-auto min-h-0 pr-1 [&>*]:shrink-0">

        <!-- ============================================ -->
        <!-- LIGNE 1 : Contexte + progression saison      -->
        <!-- ============================================ -->
        <Panel as="div">
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
                    <div v-if="!isWorldCup" class="text-center">
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
            <div ref="seasonProgressRoot">
                <div class="flex justify-between text-2xs text-slate-400 mb-1">
                    <span>Semaine 1</span>
                    <span class="font-semibold text-teal-500">{{ animatedSeasonProgress('progress') }}% {{ isWorldCup ? 'du tournoi' : 'de la saison' }}</span>
                    <span>Semaine {{ totalWeeks }}</span>
                </div>
                <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-teal-400 to-teal-500 rounded-full"
                         :style="{ width: seasonProgressWidth('progress') }">
                    </div>
                </div>
            </div>
        </Panel>

        <!-- Coupe du Monde : bannière champion -->
        <div v-if="isWorldCup && wcChampion"
             class="flex items-center justify-center gap-3 px-5 py-4 rounded-2xl bg-gradient-to-r from-amber-100 to-yellow-50 border border-amber-300">
            <span class="text-4xl">🏆</span>
            <div class="text-center">
                <div class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Champion du Monde</div>
                <div class="text-xl font-extrabold text-amber-800"><NationFlag :flag="wcChampion.flag" :url="wcChampion.flag_url" :name="wcChampion.name" /> {{ wcChampion.name }}</div>
            </div>
            <Link :href="route('game-saves.tournament-end', gameSave.id)"
                  class="ml-2 px-3 py-1.5 rounded-lg bg-amber-500 text-white text-xs font-bold hover:bg-amber-600">
                Bilan du tournoi
            </Link>
        </div>

        <!-- ============================================ -->
        <!-- LIGNE 2 : 3 indicateurs (clic → détail)     -->
        <!-- ============================================ -->
        <!-- Téléphone : Classement + Budget côte à côte, Bilan en dessous -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">

            <!-- Classement — Coupe du Monde : position dans la poule -->
            <DashboardWorldCupTile v-if="isWorldCup" :tournament="tournament" :team="team" @open="emit('change-tab', 'standings')" />

            <!-- Classement — Ligue : position, points, évolution, objectif -->
            <button v-else type="button" :class="KPI" class="flex flex-col items-center gap-1"
                    @click="emit('change-tab', 'standings')" title="Voir le classement">
                <SectionTitle as="span">Classement</SectionTitle>
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
                <SectionTitle as="span" class="block mb-2 text-center">Bilan</SectionTitle>
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
                <SectionTitle as="span">Budget</SectionTitle>
                <span class="text-3xl font-black text-teal-600 tabular-nums">{{ Number(teamBudget).toLocaleString('fr-FR') }}</span>
                <span v-if="projection && !isWorldCup" class="text-2xs text-slate-400">
                    ≈ {{ projection.likely.toLocaleString('fr-FR') }} en S{{ seasonEndWeek }}
                </span>
            </button>
        </div>

        <!-- ============================================ -->
        <!-- LIGNE 3 : Prochain match (hero)              -->
        <!-- ============================================ -->
        <DashboardNextMatchCard :gameSave="gameSave" :team="team" :opponentTeam="opponentTeam" :nextMatch="nextMatch"
                                :nextMatchInfo="nextMatchInfo" :isByeWeek="isByeWeek"
                                :midseasonBreakRecovery="midseasonBreakRecovery" :simulating="simulating"
                                :launchingMatch="launchingMatch" :canSimulateMatch="canSimulateMatch"
                                :isWorldCup="isWorldCup" :tournament="tournament" :clubStanding="clubStanding"
                                :standings="standings" :matches="matches" :week="week"
                                @play-next-match="emit('play-next-match')" @simulate-match="emit('simulate-match')"
                                @simulate-week="emit('simulate-week')" />

        <!-- Conseils de l'adjoint -->
        <AssistantAdviceCard class="border-l-4 border-l-teal-400" :advice="advice" @apply="a => emit('apply-advice', a)" />

        <!-- Cartes de la colonne gauche (rappels, mandat, récap) sur petit écran -->
        <slot name="side-cards" />

        <!-- ============================================ -->
        <!-- LIGNE 4 : Forces face à face                -->
        <!-- ============================================ -->
        <DashboardForcesCard :team="team" :opponentTeam="opponentTeam" :nextMatchInfo="nextMatchInfo" :roster="roster"
                             :isByeWeek="isByeWeek" :playerSeasonStats="playerSeasonStats" :week="week" />

        <!-- Demandes des joueurs en attente de réponse -->
        <PlayerRequestsCard v-if="playerRequests.length" :requests="playerRequests" :budget="teamBudget"
                            :processing="respondingToRequest"
                            @respond="(request, answer) => emit('respond-request', request, answer)"
                            @open-player="id => { const p = roster.find(x => Number(x.id) === Number(id)); if (p) emit('open-player', p); }" />

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
