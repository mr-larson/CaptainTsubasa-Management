<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import H2 from '@/Components/H2.vue';
</script>

<template>
    <Head title="Menu principal" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Menu principal</H2>
        </template>

        <div class="min-h-[calc(100vh-64px)] flex items-center">
            <div class="max-w-5xl mx-auto px-6 w-full">
                <div class="flex items-center gap-12">

                    <!-- Illustration -->
                    <div class="hidden md:block flex-1">
                        <img src="/images/Ryo_Ishizaki.webp" alt="Captain Tsubasa"
                             class="max-h-[480px] object-contain drop-shadow-xl" />
                    </div>

                    <!-- Contenu -->
                    <div class="flex-1 flex flex-col gap-8">

                        <!-- Titre -->
                        <div>
                            <div class="text-xs font-bold text-teal-500 uppercase tracking-widest mb-2">Bienvenue</div>
                            <h1 class="text-4xl font-extrabold text-slate-800 leading-tight">
                                Captain Tsubasa<br>
                                <span class="text-teal-500">Management</span>
                            </h1>
                            <p class="text-slate-400 text-sm mt-2">Gère ton club, dispute des championnats, bats l'IA.</p>
                        </div>

                        <!-- Boutons -->
                        <div class="flex flex-col gap-3">

                            <Link :href="route('game-saves.create')"
                                  class="group flex items-center gap-4 px-6 py-4 bg-teal-500 hover:bg-teal-400 text-white rounded-2xl font-bold text-base transition-all shadow-sm hover:shadow-md active:scale-[0.98]">
                                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-xl shrink-0">
                                    ⚽
                                </div>
                                <div>
                                    <div class="text-base font-bold">Nouvelle partie</div>
                                    <div class="text-xs text-white/70 font-normal">Créer une nouvelle sauvegarde</div>
                                </div>
                                <div class="ml-auto text-white/50 group-hover:text-white transition-all">→</div>
                            </Link>

                            <Link :href="route('game-saves.index')"
                                  class="group flex items-center gap-4 px-6 py-4 bg-white hover:bg-slate-50 text-slate-800 rounded-2xl font-bold text-base transition-all border border-slate-200 hover:border-teal-300 shadow-sm active:scale-[0.98]">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-xl shrink-0">
                                    💾
                                </div>
                                <div>
                                    <div class="text-base font-bold text-slate-700">Charger une partie</div>
                                    <div class="text-xs text-slate-400 font-normal">Reprendre une sauvegarde existante</div>
                                </div>
                                <div class="ml-auto text-slate-300 group-hover:text-teal-400 transition-all">→</div>
                            </Link>

                            <Link :href="route('dataBaseMenu')"
                                  class="group flex items-center gap-4 px-6 py-4 bg-white hover:bg-slate-50 text-slate-800 rounded-2xl font-bold text-base transition-all border border-slate-200 hover:border-slate-300 shadow-sm active:scale-[0.98]">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-xl shrink-0">
                                    🗂️
                                </div>
                                <div>
                                    <div class="text-base font-bold text-slate-700">Éditer les données</div>
                                    <div class="text-xs text-slate-400 font-normal">Équipes, joueurs, contrats</div>
                                </div>
                                <div class="ml-auto text-slate-300 group-hover:text-slate-500 transition-all">→</div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
