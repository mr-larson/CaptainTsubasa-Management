<!-- resources/js/Pages/GameSaves/Show.vue -->
<script setup>
/**
 * Dashboard de partie Captain Tsubasa
 * - Vue Inertia pour gérer :
 *   - Dashboard général
 *   - Mon équipe (GameTeam + GamePlayers)
 *   - Autres équipes
 *   - Transferts (joueurs libres)
 *   - Calendrier & classement
 */

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import H2 from '@/Components/H2.vue';

// ==========================
//   PROPS INERTIA
// ==========================

const props = defineProps({
    gameSave:   { type: Object, required: true },
    teams:      { type: Array,  required: true }, // GameTeam[] avec contracts.*
    freePlayers:{ type: Array,  required: true }, // GamePlayer[] sans contrat
    matches:    { type: Array,  required: true }, // GameMatch[] avec home_team / away_team
    controlledTeam: { type: Object, required: false, default: null },
});

// ==========================
//   SAISON / SEMAINE
// ==========================

const season = ref(props.gameSave.season || 1);
const week   = ref(props.gameSave.week   || 1);

const currentState = ref(props.gameSave.state || { match: null });
const saving = ref(false);

// ==========================
//   RACCOURCIS PRINCIPAUX
// ==========================

const team = computed(() => props.controlledTeam || null);

const teamById = computed(() => {
    const map = {};
    (props.teams ?? []).forEach((t) => { map[t.id] = t; });
    return map;
});

const isByeMatch = (match) => {
    if (!match) return false;
    if (match.is_bye === true) return true;
    return !match.home_team_id || !match.away_team_id;
};

const opponentTeamIdFor = (match) => {
    if (!team.value || !match || isByeMatch(match)) return null;
    const isHome = match.home_team_id === team.value.id;
    return isHome ? match.away_team_id : match.home_team_id;
};

const opponentNameFor = (match) => {
    if (!team.value) return '???';
    if (!match || isByeMatch(match)) return 'Repos';
    const opponentId = opponentTeamIdFor(match);
    if (!opponentId) return 'Repos';
    return teamById.value[opponentId]?.name ?? '???';
};

// ✅ Photo URL (GamePlayer)
const playerPhotoUrl = (p) => {
    if (!p) return null;
    if (p.photo_path) return `/storage/${p.photo_path}`;
    if (p.player?.photo_path) return `/storage/${p.player.photo_path}`;
    return null;
};

/**
 * ✅ AJOUT : Logo URL (GameTeam.logo_path stocké en public/images/teams/xxx.webp)
 * logo_path = "images/teams/xxx.webp" => URL = "/images/teams/xxx.webp"
 */
const teamLogoUrl = (t) => {
    if (!t?.logo_path) return null;
    return `/${t.logo_path}`;
};

// ✅ AJOUT : équipe adverse du prochain match (robuste via IDs)
const nextOpponentTeamId = computed(() => {
    if (!team.value || !nextMatch.value) return null;
    return opponentTeamIdFor(nextMatch.value);
});

const nextOpponentTeam = computed(() => {
    const id = nextOpponentTeamId.value;
    if (!id) return null;
    return teamById.value[id] ?? null;
});

const myLogoUrl = computed(() => teamLogoUrl(team.value));
const opponentLogoUrl = computed(() => teamLogoUrl(nextOpponentTeam.value));

/**
 * Effectif = joueurs liés via les game_contracts
 * IMPORTANT : selon ton backend, l'objet peut être :
 * - c.game_player
 * - c.gamePlayer
 * - c.player
 */
const roster = computed(() => {
    if (!team.value || !Array.isArray(team.value.contracts)) return [];
    return team.value.contracts
        .map(c => c.game_player ?? c.gamePlayer ?? c.player ?? null)
        .filter(Boolean);
});

// ==========================
//   MON ÉQUIPE - SÉLECTION JOUEUR
// ==========================

const selectedMyPlayerId = ref(null);

const selectedMyPlayer = computed(() => {
    if (!roster.value.length) return null;
    if (!selectedMyPlayerId.value) return roster.value[0];
    return roster.value.find(p => p.id === selectedMyPlayerId.value) ?? roster.value[0];
});

const selectMyPlayer = (player) => { selectedMyPlayerId.value = player.id; };

// ==========================
//   BILAN / BUDGET
// ==========================

const teamRecord = computed(() => {
    if (!team.value) return { wins: 0, draws: 0, losses: 0 };
    return {
        wins:   team.value.wins   ?? 0,
        draws:  team.value.draws  ?? 0,
        losses: team.value.losses ?? 0,
    };
});

const teamBudget = computed(() => team.value?.budget ?? 0);

// ==========================
//   ONGLET & NAVIGATION
// ==========================

const tabs = [
    { key: 'dashboard',   label: 'Dashboard' },
    { key: 'my-team',     label: 'Mon équipe' },
    { key: 'other-teams', label: 'Autres équipes' },
    { key: 'transfers',   label: 'Transferts' },
    { key: 'calendar',    label: 'Calendrier' },
    { key: 'standings',   label: 'Classement' },
    { key: 'training',    label: 'Entraînement' },
    { key: 'cards',       label: 'Cartes bonus' },
];

const activeTab = ref('dashboard');

// ==========================
//   CALENDRIER & MATCHS
// ==========================

const myMatches = computed(() => {
    if (!team.value) return [];
    return (props.matches ?? [])
        .filter((m) => m.home_team_id === team.value.id || m.away_team_id === team.value.id)
        .sort((a, b) => (a.week ?? 0) - (b.week ?? 0));
});

const seasonWeeksCount = computed(() => {
    const n = props.teams?.length ?? 0;
    if (n < 2) return 0;
    return (n % 2 === 1) ? (n * 2) : ((n - 1) * 2);
});

// ==========================
//   CALENDRIER - ÉQUIPE SÉLECTIONNÉE
// ==========================

const selectedCalendarTeamId = ref(null);
const calendarTeams = computed(() => props.teams ?? []);

const calendarTeam = computed(() => {
    if (!calendarTeams.value.length) return null;

    if (!selectedCalendarTeamId.value) {
        return team.value || calendarTeams.value[0];
    }

    const id = Number(selectedCalendarTeamId.value);
    return calendarTeams.value.find(t => Number(t.id) === id) ?? (team.value || calendarTeams.value[0]);
});

