<!-- resources/js/Pages/GameSaves/TeamSelection.vue (ou ton fichier actuel) -->
<template>
    <Head title="Choix de l'équipe" />

    <AuthenticatedLayout>
        <template #header></template>

        <!-- SIDEBAR : liste d'équipes -->
        <aside
            id="separator-sidebar"
            class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0"
            aria-label="Sidebar"
        >
            <div class="h-full px-3 py-2 overflow-y-auto bg-slate-700">
                <div class="p-3 pb-5 mb-3 border-b text-center text-gray-200">
                    <H2>Équipes</H2>
                </div>

                <!-- Recherche -->
                <div class="mb-2">
                    <form>
                        <label for="team-search" class="mb-2 text-sm font-medium text-gray-900 sr-only">
                            Search
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg
                                    class="w-4 h-4 text-gray-500"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        stroke="currentColor"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                                    />
                                </svg>
                            </div>
                            <input
                                type="search"
                                id="team-search"
                                v-model="searchQuery"
                                class="block w-full p-1 pl-10 text-sm text-gray-900 border border-gray-300 bg-gray-100 focus:ring-blue-500 focus:border-blue-500 rounded"
                                placeholder="Recherche"
                            >
                        </div>
                    </form>
                </div>

                <!-- Liste d'équipes -->
                <ul class="space-y-1 font-medium">
                    <li
                        v-for="team in filteredTeams"
                        :key="team.id"
                        @click="selectTeam(team)"
                    >
                        <a
                            href="#"
                            :class="{'bg-slate-500': startForm.team_id === team.id}"
                            class="flex items-center gap-2 p-1 text-gray-100 transition duration-75 rounded-lg hover:bg-slate-500"
                        >
                            <!-- ✅ mini logo -->
                            <div class="h-7 w-7 rounded border border-slate-500 bg-slate-800 overflow-hidden flex items-center justify-center shrink-0">
                                <img
                                    v-if="teamLogoUrl(team)"
                                    :src="teamLogoUrl(team)"
                                    class="h-full w-full object-cover"
                                    alt="Logo équipe"
                                />
                                <span v-else class="text-[10px] text-slate-400">—</span>
                            </div>

                            <span class="truncate">{{ team.name }}</span>
                        </a>
                    </li>
                </ul>

                <!-- Actions bas -->
                <ul class="pt-3 mt-2 space-y-1 font-medium border-t border-gray-200">
                    <li class="pt-2 flex">
                        <Link
                            :href="route('mainMenu')"
                            class="bg-slate-500 hover:bg-slate-600 border border-slate-300 text-white p-1 w-full rounded text-center"
                        >
                            Retour
                        </Link>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- CONTENU PRINCIPAL -->
        <div class="p-4 sm:ml-64">
            <H1>Choix de l'équipe de départ</H1>

            <p class="text-slate-600 mb-4">
                Partie : <strong>{{ label || 'Sans nom' }}</strong> — Période : <strong>Collège</strong>
            </p>

            <!-- 1/4 - 3/4 sur desktop -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <!-- Carte détails équipe (1/4) -->
                <div class="border border-slate-300 rounded-lg bg-white p-4 min-h-[260px] lg:col-span-1">
                    <h2 class="text-xl font-semibold text-slate-700 mb-3">
                        Détails de l'équipe
                    </h2>

                    <div v-if="selectedTeam">
                        <!-- ✅ logo + nom -->
                        <div class="flex items-center gap-3 mb-3">
                            <div class="h-16 w-16 rounded overflow-hidden flex items-center justify-center ">
                                <img
                                    v-if="teamLogoUrl(selectedTeam)"
                                    :src="teamLogoUrl(selectedTeam)"
                                    class="h-full w-full object-cover"
                                    alt="Logo équipe"
                                />
                                <span v-else class="text-xs text-slate-400">Aucun</span>
                            </div>

                            <div class="min-w-0">
                                <p class="text-slate-700">
                                    <span class="font-semibold">Nom :</span> {{ selectedTeam.name }}
                                </p>
                                <p class="text-slate-700">
                                    <span class="font-semibold">Budget :</span>
                                    {{ selectedTeam.budget ?? 0 }}
                                </p>
                            </div>
                        </div>

                        <p class="text-slate-700">
                            <span class="font-semibold">Bilan :</span>
                            {{ selectedTeam.wins ?? 0 }} V /
                            {{ selectedTeam.draws ?? 0 }} N /
                            {{ selectedTeam.losses ?? 0 }} D
                        </p>
                        <p class="text-slate-700">
                            <span class="font-semibold">Joueurs sous contrat :</span>
                            {{ roster.length }}
                        </p>
                        <p class="text-slate-700">
                            <span class="font-semibold">Description :</span>
                            {{ selectedTeam.description ?? '-'  }}
                        </p>
                    </div>

                    <div v-else class="text-slate-500">
                        Sélectionne une équipe dans la colonne de gauche.
                    </div>
                </div>

                <!-- Carte joueurs sous contrat (3/4) -->
                <div class="border border-slate-300 rounded-lg bg-white p-4 min-h-[360px] lg:col-span-3">
                    <h2 class="text-xl font-semibold text-slate-700 mb-3">
                        Joueurs sous contrat
                    </h2>

                    <div v-if="selectedTeam && roster.length > 0">
                        <div class="max-h-96 overflow-y-auto">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left min-w-max">
                                    <thead class="text-xs uppercase text-slate-500 border-b">
                                    <tr>
                                        <th class="py-1 pr-2">Photo</th>
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
                                        v-for="player in roster"
                                        :key="player.id"
                                        class="border-b last:border-b-0"
                                    >
                                        <td class="py-1 pr-2">
                                            <div class="h-10 w-10 rounded border bg-white overflow-hidden flex items-center justify-center">
                                                <img
                                                    v-if="playerPhotoUrl(player)"
                                                    :src="playerPhotoUrl(player)"
                                                    class="h-full w-full object-cover"
                                                    alt="Photo joueur"
                                                />
                                                <span v-else class="text-[10px] text-slate-400">—</span>
                                            </div>
                                        </td>

                                        <td class="py-1 pr-2">
                                            {{ player.firstname }} {{ player.lastname }}
                                        </td>
                                        <td class="py-1 pr-2">
                                            {{ player.position }}
                                        </td>

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

                                        <td class="py-1 pr-2 text-right">
                                            {{ player.shot ?? player.stats?.shot ?? '-' }}
                                        </td>
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.pass ?? player.stats?.pass ?? '-' }}
                                        </td>
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.dribble ?? player.stats?.dribble ?? '-' }}
                                        </td>

                                        <td class="py-1 pr-2 text-right">
                                            {{ player.block ?? player.stats?.block ?? '-' }}
                                        </td>
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.intercept ?? player.stats?.intercept ?? '-' }}
                                        </td>
                                        <td class="py-1 pr-2 text-right">
                                            {{ player.tackle ?? player.stats?.tackle ?? '-' }}
                                        </td>

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
                    </div>

                    <div v-else-if="selectedTeam" class="text-slate-500">
                        Aucun joueur avec contrat pour cette équipe.
                    </div>

                    <div v-else class="text-slate-500">
                        Sélectionne une équipe pour voir les joueurs.
                    </div>
                </div>
            </div>

            <!-- Boutons de choix de mode -->
            <div class="flex flex-wrap justify-center gap-4 mt-6">
                <button
                    type="button"
                    class="w-52 bg-cyan-300 hover:bg-cyan-400 text-center font-semibold py-2 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md disabled:opacity-50"
                    :disabled="!startForm.team_id || startForm.processing"
                    @click="startWithTeam"
                >
                    Sélectionner
                </button>

                <button
                    type="button"
                    class="w-52 bg-fuchsia-300 text-center font-semibold py-2 px-5 border-2 border-fuchsia-500 rounded-full drop-shadow-md opacity-60 cursor-not-allowed"
                    disabled
                >
                    Mode Draft (bientôt)
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import H2 from '@/Components/H2.vue';
import H1 from '@/Components/H1.vue';

