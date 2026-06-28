<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick } from 'vue';
import TeamStyleBadges from '@/Pages/GameSaves/Play/components/TeamStyleBadges.vue';
import Modal from '@/Components/Modal.vue';
import PlayerIdentityCard from '@/Pages/GameSaves/Play/components/PlayerIdentityCard.vue';
import RadarChart from '@/Pages/GameSaves/Play/components/RadarChart.vue';
import StatBars from '@/Pages/GameSaves/Play/components/StatBars.vue';
import { usePlayerUtils } from '@/Pages/GameSaves/Play/usePlayerUtils.js';

const { keyStatsFor } = usePlayerUtils();

// Classe d'une cellule de stat : surlignée si c'est une stat clé du poste du
// joueur, sinon en gris-noir comme les stats secondaires.
function statClass(player, key) {
    return keyStatsFor(player.position).includes(key)
        ? 'text-amber-600 font-black'
        : 'text-slate-600 font-semibold';
}

const props = defineProps({
    gameSave:          { type: Object, required: true },
    teams:             { type: Array,  required: true },
    freePlayers:       { type: Array,  required: true },
    draftState:        { type: Object, default: null },
    controlledTeamId:  { type: Number, default: null },
    controlledTeamIds: { type: Array,  default: () => [] },
});

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
const sortBy         = ref('overall');
const searchQuery    = ref('');
const inspectedTeamId = ref(props.controlledTeamId);

// Rosters locaux par équipe (reconstruits depuis les picks)
const teamRosters = ref({});

