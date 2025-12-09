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
                <div class="basis-3/4 p-4 border border-slate-300 rounded-lg mx-6 bg-white min-h-[500px] flex flex-col">
                    <!-- Infos générales -->
                    <div class="mb-4 flex flex-col lg:flex-row lg:items-center lg:justify-around gap-2">
                        <!-- Période -->
                        <p class="text-slate-600">
                            Période :
                            <span class="font-semibold">{{ periodLabel(gameSave.period) }}</span>
                        </p>

                        <!-- Saison / Semaine -->
                        <p class="text-slate-600">
                            Saison {{ season }} — Semaine {{ week }}
                        </p>

                        <!-- Équipe contrôlée -->
                        <p class="text-slate-600" v-if="team">
                            Équipe contrôlée :
                            <span class="font-semibold">{{ team.name }}</span>
                        </p>
                    </div>

                    <!-- Cartes de dashboard -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 flex-1">
                        <!-- Prochain match (placeholder) -->
                        <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                            <h3 class="text-lg font-semibold text-slate-700 mb-2">
                                Prochain match
                            </h3>
                            <p class="text-sm text-slate-600 mb-2">
                                Le calendrier de la saison sera géré ici (MVP futur).
                            </p>
                            <ul class="text-sm text-slate-700 space-y-1">
                                <li>
                                    <span class="font-semibold">Adversaire :</span>
                                    <span class="text-slate-500">Non défini</span>
                                </li>
                                <li>
                                    <span class="font-semibold">Lieu :</span>
                                    <span class="text-slate-500">À venir</span>
                                </li>
                                <li>
                                    <span class="font-semibold">Contexte :</span>
                                    <span class="text-slate-500">Match amical / J1</span>
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

                        <!-- Résumé d'équipe -->
                        <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                            <h3 class="text-lg font-semibold text-slate-700 mb-2">
                                Résumé du club
                            </h3>

                            <div v-if="team" class="space-y-1 text-sm text-slate-700">
                                <p>
                                    <span class="font-semibold">Nom :</span>
                                    {{ team.name }}
                                </p>
                                <p>
                                    <span class="font-semibold">Budget :</span>
                                    {{ teamBudget }} €
                                </p>
                                <p>
                                    <span class="font-semibold">Bilan :</span>
                                    {{ teamRecord.wins }} V /
                                    {{ teamRecord.draws }} N /
                                    {{ teamRecord.losses }} D
                                </p>
                                <p>
                                    <span class="font-semibold">Joueurs sous contrat :</span>
                                    {{ roster.length }}
                                </p>
                            </div>

                            <div v-else class="text-sm text-slate-500">
                                Aucune équipe liée à cette sauvegarde.
                            </div>
                        </div>

                        <!-- Effectif -->
                        <div class="border border-slate-200 rounded-lg p-4 bg-slate-50 lg:col-span-2">
                            <h3 class="text-lg font-semibold text-slate-700 mb-2">
                                Effectif sous contrat
                            </h3>

                            <div v-if="roster.length > 0" class="max-h-52 overflow-y-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="text-xs uppercase text-slate-500 border-b">
                                    <tr>
                                        <th class="py-1 pr-2">Joueur</th>
                                        <th class="py-1 pr-2">Poste</th>
                                        <th class="py-1 pr-2">Coût / match</th>
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
                                        <td class="py-1 pr-2">
                                            {{ player.cost ?? '-' }}
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-else class="text-sm text-slate-500">
                                Aucun joueur sous contrat pour l'instant.
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>
