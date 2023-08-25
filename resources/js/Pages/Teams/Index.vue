<template>
    <Head title="Teams" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Teams</H2>
        </template>

        <aside id="separator-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidebar">
            <div class="h-full px-3 py-4 overflow-y-auto bg-slate-700">
                <div class="pb-4 mb-3 border-b text-center text-gray-200">
                    <H2>Teams</H2>
                </div>
                <div class="mb-4">
                    <form>
                        <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="search" id="default-search" class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300  bg-gray-100 focus:ring-blue-500 focus:border-blue-500 rounded" placeholder="Search Mockups, Logos..." required>
                        </div>
                    </form>
                </div>
                <ul class="space-y-3 font-medium">
                    <li v-for="team in teams" :key="team.id" @click="selectTeam(team)">
                        <a href="#" class="flex items-center p-1 text-gray-100 transition duration-75 rounded-lg hover:bg-slate-500">
                            <span class="ml-4">{{ team.name }}</span>
                        </a>
                    </li>
                </ul>
                <ul class="pt-4 mt-6 space-y-2 font-medium border-t border-gray-200">
                    <li class="">
                        <button @click="goToCreateTeam" class="bg-teal-500 hover:bg-teal-600 border border-teal-300 text-white p-2 w-full rounded">Add a team</button>
                    </li>
                    <li class="py-2 flex">
                        <Link :href="route('dataBaseMenu')" class="bg-cyan-500 hover:bg-cyan-600 border border-cyan-300 shadow-gray-100	 text-white p-2 w-full rounded mt-2 text-center">Return</Link>
                    </li>
                </ul>
            </div>
        </aside>

        <div class="p-4 sm:ml-64">
            <div class="flex justify-center">
                <h1 class="text-3xl font-bold text-slate-600 mb-2">Teams Edit</h1>
            </div>
            <div class="p-4 border-2 border-gray-200 border-dashed rounded-lg dark:border-gray-700">
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4 text-slate-700">
                        <div class="py-4">
                            <div>
                                <img :src="form.logo_path || 'default-image-path.jpg'" alt="Logo de l'équipe" width="100">
                            </div>
                            <label for="logo_path" class="">Logo : </label>
                            <input type="file" id="logo_path" ref="logoInput" @change="handleLogoChange" class="p-2 text-sm text-gray-900 border border-gray-300 rounded-full">
                        </div>

                        <div class="py-4">
                            <label for="name" class="">Nom : </label>
                            <input type="text" id="name" v-model="form.name" class="p-2  text-sm text-gray-900 border border-gray-300 rounded-full" placeholder="Nom de l'équipe" required>
                        </div>

                        <div class="py-4">
                            <label for="budget" class="">Budget : </label>
                            <input type="number" id="budget" v-model="form.budget" class="p-2  text-sm text-gray-900 border border-gray-300 rounded-full" placeholder="Budget de l'équipe" required>
                        </div>

                        <div class="py-4">
                            <label for="points" class="">Points : </label>
                            <input type="number" id="points" v-model="form.points" class="p-2 text-sm text-gray-900 border border-gray-300 rounded-full" placeholder="Points de l'équipe" required>
                        </div>

                        <div class="py-4">
                            <label for="wins" class="">Victoires : </label>
                            <input type="number" id="wins" v-model="form.wins" class="p-2 text-sm text-gray-900 border border-gray-300 rounded-full" placeholder="Victoires de l'équipe" required>
                        </div>

                        <div class="py-4">
                            <label for="draws" class="">Matchs nuls : </label>
                            <input type="number" id="draws" v-model="form.draws" class="p-2 text-sm text-gray-900 border border-gray-300 rounded-full" placeholder="Matchs nuls de l'équipe" required>
                        </div>

                    </div>

                    <div class="flex justify-center">
                        <button type="submit" class="ml-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import { defineProps } from 'vue';
import { reactive } from "vue";
import { onMounted } from 'vue';

import { router} from "@inertiajs/vue3";


//Component
import H2 from '@/Components/H2.vue';


const props = defineProps({
    teams: {
        type: Array,
        required: true
    }
});
const form = reactive({
    id: '',
    name: '',
    logo_path: null,
    budget: '',
    points: '',
    wins: '',
    draws: '',
    losses: '',
    description: ''
});

function handleLogoChange(event) {
    form.logo_path = event.target.files[0];
}



onMounted(() => {
    if (teams.length > 0) {
        selectTeam(teams[0]);
    }
});

function selectTeam(team) {
    form.id = team.id;
    form.name = team.name;
    form.logo_path = team.logo_path;
    form.budget = team.budget;
    form.points = team.points;
    form.wins = team.wins;
    form.draws = team.draws;
    form.losses = team.losses;
    form.description = team.description;
}


function submit() {
    Inertia.put(route('teams.update', form.id), form);
}

function goToCreateTeam() {
    Inertia.visit(route('teams.create'));
}

</script>


<style scoped>
/* Vos styles pour la sidebar */
.sidebar {
    width: 200px;
    height: 100vh;
    color: #fff;
}

.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar ul li {
    cursor: pointer;
    padding: 0.5rem 2px;
}
</style>
