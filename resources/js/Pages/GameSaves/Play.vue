<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import H2 from '@/Components/H2.vue';

// ==========================
//   COMPOSABLES
// ==========================
import { useTeam }      from '@/Pages/GameSaves/Play/useTeam.js';
import { useDashboard } from '@/Pages/GameSaves/Play/useDashboard.js';
import { useCalendar }  from '@/Pages/GameSaves/Play/useCalendar.js';
import { useStats }     from '@/Pages/GameSaves/Play/useStats.js';
import { useTraining }  from '@/Pages/GameSaves/Play/useTraining.js';
import { useTransfers } from '@/Pages/GameSaves/Play/useTransfers.js';
import { useOtherTeam } from '@/Pages/GameSaves/Play/useOtherTeam.js';
import { useBonusCards } from '@/Pages/GameSaves/Play/useBonusCards.js';

// ==========================
//   COMPOSANTS TABS
// ==========================
import TabDashboard  from '@/Pages/GameSaves/Play/TabDashboard.vue';
import TabMyTeam     from '@/Pages/GameSaves/Play/TabMyTeam.vue';
import TabOtherTeams from '@/Pages/GameSaves/Play/TabOtherTeams.vue';
import TabCalendar   from '@/Pages/GameSaves/Play/TabCalendar.vue';
import TabStandings  from '@/Pages/GameSaves/Play/TabStandings.vue';
import TabStats      from '@/Pages/GameSaves/Play/TabStats.vue';
import TabTraining   from '@/Pages/GameSaves/Play/TabTraining.vue';
import TabTransfers  from '@/Pages/GameSaves/Play/TabTransfers.vue';
import TabBonusCards     from '@/Pages/GameSaves/Play/TabBonusCards.vue';
import TabManagement from '@/Pages/GameSaves/Play/TabManagement.vue';

// ==========================
//   PROPS INERTIA
// ==========================
const props = defineProps({
    gameSave:          { type: Object, required: true },
    teams:             { type: Array,  required: true },
    freePlayers:       { type: Array,  required: true },
    matches:           { type: Array,  required: true },
    controlledTeam:    { type: Object, required: false, default: null },
    playerSeasonStats: { type: Object, default: () => ({}) },
    activeInjuries:    { type: Array,  default: () => [] },
    activeSuspensions: { type: Array,  default: () => [] },
    activeYellowCards: { type: Object, default: () => ({}) },
    weeklyRecap:       { type: Array,  default: () => [] },
    bonusCardOffers:    { type: Array, default: () => [] },
    bonusCardInventory: { type: Array, default: () => [] },
    managementStats:    { type: Object, default: () => ({}) },
    moraleLogs:         { type: Object, default: () => ({}) },
    playerPromises:     { type: Object, default: () => ({}) },
    playerDeclarations: { type: Object, default: () => ({}) },
    hotSeat:            { type: Object, required: false, default: null },
});

// Promesse au joueur (relation coach) — type : playing_time | starter | renewal
const makePromise = (player, type = 'playing_time') => {
    router.post(
        route('game-saves.players.promises.store', { gameSave: props.gameSave.id, player: player.id }),
        { type },
        { preserveScroll: true }
    );
};

// Déclaration publique — type : praise | criticize
const makeDeclaration = (player, type) => {
    router.post(
        route('game-saves.players.declarations.store', { gameSave: props.gameSave.id, player: player.id }),
        { type },
        { preserveScroll: true }
    );
};

// ==========================
//   SAISON / SEMAINE / STATE
// ==========================
const season       = ref(props.gameSave.season || 1);
const week         = ref(props.gameSave.week   || 1);
const currentState = ref(props.gameSave.state  || {});
const saving       = ref(false);

watch(() => props.gameSave.season, (v) => { season.value = v ?? 1; }, { immediate: true });
watch(() => props.gameSave.week,   (v) => { week.value   = v ?? 1; }, { immediate: true });