// Init rosters depuis les contrats existants
function initRosters() {
    const rosters = {};
    props.teams.forEach(t => {
        rosters[t.id] = {
            count:  t.contracts?.length ?? 0,
            budget: t.budget ?? 0,
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

const myBudget = computed(() =>
    teamRosters.value[activeHumanTeamId.value]?.budget ?? 0
);

const seasonLength = computed(() => {
    const count = props.teams.length;
    return count % 2 === 1 ? count * 2 : (count - 1) * 2;
});

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

    list = [...list].sort((a, b) => {
        if (sortBy.value === 'overall') return overallOf(b) - overallOf(a);
        if (sortBy.value === 'cost')    return (b.cost ?? 0) - (a.cost ?? 0);
        if (sortBy.value === 'name')    return (a.lastname ?? '').localeCompare(b.lastname ?? '');
        return 0;
    });

    return list;
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
function overallOf(p) {
    if (!p) return 0;
    const keys = ['speed','stamina','attack','defense','shot','pass','dribble','block','intercept','tackle'];
    const vals = keys.map(k => Number(p[k] ?? 0)).filter(v => v > 0);
    return vals.length ? Math.round(vals.reduce((a, b) => a + b, 0) / vals.length) : 0;
}

function positionGroup(pos) {
    const p = (pos ?? '').toUpperCase();
    if (p.includes('GK') || p.includes('GOAL')) return 'GK';
    if (p.includes('DEF') || p.includes('BACK')) return 'DEF';
    if (p.includes('MDF') || p.includes('MID') || p.includes('MOF')) return 'MID';
    if (p.includes('ATT') || p.includes('FOR')) return 'ATT';
    return 'MID';
}

function positionColor(pos) {
    const g = positionGroup(pos);
    return {
        GK:  'bg-yellow-100 text-yellow-700',
        DEF: 'bg-blue-100 text-blue-700',
        MID: 'bg-green-100 text-green-700',
        ATT: 'bg-red-100 text-red-700',
    }[g] ?? 'bg-slate-100 text-slate-700';
}

// ── Modal détail joueur ──────────────────────────────
const inspectedPlayer = ref(null);

function openPlayerModal(player) {
    inspectedPlayer.value = player;
}

function closePlayerModal() {
    inspectedPlayer.value = null;
}

async function pickFromModal(player) {
    closePlayerModal();
    await pickPlayer(player);
}

const compareList = ref([]);

function isComparing(player) {
    return compareList.value.some(p => p.id === player.id);
}

function toggleCompare(player) {
    const idx = compareList.value.findIndex(p => p.id === player.id);
    if (idx !== -1) {
        compareList.value.splice(idx, 1);
        return;
    }
    if (compareList.value.length >= 2) compareList.value.shift();
    compareList.value.push(player);
}

function clearCompare() {
    compareList.value = [];
}

const compareStatRows = [
    { key: 'overall', label: 'OVR', overall: true },
    { key: 'speed', label: 'Vitesse' },
    { key: 'stamina', label: 'Endurance' },
    { key: 'shot', label: 'Tir' },
    { key: 'pass', label: 'Passe' },
    { key: 'dribble', label: 'Dribble' },
    { key: 'attack', label: 'Attaque' },
    { key: 'defense', label: 'Défense' },
    { key: 'tackle', label: 'Tacle' },
    { key: 'intercept', label: 'Interception' },
    { key: 'block', label: 'Blocage' },
    { key: 'heading', label: 'Heading' },
    { key: 'hand_save', label: 'Main' },
    { key: 'punch_save', label: 'Poing' },
];

function compareStatValue(player, key) {
    if (key === 'overall') return overallOf(player);
    return Number(player?.[key] ?? 0);
}

function canAfford(player) {
    return Math.floor((player.cost ?? 0) * seasonLength.value * 0.5) <= myBudget.value;
}

function teamLogoUrl(team) {
    const path = team?.logo_path;
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/')) return path;
    if (path.startsWith('teams/')) return '/images/' + path;
    return '/' + path;
}

async function finishMyDraft() {
    if (myRosterCount.value < 14) return;

    isProcessing.value = true;
    try {
        const response = await axios.post(`/game-saves/${props.gameSave.id}/draft/finish`);
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
    await new Promise(r => setTimeout(r, 1800));
    showPickAnim.value = false;
}

// ══════════════════════════════════════
//  AI PICK FLOW
// ══════════════════════════════════════
async function runAiPicks() {
    while (!isMyTurn.value && !draftCompleted.value) {
        isProcessing.value = true;

        // Pause de suspense
        await new Promise(r => setTimeout(r, 800));

        try {
            const response = await axios.post(`/game-saves/${props.gameSave.id}/draft/ai-pick`);
            const data = response.data;

            if (data.completed) {
                draftCompleted.value = true;
                break;
            }

            if (data.completed) {
                draftCompleted.value = true;
                break;
            }

            if (data.finished) {
                // L'IA a terminé son draft
                if (data.draftState) draft.value = data.draftState;
                continue;
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

            if (data.draftState) {
                draft.value = data.draftState;
            }
        } catch (err) {
            console.error('AI pick error:', err);
            break;
        }

        isProcessing.value = false;
    }

    isProcessing.value = false;
}

// ══════════════════════════════════════
//  HUMAN PICK
// ══════════════════════════════════════
async function pickPlayer(player) {
    if (!isMyTurn.value || isProcessing.value) return;
    if (!canAfford(player)) return;

    isProcessing.value = true;

    try {
        const response = await axios.post(`/game-saves/${props.gameSave.id}/draft/pick`, {
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
        alert('Impossible de sélectionner ce joueur. Vérifie ton budget.');
    }

    isProcessing.value = false;
}

// ══════════════════════════════════════
//  FIN DU DRAFT
// ══════════════════════════════════════
function goToPlay() {
    router.visit(`/game-saves/${props.gameSave.id}/play`);
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
        <template #header></template>

        <div class="min-h-screen bg-slate-50 p-4">
            <div class="max-w-7xl mx-auto">

                <!-- ══════ HEADER ══════ -->
                <div class="text-center mb-6">
                    <div class="text-xs font-bold text-amber-500 uppercase tracking-widest mb-1">
                        🎯 Draft — Saison {{ gameSave.season }}
                    </div>
                    <h1 class="text-2xl font-black text-slate-800">
                        Tour {{ draft?.round ?? 1 }} — Pick {{ (draft?.current_pick_index ?? 0) + 1 }} / {{ draft?.order?.length ?? 0 }}
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">
                        {{ availablePlayers.length }} joueurs disponibles
                    </p>
                </div>

                <!-- ══════ PICK ANIMATION OVERLAY ══════ -->
                <Teleport to="body">
                    <Transition name="fade">
                        <div v-if="showPickAnim && lastPick"
                             class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 pointer-events-none">
                            <div class="bg-white rounded-2xl shadow-2xl p-8 text-center max-w-sm animate-bounce-in">
                                <div class="w-20 h-20 rounded-full overflow-hidden bg-slate-200 mx-auto mb-4 border-4"
                                     :class="isHumanTeam(lastPick.team_id) ? 'border-amber-400' : 'border-slate-300'">
                                    <img v-if="lastPick.photo_path" :src="`/storage/${lastPick.photo_path}`"
                                         class="w-full h-full object-cover" alt=""/>
                                    <div v-else class="w-full h-full flex items-center justify-center text-2xl text-slate-400">👤</div>
                                </div>
                                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                                    {{ lastPick.team_name }}
                                </div>
                                <div class="text-xl font-black text-slate-800">
                                    {{ lastPick.player_name }}
                                </div>
                                <div class="mt-1 text-sm text-slate-500">
                                    {{ lastPick.position }} • {{ lastPick.cost }} €
                                </div>
                            </div>
                        </div>
                    </Transition>
                </Teleport>

                <!-- ══════ DRAFT TERMINÉ ══════ -->
                <div v-if="draftCompleted" class="text-center py-16">
                    <div class="text-5xl mb-4">🏆</div>
                    <h2 class="text-2xl font-black text-slate-800 mb-2">Draft terminé !</h2>
                    <p class="text-sm text-slate-500 mb-2">
                        {{ pickLog.length }} joueurs ont été sélectionnés.
                        Ton équipe compte {{ myRosterCount }} joueurs.
                    </p>
                    <button @click="goToPlay"
                            class="mt-6 px-8 py-3 bg-teal-500 hover:bg-teal-400 text-white font-bold rounded-xl text-sm transition-all shadow-lg shadow-teal-200">
                        ⚽ Commencer la saison
                    </button>
                </div>

                <!-- ══════ DRAFT EN COURS ══════ -->
                <template v-else>
                    <div class="grid grid-cols-12 gap-4">

                        <!-- ══════ COL GAUCHE : Ordre + Log ══════ -->
                        <div class="col-span-3 flex flex-col gap-4">

                            <!-- Mon équipe -->
                            <div class="border border-amber-200 rounded-xl bg-amber-50 p-3">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-8 h-8 rounded-lg overflow-hidden bg-white border border-slate-200 shrink-0">
                                        <img v-if="teamLogoUrl(myTeam)" :src="teamLogoUrl(myTeam)" class="w-full h-full object-contain" alt=""/>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-amber-700">{{ myTeam?.name }}</div>
                                        <div class="text-[10px] text-amber-500">{{ myRosterCount }}/18 joueurs • {{ myBudget }} €</div>
                                    </div>
                                </div>
                                <TeamStyleBadges :team="myTeam" size="sm" />
                            </div>

                            <!-- Effectif d'une équipe -->
                            <div class="border border-slate-200 rounded-xl bg-white p-3">
                                <div class="mb-2">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Effectif</h4>
                                        <span class="text-[10px] font-bold text-slate-400">{{ inspectedRoster.length }}</span>
                                    </div>
                                    <div class="flex gap-1">
        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-yellow-100 text-yellow-700">
            GK {{ inspectedPosCounts.GK }}
        </span>
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-700">
            DEF {{ inspectedPosCounts.DEF }}
        </span>
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-green-100 text-green-700">
            MID {{ inspectedPosCounts.MID }}
        </span>
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-100 text-red-700">
            ATT {{ inspectedPosCounts.ATT }}
        </span>
                                    </div>
                                </div>

                                <!-- Sélecteur équipe -->
                                <select v-model="inspectedTeamId"
                                        class="w-full border border-slate-200 rounded-lg px-2 py-1 text-[11px] font-semibold text-slate-700 bg-slate-50 mb-2 focus:ring-2 focus:ring-amber-300 focus:outline-none">
                                    <option v-for="t in draftOrderTeams" :key="t.id" :value="t.id">
                                        {{ t.name }} ({{ t.rosterCount }})
                                    </option>
                                </select>

                                <!-- Liste des joueurs piochés -->
                                <div class="max-h-[200px] overflow-y-auto space-y-1">
                                    <div v-for="pick in inspectedRoster" :key="pick.player_id"
                                         class="flex items-center gap-2 px-2 py-1 rounded-lg bg-slate-50 text-[11px]">
                                        <div class="w-5 h-5 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                            <img v-if="pick.photo_path" :src="`/storage/${pick.photo_path}`"
                                                 class="w-full h-full object-cover" alt=""/>
                                            <div v-else class="w-full h-full flex items-center justify-center text-[8px] text-slate-400">?</div>
                                        </div>
                                        <span class="flex-1 font-semibold text-slate-700 truncate">{{ pick.player_name }}</span>
                                        <span :class="positionColor(pick.position)"
                                              class="px-1 py-0.5 rounded text-[9px] font-bold">
                {{ positionGroup(pick.position) }}
            </span>
                                        <span class="text-[9px] text-slate-400">{{ pick.cost }}€</span>
                                    </div>
                                    <p v-if="!inspectedRoster.length" class="text-[10px] text-slate-400 italic text-center py-2">
                                        Aucun joueur pioché
                                    </p>
                                </div>
                            </div>

                            <!-- Ordre de draft -->
                            <div class="border border-slate-200 rounded-xl bg-white p-3 flex-1 max-h-[300px] overflow-y-auto">
                                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                                    Ordre — Tour {{ draft?.round ?? 1 }}
                                </h4>
                                <div class="space-y-1">
                                    <div v-for="(t, idx) in draftOrderTeams" :key="t.id"
                                         class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs transition-all"
                                         :class="t.isCurrent
                                             ? 'bg-amber-100 border border-amber-300 font-bold'
                                             : 'bg-slate-50'">
                                        <div class="w-5 h-5 rounded overflow-hidden bg-white shrink-0">
                                            <img v-if="teamLogoUrl(t)" :src="teamLogoUrl(t)" class="w-full h-full object-contain" alt=""/>
                                        </div>
                                        <span class="flex-1 truncate"
                                              :class="isHumanTeam(t.id) ? 'text-amber-700 font-semibold' : 'text-slate-600'">
                                            {{ t.name }}
                                        </span>
                                        <span class="text-[10px] text-slate-400">{{ t.rosterCount }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Log des derniers picks -->
                            <div class="border border-slate-200 rounded-xl bg-white p-3 max-h-[250px] overflow-y-auto">
                                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                                    Derniers picks
                                </h4>
                                <div v-if="pickLog.length" class="space-y-1">
                                    <div v-for="(pick, i) in [...pickLog].reverse().slice(0, 20)" :key="i"
                                         class="flex items-center gap-2 text-[11px] px-2 py-1 rounded"
                                         :class="isHumanTeam(pick.team_id) ? 'bg-amber-50' : 'bg-slate-50'">
                                        <span class="font-bold text-slate-500 w-14 truncate">{{ pick.team_name }}</span>
                                        <span class="flex-1 text-slate-700 truncate">{{ pick.player_name }}</span>
                                        <span :class="positionColor(pick.position)"
                                              class="px-1.5 py-0.5 rounded text-[9px] font-bold">
                                            {{ positionGroup(pick.position) }}
                                        </span>
                                    </div>
                                </div>
                                <p v-else class="text-[10px] text-slate-400 italic">Aucun pick encore</p>
                            </div>
                        </div>

                        <!-- ══════ COL CENTRALE + DROITE : Joueurs ══════ -->
                        <div class="col-span-9">

                            <!-- Qui pioche ? -->
                            <div class="border rounded-xl p-4 mb-4 text-center"
                                 :class="isMyTurn
                                     ? 'border-amber-300 bg-amber-50'
                                     : 'border-slate-200 bg-white'">
                                <div v-if="isProcessing && !isMyTurn" class="flex items-center justify-center gap-3">
                                    <div class="relative w-8 h-8 rounded-full bg-slate-50 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                                        <img v-if="teamLogoUrl(currentTeam)" :src="teamLogoUrl(currentTeam)" class="w-full h-full object-contain" alt=""/>
                                        <span v-else class="text-xs text-slate-400">{{ (currentTeam?.name || '?').charAt(0) }}</span>
                                        <div class="absolute inset-0 rounded-full border-2 border-slate-300 border-t-slate-600 animate-spin"></div>
                                    </div>
                                    <span class="text-sm font-bold text-slate-600">
                                        {{ currentTeam?.name ?? '—' }} est en train de choisir...
                                    </span>
                                </div>
                                <div v-else-if="isMyTurn" class="text-lg font-black text-amber-700">
                                    🎯 <span v-if="humanTeamIds.length > 1">{{ myTeam?.name }} — à toi de piocher !</span>
                                    <span v-else>C'est ton tour ! Choisis un joueur.</span>
                                </div>
                            </div>
                            <!-- Bouton terminer le draft (à partir de 14 joueurs) -->
                            <div v-if="isMyTurn && myRosterCount >= 14" class="mb-4 text-center">
                                <button @click="finishMyDraft"
                                        :disabled="isProcessing"
                                        class="px-6 py-2.5 rounded-xl text-sm font-bold border-2 border-teal-500 text-teal-600 bg-teal-50 hover:bg-teal-500 hover:text-white transition-all disabled:opacity-50">
                                    ✅ Terminer mon draft ({{ myRosterCount }} joueurs)
                                </button>
                            </div>

                            <!-- Filtres -->
                            <div v-if="isMyTurn" class="flex items-center gap-3 mb-4">
                                <!-- Recherche -->
                                <div class="relative flex-1 ">
                                    <input type="search" v-model="searchQuery"
                                           class="w-full pl-14 pr-3 py-2 text-xs border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-amber-300 focus:outline-none"
                                           placeholder="Rechercher un joueur..."/>
                                </div>

                                <!-- Position -->
                                <div class="flex rounded-lg overflow-hidden border border-slate-200 text-[11px] font-semibold">
                                    <button v-for="pos in ['ALL','GK','DEF','MID','ATT']" :key="pos"
                                            @click="filterPosition = pos"
                                            class="px-3 py-1.5 transition-all"
                                            :class="filterPosition === pos
                                                ? 'bg-amber-500 text-white'
                                                : 'bg-white text-slate-500 hover:bg-slate-50'">
                                        {{ pos }}
                                    </button>
                                </div>

                                <!-- Tri -->
                                <select v-model="sortBy"
                                        class="border border-slate-200 rounded-lg px-2 py-1.5 text-xs bg-white text-slate-600">
                                    <option value="overall">Meilleur overall</option>
                                    <option value="cost">Plus cher</option>
                                    <option value="name">Alphabétique</option>
                                </select>
                            </div>

                            <!-- Grille joueurs -->
                            <!-- Liste joueurs compacte -->
                            <div v-if="isMyTurn" class="border border-slate-200 rounded-xl bg-white overflow-hidden">
                                <div class="max-h-[60vh] overflow-y-auto">
                                    <table class="w-full text-xs">
                                        <thead class="sticky top-0 bg-slate-50 border-b border-slate-200 z-10">
                                        <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                            <th class="py-2 pl-3 pr-1 w-8 text-center" title="Comparer">⚖️</th>
                                            <th class="text-left py-2 pl-1 pr-2">Joueur</th>
                                            <th class="text-center py-2 px-1 w-12">Poste</th>
                                            <th class="text-center py-2 px-1 w-10">OVR</th>
                                            <th class="text-center py-2 px-1 w-10">Vit</th>
                                            <th class="text-center py-2 px-1 w-10">End</th>
                                            <th class="text-center py-2 px-1 w-10">Tir</th>
                                            <th class="text-center py-2 px-1 w-10">Pass</th>
                                            <th class="text-center py-2 px-1 w-10">Drib</th>
                                            <th class="text-center py-2 px-1 w-10">Att</th>
                                            <th class="text-center py-2 px-1 w-10">Déf</th>
                                            <th class="text-center py-2 px-1 w-10">Tac</th>
                                            <th class="text-center py-2 px-1 w-10">Int</th>
                                            <th class="text-center py-2 px-1 w-10">Blk</th>
                                            <th class="text-center py-2 px-1 w-10">Hdg</th>
                                            <th class="text-center py-2 px-1 w-10">Main</th>
                                            <th class="text-center py-2 px-1 w-10">Poing</th>
                                            <th class="text-right py-2 px-2 w-16">Coût</th>
                                            <th class="py-2 px-2 w-20"></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr v-for="player in filteredPlayers" :key="player.id"
                                            class="border-b border-slate-50 transition-colors"
                                            :class="canAfford(player)
                        ? 'hover:bg-amber-50 cursor-pointer'
                        : 'opacity-40'">

                                            <!-- Comparer -->
                                            <td class="py-1.5 pl-3 pr-1 text-center" @click.stop>
                                                <input type="checkbox"
                                                       :checked="isComparing(player)"
                                                       @change="toggleCompare(player)"
                                                       class="w-3.5 h-3.5 rounded border-slate-300 text-amber-500 focus:ring-amber-400 cursor-pointer"/>
                                            </td>

                                            <!-- Joueur -->
                                            <td class="py-1.5 pl-1 pr-2">
                                                <button type="button" @click.stop="openPlayerModal(player)"
                                                        class="flex items-center gap-2 text-left group/name"
                                                        title="Voir la fiche du joueur">
                                                    <div class="w-7 h-7 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                                        <img v-if="player.photo_path" :src="`/storage/${player.photo_path}`"
                                                             class="w-full h-full object-cover" alt=""/>
                                                        <div v-else class="w-full h-full flex items-center justify-center text-[9px] text-slate-400">?</div>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="font-semibold text-slate-800 truncate max-w-[120px] group-hover/name:text-amber-600 group-hover/name:underline">{{ player.lastname }}</div>
                                                        <div class="text-[9px] text-slate-400 truncate max-w-[120px]">{{ player.firstname }}</div>
                                                    </div>
                                                </button>
                                            </td>

                                            <!-- Poste -->
                                            <td class="py-1.5 px-1 text-center">
                        <span :class="positionColor(player.position)"
                              class="px-1.5 py-0.5 rounded text-[9px] font-bold">
                            {{ positionGroup(player.position) }}
                        </span>
                                            </td>

                                            <!-- OVR -->
                                            <td class="py-1.5 px-1 text-center">
                        <span class="font-black text-[11px]"
                              :class="overallOf(player) >= 70 ? 'text-emerald-600'
                                  : overallOf(player) >= 50 ? 'text-amber-600'
                                  : 'text-slate-500'">
                            {{ overallOf(player) }}
                        </span>
                                            </td>

                                            <!-- Stats — les stats clés du poste sont surlignées -->
                                            <td class="py-1.5 px-1 text-center" :class="statClass(player, 'speed')">{{ player.speed ?? 0 }}</td>
                                            <td class="py-1.5 px-1 text-center" :class="statClass(player, 'stamina')">{{ player.stamina ?? 0 }}</td>
                                            <td class="py-1.5 px-1 text-center" :class="statClass(player, 'shot')">{{ player.shot ?? 0 }}</td>
                                            <td class="py-1.5 px-1 text-center" :class="statClass(player, 'pass')">{{ player.pass ?? 0 }}</td>
                                            <td class="py-1.5 px-1 text-center" :class="statClass(player, 'dribble')">{{ player.dribble ?? 0 }}</td>
                                            <td class="py-1.5 px-1 text-center" :class="statClass(player, 'attack')">{{ player.attack ?? 0 }}</td>
                                            <td class="py-1.5 px-1 text-center" :class="statClass(player, 'defense')">{{ player.defense ?? 0 }}</td>
                                            <td class="py-1.5 px-1 text-center" :class="statClass(player, 'tackle')">{{ player.tackle ?? 0 }}</td>
                                            <td class="py-1.5 px-1 text-center" :class="statClass(player, 'intercept')">{{ player.intercept ?? 0 }}</td>
                                            <td class="py-1.5 px-1 text-center" :class="statClass(player, 'block')">{{ player.block ?? 0 }}</td>
                                            <td class="py-1.5 px-1 text-center" :class="statClass(player, 'heading')">{{ player.heading ?? 0 }}</td>
                                            <td class="py-1.5 px-1 text-center" :class="statClass(player, 'hand_save')">{{ player.hand_save ?? 0 }}</td>
                                            <td class="py-1.5 px-1 text-center" :class="statClass(player, 'punch_save')">{{ player.punch_save ?? 0 }}</td>

                                            <!-- Coût -->
                                            <td class="py-1.5 px-2 text-right">
                                                <span class="font-semibold text-amber-600">{{ Math.floor((player.cost ?? 0) * seasonLength * 0.5) }} €</span>
                                                <span class="text-[9px] text-slate-400 ml-1">{{ player.cost ?? 0 }} €/sem</span>
                                            </td>

                                            <!-- Bouton -->
                                            <td class="py-1.5 px-2">
                                                <button v-if="canAfford(player)"
                                                        @click.stop="pickPlayer(player)"
                                                        :disabled="isProcessing"
                                                        class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-500 text-white hover:bg-amber-400 transition-all active:scale-95 disabled:opacity-50">
                                                    Piocher
                                                </button>
                                                <span v-else class="text-[9px] text-rose-400 font-semibold">Trop cher</span>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
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
                                            <img v-if="teamLogoUrl(teams.find(t => t.id === pick.team_id))" :src="teamLogoUrl(teams.find(t => t.id === pick.team_id))" class="w-full h-full object-contain" alt=""/>
                                            <span v-else class="text-xs text-slate-400">{{ (pick.team_name || '?').charAt(0) }}</span>
                                        </div>
                                        <div class="flex-1 text-left">
                                            <p class="text-sm font-semibold text-slate-700">{{ pick.player_name }}</p>
                                            <p class="text-[10px] text-slate-400">{{ pick.team_name }}</p>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
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

        <!-- Modal fiche joueur -->
        <Modal :show="!!inspectedPlayer" maxWidth="2xl" @close="closePlayerModal">
            <div v-if="inspectedPlayer" class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-700">Fiche joueur</h3>
                    <button @click="closePlayerModal" class="text-slate-400 hover:text-slate-600 text-lg leading-none">✕</button>
                </div>

                <div class="flex flex-col gap-3">
                    <!-- Identité -->
                    <PlayerIdentityCard :player="inspectedPlayer"
                                        :showCaptainBadge="false"
                                        :subtitle="`${inspectedPlayer.position} • ${Math.floor((inspectedPlayer.cost ?? 0) * seasonLength * 0.5)} € (${inspectedPlayer.cost ?? 0} €/sem)`" />

                    <!-- Radar + Barres -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <RadarChart :player="inspectedPlayer" accent="teal" />
                        <StatBars :player="inspectedPlayer" />
                    </div>

                    <p v-if="inspectedPlayer.description" class="text-xs text-slate-400 italic">
                        {{ inspectedPlayer.description }}
                    </p>

                    <!-- Action : piocher -->
                    <div v-if="isMyTurn" class="flex justify-end pt-1">
                        <button v-if="canAfford(inspectedPlayer)"
                                @click="pickFromModal(inspectedPlayer)"
                                :disabled="isProcessing"
                                class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-500 text-white hover:bg-amber-400 transition-all active:scale-95 disabled:opacity-50">
                            🎯 Piocher ce joueur
                        </button>
                        <span v-else class="text-xs text-rose-400 font-semibold self-center">Budget insuffisant</span>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Panneau de comparaison -->
        <div v-if="compareList.length > 0"
             class="fixed bottom-4 inset-x-0 z-50 flex justify-center px-4">
            <div class="bg-white border border-slate-200 shadow-2xl rounded-2xl p-4 w-full max-w-2xl">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-slate-700">Comparaison de joueurs</h3>
                    <button @click="clearCompare" class="text-xs text-slate-400 hover:text-slate-600 font-semibold">
                        ✕ Fermer
                    </button>
                </div>

                <div v-if="compareList.length < 2" class="text-xs text-slate-400 text-center py-4">
                    Sélectionne un second joueur (⚖️) pour comparer.
                </div>

                <table v-else class="w-full text-xs">
                    <thead>
                    <tr class="text-slate-500">
                        <th class="text-left py-1 px-2 font-semibold">Stat</th>
                        <th class="text-center py-1 px-2 font-bold">{{ compareList[0].lastname }}</th>
                        <th class="text-center py-1 px-2 font-bold">{{ compareList[1].lastname }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="row in compareStatRows" :key="row.key" class="border-t border-slate-50">
                        <td class="py-1 px-2 text-slate-500">{{ row.label }}</td>
                        <td class="py-1 px-2 text-center font-semibold"
                            :class="compareStatValue(compareList[0], row.key) > compareStatValue(compareList[1], row.key)
                                ? 'text-emerald-600' : 'text-slate-600'">
                            {{ compareStatValue(compareList[0], row.key) }}
                        </td>
                        <td class="py-1 px-2 text-center font-semibold"
                            :class="compareStatValue(compareList[1], row.key) > compareStatValue(compareList[0], row.key)
                                ? 'text-emerald-600' : 'text-slate-600'">
                            {{ compareStatValue(compareList[1], row.key) }}
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

@keyframes bounce-in {
    0%   { transform: scale(0.5); opacity: 0; }
    60%  { transform: scale(1.05); }
    100% { transform: scale(1); opacity: 1; }
}
.animate-bounce-in {
    animation: bounce-in 0.5s ease-out;
}
</style>
