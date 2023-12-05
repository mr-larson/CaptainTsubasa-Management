<template>
    <Head title="Players"/>

    <AuthenticatedLayout>
        <template #header>
            <H2>Players</H2>
        </template>

        <aside id="separator-sidebar"
               class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0"
               aria-label="Sidebar">
            <div class="h-full px-3 py-2 overflow-y-auto bg-slate-700">
                <div class="p-3 mb-3 border-b text-center text-gray-200">
                    <H2>Joueurs</H2>
                </div>
                <div class="mb-2">
                    <form>
                        <label for="default-search"
                               class="mb-2 text-sm font-medium text-gray-900 sr-only">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                          stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="search" id="default-search" v-model="searchQuery"
                                   class="block w-full p-1 pl-10 text-sm text-gray-900 border border-gray-300  bg-gray-100 focus:ring-blue-500 focus:border-blue-500 rounded"
                                   placeholder="Recherche" required>
                        </div>
                    </form>
                </div>
                <ul class="space-y-1 font-medium">
                    <li v-for="player in filteredPlayers" :key="player.id" @click="selectPlayer(player)">
                        <a href="#"
                           :class="{'bg-slate-500': form.selectedPlayerId === player.id}"
                           class="flex items-center p-1 text-gray-100 transition duration-75 rounded-lg hover:bg-slate-500">
                            <span class="ml-3">{{ player.firstname }} {{ player.lastname }}</span>
                        </a>
                    </li>
                </ul>
                <ul class="pt-3 mt-2 space-y-1 font-medium border-t border-gray-200">
                    <li class="pt-1 flex">
                        <Link :href="route('players.create')"
                              class="bg-teal-500 hover:bg-teal-600 border border-teal-300 text-white p-1 w-full rounded text-center">
                            Créer une joueur
                        </Link>
                    </li>
                    <li class="pt-2 flex">
                        <Link :href="route('dataBaseMenu')"
                              class="bg-slate-500 hover:bg-slate-600 border border-slate-300 shadow-gray-100	text-white p-1 w-full rounded text-center">
                            Retour
                        </Link>
                    </li>
                </ul>
            </div>
        </aside>

        <div class="p-4 sm:ml-64">
            <H1>Editions</H1>
            <FormContainer>
                <form @submit.prevent="submit" enctype="multipart/form-data">
                    <FormRaw>
                        <FormCol>
                            <InputLabel for="firstname" value="prénom" />
                            <InputText
                                id="firstname"
                                type="text"
                                class="mt-1  w-full"
                                v-model="form.firstname"
                                required
                                autofocus
                                autocomplete="firstname"
                            />
                        </FormCol>
                        <FormCol>
                            <InputLabel for="lastname" value="nom" />
                            <InputText
                                id="lastname"
                                type="text"
                                class="mt-1  w-full"
                                v-model="form.lastname"
                                required
                                autofocus
                                autocomplete="lastname"
                            />
                        </FormCol>
                    </FormRaw>
                    <FormRaw>
                        <FormCol>
                            <InputLabel for="age" value="age" />
                            <InputText
                                id="age"
                                type="text"
                                class="mt-1  w-full"
                                v-model="form.age"
                                required
                                autofocus
                                autocomplete="age"
                            />
                        </FormCol>
                        <FormCol>
                            <InputLabel for="position" value="position" />
                            <InputText
                                id="position"
                                type="text"
                                class="mt-1  w-full"
                                v-model="form.position"
                                required
                                autofocus
                                autocomplete="position"
                            />
                        </FormCol>
                    </FormRaw>
                    <FormRaw>
                        <FormCol>
                            <InputLabel for="cost" value="cost" />
                            <InputText
                                id="cost"
                                type="number"
                                class="mt-1  w-full"
                                v-model="form.cost"
                                required
                                autofocus
                                autocomplete="cost"
                            />
                        </FormCol>
                    </FormRaw>
                    <FormRaw>
                        <FormCol>
                            <label for="stats.attack"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Attaque</label>
                            <input type="number" id="stats.attack" v-model="form.stats.attack" placeholder="Attaque" required
                                    class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </FormCol>
                        <FormCol>
                            <label for="stats.defender"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Défense</label>
                            <input type="number" id="stats.defender" v-model="form.stats.defender" placeholder="Défense" required
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </FormCol>
                    </FormRaw>
                    <FormRaw>
                        <FormCol>
                            <label for="stats.speed"
                                   class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Vitesse</label>
                            <input type="number" id="stats.speed" v-model="form.stats.speed" placeholder="Vitesse" required
                                   class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </FormCol>
                        <FormCol>
                              <label for="stats.stamina"
                                     class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Stamina</label>
                              <input type="number" id="stats.stamina" v-model="form.stats.stamina" placeholder="Stamina" required
                                      class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </FormCol>
                    </FormRaw>

                    <ButtonGroup>
                        <ButtonPrimary :disabled="form.processing">Mettre à jour</ButtonPrimary>
                        <ButtonDanger class="w-36" :disabled="form.processing" @click="deletePlayer">Supprimer</ButtonDanger>
                    </ButtonGroup>
                </form>
            </FormContainer>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, Link} from '@inertiajs/vue3';
import {Inertia} from '@inertiajs/inertia';
import {ref, defineProps, reactive, onMounted, computed} from 'vue';

//Component
import H2 from '@/Components/H2.vue';
import H1 from "@/Components/H1.vue";
import FormContainer from "@/Components/FormContainer.vue";
import FormCol from "@/Components/FormCol.vue";
import FormRaw from "@/Components/FormRaw.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputText from "@/Components/InputText.vue";
import ButtonGroup from "@/Components/ButtonGroup.vue";
import ButtonPrimary from "@/Components/ButtonPrimary.vue";
import ButtonDanger from "@/Components/ButtonDanger.vue";



// Propriétés du composant
const props = defineProps({
    players: {
        type: Array,
        required: true
    }
});

// Réactif pour le formulaire d'édition
const form = reactive({
    selectedPlayerId: null,
    id: '',
    lastname: '',
    firstname: '',
    age: '',
    position: '',
    cost: '',
    stats: {
        speed: '',
        attack: '',
        defender: '',
        stamina: '',
    },
});

// Sélectionner une joueur dès le chargement si des joueurs existent
onMounted(() => {
    if (props.players.length > 0) {
        selectPlayer(props.players[0]);
    }
});

// Réactif pour la barre de recherche
const searchQuery = ref("");

const filteredPlayers = computed(() => {
    if (!searchQuery.value) return props.players;
    return props.players.filter(player => {
        return (
            player.firstname.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            player.lastname.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    });
});

// Fonction pour mettre à jour le formulaire avec les détails d'une joueur sélectionnée
function selectPlayer(player) {
    form.id = player.id;
    form.lastname = player.lastname;
    form.firstname = player.firstname;
    form.position = player.position;
    form.age = player.age;
    form.cost = player.cost;
    form.stats = player.stats;
    form.selectedPlayerId = player.id;
}

function submit() {
    Inertia.post(route('players.update', form.id), form, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            selectPlayer(form);
            debugger;
        }
    });
}

function deletePlayer() {
    if (confirm(" Voulez-vous vraiment supprimer cette joueur ? ")) {
        Inertia.delete(route('players.destroy', form.id));
    }
}
</script>