// ==========================
//   REFS RÉACTIVES POUR COMPOSABLES
// ==========================
const gameSaveRef    = computed(() => props.gameSave);
const teamsRef       = computed(() => props.teams);
const matchesRef     = computed(() => props.matches);
const freePlayersRef = computed(() => props.freePlayers);
const teamRef        = computed(() => props.controlledTeam || null);
const team           = computed(() => props.controlledTeam || null); // alias lisible pour le template

// ==========================
//   COMPOSABLES
// ==========================
const {
    roster, rosterWithStatus, starters,
    selectedMyPlayer, selectMyPlayer,
    overallOf, toggleStarter, toggleCaptain,
    lineupForm, getSlotForPlayer, saveLineup, changeSelectedPlayerSlot,
    currentFormation, formationData, saveFormation,
    slotRoleInfo, miniPitchMarkerStyle,
    playerPhotoUrl, teamLogoUrl,
    playerPosition, slotToPlayer, playerForSlot, selectedSlot,
    isPickedUp, handlePlayerClick, handleDragStart, handleDragOver, handleDrop,
    FORMATIONS, FORMATION_LIST,
} = useTeam({ gameSave: gameSaveRef, team: teamRef });

const activeInjuriesRef    = computed(() => props.activeInjuries    ?? []);
const activeSuspensionsRef = computed(() => props.activeSuspensions ?? []);
const activeYellowCardsRef = computed(() => props.activeYellowCards ?? {});
const weeklyRecapRef       = computed(() => props.weeklyRecap ?? []);

const {
    standings, clubStanding,
    teamRecord, teamBudget,
    injuriesCount, suspensionsCount, cardsCount,
    injuriesCountForTeam, suspensionsCountForTeam, cardsCountForTeam,
    isPlayerInjured, isPlayerSuspended, playerYellowCards, playerInjury, playerSuspension,
    averageAttack, averageDefense, averageStamina, averageSpeed,
} = useDashboard({
    teams: teamsRef, gameSave: gameSaveRef, team: teamRef, roster,
    activeInjuries: activeInjuriesRef,
    activeSuspensions: activeSuspensionsRef,
    activeYellowCards: activeYellowCardsRef,
});

// Compteurs pour la checklist pré-match du dashboard
const unboughtCardsCount = computed(() =>
    (weeklyOffers.value ?? []).filter(o => !isAlreadyBought(o)).length
);

const freePlayersCount = computed(() => (availableFreePlayers.value ?? []).length);

const {
    isByeMatch, teamById,
    opponentTeamIdFor, opponentNameFor, opponentNameForTeam,
    myMatches, isByeWeek, nextMatch, nextMatchInfo,
    selectedCalendarTeamId, calendarTeams, calendarTeam, selectCalendarTeam,
    calendarRows, calendarTeamRoster, calendarOpponentRoster,
    selectedCalendarMatch, selectedCalendarMatchStats,
    selectedCalendarMyTeamStats, selectedCalendarOpponentStats,
    selectedCalendarPlayersStats, openMatchStats, selectedCalendarProgression,
    selectedCalendarEvents,
} = useCalendar({ matches: matchesRef, teams: teamsRef, team: teamRef, week });

const playerSeasonStatsRef = computed(() => props.playerSeasonStats ?? {});

const {
    playerSeasonStats,
    selectedMyPlayerPerf: _selectedMyPlayerPerf,
    selectedStatsTeamId, selectedStatsTeam,
    selectedTeamPlayerStats, teamStats,
    averageTeamStat,
} = useStats({ gameSave: gameSaveRef, teams: teamsRef, team: teamRef, roster, playerSeasonStats: playerSeasonStatsRef });

// selectedMyPlayerPerf est une fonction qui retourne un computed
const selectedMyPlayerPerf = computed(() => _selectedMyPlayerPerf(selectedMyPlayer).value);

const {
    trainingState, remainingTrainingsThisWeek,
    hasPlayerBeenTrainedThisWeek, availableTrainingStats,
    selectedTrainings, addTrainingSlot, removeTrainingSlot,
    canSubmitTraining, submitTraining,
    aiTrainingEntries, aiCurrentDisplayWeek, aiWeekMax, prevAiWeek, nextAiWeek,
} = useTraining({ gameSave: gameSaveRef, season, week });

