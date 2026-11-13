<script setup>
import { countByPositionGroup, positionLabel } from '@/Enums/positionBadges.js';
import { fieldClass } from '@/Components/fieldStyles';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import H2 from '@/Components/H2.vue';
import { Head, router } from '@inertiajs/vue3';
import { alertDialog } from '@/Composables/useDialog.js';
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import Modal from '@/Components/Modal.vue';
import TeamStyleBadges from '@/Pages/GameSaves/Play/components/TeamStyleBadges.vue';
import Panel from '@/Components/Panel.vue';
import PlayerAvatar from '@/Components/PlayerAvatar.vue';
import PositionBadge from '@/Components/PositionBadge.vue';
import PositionCounts from '@/Components/PositionCounts.vue';
import TeamLogo from '@/Components/TeamLogo.vue';
import PlayerIdentityCard from '@/Pages/GameSaves/Play/components/PlayerIdentityCard.vue';
import PlayerCompareButton from './Play/components/PlayerCompareButton.vue';
import PlayerComparison from '@/Pages/GameSaves/Play/components/PlayerComparison.vue';
import { positionRating } from '@/Pages/GameSaves/Play/recruitment.js';
import { useRecruitFilters } from '@/Pages/GameSaves/Play/useRecruitFilters.js';
import RecruitFilterBar from '@/Pages/GameSaves/Play/components/RecruitFilterBar.vue';
import RecruitRow from '@/Pages/GameSaves/Play/components/RecruitRow.vue';
import PlayerSpecialMoves from './Play/components/PlayerSpecialMoves.vue';
import StatBars from '@/Pages/GameSaves/Play/components/StatBars.vue';
import { usePlayerUtils } from '@/Pages/GameSaves/Play/usePlayerUtils.js';
import DraftPickAnimation from '@/Pages/GameSaves/Draft/DraftPickAnimation.vue';
import DraftOrderList from '@/Pages/GameSaves/Draft/DraftOrderList.vue';
import DraftPickLog from '@/Pages/GameSaves/Draft/DraftPickLog.vue';


const { moraleState, overallOf } = usePlayerUtils();

const props = defineProps({
    gameSave:          { type: Object, required: true },
    teams:             { type: Array,  required: true },
    freePlayers:       { type: Array,  required: true },
    draftState:        { type: Object, default: null },
    controlledTeamId:  { type: Number, default: null },
    controlledTeamIds: { type: Array,  default: () => [] },
    duoLinks:          { type: Object, default: () => ({}) },
});

// Duos d'alchimie : premier lien connu d'un joueur (badge 🤝 dans le tableau).
// Recruter les deux membres d'un duo débloque l'action une-deux en match.
const duoOf = (player) => (props.duoLinks?.[player.base_player_id] ?? [])[0] ?? null;
const duoTitle = (player) => {
    const d = duoOf(player);
    return d ? `${d.label ?? 'Duo'} avec ${d.partner_name} — recrutez les deux pour débloquer le une-deux` : '';
};

// Équipes humaines (hot-seat). Repli sur l'unique équipe contrôlée (mono).
const humanTeamIds = computed(() =>
    props.controlledTeamIds?.length ? props.controlledTeamIds : [props.controlledTeamId]
);
const isHumanTeam = (id) => humanTeamIds.value.includes(id);

// ══════════════════════════════════════
//  STATE RÉACTIF
// ══════════════════════════════════════
const draft          = ref({ ...props.draftState });
const availablePlayers = ref([...props.freePlayers]);
const pickLog        = ref([...(props.draftState?.picks ?? [])]);
const isProcessing   = ref(false);
const lastPick       = ref(null);
const showPickAnim   = ref(false);
const draftCompleted = ref(props.draftState?.completed ?? false);
const inspectedPlayerId = ref(null);
const comparison = ref([]);
const playerDetail = ref(null);
const rosterModalTeamId = ref(null);
const rosterModalPlayerId = ref(null);
const rosterModalTeam = computed(() => props.teams.find(team => team.id === rosterModalTeamId.value));
const rosterModalPicks = computed(() => pickLog.value.filter(pick => pick.team_id === rosterModalTeamId.value));
const rosterModalPick = computed(() => rosterModalPicks.value.find(pick => pick.player_id === rosterModalPlayerId.value)
    ?? rosterModalPicks.value[0]);
