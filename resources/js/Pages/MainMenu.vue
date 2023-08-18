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
    // Redirigez vers une page d'administration ou ouvrez une modal
};

</script>

<template>
    <Head title="MainMenu" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Main Menu</H2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        You're logged in!

                        <!-- Ajout des boutons pour les nouvelles fonctionnalités -->
                        <div class="mt-6">
                            <span class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">New Game</span>
                            <!-- <button @click="newGame" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                New Game
                            </button> -->
                            <span class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded ml-4">Load Game</span>
                            <!-- <button @click="loadGame" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded ml-4">
                                Load Game
                            </button> -->
                            <span class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded ml-4">Edit Database</span>
                            <!-- <button @click="editDatabase" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded ml-4">
                                Edit Database
                            </button> -->
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
