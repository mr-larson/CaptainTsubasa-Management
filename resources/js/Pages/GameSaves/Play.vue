<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import H2 from '@/Components/H2.vue';

const props = defineProps({
    gameSave: {
        type: Object,
        required: true,
    },
    teams: {
        type: Array,
        required: true,
    },
    freePlayers: {
        type: Array,
        required: true,
    },
});

// Saison / semaine
const season = ref(props.gameSave.season || 1);
const week   = ref(props.gameSave.week || 1);

// État de jeu MVP (à enrichir plus tard)
const currentState = ref(props.gameSave.state || {
    match: null,
});

const saving = ref(false);

// Raccourcis
const team = computed(() => props.gameSave.team || null);
const roster = computed(() => {
    if (!team.value || !team.value.contracts) return [];
    return team.value.contracts
        .map(c => c.player)
        .filter(p => !!p);
});

const teamRecord = computed(() => {
    if (!team.value) return { wins: 0, draws: 0, losses: 0 };
    return {
        wins: team.value.wins ?? 0,
        draws: team.value.draws ?? 0,
        losses: team.value.losses ?? 0,
    };
});

const teamBudget = computed(() => team.value?.budget ?? 0);

// 🔹 Onglets
const tabs = [
    { key: 'dashboard', label: 'Dashboard' },
    { key: 'my-team', label: 'Mon équipe' },
    { key: 'other-teams', label: 'Autres équipes' },
    { key: 'transfers',  label: 'Transferts' },
    { key: 'calendar', label: 'Calendrier' },
    { key: 'standings', label: 'Classement' },
    { key: 'training', label: 'Entraînement' },
    { key: 'cards', label: 'Cartes bonus' },
];

const activeTab = ref('dashboard');

// 🔹 Autres équipes (toutes sauf celle contrôlée)
const otherTeams = computed(() => {
    const currentId = team.value?.id ?? null;
    return props.teams.filter(t => t.id !== currentId);
});

const selectedOtherTeamId = ref(null);

const selectedOtherTeam = computed(() => {
    if (!otherTeams.value.length) return null;

    // si rien n'est sélectionné, on prend la première
    if (!selectedOtherTeamId.value) {
        return otherTeams.value[0];
    }

    return (
        otherTeams.value.find(t => t.id === selectedOtherTeamId.value) ??
        otherTeams.value[0]
    );
});

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

const freeAgentSignings = computed(() => {
    return (props.gameSave.state?.free_agent_signings ?? []);
});

// Ids des joueurs déjà signés
const signedFreePlayerIds = computed(() =>
    freeAgentSignings.value.map(s => s.player_id)
);

// Joueurs libres encore disponibles
const availableFreePlayers = computed(() => {
    if (!Array.isArray(props.freePlayers)) {
        return [];
    }

    return props.freePlayers.filter(
        (p) => !signedFreePlayerIds.value.includes(p.id)
    );
});



// 🔹 Classement des équipes
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

    // Tri : points desc, puis victoires, puis nom
    list.sort((a, b) => {
        if (b.points !== a.points) return b.points - a.points;
        if (b.wins !== a.wins)     return b.wins - a.wins;
        return a.name.localeCompare(b.name);
    });

    return list;
});

// Position de ton club dans le classement
const clubStanding = computed(() => {
    if (!team.value) return null;

    const index = standings.value.findIndex(row => row.id === team.value.id);
    if (index === -1) return null;

    return {
        ...standings.value[index],
        position: index + 1,
    };
});

// Blessés / suspensions / cartons (pour plus tard, lis depuis state)
const injuries = computed(() => props.gameSave.state?.injuries ?? []);
const suspensions = computed(() => props.gameSave.state?.suspensions ?? []);
const cards = computed(() => props.gameSave.state?.cards ?? []);

// Compteurs simples
const injuriesCount = computed(() => injuries.value.length);
const suspensionsCount = computed(() => suspensions.value.length);
const cardsCount = computed(() => cards.value.length);

// Moyennes simples d'attaque / défense / endurance / vitesse
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


const periodLabel = (period) => {
    if (period === 'college') return 'Collège';
    if (period === 'highschool') return 'Lycée';
    if (period === 'pro') return 'Professionnel';
    return period;
};

const saveGame = () => {
    saving.value = true;

    router.put(
        route('game-saves.update', props.gameSave.id),
        {
            label: props.gameSave.label,
            season: season.value,
            week: week.value,
            state: currentState.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                saving.value = false;
            },
        }
    );
};

