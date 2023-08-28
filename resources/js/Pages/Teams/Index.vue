<template>
    <Head title="Teams" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Teams</H2>
        </template>

        <aside id="separator-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidebar">
            <div class="h-full px-3 py-2 overflow-y-auto bg-slate-700">
                <div class="p-3 mb-3 border-b text-center text-gray-200">
                    <H2>Equipes</H2>
                </div>
                <div class="mb-2">
                    <form>
                        <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="search" id="default-search" v-model="searchQuery" class="block w-full p-1 pl-10 text-sm text-gray-900 border border-gray-300  bg-gray-100 focus:ring-blue-500 focus:border-blue-500 rounded" placeholder="Recherche" required>
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
                    <li class="pt-1 flex">
                        <Link :href="route('teams.create')" class="bg-teal-500 hover:bg-teal-600 border border-teal-300 text-white p-1 w-full rounded text-center">
                            Créer une équipe
                        </Link>
                    </li>
                    <li class="pt-2 flex">
                        <Link :href="route('dataBaseMenu')" class="bg-slate-500 hover:bg-slate-600 border border-slate-300 shadow-gray-100	text-white p-1 w-full rounded text-center">
                            Retour
                        </Link>
                    </li>
                </ul>
            </div>
        </aside>

        <div class="p-4 sm:ml-64">
            <div class="flex justify-center">
                <h1 class="text-3xl font-bold text-slate-600 mb-6">Editions</h1>
            </div>
            <div v-if="hasErrors">
                <ul>
                    <li v-for="error in allErrors" :key="error">{{ error }}</li>
                </ul>
            </div>
            <div class="p-4 border border-slate-300 rounded-lg mx-6 bg-white">
                <form @submit.prevent="submit" enctype="multipart/form-data">
                    <div class="flex flex-col md:grid lg:grid-cols-2 gap-4 text-slate-700">

                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="name" class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Nom</label>
                            <input type="text" id="name" v-model="form.name" placeholder="Nom de l'équipe" required class="appearance-none text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="wins" class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Victoire(s)</label>
                            <input type="number" id="wins" v-model="form.wins" placeholder="Victoire(s) de l'équipe" required class="text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="points" class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Points</label>
                            <input type="number" id="points" v-model="form.points" placeholder="Points de l'équipe" required class="text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="losses" class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Défaite(s)</label>
                            <input type="number" id="losses" v-model="form.losses" placeholder="Défaites de l'équipe" required class="text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="budget" class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Budget</label>
                            <input type="number" id="budget" v-model="form.budget" placeholder="Budget de l'équipe" required class="text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="draws" class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Matchs nul(s)</label>
                            <input type="number" id="draws" v-model="form.draws" placeholder="Matchs nuls de l'équipe" required class="text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-full w-full md:w-56 leading-tight focus:outline-none focus:bg-white focus:border-purple-300">
                        </div>

                        <div class="flex items-center m-3 gap-4 md:gap-0">
                            <label for="image" class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Logo</label>
                            <div class="flex flex-col">
                                <input type="file" name="image" id="image" ref="logoInput" @change="handleLogoChange" class="hidden">
                                <img :src="form.image || 'images/teams/team_default.png'" alt="Logo de l'équipe" class="rounded-lg cursor-pointer w-40" @click="uploadLogo">
                            </div>
                        </div>

                        <div class="flex items-start m-3 gap-4 md:gap-0">
                            <label for="description" class="text-gray-500 font-bold w-1/3 text-right mb-1 md:mb-0 pr-4">Description</label>
                            <textarea id="description" v-model="form.description" class="p-2 w-full h-24 text-sm text-gray-900 bg-stone-50 border border-gray-300 rounded-lg" placeholder="Description de l'équipe"></textarea>
                        </div>

                    </div>

                    <div class="flex justify-around pt-8">
                        <button type="submit" class="w-36 bg-sky-300 hover:bg-sky-400 text-center py-1 border-2 border-sky-500 rounded-full drop-shadow-md mb-2">Mettre à jour</button>
                        <button type="button" @click="deleteTeam" class="w-36 bg-rose-300 hover:bg-rose-400 text-rose-950 text-center py-1 border-2 border-rose-500 rounded-full drop-shadow-md mb-2">Supprimer</button>
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
    import { computed } from "vue";

    //Component
    import H2 from '@/Components/H2.vue';


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
        image: '',
        budget: '',
        points: '',
        wins: '',
        draws: '',
        losses: '',
        description: ''
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


    const logoInput = ref(null);
    // Fonction pour lancer le sélecteur de fichier du logo
    function uploadLogo() {
        if (logoInput.value) {
            logoInput.value.click();
        } else {
            console.warn("Logo input is not yet defined.");
        }
    }


    // Gestion du changement de logo
    function handleLogoChange(event) {
        const file = event.target.files[0];
        if (file) {
            form.image = URL.createObjectURL(file);
        }
    }

    // Fonction pour mettre à jour le formulaire avec les détails d'une équipe sélectionnée
    function selectTeam(team) {
        form.id = team.id;
        form.name = team.name;
        form.image = team.image;
        form.budget = team.budget;
        form.points = team.points;
        form.wins = team.wins;
        form.draws = team.draws;
        form.losses = team.losses;
        form.description = team.description;
        form.selectedTeamId = team.id;
    }

    // Fonction pour soumettre le formulaire et mettre à jour l'équipe
    function submit() {
        const formData = new FormData();

        // Ajoutez tous les champs du formulaire à formData
        for (const key in form) {
            formData.append(key, form[key]);
        }

      console.log("formData");

        Inertia.post(route('teams.update', form.id), formData);
    }

    // Fonction pour supprimer une équipe
    function deleteTeam() {
        if (confirm(" Voulez-vous vraiment supprimer cette équipe ? ")) {
            Inertia.delete(route('teams.destroy', form.id));
        }
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
