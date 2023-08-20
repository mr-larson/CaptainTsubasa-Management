<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

//Component
import H2 from '@/Components/H2.vue';


// Vous pouvez également ajouter des fonctions pour gérer les clics sur ces boutons si nécessaire.
const newGame = () => {
    // Logique pour créer un nouveau jeu
    // Peut-être rediriger vers une nouvelle page ou ouvrir une modal, selon votre conception
};

const loadGame = () => {
    // Logique pour charger un jeu
};

const editDatabase = () => {
    // Logique pour éditer la base de données

};

</script>

<template>
    <Head title="MainMenu" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Main Menu</H2>
        </template>

        <div class="mt-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex">
                    <div class="flex-1 p-2 bg-contain bg-center bg-no-repeat" style="background-image: url('/images/name.png')">

                    </div>
                    <div class="flex-1 p-2 flex flex-col">
                        <h1 class="text-5xl font-bold text-slate-600 mb-2">Captain Tsubasa</h1>
                        <h2 class="text-3xl font-bold text-slate-600 mb-6">Management</h2>
                        <!-- Ajout des boutons pour les nouvelles fonctionnalités -->
                        <div class="h-64 mt-6 flex flex-col items-start justify-between">
                            <button @click="newGame" class="w-60 h-12 bg-sky-400 hover:bg-sky-500 text-2xl text-white font-bold py-2 px-5 border-2 border-sky-600 rounded-full drop-shadow-md">
                                New Game
                            </button>
                            <button @click="loadGame" class="w-60 h-12 bg-cyan-400 hover:bg-cyan-500 text-2xl text-white font-bold py-2 px-5 border-2 border-cyan-600 rounded-full drop-shadow-md">
                                Load Game
                            </button>
                            <a href="/dataBaseMenu" class="w-60 h-12 bg-fuchsia-400 hover:bg-fuchsia-500 text-2xl text-white text-center font-bold py-2 px-5 border-2 border-fuchsia-600 rounded-full drop-shadow-md">
                                Edit Database
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Si vous avez des styles spécifiques pour ces boutons, ajoutez-les ici. */
</style>
