<script setup>
import { positionLabel } from '@/Enums/positionBadges.js';
import { fieldClass } from '@/Components/fieldStyles';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import H2 from '@/Components/H2.vue';
import { Head, router } from '@inertiajs/vue3';
import { alertDialog } from '@/Composables/useDialog.js';
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import Modal from '@/Components/Modal.vue';
import TeamStyleBadges from '@/Pages/GameSaves/Play/components/TeamStyleBadges.vue';
import PlayerPhoto from '@/Components/PlayerPhoto.vue';
import { usePixelArt } from '@/utils/pixelArt.js';
import PlayerIdentityCard from '@/Pages/GameSaves/Play/components/PlayerIdentityCard.vue';
import PlayerComparison from '@/Pages/GameSaves/Play/components/PlayerComparison.vue';
import { compareRecruits, positionRating, recruitmentSorts } from '@/Pages/GameSaves/Play/recruitment.js';
import PlayerSpecialMoves from './Play/components/PlayerSpecialMoves.vue';
import StatBars from '@/Pages/GameSaves/Play/components/StatBars.vue';
import { usePlayerUtils } from '@/Pages/GameSaves/Play/usePlayerUtils.js';
import DraftPickAnimation from '@/Pages/GameSaves/Draft/DraftPickAnimation.vue';
import DraftOrderList from '@/Pages/GameSaves/Draft/DraftOrderList.vue';
import DraftPickLog from '@/Pages/GameSaves/Draft/DraftPickLog.vue';
import { positionColor, positionGroup } from '@/Pages/GameSaves/Draft/draftPositions.js';

const { photoSrc } = usePixelArt();

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
const filterPosition = ref('ALL');
const sortBy = ref('profile');
const searchQuery = ref('');
const maxPrice = ref('');
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

const isComparing = player => comparison.value.some(p => p.id === player.id);

function toggleComparison(player) {
    comparison.value = isComparing(player)
        ? comparison.value.filter(p => p.id !== player.id)
        : [...comparison.value.slice(-1), player];
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

// Joueurs filtrés et triés
const filteredPlayers = computed(() => {
    let list = availablePlayers.value;

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(p =>
            (p.lastname?.toLowerCase().includes(q)) ||
            (p.firstname?.toLowerCase().includes(q))
        );
    }

    if (filterPosition.value !== 'ALL') {
        list = list.filter(p => positionGroup(p.position) === filterPosition.value);
    }

    if (maxPrice.value !== '') {
        list = list.filter(player => draftCost(player) <= Number(maxPrice.value));
    }

    return [...list].sort((a, b) => compareRecruits(a, b, sortBy.value, draftCost, overallOf));
});

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

const inspectedPosCounts = computed(() => {
    const counts = { GK: 0, DEF: 0, MID: 0, ATT: 0 };
    inspectedRoster.value.forEach(p => {
        const g = positionGroup(p.position);
        if (counts[g] !== undefined) counts[g]++;
    });
    return counts;
});

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