const draftPlayersById = computed(() => {
    // À l’ouverture de la page, les joueurs déjà piochés sont dans les contrats.
    // Les picks faits depuis sont encore présents dans le pool initial.
    const players = new Map(props.freePlayers.map(player => [player.id, player]));
    for (const team of props.teams) {
        for (const contract of team.contracts ?? []) {
            const player = contract.game_player ?? contract.gamePlayer;
            if (player) players.set(player.id, player);
        }
    }
    return players;
});
const rosterModalPlayer = computed(() => draftPlayersById.value.get(rosterModalPick.value?.player_id));

function openRosterModal(teamId) {
    rosterModalPlayerId.value = null;
    rosterModalTeamId.value = teamId;
}

function inspectPlayer(player) {
    inspectedPlayerId.value = player.id;
    if (!window.matchMedia('(min-width: 1280px)').matches) {
        nextTick(() => playerDetail.value?.scrollIntoView({ block: 'nearest' }));
    }
}
const inspectedTeamId = ref(props.controlledTeamId);

// Rosters locaux par équipe (reconstruits depuis les picks)
const teamRosters = ref({});

// Init rosters depuis les contrats existants
function initRosters() {
    const rosters = {};
    props.teams.forEach(t => {
        rosters[t.id] = {
            count:  t.contracts?.length ?? 0,
            budget: props.draftState?.budgets?.[t.id] ?? t.budget ?? 0,
            name:   t.name,
        };
    });
    teamRosters.value = rosters;
}

// ══════════════════════════════════════
//  COMPUTED
// ══════════════════════════════════════
const currentOrder = computed(() => {
    const order = draft.value?.order ?? [];
    const round = draft.value?.round ?? 1;
    return (round % 2 === 0) ? [...order].reverse() : order;
});

const currentTeamId = computed(() => {
    const idx = draft.value?.current_pick_index ?? 0;
    return currentOrder.value[idx] ?? null;
});

const currentTeam = computed(() =>
    props.teams.find(t => t.id === currentTeamId.value)
);

const isMyTurn = computed(() =>
    isHumanTeam(currentTeamId.value)
);

// Joueur humain aux commandes : l'équipe sur l'horloge si c'est un tour humain,
// sinon repli sur la 1re équipe humaine (pour l'affichage hors-tour).
const activeHumanTeamId = computed(() =>
    isMyTurn.value ? currentTeamId.value : humanTeamIds.value[0]
);

const myTeam = computed(() =>
    props.teams.find(t => t.id === activeHumanTeamId.value)
);

const myRosterCount = computed(() =>
    teamRosters.value[activeHumanTeamId.value]?.count ?? 0
);

// Sous 14 joueurs, terminer n'est permis que si plus aucun joueur n'est
// abordable (le serveur applique la même règle) : sinon le draft serait bloqué.
const MIN_SQUAD = 14;
const canAffordAnyone = computed(() => availablePlayers.value.some(p => canAfford(p)));
const canFinishDraft  = computed(() => myRosterCount.value >= MIN_SQUAD || !canAffordAnyone.value);

const myBudget = computed(() =>
    teamRosters.value[activeHumanTeamId.value]?.budget ?? 0
);

// Joueurs filtrés et triés (mêmes filtres que l'onglet Transferts)
const {
    search: searchQuery, position: filterPosition, sort: sortBy, maxPrice,
    filtered: filteredPlayers, positionOptions,
} = useRecruitFilters(availablePlayers, { cost: (player) => draftCost(player), overall: overallOf, defaultSort: 'profile' });

