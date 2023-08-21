<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

//Component
import H2 from '@/Components/H2.vue';
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";


</script>

<template>
    <Head title="MainMenu" />

    <AuthenticatedLayout>
        <template #header>
            <H2>DataBase Menu</H2>
        </template>

        <div class="mt-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex">
                    <div class="flex-1 p-2 bg-contain bg-center bg-no-repeat" style="background-image: url('/images/Hyuga.webp')">
                    </div>
                    <div class="flex-1 p-2 flex flex-col">
                        <h1 class="text-5xl font-bold text-slate-600 mb-2">Captain Tsubasa</h1>
                        <h2 class="text-3xl font-bold text-slate-600 mb-4">Management</h2>
                        <p class="text-xl font-semibold underline text-slate-500">Menu D’éditions  de la base de données </p>
                        <!-- Ajout des boutons pour les nouvelles fonctionnalités -->
                        <div class="h-72 mt-6 flex">
                            <div class="flex-1 flex flex-col items-start justify-between">
                                <a href="/profile" class="w-60 h-12 bg-sky-400 hover:bg-sky-500 text-2xl text-white text-center font-bold py-2 px-5 border-2 border-sky-600 rounded-full drop-shadow-md mb-2">
                                    My profile
                                </a>
                                <a href="/players" class="w-60 h-12 bg-fuchsia-400 hover:bg-fuchsia-500 text-2xl text-white text-center font-bold py-2 px-5 border-2 border-fuchsia-600 rounded-full drop-shadow-md mb-2">
                                    Players
                                </a>
                                <a href="/dataBaseMenu" class="w-60 h-12 bg-sky-400 hover:bg-sky-500 text-2xl text-white text-center font-bold py-2 px-5 border-2 border-sky-600 rounded-full drop-shadow-md mb-2">
                                    Injuries
                                </a>
                                <a href="/dataBaseMenu" class="w-60 h-12 bg-fuchsia-400 hover:bg-fuchsia-500 text-2xl text-white text-center font-bold py-2 px-5 border-2 border-fuchsia-600 rounded-full drop-shadow-md mb-2">
                                    Cards
                                </a>
                            </div>
                            <div class="flex-1 flex flex-col items-start justify-between">
                                <a href="/dataBaseMenu" class="w-60 h-12 bg-cyan-400 hover:bg-cyan-500 text-2xl text-white text-center font-bold py-2 px-5 border-2 border-cyan-600 rounded-full drop-shadow-md mb-2">
                                    Contracts
                                </a>
                                <a href="/teams" class="w-60 h-12 bg-teal-400 hover:bg-teal-500 text-2xl text-white text-center font-bold py-2 px-5 border-2 border-teal-600 rounded-full drop-shadow-md mb-2">
                                    Teams
                                </a>
                                <a href="/dataBaseMenu" class="w-60 h-12 bg-cyan-400 hover:bg-cyan-500 text-2xl text-white text-center font-bold py-2 px-5 border-2 border-cyan-600 rounded-full drop-shadow-md mb-2">
                                    Sanctions
                                </a>
                                <a href="/dataBaseMenu" class="w-60 h-12 bg-teal-400 hover:bg-teal-500 text-2xl text-white text-center font-bold py-2 px-5 border-2 border-teal-600 rounded-full drop-shadow-md mb-2">
                                    Trainings
                                </a>
                            </div>
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