function teamLogoUrl(team) {
    const path = team?.logo_path;
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/')) return path;
    // Tous les logos (canon « teams/… », packages, uploads) sont servis par le disque public sous /storage.
    return '/storage/' + path;
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
                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-8 h-8 rounded-lg overflow-hidden bg-white border border-slate-200 shrink-0">
                                        <TeamLogoImage loading="lazy" decoding="async" v-if="teamLogoUrl(myTeam)" :src="teamLogoUrl(myTeam)" class="w-full h-full" alt=""/>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-teal-700">{{ myTeam?.name }}</div>
                                        <div class="text-2xs text-teal-500">{{ myRosterCount }}/18 joueurs • Budget draft {{ myBudget }} <GameBanknote /></div>
                                    </div>
                                </div>
                                <TeamStyleBadges :team="myTeam" size="sm" />
                            </div>

                            <!-- Effectif d'une équipe -->
                            <div class="border border-slate-200 rounded-xl bg-white p-3">
                                <div class="mb-2">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <h4 class="text-2xs font-bold text-slate-400 uppercase tracking-wider">Effectif</h4>
                                        <span class="text-2xs font-bold text-slate-400">{{ inspectedRoster.length }}</span>
                                    </div>
                                    <div class="flex gap-1">
        <span class="px-1.5 py-0.5 rounded text-3xs font-bold bg-yellow-100 text-yellow-700">
            GK {{ inspectedPosCounts.GK }}
        </span>
                                        <span class="px-1.5 py-0.5 rounded text-3xs font-bold bg-blue-100 text-blue-700">
            DEF {{ inspectedPosCounts.DEF }}
        </span>
                                        <span class="px-1.5 py-0.5 rounded text-3xs font-bold bg-green-100 text-green-700">
            MID {{ inspectedPosCounts.MID }}
        </span>
                                        <span class="px-1.5 py-0.5 rounded text-3xs font-bold bg-red-100 text-red-700">
            ATT {{ inspectedPosCounts.ATT }}
        </span>
                                    </div>
                                </div>

                                <!-- Sélecteur équipe -->
                                <select v-model="inspectedTeamId"
                                        class="w-full px-2 py-1 mb-2" :class="fieldClass({ shape: 'rect', size: 'sm', border: 'border-slate-200' })">
                                    <option v-for="t in draftOrderTeams" :key="t.id" :value="t.id">
                                        {{ t.name }} ({{ t.rosterCount }})
                                    </option>
                                </select>

                                <!-- Liste des joueurs piochés -->
                                <div class="max-h-[200px] overflow-y-auto space-y-1">
                                    <div v-for="pick in inspectedRoster" :key="pick.player_id"
                                         class="flex items-center gap-2 px-2 py-1 rounded-lg bg-slate-50 text-[11px]">
                                        <div class="w-5 h-5 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                            <PlayerPhoto :src="pick.photo_path ? `/storage/${pick.photo_path}` : null"
                                                 class="w-full h-full object-cover" alt="" />
                                            <div v-if="!pick.photo_path" class="w-full h-full flex items-center justify-center text-[8px] text-slate-400">?</div>
                                        </div>
                                        <span class="flex-1 font-semibold text-slate-700 truncate">{{ pick.player_name }}</span>
                                        <span :class="positionColor(pick.position)"
                                              class="px-1 py-0.5 rounded text-3xs font-bold">
                {{ positionGroup(pick.position) }}
            </span>
                                        <span class="text-3xs text-slate-400">{{ pick.cost }} <GameBanknote /></span>
                                    </div>
                                    <p v-if="!inspectedRoster.length" class="text-2xs text-slate-400 italic text-center py-2">
                                        Aucun joueur pioché
                                    </p>
                                </div>
                            </div>

                            <!-- Ordre de draft -->
                            <DraftOrderList :teams="draftOrderTeams" :round="draft?.round ?? 1"
                                            :is-human-team="isHumanTeam" :team-logo-url="teamLogoUrl"
                                            @inspect-team="openRosterModal" />

                            <!-- Log des derniers picks -->
                            <DraftPickLog :picks="pickLog" :is-human-team="isHumanTeam" />
                        </div>

                        <!-- ══════ COL CENTRALE + DROITE : Joueurs ══════ -->
                        <div class="col-span-1 flex min-w-0 flex-col lg:col-span-9">

                            <div class="mb-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-xl border border-slate-200 bg-white px-4 py-3">
                                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                    <h1 class="text-sm font-bold text-slate-700">
                                        Tour {{ draft?.round ?? 1 }} — Pick {{ (draft?.current_pick_index ?? 0) + 1 }} / {{ draft?.order?.length ?? 0 }}
                                    </h1>
                                    <p class="text-xs text-slate-500">{{ availablePlayers.length }} joueurs disponibles</p>
                                </div>
                                <div role="status">
                                    <div v-if="isProcessing && !isMyTurn" class="flex items-center gap-2">
                                        <div class="relative w-8 h-8 rounded-full bg-slate-50 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                                            <TeamLogoImage loading="lazy" decoding="async" v-if="teamLogoUrl(currentTeam)" :src="teamLogoUrl(currentTeam)" class="w-full h-full" alt=""/>
                                            <span v-else class="text-xs text-slate-400">{{ (currentTeam?.name || '?').charAt(0) }}</span>
                                            <div class="absolute inset-0 rounded-full border-2 border-slate-200 border-t-slate-600 animate-spin"></div>
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

                            <div v-if="isMyTurn" class="mb-4 flex flex-wrap items-end gap-2">
                                <label class="min-w-[160px] flex-1 text-xs text-slate-500">
                                    Rechercher un joueur
                                    <input v-model="searchQuery" type="search" class="mt-1 w-full" :class="fieldClass({ size: 'sm', border: 'border-slate-200' })" placeholder="Nom ou prénom" />
                                </label>
                                <label class="text-xs text-slate-500">
                                    Poste
                                    <select v-model="filterPosition" class="mt-1 block" :class="fieldClass({ size: 'sm', border: 'border-slate-200' })">
                                        <option value="ALL">Tous</option><option value="GK">Gardiens</option>
                                        <option value="DEF">Défenseurs</option><option value="MID">Milieux</option><option value="ATT">Attaquants</option>
                                    </select>
                                </label>
                                <label class="text-xs text-slate-500">
                                    Trier par
                                    <select v-model="sortBy" class="mt-1 block" :class="fieldClass({ size: 'sm', border: 'border-slate-200' })">
                                        <option v-for="sort in recruitmentSorts" :key="sort.key" :value="sort.key">{{ sort.label }}</option>
                                    </select>
                                </label>
                                <label class="text-xs text-slate-500">
                                    Prix max. (GL)
                                    <input v-model.number="maxPrice" type="number" min="0" class="mt-1 block w-24" :class="fieldClass({ size: 'sm', border: 'border-slate-200' })" placeholder="Tous" />
                                </label>
                            </div>

                            <div v-if="isMyTurn" class="grid min-w-0 gap-4 xl:flex-1 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                                <div class="flex min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-3">
                                    <h3 class="mb-2 text-xs font-bold text-slate-600">Joueurs disponibles · {{ filteredPlayers.length }}</h3>
                                    <div class="relative flex-1">
                                        <div class="max-h-[55vh] space-y-1 overflow-y-auto xl:absolute xl:inset-0 xl:max-h-none">
                                            <div v-for="player in filteredPlayers" :key="player.id"
                                                 class="flex items-center gap-2 rounded-lg border pr-2 text-xs"
                                                 :class="inspectedPlayer?.id === player.id ? 'border-teal-300 bg-teal-50' : 'border-slate-200 hover:bg-slate-50'">
                                                <button type="button" class="flex min-w-0 flex-1 items-center gap-2 py-2 pl-2 text-left"
                                                        :aria-pressed="inspectedPlayer?.id === player.id" @click="inspectPlayer(player)">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-slate-400 shadow-sm">
                                                        <PlayerPhoto v-if="player.photo_path" :src="photoSrc(`/storage/${player.photo_path}`)"
                                                                     class="h-full w-full object-cover" alt="" />
                                                        <svg v-else aria-hidden="true" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                                            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5Z" />
                                                        </svg>
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block truncate font-bold text-slate-700">
                                                            {{ player.firstname }} {{ player.lastname }}
                                                            <span v-if="duoOf(player)" :title="duoTitle(player)">🤝</span>
                                                        </span>
                                                        <span class="block text-2xs text-slate-500">{{ positionLabel(player.position) }} · Note {{ overallOf(player) }} · End. {{ player.stamina ?? '—' }} · Moral {{ player.morale ?? 60 }}</span>
                                                    </span>
                                                    <span class="shrink-0 text-right" :class="canAfford(player) ? 'text-teal-700' : 'text-rose-600'">
                                                        <strong>{{ draftCost(player) }} <GameBanknote /></strong>
                                                        <span class="block text-2xs text-slate-500">{{ weeklyCost(player) }} / sem.</span>
                                                    </span>
                                                </button>
                                                <button type="button"
                                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border bg-white hover:border-teal-500 focus-visible:ring-2 focus-visible:ring-teal-500"
                                                        :class="isComparing(player) ? 'border-teal-500 text-teal-700 ring-1 ring-teal-500' : 'border-slate-200 text-slate-500'"
                                                        :aria-pressed="isComparing(player)"
                                                        :aria-label="`${isComparing(player) ? 'Retirer du comparatif' : 'Ajouter au comparatif'} : ${player.firstname} ${player.lastname}`"
                                                        :title="isComparing(player) ? 'Retirer du comparatif' : 'Ajouter au comparatif'"
                                                        @click="toggleComparison(player)">
                                                    <span aria-hidden="true">⚖️</span>
                                                </button>
                                            </div>
                                            <p v-if="!filteredPlayers.length" class="p-3 text-xs text-slate-500">Aucun joueur ne correspond aux filtres.</p>
                                        </div>
                                    </div>
                                </div>

                                <div ref="playerDetail" class="flex min-w-0 flex-col gap-3">
                                    <template v-if="inspectedPlayer">
                                        <PlayerIdentityCard :player="inspectedPlayer" :show-captain-badge="false"
                                                            :subtitle="`${positionLabel(inspectedPlayer.position)} · Note au poste ${positionRating(inspectedPlayer)}`">
                                            <template #footer>
                                                <p v-if="inspectedPlayer.description" class="mt-2 text-xs italic text-slate-500">{{ inspectedPlayer.description }}</p>
                                            </template>
                                        </PlayerIdentityCard>
                                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white p-3 text-xs">
                                            <span>Moral <strong :class="moraleState(inspectedPlayer.morale).text">{{ inspectedPlayer.morale ?? 60 }}/100</strong></span>
                                            <span>Coût draft <strong class="text-teal-700">{{ draftCost(inspectedPlayer) }} <GameBanknote /></strong></span>
                                            <ButtonPrimary type="button" size="sm" :disabled="isProcessing || !canAfford(inspectedPlayer)" @click="pickPlayer(inspectedPlayer)">
                                                {{ canAfford(inspectedPlayer) ? 'Piocher' : 'Budget insuffisant' }}
                                            </ButtonPrimary>
                                        </div>
                                        <StatBars :player="inspectedPlayer" highlight-position />
                                        <PlayerSpecialMoves :moves="inspectedPlayer.special_moves ?? []" names-only show-empty class="flex-1" />
                                    </template>
                                    <PlayerComparison v-model="comparison" :cost="draftCost" cost-label="Coût draft" open-on-second />
                                </div>
                            </div>

                            <!-- Pas mon tour → derniers picks -->
                            <div v-else-if="!draftCompleted"
                                 class="border border-dashed border-slate-200 rounded-xl p-6 text-slate-400">
                                <p class="text-center text-xs mb-4">Les autres équipes sont en train de piocher... Tu pourras choisir quand ce sera ton tour.</p>

                                <div v-if="pickLog.length" class="flex flex-col gap-2 max-w-md mx-auto">
                                    <div v-for="(pick, idx) in [...pickLog].reverse().slice(0, 6)" :key="idx"
                                         class="flex items-center gap-3 bg-white border border-slate-200 rounded-lg px-3 py-2">
                                        <div class="h-8 w-8 rounded-full bg-slate-50 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                                            <TeamLogoImage loading="lazy" decoding="async" v-if="teamLogoUrl(teams.find(t => t.id === pick.team_id))" :src="teamLogoUrl(teams.find(t => t.id === pick.team_id))" class="w-full h-full" alt=""/>
                                            <span v-else class="text-xs text-slate-400">{{ (pick.team_name || '?').charAt(0) }}</span>
                                        </div>
                                        <div class="flex-1 text-left">
                                            <p class="text-sm font-semibold text-slate-700">{{ pick.player_name }}</p>
                                            <p class="text-2xs text-slate-400">{{ pick.team_name }}</p>
                                        </div>
                                        <span class="text-2xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                            {{ positionGroup(pick.position) }}
                                        </span>
                                    </div>
                                </div>
                                <div v-else class="text-center text-3xl">⏳</div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <Modal :show="!!rosterModalTeam" max-width="2xl" @close="rosterModalTeamId = null">
            <div class="space-y-4 p-4 sm:p-5">
                <header class="flex items-center justify-between gap-2">
                    <h3 class="text-sm font-bold text-slate-700">
                        {{ rosterModalTeam?.name }} · {{ rosterModalPicks.length }} joueur{{ rosterModalPicks.length > 1 ? 's' : '' }} pioché{{ rosterModalPicks.length > 1 ? 's' : '' }}
                    </h3>
                    <button type="button" class="rounded-full px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                            @click="rosterModalTeamId = null">Fermer</button>
                </header>
                <div v-if="rosterModalPicks.length" class="grid gap-3 sm:grid-cols-2">
                    <div class="max-h-[55vh] space-y-1 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2">
                        <button v-for="pick in rosterModalPicks" :key="pick.player_id" type="button"
                                class="block w-full rounded-lg border px-2 py-2 text-left text-xs"
                                :class="rosterModalPick?.player_id === pick.player_id ? 'border-teal-300 bg-teal-50' : 'border-slate-200 bg-white hover:border-slate-200'"
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
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