// Ordre de draft visuel (toutes les équipes dans l'ordre du round courant)
const draftOrderTeams = computed(() => {
    return currentOrder.value.map((id, idx) => {
        const team = props.teams.find(t => t.id === id);
        return {
            ...team,
            isCurrent: idx === (draft.value?.current_pick_index ?? 0),
            rosterCount: teamRosters.value[id]?.count ?? 0,
            budget: teamRosters.value[id]?.budget ?? 0,
        };
    });
});

const inspectedTeam = computed(() =>
    props.teams.find(t => t.id === inspectedTeamId.value)
);

const inspectedRoster = computed(() => {
    return pickLog.value
        .filter(p => p.team_id === inspectedTeamId.value)
        .reverse();
});

const inspectedPosCounts = computed(() => countByPositionGroup(inspectedRoster.value));

// ══════════════════════════════════════
//  HELPERS
// ══════════════════════════════════════
const inspectedPlayer = computed(() => filteredPlayers.value.find(p => p.id === inspectedPlayerId.value)
    ?? filteredPlayers.value[0] ?? null);

// Le serveur facture le coût majoré de la polyvalence (adjusted_cost) :
// prix affiché, tri et « abordable » doivent suivre la même base.
const weeklyCost = (player) => player?.adjusted_cost ?? player?.cost ?? 0;
const draftCost = (player) => {
    if (player.draft_cost != null) return player.draft_cost;
    // Une page de draft ouverte avant la mise à jour peut encore avoir les anciennes données.
    const teams = props.teams.length;
    const weeks = teams < 2 ? 28 : teams % 2 === 1 ? teams * 2 : (teams - 1) * 2;
    return Math.floor(weeklyCost(player) * weeks / 2);
};

function canAfford(player) {
    return draftCost(player) <= myBudget.value;
}

async function finishMyDraft() {
    if (!canFinishDraft.value) return;

    isProcessing.value = true;
    try {
        const response = await axios.post(route('game-saves.draft.finish', props.gameSave.id));
        const data = response.data;

        if (data.draftState) draft.value = data.draftState;
        if (data.completed) {
            draftCompleted.value = true;
        } else {
            // Continuer les picks IA
            await nextTick();
            runAiPicks();
        }
    } catch (err) {
        console.error('Finish error:', err);
    }
    isProcessing.value = false;
}

// ══════════════════════════════════════
//  PICK ANIMATION
// ══════════════════════════════════════
async function showPickAnimation(pick) {
    lastPick.value    = pick;
    showPickAnim.value = true;
    await wait(1800);
    showPickAnim.value = false;
}

// ══════════════════════════════════════
//  AI PICK FLOW
// ══════════════════════════════════════
// La boucle s'arrête au démontage de la page (navigation Inertia) et ne peut
// tourner qu'en un seul exemplaire. Si le serveur répond plusieurs fois sans
// faire avancer le draft, on sort avec un message au lieu de boucler.
const MAX_STALLED_AI_RESPONSES = 3;
let alive         = true;
let aiLoopRunning = false;
const pendingTimers = new Set();
const aiStalled     = ref(false);

function wait(ms) {
    return new Promise(resolve => {
        const t = setTimeout(() => { pendingTimers.delete(t); resolve(); }, ms);
        pendingTimers.add(t);
    });
}

function draftProgressKey() {
    const d = draft.value ?? {};
    return [d.round, d.current_pick_index, (d.finished_teams ?? []).length, d.completed, pickLog.value.length].join('|');
}