// Bouton "Jouer le prochain match"
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
                                        ? 'border-emerald-500 text-slate-900 bg-slate-50'
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
                            <div
                                class="border border-slate-200 rounded-lg p-4 bg-slate-50"
                            >
                                <h3
                                    class="text-lg font-semibold text-slate-700 mb-2"
                                >
                                    Prochain match
                                </h3>
                                <p class="text-sm text-slate-600 mb-2">
                                    Le calendrier de la saison sera géré ici
                                    (MVP futur).
                                </p>
                                <ul class="text-sm text-slate-700 space-y-1">
                                    <li>
                                        <span class="font-semibold">
                                            Adversaire :
                                        </span>
                                        <span class="text-slate-500">
                                            Non défini
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">Lieu :</span>
                                        <span class="text-slate-500">
                                            À venir
                                        </span>
                                    </li>
                                    <li>
                                        <span class="font-semibold">
                                            Contexte :
                                        </span>
                                        <span class="text-slate-500">
                                            Match amical / J1
                                        </span>
                                    </li>
                                </ul>

                                <div class="mt-4 flex justify-center">
                                    <button
                                        type="button"
                                        class="w-60 bg-emerald-300 hover:bg-emerald-400 text-center font-semibold py-1 px-5 border-2 border-emerald-500 rounded-full drop-shadow-md mb-2"
                                        @click="playNextMatch"
                                    >
                                        Jouer le prochain match
                                    </button>
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
                                class="w-40 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-2 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md mb-2 disabled:opacity-50"
                                :disabled="saving"
                                @click="saveGame"
                            >
                                {{ saving ? 'Sauvegarde...' : 'Sauvegarder' }}
                            </button>

                            <button
                                type="button"
                                class="w-40 bg-slate-300 hover:bg-slate-400 text-center font-semibold py-2 px-5 border-2 border-slate-500 rounded-full drop-shadow-md mb-2"
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

                        <!-- Formation -->
                        <div
                            class="border border-slate-200 rounded-lg p-4 bg-slate-50 mb-2"
                        >
                            <p class="text-sm text-slate-600 mb-2">
                                Ici s’affichera la formation (terrain + postes +
                                drag & drop).
                            </p>
                        </div>

                        <!-- Joueurs -->
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

                                            <th class="py-1 pr-2 text-right">Coût / match</th>
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
                        ? 'bg-emerald-100 text-slate-900'
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
                        class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4 flex flex-col gap-4"
                    >
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-slate-700">
                                Joueurs libres
                            </h3>
                            <p class="text-xs text-slate-500">
                                Les signatures sont propres à cette sauvegarde de partie.
                            </p>
                        </div>

                        <div v-if="availableFreePlayers.length" class="max-h-96 overflow-y-auto">
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
                                        v-for="player in availableFreePlayers"
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
                                            <form
                                                method="post"
                                                :action="route('game-saves.free-agents.sign', { gameSave: gameSave.id, player: player.id })"
                                                @submit.prevent="
                                    $inertia.post(
                                        route('game-saves.free-agents.sign', { gameSave: gameSave.id, player: player.id }),
                                        {
                                            team_id: team?.id,
                                            salary: null,
                                        }
                                    )
                                "
                                            >
                                                <button
                                                    type="submit"
                                                    class="text-xs px-3 py-0.5 rounded-full border border-emerald-500 bg-emerald-100 hover:bg-emerald-200 font-semibold"
                                                    :disabled="!team"
                                                >
                                                    Offre
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <p v-else class="text-sm text-slate-500">
                            Aucun joueur libre disponible.
                        </p>

                        <!-- Liste des signatures déjà réalisées dans cette gameSave -->
                        <div v-if="freeAgentSignings.length" class="mt-4">
                            <h4 class="text-md font-semibold text-slate-700 mb-1">
                                Joueurs déjà signés dans cette partie
                            </h4>
                            <ul class="text-sm text-slate-600 list-disc list-inside">
                                <li
                                    v-for="signing in freeAgentSignings"
                                    :key="signing.player_id"
                                >
                                    Joueur #{{ signing.player_id }} → équipe #{{ signing.team_id }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- ============================== -->
                    <!--          CALENDRIER           -->
                    <!-- ============================== -->
                    <div
                        v-else-if="activeTab === 'calendar'"
                        class="flex-1"
                    >
                        <h3 class="text-lg font-semibold text-slate-700 mb-2">
                            Calendrier
                        </h3>
                        <p class="text-sm text-slate-600">
                            Affichage du calendrier de la saison (à venir).
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
                            ? 'bg-emerald-50 font-semibold'
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

