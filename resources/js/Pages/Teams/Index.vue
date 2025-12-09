<template>
    <Head title="Teams"/>

    <AuthenticatedLayout>
        <template #header>
            <H2>Teams</H2>
        </template>

        <aside id="separator-sidebar"
               class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0"
               aria-label="Sidebar">
            <div class="h-full px-3 py-2 overflow-y-auto bg-slate-700">
                <div class="p-3 pb-5 mb-4 border-b text-center text-gray-200">
                    <H2>Equipes</H2>
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
                    <li v-for="team in filteredTeams" :key="team.id" @click="selectTeam(team)">
                        <a href="#"
                           :class="{'bg-slate-500': form.selectedTeamId === team.id}"
                           class="flex items-center p-1 text-gray-100 transition duration-75 rounded-lg hover:bg-slate-500">
                            <span class="ml-3">{{ team.name }}</span>
                        </a>
                    </li>
                </ul>
                <ul class="pt-3 mt-2 space-y-1 font-medium border-t border-gray-200">
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
            <H1>Liste des équipes</H1>
            <FormContainer>
                <FormRaw>
                    <FormCol>
                        <InputLabel for="name" value="Name" />
                        <span class="mt-1 block w-full rounded-md shadow-sm">

                      </span>
                    </FormCol>
                </FormRaw>
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
import FormRaw from "@/Components/FormRaw.vue";
import FormCol from "@/Components/FormCol.vue";
import InputLabel from "@/Components/InputLabel.vue";


// Propriétés du composant
const props = defineProps({
    teams: {
        type: Array,
        required: true
    }
});

// Réactif pour le formulaire d'édition
const form = reactive({
    selectedTeamId: null,
    id: '',
    name: '',
    budget: '',
    points: '',
    wins: '',
    draws: '',
    losses: '',
});

// Sélectionner une équipe dès le chargement si des équipes existent
onMounted(() => {
    if (props.teams.length > 0) {
        selectTeam(props.teams[0]);
    }
});

// Réactif pour la barre de recherche
const searchQuery = ref("");

const filteredTeams = computed(() => {
    if (!searchQuery.value) return props.teams;
    return props.teams.filter(team => team.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

function selectTeam(team) {
    form.id = team.id;
    form.name = team.name;
    form.budget = team.budget;
    form.points = team.points;
    form.wins = team.wins;
    form.draws = team.draws;
    form.losses = team.losses;
    form.selectedTeamId = team.id;
}
</script>

