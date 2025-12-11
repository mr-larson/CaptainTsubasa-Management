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
    gameSave:   { type: Object, required: true }, // GameSave (contient encore la Team de base)
    teams:      { type: Array,  required: true }, // GameTeam[] avec contracts.player
    freePlayers:{ type: Array,  required: true }, // GamePlayer[] sans contrat
    matches:    { type: Array,  required: true }, // GameMatch[] avec home_team / away_team
    controlledTeam: {
        type: Object,           // GameTeam contrôlée dans cette partie
        required: false,
        default: null,
    },
});

// ==========================
//   SAISON / SEMAINE
// ==========================

const season = ref(props.gameSave.season || 1);
const week   = ref(props.gameSave.week   || 1);

// Etat de jeu générique (pour d'autres infos plus tard : blessures, cartes...)
const currentState = ref(props.gameSave.state || {
    match: null,
});

const saving = ref(false);

// ==========================
//   RACCOURCIS PRINCIPAUX
// ==========================

/**
 * Equipe contrôlée = GameTeam (runtime)
 * et non plus la Team "template" de base.
 */
const team = computed(() => props.controlledTeam || null);

/**
 * Effectif = joueurs liés via les game_contracts
 * -> on map sur contracts.player
 */
const roster = computed(() => {
    if (!team.value || !team.value.contracts) return [];
    return team.value.contracts
        .map(c => c.player)
        .filter(p => !!p);
});

/**
 * Bilan simple de l'équipe (GameTeam)
 */
const teamRecord = computed(() => {
    if (!team.value) return { wins: 0, draws: 0, losses: 0 };

    return {
        wins:   team.value.wins   ?? 0,
        draws:  team.value.draws  ?? 0,
        losses: team.value.losses ?? 0,
    };
});

/**
 * Budget de l'équipe dans la PARTIE
 * (GameTeam.budget, modifié par les transferts)
 */
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

/**
 * Liste des matchs de l'équipe contrôlée
 * (home ou away dans GameMatch)
 */
const myMatches = computed(() => {
    if (!team.value) return [];

    return props.matches.filter(
        (m) => m.home_team_id === team.value.id || m.away_team_id === team.value.id
    );
});

/**
 * Prochain match à jouer pour mon équipe
 * (status = scheduled, semaine >= semaine courante)
 */
const nextMatch = computed(() => {
    if (!team.value || !myMatches.value.length) return null;

    const currentWeek = week.value ?? 1;

    const candidates = myMatches.value
        .filter(m => m.status === 'scheduled' && m.week >= currentWeek)
        .sort((a, b) => a.week - b.week);

    return candidates[0] ?? null;
});

/**
 * Infos pratiques sur le prochain match :
 * - domicile / extérieur
 * - nom de l'adversaire
 * - semaine
 */
const nextMatchInfo = computed(() => {
    if (!nextMatch.value || !team.value) return null;

    const isHome   = nextMatch.value.home_team_id === team.value.id;
    const opponent = isHome ? nextMatch.value.away_team : nextMatch.value.home_team;

    return {
        isHome,
        opponentName: opponent?.name ?? 'Adversaire inconnu',
        week: nextMatch.value.week,
    };
});

/**
 * Label lisible pour un match donné (pour la liste)
 */
const matchLabel = (match) => {
    if (!team.value) return '';

    const isHome   = match.home_team_id === team.value.id;
    const opponent = isHome ? match.away_team : match.home_team;

    return `${isHome ? 'Domicile' : 'Extérieur'} vs ${opponent?.name ?? '???'}`;
};

// ==========================
//   AUTRES EQUIPES
// ==========================

/**
 * Toutes les GameTeam de la ligue sauf celle contrôlée
 */
const otherTeams = computed(() => {
    const currentId = team.value?.id ?? null;
    return props.teams.filter(t => t.id !== currentId);
});

const selectedOtherTeamId = ref(null);