async function runAiPicks() {
    if (aiLoopRunning) return;
    aiLoopRunning   = true;
    aiStalled.value = false;
    let stalled     = 0;

    while (alive && !isMyTurn.value && !draftCompleted.value) {
        isProcessing.value = true;
        const before = draftProgressKey();

        // Pause de suspense
        await wait(800);
        if (!alive) break;

        try {
            const response = await axios.post(route('game-saves.draft.ai-pick', props.gameSave.id));
            if (!alive) break;
            const data = response.data;

            if (data.completed) {
                draftCompleted.value = true;
                break;
            }

            if (data.pick && !data.skipped) {
                pickLog.value.push(data.pick);
                availablePlayers.value = availablePlayers.value.filter(p => p.id !== data.pick.player_id);

                if (teamRosters.value[data.pick.team_id]) {
                    teamRosters.value[data.pick.team_id].count++;
                    teamRosters.value[data.pick.team_id].budget -= data.pick.total_cost;
                }

                await showPickAnimation(data.pick);
            }

            if (data.draftState) {
                draft.value = data.draftState;
            }
        } catch (err) {
            console.error('AI pick error:', err);
            aiStalled.value = true;
            break;
        }

        stalled = draftProgressKey() === before ? stalled + 1 : 0;
        if (stalled >= MAX_STALLED_AI_RESPONSES) {
            aiStalled.value = true;
            break;
        }
    }

    aiLoopRunning = false;
    if (alive) isProcessing.value = false;
}

function retryAiPicks() {
    aiStalled.value = false;
    runAiPicks();
}

onBeforeUnmount(() => {
    alive = false;
    pendingTimers.forEach(clearTimeout);
    pendingTimers.clear();
});

// ══════════════════════════════════════
//  HUMAN PICK
// ══════════════════════════════════════
async function pickPlayer(player) {
    if (!isMyTurn.value || isProcessing.value) return;
    if (!canAfford(player)) return;

    isProcessing.value = true;

    try {
        const response = await axios.post(route('game-saves.draft.pick', props.gameSave.id), {
            player_id: player.id,
        });
        const data = response.data;

        if (data.pick) {
            pickLog.value.push(data.pick);
            availablePlayers.value = availablePlayers.value.filter(p => p.id !== data.pick.player_id);

            if (teamRosters.value[data.pick.team_id]) {
                teamRosters.value[data.pick.team_id].count++;
                teamRosters.value[data.pick.team_id].budget -= data.pick.total_cost;
            }

            await showPickAnimation(data.pick);
        }

        if (data.draftState) {
            draft.value = data.draftState;
        }

        if (data.completed) {
            draftCompleted.value = true;
        } else {
            // Lancer les picks IA suivants
            await nextTick();
            runAiPicks();
        }
    } catch (err) {
        console.error('Pick error:', err);
        alertDialog({ title: 'Sélection impossible', message: err.response?.data?.error ?? 'Impossible de sélectionner ce joueur. Vérifie ton budget.' });
    }

    isProcessing.value = false;
}

// ══════════════════════════════════════
//  FIN DU DRAFT
// ══════════════════════════════════════
function goToPlay() {
    router.visit(route('game-saves.Play', props.gameSave.id)); // la route est « /Play » (majuscule)
}

// ══════════════════════════════════════
//  INIT
// ══════════════════════════════════════
onMounted(() => {
    initRosters();

    if (!draftCompleted.value && !isMyTurn.value) {
        // C'est le tour de l'IA au chargement → lancer
        runAiPicks();
    }
});
</script>

