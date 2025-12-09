<template>
    <Head title="Players"/>

    <AuthenticatedLayout>
        <template #header>
            <H2>Players</H2>
        </template>

        <!-- SIDEBAR -->
        <aside
            id="separator-sidebar"
            class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0"
            aria-label="Sidebar"
        >
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
        </aside>

        <!-- CONTENU PRINCIPAL -->
        <div class="p-4 sm:ml-64">
            <H1>Editions</H1>

            <FormContainer>
                <form @submit.prevent="submit">
                    <!-- Ligne 1 : prénom / nom -->
                    <FormRaw>
                        <FormCol>
                            <InputLabel for="firstname" value="Prénom" />
                            <InputText
                                id="firstname"
                                type="text"
                                class="mt-1 w-full"
                                v-model="form.firstname"
                                autocomplete="given-name"
                            />
                            <p v-if="form.errors.firstname" class="text-sm text-red-600 mt-1">
                                {{ form.errors.firstname }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <InputLabel for="lastname" value="Nom" />
                            <InputText
                                id="lastname"
                                type="text"
                                class="mt-1 w-full"
                                v-model="form.lastname"
                                autocomplete="family-name"
                            />
                            <p v-if="form.errors.lastname" class="text-sm text-red-600 mt-1">
                                {{ form.errors.lastname }}
                            </p>
                        </FormCol>
                    </FormRaw>

                    <!-- Ligne 2 : âge / position -->
                    <FormRaw>
                        <FormCol>
                            <InputLabel for="age" value="Âge" />
                            <InputText
                                id="age"
                                type="number"
                                class="mt-1 w-full"
                                v-model="form.age"
                            />
                            <p v-if="form.errors.age" class="text-sm text-red-600 mt-1">
                                {{ form.errors.age }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <InputLabel for="position" value="Position" />
                            <InputSelect
                                id="position"
                                v-model="form.position"
                                class="mt-1"
                                required
                            >
                                <option
                                    v-for="pos in positions"
                                    :key="pos"
                                    :value="pos"
                                >
                                    {{ positionLabels[pos] ?? pos }}
                                </option>
                            </InputSelect>
                        </FormCol>
                    </FormRaw>

                    <!-- Ligne 3 : coût -->
                    <FormRaw>
                        <FormCol>
                            <InputLabel for="cost" value="Coût" />
                            <InputText
                                id="cost"
                                type="number"
                                class="mt-1 w-full"
                                v-model="form.cost"
                            />
                            <p v-if="form.errors.cost" class="text-sm text-red-600 mt-1">
                                {{ form.errors.cost }}
                            </p>
                        </FormCol>
                    </FormRaw>

                    <!-- Ligne 4 : stats attaque / défense -->
                    <FormRaw>
                        <FormCol>
                            <label
                                for="stats.attack"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Attaque
                            </label>
                            <input
                                type="number"
                                id="stats.attack"
                                v-model="form.stats.attack"
                                placeholder="Attaque"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.attack']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.attack'] }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <label
                                for="stats.defender"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Défense
                            </label>
                            <input
                                type="number"
                                id="stats.defender"
                                v-model="form.stats.defender"
                                placeholder="Défense"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.defender']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.defender'] }}
                            </p>
                        </FormCol>
                    </FormRaw>

                    <!-- Ligne 5 : stats speed / stamina -->
                    <FormRaw>
                        <FormCol>
                            <label
                                for="stats.speed"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Vitesse
                            </label>
                            <input
                                type="number"
                                id="stats.speed"
                                v-model="form.stats.speed"
                                placeholder="Vitesse"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.speed']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.speed'] }}
                            </p>
                        </FormCol>

                        <FormCol>
                            <label
                                for="stats.stamina"
                                class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4"
                            >
                                Stamina
                            </label>
                            <input
                                type="number"
                                id="stats.stamina"
                                v-model="form.stats.stamina"
                                placeholder="Stamina"
                                class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300"
                            >
                            <p v-if="form.errors['stats.stamina']" class="text-sm text-red-600 mt-1">
                                {{ form.errors['stats.stamina'] }}
                            </p>
                        </FormCol>
                    </FormRaw>

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
import { ref, defineProps, computed, onMounted } from 'vue';

// Components
import H2 from '@/Components/H2.vue';
import H1 from '@/Components/H1.vue';
import FormContainer from '@/Components/FormContainer.vue';
import FormCol from '@/Components/FormCol.vue';
import FormRaw from '@/Components/FormRaw.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputText from '@/Components/InputText.vue';
import ButtonGroup from '@/Components/ButtonGroup.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonDanger from '@/Components/ButtonDanger.vue';
import InputSelect from "@/Components/InputSelect.vue";

const props = defineProps({
    players: {
        type: Array,
        required: true,
    },
    positions: {
        type: Array,
        required: true,
    },
    positionLabels: {
        type: Object,
        required: true,
    },
});


const form = useForm({
    selectedPlayerId: null,
    id: null,
    lastname: '',
    firstname: '',
    age: '',
    position: '',
    cost: '',
    stats: {
        speed: 0,
        attack: 0,
        defender: 0,
        stamina: 0,
    },
});

const searchQuery = ref('');

const filteredPlayers = computed(() => {
    if (!searchQuery.value) return props.players;

    return props.players.filter((player) => {
        const q = searchQuery.value.toLowerCase();
        return (
            player.firstname.toLowerCase().includes(q) ||
            player.lastname.toLowerCase().includes(q)
        );
    });
});

function selectPlayer(player) {
    form.selectedPlayerId = player.id;
    form.id               = player.id;
    form.firstname        = player.firstname ?? '';
    form.lastname         = player.lastname ?? '';
    form.age              = player.age ?? '';
    form.position         = player.position ?? '';
    form.cost             = player.cost ?? '';

    // stats peut être null en DB → on sécurise
    const stats = player.stats || {
        speed: 0,
        attack: 0,
        defender: 0,
        stamina: 0,
    };

    form.stats = {
        speed: Number(stats.speed ?? 0),
        attack: Number(stats.attack ?? 0),
        defender: Number(stats.defender ?? 0),
        stamina: Number(stats.stamina ?? 0),
    };

    form.clearErrors();
}

function submit() {
    if (!form.id) return;

    // Ton route.php utilise POST pour update : Route::post('/players/{player}', 'update')
    form.post(route('players.update', form.id), {
        preserveScroll: true,
    });
}

function deletePlayer() {
    if (!form.id) return;

    if (!confirm('Voulez-vous vraiment supprimer ce joueur ?')) {
        return;
    }

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
