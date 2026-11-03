<!-- resources/js/Pages/Players/Index.vue -->
<template>
    <Head title="Joueurs"/>

    <AuthenticatedLayout>
        <template #header>
            <H2>Joueurs</H2>
        </template>

        <!-- SIDEBAR -->
        <MobileSidebar>
            <div class="h-full px-3 py-2 overflow-y-auto bg-slate-700">
                <div class="p-3 mb-3 border-b text-center text-gray-200">
                    <H2>Joueurs</H2>
                </div>

                <!-- Recherche -->
                <div class="mb-2">
                    <form>
                        <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only">
                            Search
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg
                                    class="w-4 h-4 text-gray-500 dark:text-gray-400"
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
                                id="default-search"
                                v-model="searchQuery"
                                class="block w-full p-1 pl-10 text-sm text-gray-900 border border-gray-300 bg-gray-100 focus:ring-blue-500 focus:border-blue-500 rounded"
                                placeholder="Recherche"
                            >
                        </div>
                    </form>
                </div>

                <!-- Liste joueurs -->
                <ul class="space-y-1 font-medium">
                    <li
                        v-for="player in filteredPlayers"
                        :key="player.id"
                        @click="selectPlayer(player)"
                    >
                        <a
                            href="#"
                            :class="{'bg-slate-500': form.selectedPlayerId === player.id}"
                            class="flex items-center p-1 text-gray-100 transition duration-75 rounded-lg hover:bg-slate-500"
                        >
                            <span class="ml-3">
                                {{ player.firstname }} {{ player.lastname }}
                            </span>
                        </a>
                    </li>
                </ul>

                <!-- Bas de sidebar -->
                <ul class="pt-3 mt-2 space-y-1 font-medium border-t border-gray-200">
                    <li class="pt-1 flex">
                        <Link
                            :href="route('players.create')"
                            class="bg-teal-500 hover:bg-teal-600 border border-teal-300 text-white p-1 w-full rounded text-center"
                        >
                            Créer un joueur
                        </Link>
                    </li>
                    <li class="pt-2 flex">
                        <Link
                            :href="route('dataBaseMenu')"
                            class="bg-slate-500 hover:bg-slate-600 border border-slate-300 shadow-gray-100 text-white p-1 w-full rounded text-center"
                        >
                            Retour
                        </Link>
                    </li>
                </ul>
            </div>
        </MobileSidebar>

        <!-- CONTENU PRINCIPAL -->
        <div class="p-4 sm:ml-64">

            <FormContainer>
                <form @submit.prevent="submit">
                    <PlayerFormFields
                        :form="form"
                        stats-key="stats"
                        :positions="positions"
                        :position-labels="positionLabels"
                        show-age
                        :photo-url="photoPath ? `/storage/${photoPath}` : null"
                    />

                    <ButtonGroup>
                        <ButtonPrimary :disabled="form.processing || !form.id">
                            Mettre à jour
                        </ButtonPrimary>

                        <ButtonDanger
                            class="w-36"
                            type="button"
                            :disabled="form.processing || !form.id"
                            @click="deletePlayer"
                        >
                            Supprimer
                        </ButtonDanger>
                    </ButtonGroup>
                </form>
            </FormContainer>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

// Components
import H2 from '@/Components/H2.vue';
import MobileSidebar from '@/Components/MobileSidebar.vue';
import FormContainer from '@/Components/FormContainer.vue';
import ButtonGroup from '@/Components/ButtonGroup.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonDanger from '@/Components/ButtonDanger.vue';
import PlayerFormFields from '@/Components/PlayerFormFields.vue';
import { PLAYER_STATS, defaultPlayerStats } from '@/Enums/playerStats.js';

const props = defineProps({
    players: { type: Array, required: true },
    positions: { type: Array, required: true },
    positionLabels: { type: Object, required: true },
});

const form = useForm({
    selectedPlayerId: null,
    id: null,
    lastname: '',
    firstname: '',
    age: '',
    position: '',
    secondary_positions: [],
    cost: '',
    description: '',
    photo: null, // File
    stats: defaultPlayerStats(),
});

// Photo enregistrée : affichée seulement, jamais renvoyée au serveur.
const photoPath = ref(null);

const searchQuery = ref('');

const filteredPlayers = computed(() => {
    if (!searchQuery.value) return props.players;

    const q = searchQuery.value.toLowerCase();
    return props.players.filter((player) => {
        return (
            (player.firstname ?? '').toLowerCase().includes(q) ||
            (player.lastname ?? '').toLowerCase().includes(q)
        );
    });
});

// ==========================
// SELECT / CRUD
// ==========================

function selectPlayer(player) {
    // reset du fichier (et de son aperçu) quand on change de joueur
    form.photo = null;

    form.selectedPlayerId = player.id;
    form.id               = player.id;
    form.firstname        = player.firstname ?? '';
    form.lastname         = player.lastname ?? '';
    form.age              = player.age ?? '';
    form.position         = player.position ?? '';
    form.secondary_positions = [...(player.secondary_positions ?? [])]; // copie : ne pas muter la prop
    form.cost             = player.cost ?? '';
    form.description      = player.description ?? '';
    photoPath.value       = player.photo_path ?? null;

    // Copie des stats (heading absent = 15, défaut du modèle Player).
    const stats = player.stats || {};
    form.stats = Object.fromEntries(
        PLAYER_STATS.map(({ key }) => [key, Number(stats[key] ?? (key === 'heading' ? 15 : 0))])
    );

    form.clearErrors();
}

function submit() {
    if (!form.id) return;

    form.post(route('players.update', form.id), {
        preserveScroll: true,
        forceFormData: true, // ✅ nécessaire pour envoyer form.photo (File)
        // Recharger le joueur enregistré (photo à jour, fichier remis à zéro).
        onSuccess: () => {
            const fresh = props.players.find(p => p.id === form.id);
            if (fresh) selectPlayer(fresh);
        },
    });
}

function deletePlayer() {
    if (!form.id) return;

    if (!confirm('Voulez-vous vraiment supprimer ce joueur ?')) return;

    form.delete(route('players.destroy', form.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (props.players.length > 0) {
                selectPlayer(props.players[0]);
            } else {
                form.reset();
            }
        },
    });
}

onMounted(() => {
    if (props.players.length > 0) {
        selectPlayer(props.players[0]);
    }
});
</script>
