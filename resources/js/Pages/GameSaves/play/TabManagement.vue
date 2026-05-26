<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    gameSave: { type: Object, required: true },
});

const sections = [
    { key: 'dataBase', label: 'Base de données' },
    { key: 'profil',   label: 'Profil' },
    { key: 'config',   label: 'Configuration' },
];
const activeSection = ref('dataBase');
</script>

<template>
    <div class="flex-1 flex gap-4 overflow-y-auto min-h-[75vh]">
        <!-- Sidebar -->
        <div class="w-1/5 border border-slate-200 rounded-lg bg-slate-50 p-3">
            <h3 class="text-md font-semibold text-slate-700 mb-2">Gestion</h3>
            <nav class="space-y-1">
                <button v-for="sec in sections" :key="sec.key" type="button"
                        @click="activeSection = sec.key"
                        :class="['w-full text-left text-sm px-2 py-1 rounded',
                        activeSection === sec.key ? 'bg-teal-100 text-slate-900' : 'bg-white hover:bg-slate-100 text-slate-700']">
                    {{ sec.label }}
                </button>
            </nav>
        </div>

        <!-- Panneau droit -->
        <div class="flex-1 border border-slate-200 rounded-lg bg-slate-50 p-4 flex flex-col gap-4">

            <!-- Base de données -->
            <div v-if="activeSection === 'dataBase'" class="flex-1 flex flex-col gap-3">
                <h3 class="text-lg font-semibold text-slate-800 mb-1">Base de données</h3>
                <p class="text-sm text-slate-600 mb-2">Crée, édite et assigne des joueurs, contrats et équipes.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                        <h4 class="text-sm font-semibold text-slate-700">Joueurs</h4>
                        <p class="text-xs text-slate-500">Crée, édite et supprime les joueurs de la base de données.</p>
                        <button type="button" class="mt-2 px-3 py-1.5 text-xs rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold"
                                @click="router.get(route('game-saves.players.index', { gameSave: gameSave.id }))">
                            Gérer les joueurs
                        </button>
                    </div>
                    <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                        <h4 class="text-sm font-semibold text-slate-700">Contrats</h4>
                        <p class="text-xs text-slate-500">Assigne des joueurs à des équipes, ajuste les contrats.</p>
                        <button type="button" class="mt-2 px-3 py-1.5 text-xs rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold"
                                @click="router.get(route('contracts.index'))">
                            Gérer les contrats
                        </button>
                    </div>
                    <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                        <h4 class="text-sm font-semibold text-slate-700">Équipes</h4>
                        <p class="text-xs text-slate-500">Accéder à la gestion complète des équipes.</p>
                        <button type="button" class="mt-2 px-3 py-1.5 text-xs rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold"
                                @click="router.get(route('game-saves.teams.index', { gameSave: gameSave.id }))">
                            Gérer les équipes
                        </button>
                    </div>
                </div>
            </div>

            <!-- Profil -->
            <div v-else-if="activeSection === 'profil'" class="flex-1 flex flex-col gap-3">
                <h3 class="text-lg font-semibold text-slate-800 mb-1">Mon profil</h3>
                <div class="border border-slate-200 rounded-lg bg-white p-3 flex flex-col gap-2">
                    <h4 class="text-sm font-semibold text-slate-700">Édition de mon profil</h4>
                    <p class="text-xs text-slate-500">Accéder aux informations de ton compte (nom, email, mot de passe…).</p>
                    <button type="button" class="mt-2 px-3 py-1.5 text-xs rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold"
                            @click="router.get(route('profile.edit'))">
                        Modifier mon profil
                    </button>
                </div>
            </div>

            <!-- Configuration -->
            <div v-else-if="activeSection === 'config'" class="flex-1 flex flex-col gap-3">
                <h3 class="text-lg font-semibold text-slate-800 mb-1">Configuration du jeu</h3>
                <p class="text-sm text-slate-600 mb-2">Paramètres globaux du jeu (balancing, règles, etc.).</p>
                <div class="border border-dashed border-slate-300 rounded-lg bg-white p-4 text-sm text-slate-500">
                    Zone de configuration à implémenter :
                    <ul class="list-disc pl-5 mt-2 text-xs">
                        <li>Coefficients de training (gain min/max, stamina cost…)</li>
                        <li>Paramètres IA (probabilités, bonus/malus)</li>
                        <li>Règles de match (nombre de tours, etc.)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