/**
 * Equipe sélectionnée dans l’onglet "Autres équipes"
 */
const selectedOtherTeam = computed(() => {
    if (!otherTeams.value.length) return null;

    if (!selectedOtherTeamId.value) {
        return otherTeams.value[0];
    }

    return (
        otherTeams.value.find(t => t.id === selectedOtherTeamId.value) ??
        otherTeams.value[0]
    );
});

/**
 * Effectif de l’équipe sélectionnée (GameTeam + GameContracts)
 */
const selectedOtherTeamRoster = computed(() => {
    if (!selectedOtherTeam.value || !selectedOtherTeam.value.contracts) {
        return [];
    }

    return selectedOtherTeam.value.contracts
        .map(c => c.player)
        .filter(p => !!p);
});

const selectOtherTeam = (t) => {
    selectedOtherTeamId.value = t.id;
};

// ==========================
//   CLASSEMENT
// ==========================

/**
 * Classement général des GameTeam
 * Tri : points desc, victoires desc, nom asc
 */
const standings = computed(() => {
    const list = props.teams.map((t) => {
        const wins   = t.wins   ?? 0;
        const draws  = t.draws  ?? 0;
        const losses = t.losses ?? 0;
        const played = wins + draws + losses;
        const points = wins * 3 + draws;

        return {
            ...t,
            wins,
            draws,
            losses,
            played,
            points,
        };
    });

    list.sort((a, b) => {
        if (b.points !== a.points) return b.points - a.points;
        if (b.wins !== a.wins)     return b.wins - a.wins;
        return a.name.localeCompare(b.name);
    });

    return list;
});

/**
 * Position de ton club dans le classement
 */
const clubStanding = computed(() => {
    if (!team.value) return null;

    const index = standings.value.findIndex(row => row.id === team.value.id);
    if (index === -1) return null;

    return {
        ...standings.value[index],
        position: index + 1,
    };
});

// ==========================
//   BLESSURES / CARTONS
//   (pour la "santé du club")
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

/**
 * Calcule la moyenne d’une stat (attack, defense, stamina, speed)
 * en allant chercher dans :
 * - les colonnes directes si présentes
 * - sinon dans player.stats (JSON)
 */
const averageStat = (key) => {
    if (!roster.value.length) return 0;

    const total = roster.value.reduce((sum, p) => {
        const stats = p.stats ?? {};
        const value = p[key] ?? stats[key] ?? 0;
        return sum + Number(value || 0);
    }, 0);

    return Math.round(total / roster.value.length);
};

const averageAttack  = computed(() => averageStat('attack'));
const averageDefense = computed(() => averageStat('defense'));
const averageStamina = computed(() => averageStat('stamina'));
const averageSpeed   = computed(() => averageStat('speed'));

// ==========================
//   MODAL TRANSFERT
// ==========================

// Signatures déjà effectuées dans le state
const freeAgentSignings = computed(() => {
    return props.gameSave.state?.free_agent_signings ?? [];
});

// Ids des joueurs déjà signés
const signedFreePlayerIds = computed(() =>
    freeAgentSignings.value.map(s => s.player_id)
);

// Joueurs libres encore disponibles (après filtrage)
const freePlayers = computed(() => {
    if (!Array.isArray(props.freePlayers)) {
        return [];
    }

    return props.freePlayers.filter(
        (p) => !signedFreePlayerIds.value.includes(p.id)
    );
});

// État du modal
const showTransferModal = ref(false);
const transferTarget    = ref(null);   // joueur sélectionné
const transferMatches   = ref(10);     // nb de matchs du contrat
const transferSalary    = ref(0);      // coût / match
const transferReason    = ref('');     // raison RP / note

// Total impact budget
const transferTotalCost = computed(() => {
    return (Number(transferMatches.value) || 0) * (Number(transferSalary.value) || 0);
});

