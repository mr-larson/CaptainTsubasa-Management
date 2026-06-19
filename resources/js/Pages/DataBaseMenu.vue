<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import H2 from '@/Components/H2.vue';

const isAdmin = computed(() => !!usePage().props.auth?.user?.is_admin);

const sections = [
    { label: 'Mon profil',  desc: 'Modifier mes informations',     icon: '👤', route: 'profile.edit',   admin: false, color: 'border-slate-200 hover:border-teal-300 hover:bg-teal-50' },
    { label: 'Joueurs',     desc: 'Gérer les joueurs de la DB',    icon: '⚽', route: 'players.edit',   admin: true,  color: 'border-slate-200 hover:border-teal-300 hover:bg-teal-50' },
    { label: 'Équipes',     desc: 'Gérer les équipes de la DB',    icon: '🏟️', route: 'teams.edit',     admin: true,  color: 'border-slate-200 hover:border-teal-300 hover:bg-teal-50' },
    { label: 'Contrats',    desc: 'Gérer les contrats de la DB',   icon: '📋', route: 'contracts.edit', admin: true,  color: 'border-slate-200 hover:border-teal-300 hover:bg-teal-50' },
];

// Les sections d'administration ne sont visibles que pour les admins.
const visibleSections = computed(() => sections.filter(s => !s.admin || isAdmin.value));
</script>

<template>
    <Head title="Base de données" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Base de données</H2>
        </template>

        <div class="min-h-[calc(100vh-64px)] flex items-center">
            <div class="max-w-5xl mx-auto px-6 w-full">
                <div class="flex items-center gap-12">

                    <!-- Illustration -->
                    <div class="hidden md:block flex-1">
                        <img src="/images/Hyuga.webp" alt="Hyuga"
                             class="max-h-[480px] object-contain drop-shadow-xl" />
                    </div>

                    <!-- Contenu -->
                    <div class="flex-1 flex flex-col gap-8">

                        <!-- Titre -->
                        <div>
                            <div class="text-xs font-bold text-teal-500 uppercase tracking-widest mb-2">Administration</div>
                            <h1 class="text-3xl font-extrabold text-slate-800 leading-tight">
                                Éditer les<br><span class="text-teal-500">données</span>
                            </h1>
                            <p class="text-slate-400 text-sm mt-2">Gère les équipes, joueurs et contrats de la base.</p>
                        </div>

                        <!-- Grille boutons -->
                        <div class="grid grid-cols-2 gap-3">
                            <Link v-for="s in visibleSections" :key="s.route"
                                  :href="route(s.route)"
                                  class="group flex items-center gap-3 px-4 py-3.5 bg-white rounded-xl border transition-all shadow-sm active:scale-[0.98]"
                                  :class="s.color">
                                <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center text-lg shrink-0">
                                    {{ s.icon }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-slate-700">{{ s.label }}</div>
                                    <div class="text-[11px] text-slate-400">{{ s.desc }}</div>
                                </div>
                                <div class="ml-auto text-slate-300 group-hover:text-teal-400 transition-all shrink-0">→</div>
                            </Link>
                        </div>

                        <!-- Retour -->
                        <Link :href="route('mainMenu')"
                              class="flex items-center justify-center gap-2 px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-500 text-sm font-semibold rounded-xl border border-slate-200 transition-all">
                            ← Retour au menu principal
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