<template>
    <Head title="Draft" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Draft — Saison {{ gameSave.season }}</H2>
        </template>

        <div class="min-h-full bg-slate-50 p-2 sm:p-4">
            <div class="max-w-7xl mx-auto">

                <!-- ══════ PICK ANIMATION OVERLAY ══════ -->
                <DraftPickAnimation :show="showPickAnim" :pick="lastPick" :highlight="!!lastPick && isHumanTeam(lastPick.team_id)" />

                <!-- ══════ DRAFT TERMINÉ ══════ -->
                <div v-if="draftCompleted" class="text-center py-16">
                    <div class="text-5xl mb-4">🏆</div>
                    <h2 class="text-2xl font-black text-slate-700 mb-2">Draft terminé !</h2>
                    <p class="text-sm text-slate-500 mb-2">
                        {{ pickLog.length }} joueurs ont été sélectionnés.
                        Ton équipe compte {{ myRosterCount }} joueurs.
                    </p>
                    <ButtonPrimary type="button" size="lg" class="mt-6" @click="goToPlay">
                        ⚽ Commencer la saison
                    </ButtonPrimary>
                </div>

                <!-- ══════ DRAFT EN COURS ══════ -->
                <template v-else>
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

                        <!-- ══════ COL GAUCHE : Ordre + Log ══════ -->
                        <div class="col-span-1 lg:col-span-3 flex flex-col gap-4">

                            <!-- Mon équipe -->
                            <Panel tone="white" padding="sm">
                                <div class="flex items-center gap-2 mb-2">
                                    <TeamLogo :team="myTeam" size="md" shape="rounded" fallback="none" />
                                    <div>
                                        <div class="text-xs font-bold text-teal-700">{{ myTeam?.name }}</div>
                                        <div class="text-2xs text-teal-500">{{ myRosterCount }}/18 joueurs • Budget draft {{ myBudget }} <GameBanknote /></div>
                                    </div>
                                </div>
                                <TeamStyleBadges :team="myTeam" size="sm" />
                            </Panel>

                            <!-- Effectif d'une équipe -->
                            <Panel tone="white" padding="sm" title="Effectif">
                                <template #header-actions>
                                    <span class="font-bold">{{ inspectedRoster.length }}</span>
                                </template>
                                <PositionCounts :counts="inspectedPosCounts" class="mb-2" />

                                <!-- Sélecteur équipe -->
                                <select v-model="inspectedTeamId"
                                        class="w-full px-2 py-1 mb-2" :class="fieldClass({ shape: 'rect', size: 'sm', border: 'border-slate-300' })">
                                    <option v-for="t in draftOrderTeams" :key="t.id" :value="t.id">
                                        {{ t.name }} ({{ t.rosterCount }})
                                    </option>
                                </select>

                                <!-- Liste des joueurs piochés -->
                                <div class="max-h-[200px] overflow-y-auto space-y-1">
                                    <div v-for="pick in inspectedRoster" :key="pick.player_id"
                                         class="flex items-center gap-2 px-2 py-1 rounded-lg bg-slate-50 text-[11px]">
                                        <PlayerAvatar :player="pick" size="xs" />
                                        <span class="flex-1 font-semibold text-slate-700 truncate">{{ pick.player_name }}</span>
                                        <PositionBadge :position="pick.position" />
                                        <span class="text-3xs text-slate-400">{{ pick.cost }} <GameBanknote /></span>
                                    </div>
                                    <p v-if="!inspectedRoster.length" class="text-2xs text-slate-400 italic text-center py-2">
                                        Aucun joueur pioché
                                    </p>
                                </div>
                            </Panel>

                            <!-- Ordre de draft -->
                            <DraftOrderList :teams="draftOrderTeams" :round="draft?.round ?? 1"
                                            :is-human-team="isHumanTeam"
                                            @inspect-team="openRosterModal" />

                            <!-- Log des derniers picks -->
                            <DraftPickLog :picks="pickLog" :is-human-team="isHumanTeam" />
                        </div>

                        <!-- ══════ COL CENTRALE + DROITE : Joueurs ══════ -->
                        <div class="col-span-1 flex min-w-0 flex-col lg:col-span-9">

                            <div class="mb-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-xl border border-slate-300 bg-white px-4 py-3">
                                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                    <h1 class="text-sm font-bold text-slate-700">
                                        Tour {{ draft?.round ?? 1 }} — Pick {{ (draft?.current_pick_index ?? 0) + 1 }} / {{ draft?.order?.length ?? 0 }}
                                    </h1>
                                    <p class="text-xs text-slate-500">{{ availablePlayers.length }} joueurs disponibles</p>
                                </div>
                                <div role="status">
                                    <div v-if="isProcessing && !isMyTurn" class="flex items-center gap-2">
                                        <div class="relative shrink-0">
                                            <TeamLogo :team="currentTeam" size="md" />
                                            <div class="absolute inset-0 rounded-full border-2 border-slate-300 border-t-slate-300 animate-spin"></div>
                                        </div>
                                        <span class="text-sm font-bold text-slate-600">
                                            {{ currentTeam?.name ?? '—' }} est en train de choisir...
                                        </span>
                                    </div>
                                    <div v-else-if="aiStalled && !isMyTurn" class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-bold text-rose-600">
                                            Le draft de {{ currentTeam?.name ?? 'l\'IA' }} ne progresse plus.
                                        </span>
                                        <button type="button" @click="retryAiPicks"
                                                class="px-4 py-1.5 rounded-full text-xs font-bold bg-slate-700 text-white hover:bg-slate-600">
                                            Réessayer
                                        </button>
                                    </div>
                                    <div v-else-if="isMyTurn" class="text-sm font-bold text-teal-700">
                                        🎯 <span v-if="humanTeamIds.length > 1">{{ myTeam?.name }} — à toi de piocher !</span>
                                        <span v-else>C'est ton tour ! Choisis un joueur.</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Bouton terminer le draft (à partir de 14 joueurs, ou plus rien d'abordable) -->
                            <div v-if="isMyTurn && canFinishDraft" class="mb-4 text-center">
                                <p v-if="myRosterCount < MIN_SQUAD" class="mb-2 text-sm text-teal-700">
                                    Plus aucun joueur n'est dans ton budget : tu peux terminer ton draft avec {{ myRosterCount }} joueurs.
                                </p>
                                <button @click="finishMyDraft"
                                        :disabled="isProcessing"
                                        class="px-6 py-2.5 rounded-xl text-sm font-bold border-2 border-teal-500 text-teal-600 bg-teal-50 hover:bg-teal-500 hover:text-white transition-all disabled:opacity-50">
                                    ✅ Terminer mon draft ({{ myRosterCount }} joueurs)
                                </button>
                            </div>

                            <RecruitFilterBar v-if="isMyTurn" class="mb-4"
                                              v-model:search="searchQuery" v-model:position="filterPosition"
                                              v-model:sort="sortBy" v-model:max-price="maxPrice"
                                              :positions="positionOptions" price-label="Coût draft max. (GL)" />

                            <div v-if="isMyTurn" class="grid min-w-0 gap-4 xl:flex-1 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                                <Panel tone="white" padding="sm" class="flex min-w-0 flex-col" :title="`Joueurs disponibles · ${filteredPlayers.length}`">
                                    <div class="relative flex-1">
                                        <div class="max-h-[55vh] space-y-1 overflow-y-auto xl:absolute xl:inset-0 xl:max-h-none">
                                            <RecruitRow v-for="player in filteredPlayers" :key="player.id" :player="player"
                                                        :selected="inspectedPlayer?.id === player.id" :overall="overallOf(player)"
                                                        :price="draftCost(player)" :price-sub="`${weeklyCost(player)} / sem.`"
                                                        :affordable="canAfford(player)" @select="inspectPlayer">
                                                <template #name-suffix>
                                                    <span v-if="duoOf(player)" :title="duoTitle(player)">🤝</span>
                                                </template>
                                                <template #action>
                                                    <PlayerCompareButton v-model="comparison" :player="player" />
                                                </template>
                                            </RecruitRow>
                                            <p v-if="!filteredPlayers.length" class="p-3 text-xs text-slate-500">Aucun joueur ne correspond aux filtres.</p>
                                        </div>
                                    </div>
                                </Panel>

                                <div ref="playerDetail" class="flex min-w-0 flex-col gap-3">
                                    <template v-if="inspectedPlayer">
                                        <PlayerIdentityCard :player="inspectedPlayer" :show-captain-badge="false"
                                                            :subtitle="`${positionLabel(inspectedPlayer.position)} · Note au poste ${positionRating(inspectedPlayer)}`">
                                            <template #footer>
                                                <p v-if="inspectedPlayer.description" class="mt-2 text-xs italic text-slate-500">{{ inspectedPlayer.description }}</p>
                                            </template>
                                        </PlayerIdentityCard>
                                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-300 bg-white p-3 text-xs">
                                            <span>Moral <strong :class="moraleState(inspectedPlayer.morale).text">{{ inspectedPlayer.morale ?? 60 }}/100</strong></span>
                                            <span>Coût draft <strong class="text-teal-700">{{ draftCost(inspectedPlayer) }} <GameBanknote /></strong></span>
                                            <ButtonPrimary type="button" size="sm" :disabled="isProcessing || !canAfford(inspectedPlayer)" @click="pickPlayer(inspectedPlayer)">
                                                {{ canAfford(inspectedPlayer) ? 'Piocher' : 'Budget insuffisant' }}
                                            </ButtonPrimary>
                                        </div>
                                        <StatBars :player="inspectedPlayer" highlight-position />
                                        <PlayerSpecialMoves :moves="inspectedPlayer.special_moves ?? []" names-only show-empty class="flex-1" />
                                    </template>
                                    <PlayerComparison v-model="comparison" :cost="draftCost" cost-label="Coût draft" />
                                </div>
                            </div>

                            <!-- Pas mon tour → derniers picks -->
                            <div v-else-if="!draftCompleted"
                                 class="border border-dashed border-slate-300 rounded-xl p-6 text-slate-400">
                                <p class="text-center text-xs mb-4">Les autres équipes sont en train de piocher... Tu pourras choisir quand ce sera ton tour.</p>

                                <div v-if="pickLog.length" class="flex flex-col gap-2 max-w-md mx-auto">
                                    <div v-for="(pick, idx) in [...pickLog].reverse().slice(0, 6)" :key="idx"
                                         class="flex items-center gap-3 bg-white border border-slate-300 rounded-lg px-3 py-2">
                                        <TeamLogo :team="teams.find(t => t.id === pick.team_id) ?? { name: pick.team_name }" size="md" />
                                        <div class="flex-1 text-left">
                                            <p class="text-sm font-semibold text-slate-700">{{ pick.player_name }}</p>
                                            <p class="text-2xs text-slate-400">{{ pick.team_name }}</p>
                                        </div>
                                        <PositionBadge :position="pick.position" size="sm" />
                                    </div>
                                </div>
                                <div v-else class="text-center text-3xl">⏳</div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <Modal :show="!!rosterModalTeam" max-width="2xl" @close="rosterModalTeamId = null"
               :title="`${rosterModalTeam?.name ?? ''} · ${rosterModalPicks.length} joueur${rosterModalPicks.length > 1 ? 's' : ''} pioché${rosterModalPicks.length > 1 ? 's' : ''}`">
                <div v-if="rosterModalPicks.length" class="grid gap-3 sm:grid-cols-2">
                    <div class="max-h-[55vh] space-y-1 overflow-y-auto rounded-xl border border-slate-300 bg-slate-50 p-2">
                        <button v-for="pick in rosterModalPicks" :key="pick.player_id" type="button"
                                class="block w-full rounded-lg border px-2 py-2 text-left text-xs"
                                :class="rosterModalPick?.player_id === pick.player_id ? 'border-teal-300 bg-teal-50' : 'border-slate-300 bg-white hover:border-slate-300'"
                                :aria-pressed="rosterModalPick?.player_id === pick.player_id"
                                @click="rosterModalPlayerId = pick.player_id">
                            <span class="block font-semibold text-slate-700">{{ pick.player_name }}</span>
                            <span class="text-2xs text-slate-500">{{ positionLabel(pick.position) }} · Tour {{ pick.round }} · {{ pick.total_cost ?? pick.cost }} <GameBanknote /></span>
                        </button>
                    </div>
                    <div v-if="rosterModalPlayer" class="min-w-0 space-y-2">
                        <PlayerIdentityCard :player="rosterModalPlayer" :show-captain-badge="false"
                                            :subtitle="`${positionLabel(rosterModalPlayer.position)} · Moral ${rosterModalPlayer.morale ?? 60}/100`" />
                        <StatBars :player="rosterModalPlayer" highlight-position />
                    </div>
                    <p v-else class="text-xs text-slate-500">La fiche détaillée de ce joueur est indisponible.</p>
                </div>
                <p v-else class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Cette équipe n’a encore pioché aucun joueur.</p>
        </Modal>
    </AuthenticatedLayout>
</template>