// Ouvrir le modal pour un joueur
const openTransferModal = (player) => {
    transferTarget.value  = player;
    transferMatches.value = 10;
    transferSalary.value  = player.cost ?? 0;
    transferReason.value  = '';
    showTransferModal.value = true;
};

// Fermer le modal
const closeTransferModal = () => {
    showTransferModal.value = false;
    transferTarget.value    = null;
};

// Confirmer la signature
const confirmTransfer = () => {
    if (!team.value || !transferTarget.value) {
        return;
    }

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
            onSuccess: () => {
                closeTransferModal();
            }
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

// Sauvegarde de la partie (saison / semaine / state)
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
            onFinish: () => {
                saving.value = false;
            },
        }
    );
};

// Lancer le moteur de match pour le prochain match
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
                <div
                    class="basis-3/4 p-4 border border-slate-300 rounded-lg mx-6 bg-white min-h-[500px] flex flex-col"
                >
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
                    <div
                        v-if="activeTab === 'dashboard'"
                        class="flex-1 flex flex-col"
                    >
                        <!-- Infos générales -->
                        <div
                            class="mb-4 flex flex-col lg:flex-row lg:items-center lg:justify-around gap-2"
                        >
                            <p class="text-slate-600">
                                Période :
                                <span class="font-semibold">
                                    {{ periodLabel(gameSave.period) }}
                                </span>
                            </p>

                            <p class="text-slate-600">
                                Saison {{ season }} — Semaine {{ week }}
                            </p>

                            <p class="text-slate-600" v-if="team">
                                Équipe contrôlée :
                                <span class="font-semibold">
                                    {{ team.name }}
                                </span>
                            </p>
                        </div>

                        <!-- Cartes du dashboard -->
                        <div
                            class="grid grid-cols-1 lg:grid-cols-2 gap-4 flex-1"
                        >
                            <!-- Prochain match -->
                            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                                <h3 class="text-lg font-semibold text-slate-700 mb-2">
                                    Prochain match
                                </h3>

                                <!-- Si un match est planifié -->
                                <div v-if="nextMatch && nextMatchInfo" class="text-sm text-slate-700">
                                    <ul class="space-y-1">
                                        <li>
                                            <span class="font-semibold">Semaine :</span>
                                            {{ nextMatchInfo.week }}
                                        </li>
                                        <li>
                                            <span class="font-semibold">Adversaire :</span>
                                            {{ nextMatchInfo.opponentName }}
                                        </li>
                                        <li>
                                            <span class="font-semibold">Lieu :</span>
                                            {{ nextMatchInfo.isHome ? 'Domicile' : 'Extérieur' }}
                                        </li>
                                        <li>
                                            <span class="font-semibold">Contexte :</span>
                                            Saison {{ season }} — Championnat
                                        </li>
                                    </ul>

                                    <div class="mt-4 flex justify-center">
                                        <button
                                            type="button"
                                            class="w-60 bg-teal-300 hover:bg-teal-400 text-center font-semibold py-1 px-5 border-2 border-teal-500 rounded-full drop-shadow-md mb-2"
                                            @click="playNextMatch"
                                        >
                                            Jouer le prochain match
                                        </button>
                                    </div>
                                </div>

                                <!-- Si aucun match trouvé -->
                                <div v-else class="text-sm text-slate-600">
                                    <p class="mb-2">
                                        Aucun match de championnat planifié pour le moment.
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        Vérifie que le calendrier a bien été généré pour cette sauvegarde.
                                    </p>
                                </div>
                            </div>

                            <!-- Résumé du club -->
                            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                                <h3 class="text-lg font-semibold text-slate-700 mb-2">
                                    Résumé du club
                                </h3>

                                <div v-if="team" class="space-y-2 text-sm text-slate-700">
                                    <p>
                                        <span class="font-semibold">Nom :</span>
                                        {{ team.name }}
                                    </p>

                                    <p>
                                        <span class="font-semibold">Budget :</span>
                                        {{ teamBudget }} €
                                    </p>

                                    <p>
                                        <span class="font-semibold">Joueurs sous contrat :</span>
                                        {{ roster.length }}
                                    </p>

                                    <p v-if="team.description">
                                        <span class="font-semibold">Description :</span>
                                        <span class="text-slate-600">{{ team.description }}</span>
                                    </p>
                                </div>

                                <div v-else class="text-sm text-slate-500">
                                    Aucune équipe liée à cette sauvegarde.
                                </div>
                            </div>

                            <!-- Statut du club -->
                            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50 lg:col-span-2">
                                <h3 class="text-lg font-semibold text-slate-700 mb-2">
                                    Statut du club
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-700">
                                    <!-- Bloc classement -->
                                    <div>
                                        <p class="font-semibold mb-1">Classement</p>
                                        <p v-if="clubStanding">
                                            Place : {{ clubStanding.position }}<sup>e</sup> / {{ standings.length }}
                                        </p>
                                        <p v-else>
                                            Classement non disponible.
                                        </p>
                                        <p class="mt-1">
                                            Bilan : {{ teamRecord.wins }} V /
                                            {{ teamRecord.draws }} N /
                                            {{ teamRecord.losses }} D
                                        </p>
                                        <p>
                                            Matchs joués : {{ teamRecord.wins + teamRecord.draws + teamRecord.losses }}
                                        </p>
                                    </div>

                                    <!-- Bloc forces moyennes -->
                                    <div>
                                        <p class="font-semibold mb-1">Forces moyennes de l’équipe</p>
                                        <p>Attaque : {{ averageAttack }}</p>
                                        <p>Défense : {{ averageDefense }}</p>
                                        <p>Endurance : {{ averageStamina }}</p>
                                        <p>Vitesse : {{ averageSpeed }}</p>
                                    </div>

                                    <!-- Bloc état de l’effectif -->
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
                    <div
                        v-else-if="activeTab === 'my-team'"
                        class="flex-1 flex flex-col gap-4"
                    >
                        <h3 class="text-lg font-semibold text-slate-700">
                            Mon équipe
                        </h3>

                        <div
                            class="border border-slate-200 rounded-lg p-4 bg-slate-50 flex-1"
                        >
                            <h4 class="text-md font-semibold text-slate-700 mb-2">
                                Effectif complet
                            </h4>

                            <div
                                v-if="roster.length > 0"
                                class="max-h-64 overflow-y-auto"
                            >
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left min-w-max">
                                        <thead class="text-xs uppercase text-slate-500 border-b">
                                        <tr>
                                            <th class="py-1 pr-2">Joueur</th>
                                            <th class="py-1 pr-2">Poste</th>

                                            <!-- Core -->
                                            <th class="py-1 pr-2 text-right">Vit</th>
                                            <th class="py-1 pr-2 text-right">End</th>
                                            <th class="py-1 pr-2 text-right">Att</th>
                                            <th class="py-1 pr-2 text-right">Def</th>

                                            <!-- Offensif -->
                                            <th class="py-1 pr-2 text-right">Tir</th>
                                            <th class="py-1 pr-2 text-right">Passe</th>
                                            <th class="py-1 pr-2 text-right">Dribble</th>

                                            <!-- Défensif spé -->
                                            <th class="py-1 pr-2 text-right">Block</th>
                                            <th class="py-1 pr-2 text-right">Interc.</th>
                                            <th class="py-1 pr-2 text-right">Tacle</th>

                                            <!-- Gardien -->
                                            <th class="py-1 pr-2 text-right">Main</th>
                                            <th class="py-1 pr-2 text-right">Poings</th>

                                            <th class="py-1 pr-2 text-right">Coût</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr
                                            v-for="player in roster"
                                            :key="player.id"
                                            class="border-b last:border-b-0"
                                        >
                                            <td class="py-1 pr-2">
                                                {{ player.firstname }} {{ player.lastname }}
                                            </td>

                                            <td class="py-1 pr-2">
                                                {{ player.position }}
                                            </td>

                                            <!-- Core -->
                                            <td class="py-1 pr-2 text-right">
                                                {{ player.speed ?? player.stats?.speed ?? '-' }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ player.stamina ?? player.stats?.stamina ?? '-' }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ player.attack ?? player.stats?.attack ?? '-' }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ player.defense ?? player.stats?.defense ?? '-' }}
                                            </td>

                                            <!-- Offensif -->
                                            <td class="py-1 pr-2 text-right">
                                                {{ player.shot ?? player.stats?.shot ?? '-' }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ player.pass ?? player.stats?.pass ?? '-' }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ player.dribble ?? player.stats?.dribble ?? '-' }}
                                            </td>

                                            <!-- Défensif spé -->
                                            <td class="py-1 pr-2 text-right">
                                                {{ player.block ?? player.stats?.block ?? '-' }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ player.intercept ?? player.stats?.intercept ?? '-' }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ player.tackle ?? player.stats?.tackle ?? '-' }}
                                            </td>

                                            <!-- Gardien -->
                                            <td class="py-1 pr-2 text-right">
                                                {{ player.hand_save ?? player.stats?.hand_save ?? '-' }}
                                            </td>
                                            <td class="py-1 pr-2 text-right">
                                                {{ player.punch_save ?? player.stats?.punch_save ?? '-' }}
                                            </td>

                                            <td class="py-1 pr-2 text-right">
                                                {{ player.cost ?? '-' }}
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div v-else class="text-sm text-slate-500">
                                Aucun joueur.
                            </div>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--        AUTRES ÉQUIPES          -->
                    <!-- ============================== -->
                    <div
                        v-else-if="activeTab === 'other-teams'"
                        class="flex-1 flex gap-4"
                    >
                        <!-- Liste des équipes -->
                        <div class="w-1/5 border border-slate-200 rounded-lg bg-slate-50 p-3">
                            <h3 class="text-md font-semibold text-slate-700 mb-2">
                                Équipes de la ligue
                            </h3>

                            <div
                                v-if="otherTeams.length"
                                class="max-h-80 overflow-y-auto space-y-1"
                            >
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

                            <p v-else class="text-sm text-slate-500">
                                Aucune autre équipe trouvée.
                            </p>
                        </div>

                        <!-- Détail de l'équipe sélectionnée -->
                        <div class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4">
                            <div v-if="selectedOtherTeam">
                                <h3 class="text-lg font-semibold text-slate-700 mb-2">
                                    {{ selectedOtherTeam.name }}
                                </h3>

                                <p class="text-sm text-slate-700 mb-2">
                                    <span class="font-semibold">Budget :</span>
                                    {{ selectedOtherTeam.budget ?? 0 }} €
                                </p>

                                <p class="text-sm text-slate-700 mb-4">
                                    <span class="font-semibold">Bilan :</span>
                                    {{ selectedOtherTeam.wins ?? 0 }} V /
                                    {{ selectedOtherTeam.draws ?? 0 }} N /
                                    {{ selectedOtherTeam.losses ?? 0 }} D
                                </p>

                                <h4 class="text-md font-semibold text-slate-700 mb-2">
                                    Effectif
                                </h4>

                                <div
                                    v-if="selectedOtherTeamRoster.length"
                                    class="max-h-72 overflow-y-auto"
                                >
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm text-left min-w-max">
                                            <thead class="text-xs uppercase text-slate-500 border-b">
                                            <tr>
                                                <th class="py-1 pr-2">Joueur</th>
                                                <th class="py-1 pr-2">Poste</th>

                                                <!-- Core -->
                                                <th class="py-1 pr-2 text-right">Vit</th>
                                                <th class="py-1 pr-2 text-right">End</th>
                                                <th class="py-1 pr-2 text-right">Att</th>
                                                <th class="py-1 pr-2 text-right">Def</th>

                                                <!-- Offensif -->
                                                <th class="py-1 pr-2 text-right">Tir</th>
                                                <th class="py-1 pr-2 text-right">Passe</th>
                                                <th class="py-1 pr-2 text-right">Dribble</th>

                                                <!-- Défensif spé -->
                                                <th class="py-1 pr-2 text-right">Block</th>
                                                <th class="py-1 pr-2 text-right">Interc.</th>
                                                <th class="py-1 pr-2 text-right">Tacle</th>

                                                <!-- Gardien -->
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
                                                    {{ player.firstname }} {{ player.lastname }}
                                                </td>

                                                <td class="py-1 pr-2">
                                                    {{ player.position }}
                                                </td>

                                                <!-- Core -->
                                                <td class="py-1 pr-2 text-right">
                                                    {{ player.speed ?? player.stats?.speed ?? '-' }}
                                                </td>
                                                <td class="py-1 pr-2 text-right">
                                                    {{ player.stamina ?? player.stats?.stamina ?? '-' }}
                                                </td>
                                                <td class="py-1 pr-2 text-right">
                                                    {{ player.attack ?? player.stats?.attack ?? '-' }}
                                                </td>
                                                <td class="py-1 pr-2 text-right">
                                                    {{ player.defense ?? player.stats?.defense ?? '-' }}
                                                </td>

                                                <!-- Offensif -->
                                                <td class="py-1 pr-2 text-right">
                                                    {{ player.shot ?? player.stats?.shot ?? '-' }}
                                                </td>
                                                <td class="py-1 pr-2 text-right">
                                                    {{ player.pass ?? player.stats?.pass ?? '-' }}
                                                </td>
                                                <td class="py-1 pr-2 text-right">
                                                    {{ player.dribble ?? player.stats?.dribble ?? '-' }}
                                                </td>

                                                <!-- Défensif spé -->
                                                <td class="py-1 pr-2 text-right">
                                                    {{ player.block ?? player.stats?.block ?? '-' }}
                                                </td>
                                                <td class="py-1 pr-2 text-right">
                                                    {{ player.intercept ?? player.stats?.intercept ?? '-' }}
                                                </td>
                                                <td class="py-1 pr-2 text-right">
                                                    {{ player.tackle ?? player.stats?.tackle ?? '-' }}
                                                </td>

                                                <!-- Gardien -->
                                                <td class="py-1 pr-2 text-right">
                                                    {{ player.hand_save ?? player.stats?.hand_save ?? '-' }}
                                                </td>
                                                <td class="py-1 pr-2 text-right">
                                                    {{ player.punch_save ?? player.stats?.punch_save ?? '-' }}
                                                </td>

                                                <!-- Coût -->
                                                <td class="py-1 pr-2 text-right">
                                                    {{ player.cost ?? '-' }}
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <p v-else class="text-sm text-slate-500">
                                    Aucun joueur sous contrat pour cette équipe.
                                </p>
                            </div>

                            <div v-else class="text-sm text-slate-500">
                                Sélectionne une équipe dans la liste à gauche.
                            </div>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--          TRANSFERTS           -->
                    <!-- ============================== -->
                    <div
                        v-else-if="activeTab === 'transfers'"
                        class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4 flex flex-col gap-4 relative"
                    >
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-slate-700">
                                Joueurs libres
                            </h3>
                            <p class="text-xs text-slate-500">
                                Les signatures impactent le budget de ton club dans cette sauvegarde.
                            </p>
                        </div>

                        <div v-if="freePlayers.length" class="max-h-96 overflow-y-auto">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left min-w-max">
                                    <thead class="text-xs uppercase text-slate-500 border-b">
                                    <tr>
                                        <th class="py-1 pr-2">Joueur</th>
                                        <th class="py-1 pr-2">Poste</th>

                                        <!-- Core -->
                                        <th class="py-1 pr-2 text-right">Vit</th>
                                        <th class="py-1 pr-2 text-right">End</th>
                                        <th class="py-1 pr-2 text-right">Att</th>
                                        <th class="py-1 pr-2 text-right">Def</th>

                                        <!-- Offensif -->
                                        <th class="py-1 pr-2 text-right">Tir</th>
                                        <th class="py-1 pr-2 text-right">Passe</th>
                                        <th class="py-1 pr-2 text-right">Dribble</th>

                                        <!-- Défensif spé -->
                                        <th class="py-1 pr-2 text-right">Block</th>
                                        <th class="py-1 pr-2 text-right">Interc.</th>
                                        <th class="py-1 pr-2 text-right">Tacle</th>

                                        <!-- Gardien -->
                                        <th class="py-1 pr-2 text-right">Main</th>
                                        <th class="py-1 pr-2 text-right">Poings</th>

                                        <th class="py-1 pr-2 text-right">Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr
                                        v-for="player in freePlayers"
                                        :key="player.id"
                                        class="border-b last:border-b-0"
                                    >
                                        <td class="py-1 pr-2">
                                            {{ player.firstname }} {{ player.lastname }}
                                        </td>
                                        <td class="py-1 pr-2">
                                            {{ player.position }}
                                        </td>

                                        <!-- Core -->
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.speed ?? player.stats?.speed ?? '-' }}
                                        </td>
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.stamina ?? player.stats?.stamina ?? '-' }}
                                        </td>
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.attack ?? player.stats?.attack ?? '-' }}
                                        </td>
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.defense ?? player.stats?.defense ?? '-' }}
                                        </td>

                                        <!-- Offensif -->
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.shot ?? player.stats?.shot ?? '-' }}
                                        </td>
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.pass ?? player.stats?.pass ?? '-' }}
                                        </td>
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.dribble ?? player.stats?.dribble ?? '-' }}
                                        </td>

                                        <!-- Défensif spé -->
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.block ?? player.stats?.block ?? '-' }}
                                        </td>
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.intercept ?? player.stats?.intercept ?? '-' }}
                                        </td>
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.tackle ?? player.stats?.tackle ?? '-' }}
                                        </td>

                                        <!-- Gardien -->
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.hand_save ?? player.stats?.hand_save ?? '-' }}
                                        </td>
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.punch_save ?? player.stats?.punch_save ?? '-' }}
                                        </td>

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

                        <p v-else class="text-sm text-slate-500">
                            Aucun joueur libre disponible.
                        </p>

                        <!-- MODAL TRANSFERT -->
                        <div
                            v-if="showTransferModal && transferTarget"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                        >
                            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-5">
                                <h3 class="text-lg font-semibold text-slate-800 mb-3">
                                    Proposer un contrat à
                                    {{ transferTarget.firstname }} {{ transferTarget.lastname }}
                                </h3>

                                <p class="text-sm text-slate-600 mb-3">
                                    Club : <span class="font-semibold">{{ team?.name }}</span><br>
                                    Budget actuel : <span class="font-semibold">{{ teamBudget }} €</span>
                                </p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                                            Nombre de matchs (durée du contrat)
                                        </label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="60"
                                            v-model.number="transferMatches"
                                            class="w-full border border-slate-300 rounded-md px-2 py-1 text-sm"
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                                            Coût par match (€)
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            v-model.number="transferSalary"
                                            class="w-full border border-slate-300 rounded-md px-2 py-1 text-sm"
                                        >
                                    </div>
                                </div>

                                <p class="text-sm text-slate-700 mb-3">
                                    Coût total estimé :
                                    <span class="font-semibold">{{ transferTotalCost }} €</span>
                                </p>

                                <div class="mb-4">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                                        Raison du recrutement (note RP / mémo)
                                    </label>
                                    <textarea
                                        rows="3"
                                        v-model="transferReason"
                                        class="w-full border border-slate-300 rounded-md px-2 py-1 text-sm"
                                        placeholder="Ex. : Renforcer l’aile gauche, remplacer un blessé, jeune à fort potentiel..."
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
                    <div
                        v-else-if="activeTab === 'calendar'"
                        class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4"
                    >
                        <h3 class="text-lg font-semibold text-slate-700 mb-2">
                            Calendrier de la saison
                        </h3>

                        <p class="text-xs text-slate-500 mb-3">
                            Un match aller et un match retour contre chaque équipe de la ligue.
                        </p>

                        <div v-if="myMatches.length" class="max-h-96 overflow-y-auto">
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
                                <tr
                                    v-for="match in myMatches"
                                    :key="match.id"
                                    class="border-b last:border-b-0"
                                >
                                    <td class="py-1 pr-2 text-right">
                                        {{ match.week }}
                                    </td>

                                    <td class="py-1 pr-2">
                                        {{
                                            (team && match.home_team_id === team.id)
                                                ? (match.away_team ? match.away_team.name : '???')
                                                : (match.home_team ? match.home_team.name : '???')
                                        }}
                                    </td>

                                    <td class="py-1 pr-2">
                                        {{ team && match.home_team_id === team.id ? 'Domicile' : 'Extérieur' }}
                                    </td>

                                    <td class="py-1 pr-2 text-right">
                                        <span v-if="match.status === 'scheduled'">À jouer</span>
                                        <span v-else-if="match.status === 'played'">Joué</span>
                                        <span v-else>Annulé</span>
                                    </td>

                                    <td class="py-1 pr-2 text-right">
                                        <span v-if="match.status === 'played'">
                                            {{ match.home_score }} - {{ match.away_score }}
                                        </span>
                                        <span v-else class="text-slate-400">-</span>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <p v-else class="text-sm text-slate-500">
                            Aucun match planifié pour le moment.
                        </p>
                    </div>

                    <!-- ============================== -->
                    <!--          CLASSEMENT           -->
                    <!-- ============================== -->
                    <div
                        v-else-if="activeTab === 'standings'"
                        class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4"
                    >
                        <h3 class="text-lg font-semibold text-slate-700 mb-2">
                            Classement de la saison
                        </h3>

                        <p class="text-xs text-slate-500 mb-3">
                            Ligne surlignée = ton équipe contrôlée.
                        </p>

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
                                        team && team.id === row.id
                                            ? 'bg-teal-50 font-semibold'
                                            : ''
                                    ]"
                                >
                                    <td class="py-1 pr-2 text-right">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="py-1 pr-2">
                                        {{ row.name }}
                                    </td>
                                    <td class="py-1 pr-2 text-right">
                                        {{ row.played }}
                                    </td>
                                    <td class="py-1 pr-2 text-right">
                                        {{ row.wins }}
                                    </td>
                                    <td class="py-1 pr-2 text-right">
                                        {{ row.draws }}
                                    </td>
                                    <td class="py-1 pr-2 text-right">
                                        {{ row.losses }}
                                    </td>
                                    <td class="py-1 pr-2 text-right">
                                        {{ row.points }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--         ENTRAÎNEMENT          -->
                    <!-- ============================== -->
                    <div
                        v-else-if="activeTab === 'training'"
                        class="flex-1"
                    >
                        <h3 class="text-lg font-semibold text-slate-700 mb-2">
                            Entraînement
                        </h3>
                        <p class="text-sm text-slate-600">
                            Gestion des entraînements, fatigue, progression
                            (à venir).
                        </p>
                    </div>

                    <!-- ============================== -->
                    <!--         CARTES BONUS          -->
                    <!-- ============================== -->
                    <div v-else-if="activeTab === 'cards'" class="flex-1">
                        <h3 class="text-lg font-semibold text-slate-700 mb-2">
                            Cartes bonus
                        </h3>
                        <p class="text-sm text-slate-600">
                            Système de cartes bonus / malus (à venir).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

