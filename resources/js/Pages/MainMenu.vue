<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
//Component
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
                    <div class="hidden md:block flex-1 p-2 bg-contain bg-center bg-no-repeat" style="background-image: url('/images/tsubasa.jpg')">
                    </div>

                    <div class="flex-1 p-2 flex flex-col">
                        <h1 class="text-5xl font-bold text-slate-600 mb-2">Captain Tsubasa</h1>
                        <h2 class="text-3xl font-bold text-slate-600 mb-6">Management</h2>
                        <!-- Ajout des boutons pour les nouvelles fonctionnalités -->
                        <div class="h-64 mt-6 flex flex-col items-center md:items-start justify-between">
                            <Link :href="route('teams.index')" class="w-60 h-12 bg-sky-300 hover:bg-sky-400 text-center text-xl text-sky-800 font-bold py-2 px-5 border-2 border-sky-600 rounded-full drop-shadow-md">
                              Nouvelle Partie
                            </Link>

                            <button class="w-60 h-12 bg-cyan-300 hover:bg-cyan-400 text-xl text-cyan-800 font-bold py-2 px-5 border-2 border-cyan-600 rounded-full drop-shadow-md">
                                Charger Partie
                            </button>
                            <Link :href="route('dataBaseMenu')" class="w-60 h-12 bg-fuchsia-300 hover:bg-fuchsia-400 text-xl text-fuchsia-800 text-center font-bold py-2 px-5 border-2 border-fuchsia-600 rounded-full drop-shadow-md">
                                Editer les Données
                            </Link>
                        </div>
                    </div>
                </div>
                <div class="h-32 p-2 flex items-end justify-center text-l font-bold text-slate-400">
                    Merci d'utiliser notre application
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Si vous avez des styles spécifiques pour ces boutons, ajoutez-les ici. */
</style>
