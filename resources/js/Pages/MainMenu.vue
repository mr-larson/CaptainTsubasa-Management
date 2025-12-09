<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

// Components
import H2 from '@/Components/H2.vue';
import H1 from '@/Components/H1.vue';
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
                    <!-- Illustration -->
                    <div
                        class="hidden md:block flex-1 p-2 bg-contain bg-center bg-no-repeat"
                        style="background-image: url('/images/tsubas3.webp')"
                    ></div>

                    <!-- Zone de boutons -->
                    <div class="flex-1 p-2 flex flex-col">
                        <h1 class="text-4xl font-bold text-slate-600 mb-6">Captain Tsubasa</h1>
                        <h2 class="text-3xl font-bold text-slate-600 mb-6">Management</h2>

                        <div class="h-64 mt-6 flex flex-col items-center md:items-start justify-between">
                            <!-- Nouvelle partie = création d’une sauvegarde -->
                            <Link
                                :href="route('game-saves.create')"
                                class="w-60 h-12 bg-sky-300 hover:bg-sky-400 text-center text-xl text-sky-800 font-bold py-2 px-5 border-2 border-sky-600 rounded-full drop-shadow-md"
                            >
                                Nouvelle Partie
                            </Link>

                            <!-- Charger partie = liste des sauvegardes -->
                            <Link
                                :href="route('game-saves.index')"
                                class="w-60 h-12 bg-cyan-300 hover:bg-cyan-400 text-center text-xl text-cyan-800 font-bold py-2 px-5 border-2 border-cyan-600 rounded-full drop-shadow-md"
                            >
                                Charger Partie
                            </Link>

                            <!-- Accès au menu d’édition de la base de données -->
                            <Link
                                :href="route('dataBaseMenu')"
                                class="w-60 h-12 bg-fuchsia-300 hover:bg-fuchsia-400 text-xl text-fuchsia-800 text-center font-bold py-2 px-5 border-2 border-fuchsia-600 rounded-full drop-shadow-md"
                            >
                                Éditer les Données
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="h-32 p-2 flex items-end justify-center text-lg font-bold text-slate-400">
                    Merci d'utiliser notre application
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Styles spécifiques éventuels */
</style>
