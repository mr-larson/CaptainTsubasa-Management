<template>
    <Head title="Teams" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Teams</H2>
        </template>

        <aside id="separator-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidebar">
            <div class="h-full px-3 py-2 overflow-y-auto bg-slate-700">
                <div class="pb-4 mb-3 border-b text-center text-gray-200">
                    <H2>Teams</H2>
                </div>
                <div class="mb-4">
<!--                    <form>
                        <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="search" id="default-search" class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300  bg-gray-100 focus:ring-blue-500 focus:border-blue-500 rounded" placeholder="Search Mockups, Logos..." required>
                        </div>
                    </form>-->
                </div>
                <ul class="space-y-1 font-medium">
                    <li v-for="team in teams" :key="team.id" @click="selectTeam(team)">
                        <a href="#"
                           :class="{'bg-slate-500': form.selectedTeamId === team.id}"
                           class="flex items-center p-1 text-gray-100 transition duration-75 rounded-lg hover:bg-slate-500">
                            <span class="ml-3">{{ team.name }}</span>
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
            <div class="h-5/6 p-4 border border-slate-300 rounded-lg mx-6 bg-white">
                <form @submit.prevent="submit">
                    <div class=" grid grid-cols-2 gap-4 text-slate-700">

                        <div class="md:flex md:items-center m-3">
                            <div class="md:w-1/3">
                                <label for="name" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                    Nom :
                                </label>
                            </div>
                            <div class="md:w-2/3">
                                <input type="text" id="name" v-model="form.name" placeholder="Nom de l'équipe" required class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                            </div>
                        </div>

                        <div class="md:flex md:items-center m-3">
                            <div class="md:w-1/3">
                                <label for="wins" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                    Victoire(s) :
                                </label>
                            </div>
                            <div class="md:w-2/3">
                                <input type="number" id="wins" v-model="form.wins" placeholder="Victoire(s) de l'équipe" required class=" text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                            </div>
                        </div>

                        <div class="md:flex md:items-center mb-3">
                            <div class="md:w-1/3">
                                <label for="points" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                    Points :
                                </label>
                            </div>
                            <div class="md:w-2/3">
                                <input type="number" id="points" v-model="form.points" placeholder="Points de l'équipe" required class=" text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                            </div>
                        </div>

                        <div class="md:flex md:items-center mb-3">
                            <div class="md:w-1/3">
                                <label for="losses" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                    Défaites :
                                </label>
                            </div>
                            <div class="md:w-2/3">
                                <input type="number" id="losses" v-model="form.losses" placeholder="Défaites de l'équipe" required class=" text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                            </div>
                        </div>

                        <div class="md:flex md:items-center mb-3">
                            <div class="md:w-1/3">
                                <label for="budget" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                    Budget :
                                </label>
                            </div>
                            <div class="md:w-2/3">
                                <input type="number" id="budget" v-model="form.budget" placeholder="Budget de l'équipe" required class=" text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                            </div>
                        </div>

                        <div class="md:flex md:items-center mb-3">
                            <div class="md:w-1/3">
                                <label for="draws" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                    Matchs nuls :
                                </label>
                            </div>
                            <div class="md:w-2/3">
                                <input type="number" id="draws" v-model="form.draws" placeholder="Matchs nuls de l'équipe" required class="text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                            </div>
                        </div>

                        <div class="md:flex md:items-center mb-3">
                            <div class="md:w-1/3">
                                <label for="logo_path" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                    Logo :
                                </label>
                            </div>
                            <div class="md:w-2/3">
                                <input type="file" id="logo_path" ref="logoInput" @change="handleLogoChange" class="hidden">
                                <img :src="form.logo_path || 'default-image-path.jpg'" alt="Logo de l'équipe" class="rounded-lg cursor-pointer w-40" @click="uploadLogo">
                            </div>
                        </div>

                        <div class="md:flex md:items-center mb-3">
                            <div class="md:w-1/3">
                                <label for="description" class="text-gray-500 font-bold md:text-right mb-1 md:mb-0 pr-4">
                                    Description :
                                </label>
                            </div>
                            <div class="md:w-2/3">
                                <textarea id="description" v-model="form.description" class="p-2 w-60 h-24 text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-lg" placeholder="Description de l'équipe"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-center pt-6">
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
    import { ref } from 'vue';
    import { defineProps } from 'vue';
    import { reactive } from "vue";
    import { onMounted } from 'vue';

    //Component
    import H2 from '@/Components/H2.vue';


    const props = defineProps({
        teams: {
            type: Array,
            required: true
        }
    });
    const form = reactive({
        selectedTeamId: null,
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
    const logoInput = ref(null);

    async function updateTeam() {
        // Votre logique pour mettre à jour l'équipe, par exemple une requête HTTP
        let response = await fetch(`/api/teams/${form.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name: form.name /* et autres champs */ }),
        });

        if (response.ok) {
            // Si la mise à jour est réussie
            let updatedTeam = await response.json();

            // Trouvez l'équipe dans votre liste d'équipes et mettez-la à jour
            let teamIndex = teams.findIndex(team => team.id === updatedTeam.id);
            if (teamIndex !== -1) {
                teams[teamIndex] = updatedTeam;
            }

            // Pas besoin de réinitialiser selectedTeamId ici
            // form.selectedTeamId reste inchangé
        } else {
            // Gérez les erreurs comme vous le souhaitez
            console.error('Erreur lors de la mise à jour de l\'équipe');
        }
    }

    function handleLogoChange(event) {
        form.logo_path = event.target.files[0];
    }

    function uploadLogo() {
        logoInput.value.click();
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
        form.selectedTeamId = team.id;
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