const {
    availableFreePlayers,
    showTransferModal, transferTarget,
    transferMatches, transferSalary, transferReason,
    transferTotalCost,
    openTransferModal, closeTransferModal, confirmTransfer,
    transferHistory,
} = useTransfers({ gameSave: gameSaveRef, freePlayers: freePlayersRef, team: teamRef, teams: teamsRef });

// ==========================
//   AUTRES ÉQUIPES
// ==========================
const {
    otherTeams, selectedOtherTeam, selectOtherTeam,
    otherRosterWithStatus, selectedOtherPlayer, selectOtherPlayer,
    toggleOtherStarter, toggleOtherCaptain,
    otherLineupForm, getOtherSlotForPlayer, changeOtherPlayerSlot,
    otherFormation, otherFormationData, saveOtherFormation,
    otherPlayerPosition, otherPlayerForSlot, otherSelectedSlot,
    updatePlayerNumber, isOtherPickedUp,
    handleOtherPlayerClick, handleOtherDragStart, handleOtherDragOver, handleOtherDrop,
} = useOtherTeam({
    gameSave: gameSaveRef,
    teams: teamsRef,
    controlledTeamId: computed(() => teamRef.value?.id ?? null),
});

// ==========================
//   CARTES BONUS
// ==========================
const bonusCardOffersRef    = computed(() => props.bonusCardOffers    ?? []);
const bonusCardInventoryRef = computed(() => props.bonusCardInventory ?? []);

const {
    weeklyOffers, canAfford, buyCard,
    inventory, availableCards, usedCards,
    selectedCard, selectCard, activateCard,
    tierColor, tierBadge, phaseLabel, targetLabel,
    isAlreadyBought,
} = useBonusCards({
    gameSave: gameSaveRef,
    bonusCardOffers:    bonusCardOffersRef,
    bonusCardInventory: bonusCardInventoryRef,
});

// ==========================
//   ONGLETS
// ==========================
const tabs = [
    { key: 'dashboard',   label: 'Dashboard'     },
    { key: 'my-team',     label: 'Mon équipe'     },
    { key: 'other-teams', label: 'Autres équipes' },
    { key: 'calendar',    label: 'Calendrier'     },
    { key: 'standings',   label: 'Classement'     },
    { key: 'match-stats', label: 'Stats'          },
    { key: 'training',    label: 'Entraînement'   },
    { key: 'transfers',   label: 'Transferts'     },
    { key: 'cards',       label: 'Cartes bonus'   },
    { key: 'management',  label: 'Gestion'        },
];
const activeTab = ref('dashboard');

// ==========================
//   ACTIONS GLOBALES
// ==========================
const playNextMatch  = () => router.get(route('game-saves.match', { gameSave: props.gameSave.id }));
const simulateWeek   = () => router.post(route('game-saves.simulate-week', { gameSave: props.gameSave.id }), {}, { preserveScroll: true, preserveState: false });

const saveGame = () => {
    saving.value = true;
    router.put(
        route('game-saves.update', props.gameSave.id),
        { label: props.gameSave.label, season: season.value, week: week.value, state: currentState.value },
        { preserveState: true, preserveScroll: true, onFinish: () => { saving.value = false; } }
    );
};


function updateOtherPlayerNumber(playerId, number) {
    router.patch(
        route('game-saves.players.update-number', {
            gameSave: props.gameSave.id,
            player: playerId,
        }),
        { number: parseInt(number) },
        { preserveScroll: true }
    );
}
</script>