const selectCalendarTeam = (t) => { selectedCalendarTeamId.value = t.id; };

const calendarTeamMatches = computed(() => {
    if (!calendarTeam.value) return [];
    return (props.matches ?? [])
        .filter(m => m.home_team_id === calendarTeam.value.id || m.away_team_id === calendarTeam.value.id)
        .sort((a, b) => (a.week ?? 0) - (b.week ?? 0));
});

const calendarRows = computed(() => {
    if (!calendarTeam.value) return [];

    const byWeek = new Map();
    calendarTeamMatches.value.forEach(m => byWeek.set(Number(m.week), m));

    const totalWeeks = seasonWeeksCount.value || 0;
    const rows = [];

    for (let w = 1; w <= totalWeeks; w++) {
        const match = byWeek.get(w);
        if (match) rows.push(match);
        else {
            rows.push({
                id: `bye-${calendarTeam.value.id}-${w}`,
                week: w,
                is_bye: true,
                status: 'bye',
                home_team_id: null,
                away_team_id: null,
            });
        }
    }
    return rows;
});

const myMatchThisWeek = computed(() => {
    if (!team.value) return null;
    const w = week.value ?? 1;

    return myMatches.value.find(m =>
        m.week === w &&
        !isByeMatch(m) &&
        (m.status === 'scheduled' || m.status === 'played')
    ) ?? null;
});

const isByeWeek = computed(() => {
    if (!team.value) return false;
    return !myMatchThisWeek.value;
});

const simulateWeek = () => {
    router.post(route('game-saves.simulate-week', { gameSave: props.gameSave.id }), {}, { preserveScroll: true });
};

const nextMatch = computed(() => {
    if (!team.value || !myMatches.value.length) return null;

    const currentWeek = week.value ?? 1;

    const candidates = myMatches.value
        .filter(m => m.status === 'scheduled' && m.week >= currentWeek)
        .sort((a, b) => a.week - b.week);

    return candidates[0] ?? null;
});

const nextMatchInfo = computed(() => {
    if (!nextMatch.value || !team.value) return null;

    const isHome = nextMatch.value.home_team_id === team.value.id;
    const opponent = isHome ? nextMatch.value.away_team : nextMatch.value.home_team;

    return {
        isHome,
        opponentName: opponent?.name ?? opponentNameFor(nextMatch.value),
        week: nextMatch.value.week,
    };
});

const opponentNameForTeam = (match, teamId) => {
    if (!match || !teamId) return '???';
    if (match.is_bye === true) return 'Repos';

    const isHome = match.home_team_id === teamId;
    const oppId  = isHome ? match.away_team_id : match.home_team_id;

    return teamById.value[oppId]?.name ?? '???';
};

// ==========================
//   AUTRES EQUIPES
// ==========================

const otherTeams = computed(() => {
    const currentId = team.value?.id ?? null;
    return (props.teams ?? []).filter(t => t.id !== currentId);
});

const selectedOtherTeamId = ref(null);

const selectedOtherTeam = computed(() => {
    if (!otherTeams.value.length) return null;
    if (!selectedOtherTeamId.value) return otherTeams.value[0];
    return otherTeams.value.find(t => t.id === selectedOtherTeamId.value) ?? otherTeams.value[0];
});

const selectedOtherTeamRoster = computed(() => {
    if (!selectedOtherTeam.value || !Array.isArray(selectedOtherTeam.value.contracts)) return [];
    return selectedOtherTeam.value.contracts
        .map(c => c.game_player ?? c.gamePlayer ?? c.player ?? null)
        .filter(Boolean);
});

const selectOtherTeam = (t) => { selectedOtherTeamId.value = t.id; };

// ==========================
//   HELPERS AUTRES ÉQUIPES
// ==========================

const statValueFor = (p, key) => {
    if (!p) return 0;
    const stats = p.stats ?? {};
    const value = p[key] ?? stats[key] ?? 0;
    return Number(value || 0);
};

const averageTeamStat = (players, key) => {
    if (!Array.isArray(players) || players.length === 0) return 0;
    const total = players.reduce((sum, p) => sum + statValueFor(p, key), 0);
    return Math.round(total / players.length);
};

// si dans state tu stockes team_id => on filtre, sinon fallback sur le total global
const countByTeamIdOrAll = (items, teamId) => {
    if (!Array.isArray(items)) return 0;
    const hasTeamId = items.some(x => x && Object.prototype.hasOwnProperty.call(x, 'team_id'));
    if (!hasTeamId) return items.length;
    return items.filter(x => Number(x.team_id) === Number(teamId)).length;
};

const injuriesCountForTeam = (teamId) => countByTeamIdOrAll(injuries.value, teamId);
const suspensionsCountForTeam = (teamId) => countByTeamIdOrAll(suspensions.value, teamId);
const cardsCountForTeam = (teamId) => countByTeamIdOrAll(cards.value, teamId);

// ==========================
//   CLASSEMENT
// ==========================

const standings = computed(() => {
    const list = (props.teams ?? []).map((t) => {
        const wins   = t.wins   ?? 0;
        const draws  = t.draws  ?? 0;
        const losses = t.losses ?? 0;
        const played = wins + draws + losses;
        const points = wins * 3 + draws;

        return { ...t, wins, draws, losses, played, points };
    });

    list.sort((a, b) => {
        if (b.points !== a.points) return b.points - a.points;
        if (b.wins !== a.wins)     return b.wins - a.wins;
        return a.name.localeCompare(b.name);
    });

    return list;
});

const clubStanding = computed(() => {
    if (!team.value) return null;

    const index = standings.value.findIndex(row => row.id === team.value.id);
    if (index === -1) return null;

    return { ...standings.value[index], position: index + 1 };
});

// ==========================
//   BLESSURES / CARTONS
// ==========================

const injuries    = computed(() => props.gameSave.state?.injuries    ?? []);
const suspensions = computed(() => props.gameSave.state?.suspensions ?? []);
const cards       = computed(() => props.gameSave.state?.cards       ?? []);

const injuriesCount    = computed(() => injuries.value.length);
const suspensionsCount = computed(() => suspensions.value.length);
const cardsCount       = computed(() => cards.value.length);

// ==========================
//   MOYENNES DES STATS
// ==========================

