<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import H2 from '@/Components/H2.vue';

// ==========================
//   COMPOSABLES
// ==========================
import { useTeam }      from './play/useTeam.js';
import { useDashboard } from './play/useDashboard.js';
import { useCalendar }  from './play/useCalendar.js';
import { useStats }     from './play/useStats.js';
import { useTraining }  from './play/useTraining.js';
import { useTransfers } from './play/useTransfers.js';
import { useOtherTeam } from './play/useOtherTeam.js';

// ==========================
//   COMPOSANTS TABS
// ==========================
import TabDashboard  from './play/TabDashboard.vue';
import TabMyTeam     from './play/TabMyTeam.vue';
import TabOtherTeams from './play/TabOtherTeams.vue';
import TabCalendar   from './play/TabCalendar.vue';
import TabStandings  from './play/TabStandings.vue';
import TabStats      from './play/TabStats.vue';
import TabTraining   from './play/TabTraining.vue';
import TabTransfers  from './play/TabTransfers.vue';
import TabManagement from './play/TabManagement.vue';

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
});

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
    FORMATIONS, FORMATION_LIST,
} = useTeam({ gameSave: gameSaveRef, team: teamRef });

const activeInjuriesRef    = computed(() => props.activeInjuries    ?? []);
const activeSuspensionsRef = computed(() => props.activeSuspensions ?? []);
const activeYellowCardsRef = computed(() => props.activeYellowCards ?? {});

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

const {
    isByeMatch, teamById,
    opponentTeamIdFor, opponentNameFor, opponentNameForTeam,
    myMatches, isByeWeek, nextMatch, nextMatchInfo,
    selectedCalendarTeamId, calendarTeams, calendarTeam, selectCalendarTeam,
    calendarRows, calendarTeamRoster, calendarOpponentRoster,
    selectedCalendarMatch, selectedCalendarMatchStats,
    selectedCalendarMyTeamStats, selectedCalendarOpponentStats,
    selectedCalendarPlayersStats, openMatchStats,
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
    aiTrainingEntries,
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
    updatePlayerNumber,
} = useOtherTeam({
    gameSave: gameSaveRef,
    teams: teamsRef,
    controlledTeamId: computed(() => teamRef.value?.id ?? null),
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
                                  :roster="roster"
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
                               :clubStanding="clubStanding"
                               :standings="standings"
                               :injuriesCount="injuriesCount"
                               :suspensionsCount="suspensionsCount"
                               :cardsCount="cardsCount"
                               :averageAttack="averageAttack"
                               :averageDefense="averageDefense"
                               :averageStamina="averageStamina"
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
                                   :averageTeamStat="averageTeamStat"
                                   :playerSeasonStats="props.playerSeasonStats"
                                   :isPlayerInjured="isPlayerInjured"
                                   :isPlayerSuspended="isPlayerSuspended"
                                   :playerYellowCards="playerYellowCards"
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
                                 :teamById="teamById"
                                 :selectedCalendarMatch="selectedCalendarMatch"
                                 :selectedCalendarMatchStats="selectedCalendarMatchStats"
                                 :selectedCalendarMyTeamStats="selectedCalendarMyTeamStats"
                                 :selectedCalendarOpponentStats="selectedCalendarOpponentStats"
                                 :selectedCalendarPlayersStats="selectedCalendarPlayersStats"
                                 :isByeMatch="isByeMatch"
                                 :opponentNameForTeam="opponentNameForTeam"
                                 @select-team="selectCalendarTeam"
                                 @open-match-stats="openMatchStats"
                                 :calendarOpponentRoster="calendarOpponentRoster"
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
                                 :roster="roster"
                                 :trainingState="trainingState"
                                 :remainingTrainingsThisWeek="remainingTrainingsThisWeek"
                                 :hasPlayerBeenTrainedThisWeek="hasPlayerBeenTrainedThisWeek"
                                 :availableTrainingStats="availableTrainingStats"
                                 :selectedTrainings="selectedTrainings"
                                 :canSubmitTraining="canSubmitTraining"
                                 :aiTrainingEntries="aiTrainingEntries"
                                 @add-slot="addTrainingSlot"
                                 @remove-slot="removeTrainingSlot"
                                 @submit-training="submitTraining"
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
                    <div v-else-if="activeTab === 'cards'" class="flex-1 min-h-[75vh]">
                        <h3 class="text-lg font-semibold text-slate-700 mb-2">Cartes bonus</h3>
                        <p class="text-sm text-slate-600">Système de cartes bonus / malus (à venir).</p>
                    </div>

                    <!-- ======== GESTION ======== -->
                    <TabManagement v-else-if="activeTab === 'management'"
                                   :gameSave="gameSave"
                    />

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
