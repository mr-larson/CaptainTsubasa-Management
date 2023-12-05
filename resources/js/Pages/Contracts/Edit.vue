<template>
    <Head title="Contract"/>

    <AuthenticatedLayout>
        <template #header>
            <H2>Contract</H2>
        </template>

        <aside id="separator-sidebar"
               class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0"
               aria-label="Sidebar">
            <div class="h-full px-3 py-2 overflow-y-auto bg-slate-700">
                <div class="p-3 mb-3 border-b text-center text-gray-200">
                    <H2>Contrats</H2>
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
                    <li v-for="contract in filteredContracts" :key="contract.id" @click="selectContract(contract)">
                        <a href="#"
                           :class="{'bg-slate-500': form.selectedContractId === contract.id}"
                           class="flex items-center p-1 text-gray-100 transition duration-75 rounded-lg hover:bg-slate-500">
                            <span class="ml-3">{{ contract.team_id }}</span>
                        </a>
                    </li>
                </ul>
                <ul class="pt-3 mt-2 space-y-1 font-medium border-t border-gray-200">
                    <li class="pt-1 flex">
                        <Link :href="route('contracts.create')"
                              class="bg-teal-500 hover:bg-teal-600 border border-teal-300 text-white p-1 w-full rounded text-center">
                            Créer un contrat
                        </Link>
                    </li>
                    <li class="pt-2 flex">
                        <Link :href="route('dataBaseMenu')"
                              class="bg-slate-500 hover:bg-slate-600 border border-slate-300 shadow-gray-100 text-white p-1 w-full rounded text-center">
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
                            <InputLabel for="team_id" value="Équipe" />
                            <InputText id="team_id" v-model="form.team_id" required class="mt-1 w-full">
                                <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                            </InputText>
                        </FormCol>
                        <FormCol>
                            <InputLabel for="player_id" value="Joueur" />
                            <InputSelect id="player_id" v-model="form.player_id" required class="mt-1 w-full">
                                <option v-for="player in players" :key="player.id" :value="player.id">{{ player.firstname + ' ' + player.lastname }}</option>
                            </InputSelect>
                        </FormCol>
                        <FormCol>
                            <InputLabel for="salary" value="Salaire" />
                            <InputText type="number" id="salary" v-model="form.salary" placeholder="Salaire du joueur"
                                       required
                                       class="mt-1 w-full"
                            />
                        </FormCol>
                        <FormCol>
                            <InputLabel for="start_date" value="Date de début" />
                            <InputText type="date" id="start_date" v-model="form.start_date" required class="mt-1 w-full"/>
                        </FormCol>
                        <FormCol>
                            <InputLabel for="end_date" value="Date de fin" />
                            <InputText type="date" id="end_date" v-model="form.end_date" required class="mt-1 w-full"/>
                        </FormCol>
                    </FormRaw>

                    <ButtonGroup>
                        <ButtonPrimary :disabled="form.processing">Mettre à jour</ButtonPrimary>
                        <ButtonDanger class="w-36" :disabled="form.processing" @click="deleteContract">Supprimer</ButtonDanger>
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
import H1 from '@/Components/H1.vue';
import FormContainer from "@/Components/FormContainer.vue";
import FormRaw from "@/Components/FormRaw.vue";
import FormCol from "@/Components/FormCol.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputText from "@/Components/InputText.vue";
import InputSelect from "@/Components/InputSelect.vue";
import ButtonGroup from "@/Components/ButtonGroup.vue";
import ButtonPrimary from "@/Components/ButtonPrimary.vue";
import ButtonDanger from "@/Components/ButtonDanger.vue";


// Propriétés du composant
const props = defineProps({
    contracts: {
        type: Array,
        required: true,
    },
    teams: {
        type: Array,
        required: true
    },
    players: {
        type: Array,
        required: true
    }
});

const teams = props.teams;
const players = props.players;

// Réactif pour le formulaire d'édition
const form = reactive({
    id: '',
    team_id: '',
    player_id: '',
    salary: '',
    start_date: '',
    end_date: '',
});


onMounted(() => {
    console.log("Contrats:", props.contracts);
    console.log("Équipes:", teams);
    console.log("Joueurs:", players);
    if (props.contracts.length > 0) {
        selectContract(props.contracts[0]);
    }
});

// Réactif pour la barre de recherche
const searchQuery = ref("");

const filteredContracts = computed(() => {
    if (!searchQuery.value) return props.contracts;
    return props.contracts.filter(contract => contract.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

function selectContract(contract) {
    form.id = contract.id;
    form.team_id = contract.team_id;
    form.player_id = contract.player_id;
    form.salary = contract.salary;
    form.start_date = formatDate(contract.start_date);
    form.end_date = formatDate(contract.end_date);
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toISOString().split('T')[0];
}


function submit() {
    Inertia.post(route('contracts.update', form.id), form, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            selectContract(form);
            debugger;
        }
    });
}

function deleteContract() {
    if (confirm(" Voulez-vous vraiment supprimer ce contrat ? ")) {
        Inertia.delete(route('contracts.destroy', form.id));
    }
}
</script>