const statValue = (p, key) => {
    if (!p) return 0;
    const stats = p.stats ?? {};
    const value = p[key] ?? stats[key] ?? 0;
    return Number(value || 0);
};

const averageStat = (key) => {
    if (!roster.value.length) return 0;
    const total = roster.value.reduce((sum, p) => sum + statValue(p, key), 0);
    return Math.round(total / roster.value.length);
};

const averageAttack  = computed(() => averageStat('attack'));
const averageDefense = computed(() => averageStat('defense'));
const averageStamina = computed(() => averageStat('stamina'));
const averageSpeed   = computed(() => averageStat('speed'));

// ==========================
//   MODAL TRANSFERT
// ==========================

const freeAgentSignings = computed(() => props.gameSave.state?.free_agent_signings ?? []);
const signedFreePlayerIds = computed(() => freeAgentSignings.value.map(s => s.player_id));

const freePlayers = computed(() => {
    if (!Array.isArray(props.freePlayers)) return [];
    return props.freePlayers.filter((p) => !signedFreePlayerIds.value.includes(p.id));
});

const showTransferModal = ref(false);
const transferTarget    = ref(null);
const transferMatches   = ref(10);
const transferSalary    = ref(0);
const transferReason    = ref('');

const transferTotalCost = computed(() => {
    return (Number(transferMatches.value) || 0) * (Number(transferSalary.value) || 0);
});

const openTransferModal = (player) => {
    transferTarget.value  = player;
    transferMatches.value = 10;
    transferSalary.value  = player.cost ?? 0;
    transferReason.value  = '';
    showTransferModal.value = true;
};

const closeTransferModal = () => {
    showTransferModal.value = false;
    transferTarget.value    = null;
};

const confirmTransfer = () => {
    if (!team.value || !transferTarget.value) return;

    router.post(
        route('game-saves.free-agents.sign', {
            gameSave: props.gameSave.id,
            player:   transferTarget.value.id,
        }),
        {
            team_id:       team.value.id,
            salary:        transferSalary.value,
            matches_total: transferMatches.value,
            reason:        transferReason.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => closeTransferModal(),
        }
    );
};

// ==========================
//   HELPERS GÉNÉRAUX
// ==========================

const periodLabel = (period) => {
    if (period === 'college')    return 'Collège';
    if (period === 'highschool') return 'Lycée';
    if (period === 'pro')        return 'Professionnel';
    return period;
};

const saveGame = () => {
    saving.value = true;

    router.put(
        route('game-saves.update', props.gameSave.id),
        {
            label:  props.gameSave.label,
            season: season.value,
            week:   week.value,
            state:  currentState.value,
        },
        {
            preserveState:  true,
            preserveScroll: true,
            onFinish: () => { saving.value = false; },
        }
    );
};

const playNextMatch = () => {
    router.get(route('game-saves.match', { gameSave: props.gameSave.id }));
};
</script>