const props = defineProps({
    label: { type: String, default: null },
    period: { type: String, required: true },
    teams: { type: Array, required: true },
});

const searchQuery = ref('');
const selectedTeam = ref(null);

const filteredTeams = computed(() => {
    if (!searchQuery.value) return props.teams;
    return props.teams.filter(team =>
        team.name?.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const startForm = useForm({
    label: props.label || '',
    period: props.period,
    team_id: null,
});

const roster = computed(() => {
    if (!selectedTeam.value || !selectedTeam.value.contracts) return [];
    return selectedTeam.value.contracts
        .map(c => c.player)
        .filter(p => !!p);
});

function selectTeam(team) {
    selectedTeam.value = team;
    startForm.team_id = team.id;
}

function startWithTeam() {
    if (!startForm.team_id) return;

    startForm.post(route('game-saves.start'), {
        preserveScroll: true,
    });
}

/**
 * ✅ LOGO TEAM
 * team.logo_path est stocké en "images/teams/xxx.webp"
 * -> accessible via "/images/teams/xxx.webp"
 */
const teamLogoUrl = (team) => {
    if (!team?.logo_path) return null;
    return `/${team.logo_path}`;
};

const playerPhotoUrl = (player) => {
    if (player?.photo_path) return `/storage/${player.photo_path}`;
    if (player?.photo?.path) return `/storage/${player.photo.path}`;
    return null;
};

const label = props.label;
</script>
