<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
//Component
import H2 from '@/Components/H2.vue';

</script>

<template>

    <AuthenticatedLayout>
        <template #header>
            <H2>DataBase Menu</H2>
        </template>

        <div class="mt-12">
            <div class="max-w-7xl mx-auto md:px-6 lg:px-8">
                <div class="flex">
                    <div class="hidden md:block flex-1 p-2 bg-contain bg-center bg-no-repeat" style="background-image: url('/images/Hyuga.webp')">
                    </div>
                    <div class="flex-1 p-2 flex flex-col">
                        <h1 class="text-5xl font-bold text-slate-600 mb-2">Captain Tsubasa</h1>
                        <h2 class="text-3xl font-bold text-slate-600 mb-4">Management</h2>
                        <p class="text-xl font-semibold underline text-slate-500">Menu D’éditions  de la base de données </p>
                        <div class="pt-10">
                            <div class="flex flex-col md:flex-row md:flex-wrap">
                                <div class="lg:w-1/2 mb-2 lg:h-24 flex justify-center">
                                    <Link :href="route('profile.edit')" class="block w-60 h-12 bg-sky-300 hover:bg-sky-400 text-xl text-sky-950 text-center font-bold py-2 px-5 border-2 border-sky-500 rounded-full drop-shadow-md">
                                        Mon Profil
                                    </Link>
                                </div>
                                <div class="lg:w-1/2 mb-2 lg:h-24 flex justify-center">
                                    <Link :href="route('players.edit')" class="block w-60 h-12 bg-fuchsia-300 hover:bg-fuchsia-400 text-xl text-fuchsia-950 text-center font-bold py-2 px-5 border-2 border-fuchsia-500 rounded-full drop-shadow-md">
                                        Joueurs
                                    </Link>
                                </div>
                                <div class="lg:w-1/2 mb-2 lg:h-24 flex justify-center">
                                    <Link :href="route('contracts.edit')" class="block w-60 h-12 bg-cyan-300 hover:bg-cyan-400 text-xl text-cyan-950 text-center font-bold py-2 px-5 border-2 border-cyan-500 rounded-full drop-shadow-md">
                                        Contrats
                                    </Link>
                                </div>
                                <div class="lg:w-1/2 mb-2 lg:h-24 flex justify-center">
                                    <Link :href="route('teams.edit')" class="block w-60 h-12 bg-teal-300 hover:bg-teal-400 text-xl text-teal-950 text-center font-bold py-2 px-5 border-2 border-teal-500 rounded-full drop-shadow-md">
                                        Equipes
                                    </Link>
                                </div>
                            </div>
                            <div class="h-44 mt-4 flex justify-center">
                                <Link :href="route('mainMenu')" class="block w-60 h-12 bg-slate-300 hover:bg-slate-400 text-xl text-slate-950 text-center font-bold py-2 px-5 border-2 border-slate-500 rounded-full drop-shadow-md">
                                    Retour
                                </Link>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