<template>
    <Head :title="`Partie ${gameSave.label ?? '#' + gameSave.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <H2>
                Partie : {{ gameSave.label ?? `Sauvegarde #${gameSave.id}` }}
            </H2>
        </template>

        <div class="p-4">
            <!-- Titre -->
            <div class="flex justify-center mb-6">
                <h1 class="text-3xl font-bold text-slate-600">
                    Session de jeu
                </h1>
            </div>

            <div class="flex flex-row">
                <!-- Visuel gauche -->
                <div
                    class="hidden md:block basis-1/4 p-4 bg-contain bg-center bg-no-repeat"
                    style="background-image: url('/images/wakabayashi.webp')"
                ></div>

                <!-- Carte principale -->
                <div class="basis-3/4 p-4 border border-slate-300 rounded-lg mx-6 bg-white min-h-[500px] flex flex-col">
                    <!-- Onglets -->
                    <div class="mb-4 border-b border-slate-200">
                        <nav class="-mb-px flex space-x-2 overflow-x-auto">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                type="button"
                                @click="activeTab = tab.key"
                                :class="[
                                    'whitespace-nowrap py-2 px-4 text-sm font-medium border-b-2 rounded-t-md',
                                    activeTab === tab.key
                                        ? 'border-teal-500 text-slate-900 bg-slate-50'
                                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                ]"
                            >
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>

                    <!-- ============================== -->
                    <!--            DASHBOARD           -->
                    <!-- ============================== -->
                    <div v-if="activeTab === 'dashboard'" class="flex-1 flex flex-col">
                        <!-- Infos générales -->
                        <div class="mb-4 flex flex-col lg:flex-row lg:items-center lg:justify-around gap-2">
                            <p class="text-slate-600">
                                Période :
                                <span class="font-semibold">{{ periodLabel(gameSave.period) }}</span>
                            </p>
                            <p class="text-slate-600">Saison {{ season }} — Semaine {{ week }}</p>
                            <p class="text-slate-600" v-if="team">
                                Équipe contrôlée : <span class="font-semibold">{{ team.name }}</span>
                            </p>
                        </div>

                        <!-- Cartes du dashboard -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 flex-1">
                            <!-- Prochain match -->
                            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                                <!-- header: titre à gauche, logo à droite -->
                                <div class="flex items-start justify-between gap-4 mb-2">
                                    <div v-if="nextMatch && nextMatchInfo" class="text-sm text-slate-700">
                                        <h3 class="text-lg font-semibold text-slate-700">Prochain match</h3>
                                        <ul class="space-y-1">
                                            <li><span class="font-semibold">Semaine :</span> {{ isByeWeek ? week : nextMatchInfo.week }}</li>
                                            <li><span class="font-semibold">Adversaire :</span> {{ opponentNameFor(nextMatch) }}</li>
                                            <li><span class="font-semibold">Lieu :</span> {{ nextMatchInfo.isHome ? 'Domicile' : 'Extérieur' }}</li>
                                            <li><span class="font-semibold">Contexte :</span> Saison {{ season }} — Championnat</li>
                                        </ul>
                                    </div>
                                    <div v-else class="text-sm text-slate-600">
                                        <p class="mb-2">Aucun match de championnat planifié pour le moment.</p>
                                        <p class="text-xs text-slate-500">Vérifie que le calendrier a bien été généré pour cette sauvegarde.</p>
                                    </div>
                                    <!-- ✅ Logo adversaire à droite (case rouge) -->
                                    <div class="h-24 w-24 rounded-lg overflow-hidden flex items-center justify-center shrink-0">
                                        <img
                                            v-if="nextMatch && opponentTeamIdFor(nextMatch) && teamById[opponentTeamIdFor(nextMatch)]?.logo_path"
                                            :src="`/${teamById[opponentTeamIdFor(nextMatch)].logo_path}`"
                                            class="h-full w-full object-contain"
                                            alt="Logo adversaire"
                                        />
                                        <span v-else class="text-xs text-slate-400">—</span>
                                    </div>
                                </div>
                                <div class="mt-4 pt-6 flex justify-center gap-3">
                                    <button
                                        v-if="!isByeWeek"
                                        type="button"
                                        class="w-60 bg-teal-300 hover:bg-teal-400 text-center font-semibold py-1 px-5 border-2 border-teal-500 rounded-full drop-shadow-md"
                                        @click="playNextMatch"
                                    >
                                        Jouer le prochain match
                                    </button>

                                    <button
                                        v-else
                                        type="button"
                                        class="w-60 bg-slate-300 hover:bg-slate-400 text-center font-semibold py-1 px-5 border-2 border-slate-500 rounded-full drop-shadow-md"
                                        @click="simulateWeek"
                                    >
                                        Simuler la semaine
                                    </button>
                                </div>
                            </div>

                            <!-- Résumé du club -->
                            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                                <!-- Ligne haute : texte + logo -->
                                <div class="flex items-start justify-between gap-4 mb-2">
                                    <div class="text-sm text-slate-700">
                                        <h3 class="text-lg font-semibold text-slate-700 mb-1">
                                            Résumé du club
                                        </h3>

                                        <p><span class="font-semibold">Nom :</span> {{ team.name }}</p>
                                        <p><span class="font-semibold">Budget :</span> {{ teamBudget }} €</p>
                                        <p><span class="font-semibold">Joueurs sous contrat :</span> {{ roster.length }}</p>
                                    </div>

                                    <!-- Logo équipe à droite -->
                                    <div class="h-24 w-24 rounded-lg overflow-hidden flex items-center justify-center shrink-0">
                                        <img
                                            v-if="team?.logo_path"
                                            :src="`/${team.logo_path}`"
                                            class="h-full w-full object-contain"
                                            alt="Logo équipe"
                                        />
                                        <span v-else class="text-xs text-slate-400">—</span>
                                    </div>
                                </div>

                                <!-- Description descendue (équivalent du bouton dans l’autre carte) -->
                                <div v-if="team.description" class="mt-6 text-sm text-slate-700">
                                    <span class="font-semibold">Description :</span>
                                    <span class="text-slate-600">{{ team.description }}</span>
                                </div>
                            </div>


                            <!-- Statut du club -->
                            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50 lg:col-span-2">
                                <h3 class="text-lg font-semibold text-slate-700 mb-2">Statut du club</h3>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-700">
                                    <div>
                                        <p class="font-semibold mb-1">Classement</p>
                                        <p v-if="clubStanding">
                                            Place : {{ clubStanding.position }}<sup>e</sup> / {{ standings.length }}
                                        </p>
                                        <p v-else>Classement non disponible.</p>

                                        <p class="mt-1">
                                            Bilan : {{ teamRecord.wins }} V / {{ teamRecord.draws }} N / {{ teamRecord.losses }} D
                                        </p>
                                        <p>Matchs joués : {{ teamRecord.wins + teamRecord.draws + teamRecord.losses }}</p>
                                    </div>

                                    <div>
                                        <p class="font-semibold mb-1">Forces moyennes de l’équipe</p>
                                        <p>Attaque : {{ averageAttack }}</p>
                                        <p>Défense : {{ averageDefense }}</p>
                                        <p>Endurance : {{ averageStamina }}</p>
                                    </div>

                                    <div>
                                        <p class="font-semibold mb-1">État de l’effectif</p>
                                        <p>Blessés : {{ injuriesCount }}</p>
                                        <p>Suspensions : {{ suspensionsCount }}</p>
                                        <p>Cartons en cours : {{ cardsCount }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons bas -->
                        <div class="flex justify-around mt-6">
                            <button
                                type="button"
                                class="w-40 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-1 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2 disabled:opacity-50"
                                :disabled="saving"
                                @click="saveGame"
                            >
                                {{ saving ? 'Sauvegarde...' : 'Sauvegarder' }}
                            </button>

                            <button
                                type="button"
                                class="w-40 bg-slate-300 hover:bg-slate-400 text-center font-semibold py-1 px-5 border-2 border-slate-500 rounded-full drop-shadow-md mb-2"
                                @click="$inertia.visit(route('mainMenu'))"
                            >
                                Quitter
                            </button>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--        MON ÉQUIPE              -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'my-team'" class="flex-1 flex gap-4">
                        <!-- Colonne gauche : liste joueurs -->
                        <div class="w-1/5 border border-slate-200 rounded-lg bg-slate-50 p-3">
                            <h3 class="text-md font-semibold text-slate-700 mb-2">Joueurs</h3>

                            <div v-if="roster.length" class="max-h-96 overflow-y-auto space-y-1">
                                <button
                                    v-for="p in roster"
                                    :key="p.id"
                                    type="button"
                                    @click="selectMyPlayer(p)"
                                    :class="[
                                        'w-full text-left text-sm px-2 py-1 rounded',
                                        selectedMyPlayer && selectedMyPlayer.id === p.id
                                            ? 'bg-teal-100 text-slate-900'
                                            : 'bg-white hover:bg-slate-100 text-slate-700'
                                    ]"
                                >
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 min-w-0 flex items-center justify-between">
                                            <span class="truncate">{{ p.firstname }} {{ p.lastname }}</span>
                                            <span class="text-xs text-slate-400 ml-2 shrink-0">{{ p.position }}</span>
                                        </div>
                                    </div>
                                </button>
                            </div>

                            <p v-else class="text-sm text-slate-500">Aucun joueur sous contrat.</p>
                        </div>

                        <!-- Colonne droite : profil + stats + historique -->
                        <div class="flex-1 flex flex-col gap-4">
                            <template v-if="selectedMyPlayer">
                                <!-- Carte profil -->
                                <div class="border border-slate-200 rounded-lg bg-slate-50 p-4">
                                    <div class="flex items-start gap-4">
                                        <div class="w-24 h-24 rounded-lg border border-slate-200 bg-white overflow-hidden flex items-center justify-center">
                                            <img
                                                v-if="playerPhotoUrl(selectedMyPlayer)"
                                                :src="playerPhotoUrl(selectedMyPlayer)"
                                                class="h-full w-full object-cover"
                                                alt="Photo joueur"
                                            />
                                            <span v-else class="text-xs text-slate-400 text-center px-2">
                                                Aucune<br>photo
                                            </span>
                                        </div>

                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-slate-800">
                                                {{ selectedMyPlayer.firstname }} {{ selectedMyPlayer.lastname }}
                                            </h3>

                                            <p class="text-sm text-slate-600">
                                                Poste : <span class="font-semibold">{{ selectedMyPlayer.position }}</span>
                                                <span class="text-slate-400 mx-2">•</span>
                                                Coût : <span class="font-semibold">{{ selectedMyPlayer.cost ?? 0 }} €</span>
                                            </p>

                                            <p v-if="selectedMyPlayer.description" class="mt-3 text-sm text-slate-700">
                                                <span class="font-semibold">Description :</span>
                                                <span class="text-slate-600">{{ selectedMyPlayer.description }}</span>
                                            </p>
                                            <p v-else class="mt-3 text-sm text-slate-400 italic">Aucune description.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Carte stats -->
                                <div class="border border-slate-200 rounded-lg bg-slate-50 p-4">
                                    <h4 class="text-md font-semibold text-slate-700 mb-3">Statistiques</h4>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Vitesse</span>
                                            <span class="font-semibold text-slate-800">
                                                {{ selectedMyPlayer.speed ?? selectedMyPlayer.stats?.speed ?? '-' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Stamina</span>
                                            <span class="font-semibold text-slate-800">
                                                {{ selectedMyPlayer.stamina ?? selectedMyPlayer.stats?.stamina ?? '-' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Attaque</span>
                                            <span class="font-semibold text-slate-800">
                                                {{ selectedMyPlayer.attack ?? selectedMyPlayer.stats?.attack ?? '-' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Défense</span>
                                            <span class="font-semibold text-slate-800">
                                                {{ selectedMyPlayer.defense ?? selectedMyPlayer.stats?.defense ?? '-' }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Tir</span>
                                            <span class="font-semibold text-slate-800">
                                                {{ selectedMyPlayer.shot ?? selectedMyPlayer.stats?.shot ?? '-' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Passe</span>
                                            <span class="font-semibold text-slate-800">
                                                {{ selectedMyPlayer.pass ?? selectedMyPlayer.stats?.pass ?? '-' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Dribble</span>
                                            <span class="font-semibold text-slate-800">
                                                {{ selectedMyPlayer.dribble ?? selectedMyPlayer.stats?.dribble ?? '-' }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Block</span>
                                            <span class="font-semibold text-slate-800">
                                                {{ selectedMyPlayer.block ?? selectedMyPlayer.stats?.block ?? '-' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Interception</span>
                                            <span class="font-semibold text-slate-800">
                                                {{ selectedMyPlayer.intercept ?? selectedMyPlayer.stats?.intercept ?? '-' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Tacle</span>
                                            <span class="font-semibold text-slate-800">
                                                {{ selectedMyPlayer.tackle ?? selectedMyPlayer.stats?.tackle ?? '-' }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Arrêt main</span>
                                            <span class="font-semibold text-slate-800">
                                                {{ selectedMyPlayer.hand_save ?? selectedMyPlayer.stats?.hand_save ?? '-' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between border-b border-slate-200 pb-1">
                                            <span class="text-slate-600">Arrêt poings</span>
                                            <span class="font-semibold text-slate-800">
                                                {{ selectedMyPlayer.punch_save ?? selectedMyPlayer.stats?.punch_save ?? '-' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="border border-slate-200 rounded-lg bg-slate-50 p-4">
                                    <h4 class="text-md font-semibold text-slate-700 mb-2">Historique & performance</h4>
                                    <p class="text-sm text-slate-500">
                                        Cette section arrive bientôt : historique des matchs, taux de réussite (passes, tirs, dribbles),
                                        et statistiques par semaine.
                                    </p>
                                </div>
                            </template>

                            <p v-else class="text-sm text-slate-500">
                                Sélectionne un joueur dans la liste à gauche.
                            </p>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--        AUTRES ÉQUIPES          -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'other-teams'" class="flex-1 flex gap-4">
                        <!-- Liste des équipes -->
                        <div class="w-2/5 border border-slate-200 rounded-lg bg-slate-50 p-3">
                            <h3 class="text-md font-semibold text-slate-700 mb-2">Équipes de la ligue</h3>

                            <div v-if="otherTeams.length" class="max-h-96 overflow-y-auto space-y-1">
                                <button
                                    v-for="t in otherTeams"
                                    :key="t.id"
                                    type="button"
                                    @click="selectOtherTeam(t)"
                                    :class="[
                                        'w-full text-left text-sm px-2 py-1 rounded',
                                        selectedOtherTeam && selectedOtherTeam.id === t.id
                                            ? 'bg-teal-100 text-slate-900'
                                            : 'bg-white hover:bg-slate-100 text-slate-700'
                                    ]"
                                >
                                    {{ t.name }}
                                </button>
                            </div>

                            <p v-else class="text-sm text-slate-500">Aucune autre équipe trouvée.</p>
                        </div>

                        <!-- Détail de l'équipe sélectionnée -->
                        <div v-if="selectedOtherTeam">
                            <!-- TOP : 3 blocs (Budget/Bilan) | (Description courte) | (Logo) -->
                            <div class="border border-slate-200 rounded-lg bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <!-- Gauche : Nom + Budget/Bilan -->
                                    <div class="min-w-0">
                                        <h3 class="text-lg font-semibold text-slate-700 mb-2">
                                            {{ selectedOtherTeam.name }}
                                        </h3>

                                        <div class="text-sm text-slate-700 space-y-1">
                                            <p v-if="standings?.length">
                                                <span class="font-semibold">Place :</span>
                                                {{ (standings.findIndex(row => row.id === selectedOtherTeam.id) + 1) || '—' }}
                                                <sup>e</sup> / {{ standings.length }}
                                            </p>
                                            <p class="mt-1">
                                                <span class="font-semibold">Bilan :</span>
                                                {{ selectedOtherTeam.wins ?? 0 }} V /
                                                {{ selectedOtherTeam.draws ?? 0 }} N /
                                                {{ selectedOtherTeam.losses ?? 0 }} D
                                            </p>
                                            <p>
                                                <span class="font-semibold">Budget :</span>
                                                {{ selectedOtherTeam.budget ?? 0 }} €
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Milieu : Description -->
                                    <div class="flex-1 min-w-0 px-6">
                                        <p class="text-sm text-slate-700">
                                            <span class="font-semibold">Description :</span>
                                            <span class="text-slate-600">{{
                                                    (selectedOtherTeam.description ?? '-')
                                                        ? (selectedOtherTeam.description)
                                                        : (selectedOtherTeam.description ?? '-') }}
                                            </span>
                                        </p>
                                    </div>

                                    <!-- Droite : Logo -->
                                    <div class="h-24 w-24 rounded-lg  overflow-hidden flex items-center justify-center shrink-0">
                                        <img
                                            v-if="selectedOtherTeam?.logo_path"
                                            :src="`/${selectedOtherTeam.logo_path}`"
                                            class="h-full w-full object-contain"
                                            alt="Logo club"
                                        />
                                        <span v-else class="text-xs text-slate-400">—</span>
                                    </div>
                                </div>
                            </div>

                            <!-- STATUT : carte séparée (3 colonnes) -->
                            <div class="mt-4 border border-slate-200 rounded-lg bg-slate-50 p-4">
                                <h4 class="text-md font-semibold text-slate-700 mb-3">Statut du club</h4>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-700">
                                    <!-- Performances -->
                                    <div>
                                        <p class="font-semibold mb-1">Performances</p>

                                        <p>
                                            Matchs joués :
                                            {{
                                                (selectedOtherTeam.wins ?? 0) +
                                                (selectedOtherTeam.draws ?? 0) +
                                                (selectedOtherTeam.losses ?? 0)
                                            }}
                                        </p>
                                    </div>

                                    <!-- Forces moyennes -->
                                    <div>
                                        <p class="font-semibold mb-1">Forces moyennes de l’équipe</p>
                                        <p>Attaque : {{ averageTeamStat(selectedOtherTeamRoster, 'attack') }}</p>
                                        <p>Défense : {{ averageTeamStat(selectedOtherTeamRoster, 'defense') }}</p>
                                        <p>Endurance : {{ averageTeamStat(selectedOtherTeamRoster, 'stamina') }}</p>
                                    </div>

                                    <!-- État de l’effectif -->
                                    <div>
                                        <p class="font-semibold mb-1">État de l’effectif</p>
                                        <p>Blessés : {{ injuriesCountForTeam(selectedOtherTeam.id) }}</p>
                                        <p>Suspensions : {{ suspensionsCountForTeam(selectedOtherTeam.id) }}</p>
                                        <p>Cartons en cours : {{ cardsCountForTeam(selectedOtherTeam.id) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- EFFECTIF : carte séparée -->
                            <div class="mt-4 border border-slate-200 rounded-lg bg-slate-50 p-4">
                                <h4 class="text-md font-semibold text-slate-700 mb-2">Effectif</h4>

                                <div v-if="selectedOtherTeamRoster.length" class="max-h-72 overflow-y-auto">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm text-left min-w-max">
                                            <thead class="text-xs uppercase text-slate-500 border-b">
                                            <tr>
                                                <th class="py-1 pr-2 w-10"></th>
                                                <th class="py-1 pr-2">Joueur</th>
                                                <th class="py-1 pr-2">Poste</th>

                                                <th class="py-1 pr-2 text-right">Vit</th>
                                                <th class="py-1 pr-2 text-right">End</th>
                                                <th class="py-1 pr-2 text-right">Att</th>
                                                <th class="py-1 pr-2 text-right">Def</th>

                                                <th class="py-1 pr-2 text-right">Tir</th>
                                                <th class="py-1 pr-2 text-right">Passe</th>
                                                <th class="py-1 pr-2 text-right">Dribble</th>

                                                <th class="py-1 pr-2 text-right">Block</th>
                                                <th class="py-1 pr-2 text-right">Interc.</th>
                                                <th class="py-1 pr-2 text-right">Tacle</th>

                                                <th class="py-1 pr-2 text-right">Main</th>
                                                <th class="py-1 pr-2 text-right">Poings</th>

                                                <th class="py-1 pr-2 text-right">Coût</th>
                                            </tr>
                                            </thead>

                                            <tbody>
                                            <tr
                                                v-for="player in selectedOtherTeamRoster"
                                                :key="player.id"
                                                class="border-b last:border-b-0"
                                            >
                                                <td class="py-1 pr-2">
                                                    <div class="h-7 w-7 rounded border bg-white overflow-hidden flex items-center justify-center">
                                                        <img
                                                            v-if="playerPhotoUrl(player)"
                                                            :src="playerPhotoUrl(player)"
                                                            class="h-full w-full object-cover"
                                                            alt=""
                                                        />
                                                        <span v-else class="text-[10px] text-slate-400">—</span>
                                                    </div>
                                                </td>

                                                <td class="py-1 pr-2">{{ player.firstname }} {{ player.lastname }}</td>
                                                <td class="py-1 pr-2">{{ player.position }}</td>

                                                <td class="py-1 pr-2 text-right">{{ player.speed ?? player.stats?.speed ?? '-' }}</td>
                                                <td class="py-1 pr-2 text-right">{{ player.stamina ?? player.stats?.stamina ?? '-' }}</td>
                                                <td class="py-1 pr-2 text-right">{{ player.attack ?? player.stats?.attack ?? '-' }}</td>
                                                <td class="py-1 pr-2 text-right">{{ player.defense ?? player.stats?.defense ?? '-' }}</td>

                                                <td class="py-1 pr-2 text-right">{{ player.shot ?? player.stats?.shot ?? '-' }}</td>
                                                <td class="py-1 pr-2 text-right">{{ player.pass ?? player.stats?.pass ?? '-' }}</td>
                                                <td class="py-1 pr-2 text-right">{{ player.dribble ?? player.stats?.dribble ?? '-' }}</td>

                                                <td class="py-1 pr-2 text-right">{{ player.block ?? player.stats?.block ?? '-' }}</td>
                                                <td class="py-1 pr-2 text-right">{{ player.intercept ?? player.stats?.intercept ?? '-' }}</td>
                                                <td class="py-1 pr-2 text-right">{{ player.tackle ?? player.stats?.tackle ?? '-' }}</td>

                                                <td class="py-1 pr-2 text-right">{{ player.hand_save ?? player.stats?.hand_save ?? '-' }}</td>
                                                <td class="py-1 pr-2 text-right">{{ player.punch_save ?? player.stats?.punch_save ?? '-' }}</td>

                                                <td class="py-1 pr-2 text-right">{{ player.cost ?? '-' }}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <p v-else class="text-sm text-slate-500">
                                    Aucun joueur sous contrat pour cette équipe.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--          TRANSFERTS           -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'transfers'" class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4 flex flex-col gap-4 relative">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-slate-700">Joueurs libres</h3>
                            <p class="text-xs text-slate-500">
                                Les signatures impactent le budget de ton club dans cette sauvegarde.
                            </p>
                        </div>

                        <div v-if="freePlayers.length" class="max-h-96 overflow-y-auto">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left min-w-max">
                                    <thead class="text-xs uppercase text-slate-500 border-b">
                                    <tr>
                                        <th class="py-1 pr-2 w-10"></th>
                                        <th class="py-1 pr-2">Joueur</th>
                                        <th class="py-1 pr-2">Poste</th>

                                        <th class="py-1 pr-2 text-right">Vit</th>
                                        <th class="py-1 pr-2 text-right">End</th>
                                        <th class="py-1 pr-2 text-right">Att</th>
                                        <th class="py-1 pr-2 text-right">Def</th>

                                        <th class="py-1 pr-2 text-right">Tir</th>
                                        <th class="py-1 pr-2 text-right">Passe</th>
                                        <th class="py-1 pr-2 text-right">Dribble</th>

                                        <th class="py-1 pr-2 text-right">Block</th>
                                        <th class="py-1 pr-2 text-right">Interc.</th>
                                        <th class="py-1 pr-2 text-right">Tacle</th>

                                        <th class="py-1 pr-2 text-right">Main</th>
                                        <th class="py-1 pr-2 text-right">Poings</th>

                                        <th class="py-1 pr-2 text-right">Action</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <tr v-for="player in freePlayers" :key="player.id" class="border-b last:border-b-0">
                                        <td class="py-1 pr-2">
                                            <div class="h-7 w-7 rounded border bg-white overflow-hidden flex items-center justify-center">
                                                <img
                                                    v-if="playerPhotoUrl(player)"
                                                    :src="playerPhotoUrl(player)"
                                                    class="h-full w-full object-cover"
                                                    alt=""
                                                />
                                                <span v-else class="text-[10px] text-slate-400">—</span>
                                            </div>
                                        </td>

                                        <td class="py-1 pr-2">{{ player.firstname }} {{ player.lastname }}</td>
                                        <td class="py-1 pr-2">{{ player.position }}</td>

                                        <td class="py-1 pr-2 text-right">{{ player.speed ?? player.stats?.speed ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.stamina ?? player.stats?.stamina ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.attack ?? player.stats?.attack ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.defense ?? player.stats?.defense ?? '-' }}</td>

                                        <td class="py-1 pr-2 text-right">{{ player.shot ?? player.stats?.shot ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.pass ?? player.stats?.pass ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.dribble ?? player.stats?.dribble ?? '-' }}</td>

                                        <td class="py-1 pr-2 text-right">{{ player.block ?? player.stats?.block ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.intercept ?? player.stats?.intercept ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.tackle ?? player.stats?.tackle ?? '-' }}</td>

                                        <td class="py-1 pr-2 text-right">{{ player.hand_save ?? player.stats?.hand_save ?? '-' }}</td>
                                        <td class="py-1 pr-2 text-right">{{ player.punch_save ?? player.stats?.punch_save ?? '-' }}</td>

                                        <td class="py-1 pr-2 text-right">
                                            <button
                                                type="button"
                                                class="text-xs px-3 py-0.5 rounded-full border border-teal-500 bg-teal-100 hover:bg-teal-200 font-semibold disabled:opacity-50"
                                                :disabled="!team"
                                                @click="openTransferModal(player)"
                                            >
                                                Offre
                                            </button>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <p v-else class="text-sm text-slate-500">Aucun joueur libre disponible.</p>

                        <!-- MODAL TRANSFERT -->
                        <div v-if="showTransferModal && transferTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-5">
                                <h3 class="text-lg font-semibold text-slate-800 mb-3">
                                    Proposer un contrat à {{ transferTarget.firstname }} {{ transferTarget.lastname }}
                                </h3>

                                <!-- mini header avec photo -->
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="h-10 w-10 rounded border bg-white overflow-hidden flex items-center justify-center">
                                        <img
                                            v-if="playerPhotoUrl(transferTarget)"
                                            :src="playerPhotoUrl(transferTarget)"
                                            class="h-full w-full object-cover"
                                            alt=""
                                        />
                                        <span v-else class="text-[10px] text-slate-400">—</span>
                                    </div>
                                    <p class="text-sm text-slate-600">
                                        Club : <span class="font-semibold">{{ team?.name }}</span> —
                                        Budget actuel : <span class="font-semibold">{{ teamBudget }} €</span>
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nombre de matchs</label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="60"
                                            v-model.number="transferMatches"
                                            class="w-full border border-slate-300 rounded-md px-2 py-1 text-sm"
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Coût par match (€)</label>
                                        <input
                                            type="number"
                                            min="0"
                                            v-model.number="transferSalary"
                                            class="w-full border border-slate-300 rounded-md px-2 py-1 text-sm"
                                        >
                                    </div>
                                </div>

                                <p class="text-sm text-slate-700 mb-3">
                                    Coût total estimé : <span class="font-semibold">{{ transferTotalCost }} €</span>
                                </p>

                                <div class="mb-4">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Raison du recrutement</label>
                                    <textarea
                                        rows="3"
                                        v-model="transferReason"
                                        class="w-full border border-slate-300 rounded-md px-2 py-1 text-sm"
                                        placeholder="Ex. : Renforcer l’aile gauche, remplacer un blessé..."
                                    ></textarea>
                                </div>

                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        class="px-3 py-1.5 text-sm rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100"
                                        @click="closeTransferModal"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="button"
                                        class="px-4 py-1.5 text-sm rounded-md bg-teal-500 hover:bg-teal-600 text-white font-semibold disabled:opacity-50"
                                        :disabled="!team || transferMatches <= 0 || transferSalary < 0"
                                        @click="confirmTransfer"
                                    >
                                        Confirmer l’offre
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--          CALENDRIER           -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'calendar'" class="flex-1 flex gap-4">
                        <div class="w-1/5 border border-slate-200 rounded-lg bg-slate-50 p-3">
                            <h3 class="text-md font-semibold text-slate-700 mb-2">Equipes</h3>

                            <div v-if="calendarTeams.length" class="max-h-96 overflow-y-auto space-y-1">
                                <button
                                    v-for="t in calendarTeams"
                                    :key="t.id"
                                    type="button"
                                    @click="selectCalendarTeam(t)"
                                    :class="[
                                        'w-full text-left text-sm px-2 py-1 rounded',
                                        calendarTeam && calendarTeam.id === t.id
                                            ? 'bg-teal-100 text-slate-900'
                                            : 'bg-white hover:bg-slate-100 text-slate-700'
                                    ]"
                                >
                                    {{ t.name }}
                                </button>
                            </div>

                            <p v-else class="text-sm text-slate-500">Aucune équipe trouvée.</p>
                        </div>

                        <div class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4">
                            <div v-if="calendarTeam">
                                <h3 class="text-lg font-semibold text-slate-700 mb-2">
                                    Calendrier : {{ calendarTeam.name }}
                                </h3>

                                <p class="text-xs text-slate-500 mb-3">
                                    Un match aller et un match retour contre chaque équipe de la ligue.
                                </p>

                                <div v-if="calendarRows.length" class="max-h-96 overflow-y-auto">
                                    <table class="w-full text-sm text-left">
                                        <thead class="text-xs uppercase text-slate-500 border-b">
                                        <tr>
                                            <th class="py-1 pr-2 text-right">Semaine</th>
                                            <th class="py-1 pr-2">Adversaire</th>
                                            <th class="py-1 pr-2">Lieu</th>
                                            <th class="py-1 pr-2 text-right">Statut</th>
                                            <th class="py-1 pr-2 text-right">Score</th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        <tr v-for="match in calendarRows" :key="match.id" class="border-b last:border-b-0">
                                            <td class="py-1 pr-2 text-right">{{ match.week }}</td>

                                            <td class="py-1 pr-2">
                                                <span v-if="isByeMatch(match)" class="text-slate-400 italic">Repos</span>
                                                <span v-else>{{ opponentNameForTeam(match, calendarTeam.id) }}</span>
                                            </td>

                                            <td class="py-1 pr-2">
                                                <span v-if="isByeMatch(match)" class="text-slate-400">—</span>
                                                <span v-else>{{ match.home_team_id === calendarTeam.id ? 'Domicile' : 'Extérieur' }}</span>
                                            </td>

                                            <td class="py-1 pr-2 text-right">
                                                <span v-if="isByeMatch(match)" class="text-slate-400">Repos</span>
                                                <template v-else>
                                                    <span v-if="match.status === 'scheduled'">À jouer</span>
                                                    <span v-else-if="match.status === 'played'">Joué</span>
                                                    <span v-else>Annulé</span>
                                                </template>
                                            </td>

                                            <td class="py-1 pr-2 text-right">
                                                <span v-if="isByeMatch(match)" class="text-slate-400">-</span>
                                                <span v-else-if="match.status === 'played'">{{ match.home_score }} - {{ match.away_score }}</span>
                                                <span v-else class="text-slate-400">-</span>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <p v-else class="text-sm text-slate-500">Aucun match planifié pour le moment.</p>
                            </div>

                            <p v-else class="text-sm text-slate-500">
                                Sélectionne une équipe dans la liste à gauche.
                            </p>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--          CLASSEMENT           -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'standings'" class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4">
                        <h3 class="text-lg font-semibold text-slate-700 mb-2">Classement de la saison</h3>

                        <p class="text-xs text-slate-500 mb-3">Ligne surlignée = ton équipe contrôlée.</p>

                        <div class="max-h-96 overflow-y-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs uppercase text-slate-500 border-b">
                                <tr>
                                    <th class="py-1 pr-2 text-right">#</th>
                                    <th class="py-1 pr-2">Équipe</th>
                                    <th class="py-1 pr-2 text-right">J</th>
                                    <th class="py-1 pr-2 text-right">V</th>
                                    <th class="py-1 pr-2 text-right">N</th>
                                    <th class="py-1 pr-2 text-right">D</th>
                                    <th class="py-1 pr-2 text-right">Pts</th>
                                </tr>
                                </thead>

                                <tbody>
                                <tr
                                    v-for="(row, index) in standings"
                                    :key="row.id"
                                    :class="[
                                        'border-b last:border-b-0',
                                        team && team.id === row.id ? 'bg-teal-50 font-semibold' : ''
                                    ]"
                                >
                                    <td class="py-1 pr-2 text-right">{{ index + 1 }}</td>
                                    <td class="py-1 pr-2">{{ row.name }}</td>
                                    <td class="py-1 pr-2 text-right">{{ row.played }}</td>
                                    <td class="py-1 pr-2 text-right">{{ row.wins }}</td>
                                    <td class="py-1 pr-2 text-right">{{ row.draws }}</td>
                                    <td class="py-1 pr-2 text-right">{{ row.losses }}</td>
                                    <td class="py-1 pr-2 text-right">{{ row.points }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--         ENTRAÎNEMENT          -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'training'" class="flex-1">
                        <h3 class="text-lg font-semibold text-slate-700 mb-2">Entraînement</h3>
                        <p class="text-sm text-slate-600">Gestion des entraînements, fatigue, progression (à venir).</p>
                    </div>

                    <!-- ============================== -->
                    <!--         CARTES BONUS          -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'cards'" class="flex-1">
                        <h3 class="text-lg font-semibold text-slate-700 mb-2">Cartes bonus</h3>
                        <p class="text-sm text-slate-600">Système de cartes bonus / malus (à venir).</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
