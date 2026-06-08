<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    gameSave: { type: Object, required: true },
    managementStats: { type: Object, default: () => ({}) },
});

const stats = props.managementStats ?? {};
const expiringContracts = stats.expiringContracts ?? [];

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

                <!-- Compteurs -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="border border-slate-200 rounded-xl bg-white p-3 flex flex-col items-center gap-1">
                        <span class="text-2xl font-black text-slate-800">{{ stats.playersCount ?? '—' }}</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Joueurs</span>
                    </div>
                    <div class="border border-slate-200 rounded-xl bg-white p-3 flex flex-col items-center gap-1">
                        <span class="text-2xl font-black text-slate-800">{{ stats.teamsCount ?? '—' }}</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Équipes</span>
                    </div>
                    <div class="border border-slate-200 rounded-xl bg-white p-3 flex flex-col items-center gap-1">
                        <span class="text-2xl font-black text-slate-800">{{ stats.activeContractsCount ?? '—' }}</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Contrats actifs</span>
                    </div>
                    <button type="button"
                            class="border rounded-xl p-3 flex flex-col items-center gap-1 transition-all"
                            :class="(stats.freePlayersCount ?? 0) > 0
                                ? 'border-amber-200 bg-amber-50 hover:bg-amber-100'
                                : 'border-slate-200 bg-white'"
                            @click="router.get(route('game-saves.players.index', { gameSave: gameSave.id }))">
                        <span class="text-2xl font-black" :class="(stats.freePlayersCount ?? 0) > 0 ? 'text-amber-600' : 'text-slate-800'">
                            {{ stats.freePlayersCount ?? '—' }}
                        </span>
                        <span class="text-[10px] font-bold uppercase tracking-wider"
                              :class="(stats.freePlayersCount ?? 0) > 0 ? 'text-amber-600' : 'text-slate-400'">
                            Joueurs libres
                        </span>
                    </button>
                </div>

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
                        <h4 class="text-sm font-semibold text-slate-700">Équipes</h4>
                        <p class="text-xs text-slate-500">Accéder à la gestion complète des équipes.</p>
                        <button type="button" class="mt-2 px-3 py-1.5 text-xs rounded-full bg-teal-500 hover:bg-teal-600 text-white font-semibold"
                                @click="router.get(route('game-saves.teams.index', { gameSave: gameSave.id }))">
                            Gérer les équipes
                        </button>
                    </div>
                </div>

                <!-- Raccourci : contrats arrivant à échéance -->
                <div v-if="expiringContracts.length" class="border border-rose-200 rounded-lg bg-rose-50 p-3 flex flex-col gap-2">
                    <h4 class="text-sm font-semibold text-rose-700">Contrats arrivant à échéance</h4>
                    <p class="text-xs text-rose-600">{{ expiringContracts.length }} contrat(s) se terminent dans les 4 prochaines semaines.</p>
                    <ul class="flex flex-col gap-1">
                        <li v-for="c in expiringContracts" :key="c.id"
                            class="flex items-center justify-between text-xs bg-white rounded-lg px-3 py-1.5 border border-rose-100">
                            <span class="text-slate-700 font-medium">{{ c.player }} <span class="text-slate-400 font-normal">— {{ c.team }}</span></span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">Semaine {{ c.end_week }}</span>
                        </li>
                    </ul>
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