<template>
    <Head :title="`Partie ${gameSave.label ?? '#' + gameSave.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Partie : {{ gameSave.label ?? `Sauvegarde #${gameSave.id}` }}</H2>
        </template>

        <div class="p-4">
            <div class="flex justify-center mb-3">
                <h1 class="text-3xl font-bold text-slate-600">Session de jeu</h1>
            </div>

            <!-- Hot-seat multi-manager : indicateur de tour -->
            <div v-if="hotSeat" class="max-w-3xl mx-auto mb-4">
                <div class="flex items-center gap-3 px-4 py-2.5 rounded-xl border border-teal-200 bg-teal-50">
                    <span class="text-xs font-bold text-teal-600 uppercase tracking-wider shrink-0">Hot-seat</span>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span v-for="p in hotSeat.players" :key="p.game_team_id"
                              class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border transition-all"
                              :class="p.is_active
                                  ? 'bg-teal-500 text-white border-teal-600 shadow-sm'
                                  : 'bg-white text-slate-500 border-slate-200'">
                            <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-black"
                                  :class="p.is_active ? 'bg-white/90 text-teal-700' : 'bg-slate-100 text-slate-400'">
                                {{ p.seat }}
                            </span>
                            {{ p.name }}
                            <span v-if="p.is_active" class="text-[10px] font-bold uppercase">• à toi de jouer</span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex flex-row">
                <!-- Visuel gauche -->
                <div class="hidden md:block basis-1/4 p-4 bg-contain bg-center bg-no-repeat"
                     style="background-image: url('/images/wakabayashi.webp')"></div>

                <!-- Carte principale -->
                <div class="basis-3/4 p-4 border border-slate-300 rounded-lg mx-6 bg-white min-h-[500px] flex flex-col">

                    <!-- Onglets -->
                    <div class="mb-4 border-b border-slate-200">
                        <nav class="-mb-px flex space-x-2 overflow-x-auto">
                            <button v-for="tab in tabs" :key="tab.key" type="button"
                                    @click="activeTab = tab.key"
                                    :class="['whitespace-nowrap py-2 px-4 text-sm font-medium border-b-2 rounded-t-md',
                                    activeTab === tab.key
                                        ? 'border-teal-500 text-slate-900 bg-slate-50'
                                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300']">
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>

                    <!-- ======== DASHBOARD ======== -->
                    <TabDashboard v-if="activeTab === 'dashboard'"
                                  :gameSave="gameSave"
                                  :team="teamRef"
                                  :season="season"
                                  :week="week"
                                  :roster="rosterWithStatus"
                                  :clubStanding="clubStanding"
                                  :standings="standings"
                                  :teamRecord="teamRecord"
                                  :teamBudget="teamBudget"
                                  :injuriesCount="injuriesCount"
                                  :suspensionsCount="suspensionsCount"
                                  :cardsCount="cardsCount"
                                  :averageAttack="averageAttack"
                                  :averageDefense="averageDefense"
                                  :averageStamina="averageStamina"
                                  :nextMatch="nextMatch"
                                  :nextMatchInfo="nextMatchInfo"
                                  :isByeWeek="isByeWeek"
                                  :teamById="teamById"
                                  :saving="saving"
                                  :matches="matches"
                                  :unboughtCardsCount="unboughtCardsCount"
                                  :freePlayersCount="freePlayersCount"
                                  :weeklyRecap="weeklyRecapRef"
                                  @change-tab="activeTab = $event"
                                  :isPlayerInjured="isPlayerInjured"
                                  @play-next-match="playNextMatch"
                                  @simulate-week="simulateWeek"
                                  @save-game="saveGame"
                                  @quit="router.visit(route('mainMenu'))"
                    />

                    <!-- ======== MON ÉQUIPE ======== -->
                    <TabMyTeam v-else-if="activeTab === 'my-team'"
                               :rosterWithStatus="rosterWithStatus"
                               :selectedMyPlayer="selectedMyPlayer"
                               :currentFormation="currentFormation"
                               :formationData="formationData"
                               :miniPitchMarkerStyle="miniPitchMarkerStyle"
                               :selectedMyPlayerPerf="selectedMyPlayerPerf"
                               :lineupForm="lineupForm"
                               :playerPosition="playerPosition"
                               :playerForSlot="playerForSlot"
                               :selectedSlot="selectedSlot"
                               :team="team"
                               :teamRecord="teamRecord"
                               :teamBudget="teamBudget"
                               :isPickedUp="isPickedUp"
                               @player-click="handlePlayerClick"
                               @drag-start="handleDragStart"
                               @drag-over="handleDragOver"
                               @drop-on="handleDrop"
                               :clubStanding="clubStanding"
                               :standings="standings"
                               :injuriesCount="injuriesCount"
                               :suspensionsCount="suspensionsCount"
                               :cardsCount="cardsCount"
                               :averageAttack="averageAttack"
                               :averageDefense="averageDefense"
                               :averageStamina="averageStamina"
                               :moraleLogs="moraleLogs"
                               :playerPromises="playerPromises"
                               :playerDeclarations="playerDeclarations"
                               :currentWeek="week"
                               @make-promise="makePromise"
                               @make-declaration="makeDeclaration"
                               :isPlayerInjured="isPlayerInjured"
                               :isPlayerSuspended="isPlayerSuspended"
                               :playerYellowCards="playerYellowCards"
                               :playerInjury="playerInjury"
                               :playerSuspension="playerSuspension"
                               @select-player="selectMyPlayer"
                               @toggle-starter="toggleStarter"
                               @toggle-captain="toggleCaptain"
                               @change-slot="changeSelectedPlayerSlot"
                               @save-formation="saveFormation"
                               @update-number="updatePlayerNumber"
                    />

                    <!-- ======== AUTRES ÉQUIPES ======== -->
                    <TabOtherTeams v-else-if="activeTab === 'other-teams'"
                                   :otherTeams="otherTeams"
                                   :selectedOtherTeam="selectedOtherTeam"
                                   :otherRosterWithStatus="otherRosterWithStatus"
                                   :selectedOtherPlayer="selectedOtherPlayer"
                                   :otherLineupForm="otherLineupForm"
                                   :otherSelectedSlot="otherSelectedSlot"
                                   :otherPlayerPosition="otherPlayerPosition"
                                   :otherPlayerForSlot="otherPlayerForSlot"
                                   :getOtherSlotForPlayer="getOtherSlotForPlayer"
                                   :otherFormation="otherFormation"
                                   :otherFormationData="otherFormationData"
                                   :standings="standings"
                                   :injuriesCountForTeam="injuriesCountForTeam"
                                   :suspensionsCountForTeam="suspensionsCountForTeam"
                                   :cardsCountForTeam="cardsCountForTeam"
                                   :playerInjury="playerInjury"
                                   :playerSuspension="playerSuspension"
                                   :averageTeamStat="averageTeamStat"
                                   :playerSeasonStats="props.playerSeasonStats"
                                   :isPlayerInjured="isPlayerInjured"
                                   :isPlayerSuspended="isPlayerSuspended"
                                   :playerYellowCards="playerYellowCards"
                                   :isOtherPickedUp="isOtherPickedUp"
                                   @player-click="handleOtherPlayerClick"
                                   @drag-start="handleOtherDragStart"
                                   @drag-over="handleOtherDragOver"
                                   @drop-on="handleOtherDrop"
                                   @select-team="selectOtherTeam"
                                   @select-player="selectOtherPlayer"
                                   @toggle-starter="toggleOtherStarter"
                                   @toggle-captain="toggleOtherCaptain"
                                   @change-slot="changeOtherPlayerSlot"
                                   @save-formation="saveOtherFormation"
                                   @update-number="updateOtherPlayerNumber"
                    />

                    <!-- ======== CALENDRIER ======== -->
                    <TabCalendar v-else-if="activeTab === 'calendar'"
                                 :calendarTeams="calendarTeams"
                                 :calendarTeam="calendarTeam"
                                 :calendarRows="calendarRows"
                                 :calendarTeamRoster="calendarTeamRoster"
                                 :calendarOpponentRoster="calendarOpponentRoster"
                                 :teamById="teamById"
                                 :selectedCalendarMatch="selectedCalendarMatch"
                                 :selectedCalendarMatchStats="selectedCalendarMatchStats"
                                 :selectedCalendarMyTeamStats="selectedCalendarMyTeamStats"
                                 :selectedCalendarOpponentStats="selectedCalendarOpponentStats"
                                 :selectedCalendarPlayersStats="selectedCalendarPlayersStats"
                                 :selectedCalendarProgression="selectedCalendarProgression"
                                 :selectedCalendarEvents="selectedCalendarEvents"
                                 :isByeMatch="isByeMatch"
                                 :opponentNameForTeam="opponentNameForTeam"
                                 @select-team="selectCalendarTeam"
                                 @open-match-stats="openMatchStats"
                    />

                    <!-- ======== CLASSEMENT ======== -->
                    <TabStandings v-else-if="activeTab === 'standings'"
                                  :standings="standings"
                                  :team="teamRef"
                                  :matches="matches"
                    />

                    <!-- ======== STATS ======== -->
                    <TabStats v-else-if="activeTab === 'match-stats'"
                              :teams="teams"
                              :team="team"
                              :playerSeasonStats="props.playerSeasonStats"
                    />

                    <!-- ======== ENTRAÎNEMENT ======== -->
                    <TabTraining v-else-if="activeTab === 'training'"
                                 :season="season"
                                 :week="week"
                                 :roster="rosterWithStatus"
                                 :trainingState="trainingState"
                                 :playerSeasonStats="props.playerSeasonStats"
                                 :isPlayerInjured="isPlayerInjured"
                                 :isPlayerSuspended="isPlayerSuspended"
                                 :playerYellowCards="playerYellowCards"
                                 :playerInjury="playerInjury"
                                 :playerSuspension="playerSuspension"
                                 :remainingTrainingsThisWeek="remainingTrainingsThisWeek"
                                 :hasPlayerBeenTrainedThisWeek="hasPlayerBeenTrainedThisWeek"
                                 :availableTrainingStats="availableTrainingStats"
                                 :selectedTrainings="selectedTrainings"
                                 :canSubmitTraining="canSubmitTraining"
                                 :aiTrainingEntries="aiTrainingEntries"
                                 :aiCurrentDisplayWeek="aiCurrentDisplayWeek"
                                 :aiWeekMax="aiWeekMax"
                                 @add-slot="addTrainingSlot"
                                 @remove-slot="removeTrainingSlot"
                                 @submit-training="submitTraining"
                                 @prev-ai-week="prevAiWeek"
                                 @next-ai-week="nextAiWeek"
                    />

                    <!-- ======== TRANSFERTS ======== -->
                    <TabTransfers v-else-if="activeTab === 'transfers'"
                                  :availableFreePlayers="availableFreePlayers"
                                  :team="teamRef"
                                  :teamBudget="teamBudget"
                                  :showTransferModal="showTransferModal"
                                  :transferTarget="transferTarget"
                                  :transferMatches="transferMatches"
                                  :transferSalary="transferSalary"
                                  :transferReason="transferReason"
                                  :transferTotalCost="transferTotalCost"
                                  :roster="roster"
                                  @open-modal="openTransferModal"
                                  @close-modal="closeTransferModal"
                                  @confirm-transfer="confirmTransfer"
                                  @update:transferMatches="transferMatches = $event"
                                  @update:transferSalary="transferSalary = $event"
                                  @update:transferReason="transferReason = $event"
                                  :transferHistory="transferHistory"
                    />

                    <!-- ======== CARTES BONUS ======== -->
                    <TabBonusCards v-else-if="activeTab === 'cards'"
                                   :weeklyOffers="weeklyOffers"
                                   :inventory="inventory"
                                   :teamBudget="teamBudget"
                                   :rosterWithStatus="rosterWithStatus"
                                   :season="season"
                                   :week="week"
                                   :isAlreadyBought="isAlreadyBought"
                                   :isPlayerInjured="isPlayerInjured"
                                   @buy="buyCard"
                                   @activate="activateCard"
                    />

                    <!-- ======== GESTION ======== -->
                    <TabManagement v-else-if="activeTab === 'management'"
                                   :gameSave="gameSave"
                                   :managementStats="props.managementStats"
                    />

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
