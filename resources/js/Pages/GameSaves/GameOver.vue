<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    gameSave: { type: Object, required: true },
    career:   { type: Object, required: true },
    lastRecap: { type: Object, default: null },
});

const won = computed(() => props.career.status === 'won');

const difficultyLabel = {
    survival: 'Survie',
    standard: 'Standard',
    conquest: 'Conquête',
};
</script>

<template>
    <Head :title="won ? 'Carrière accomplie' : 'Mandat terminé'" />

    <AuthenticatedLayout>
        <div class="min-h-full p-2 sm:p-4"
             :class="won ? 'bg-gradient-to-b from-emerald-50 to-slate-50' : 'bg-gradient-to-b from-rose-50 to-slate-50'">
            <div class="max-w-3xl mx-auto space-y-6">

                <!-- Bandeau principal -->
                <div class="text-center py-10 rounded-3xl shadow-xl text-white"
                     :class="won ? 'bg-gradient-to-br from-emerald-500 to-teal-600' : 'bg-gradient-to-br from-rose-500 to-slate-700'">
                    <div class="text-6xl mb-3">{{ won ? '🏆' : '📉' }}</div>
                    <p class="text-sm uppercase tracking-[0.3em] opacity-80">
                        {{ won ? 'Carrière légendaire' : 'Fin du mandat' }}
                    </p>
                    <h1 class="text-3xl font-black mt-2 px-4">
                        <template v-if="won">Tu entres dans l'histoire du club</template>
                        <template v-else>La direction t'a remercié</template>
                    </h1>
                    <p class="mt-3 text-sm opacity-90 max-w-lg mx-auto px-6">
                        <template v-if="won">
                            Objectif de carrière atteint : {{ career.titles_won }} titre(s) de champion
                            remporté(s) sur les {{ career.titles_required }} attendu(s). Mission accomplie.
                        </template>
                        <template v-else-if="career.fired_reason === 'mid_season'">
                            La confiance du board s'est effondrée en pleine saison. Ton aventure s'arrête
                            avec {{ career.titles_won }} titre(s) au compteur.
                        </template>
                        <template v-else>
                            Les résultats n'ont pas convaincu la direction. Ton mandat s'achève
                            avec {{ career.titles_won }} titre(s) au compteur.
                        </template>
                    </p>
                </div>

                <!-- Chiffres clés -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white rounded-2xl shadow p-4 text-center">
                        <p class="text-3xl font-black text-slate-800">{{ career.titles_won }}</p>
                        <p class="text-xs text-slate-400 uppercase tracking-wider mt-1">Titres</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow p-4 text-center">
                        <p class="text-3xl font-black text-slate-800">{{ career.history.length }}</p>
                        <p class="text-xs text-slate-400 uppercase tracking-wider mt-1">Saisons</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow p-4 text-center">
                        <p class="text-3xl font-black"
                           :class="career.confidence <= 25 ? 'text-rose-500' : 'text-slate-800'">
                            {{ career.confidence }}
                        </p>
                        <p class="text-xs text-slate-400 uppercase tracking-wider mt-1">Confiance</p>
                    </div>
                </div>

                <!-- Palmarès saison par saison -->
                <div v-if="career.history.length" class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-semibold text-slate-700">Parcours — mandat {{ difficultyLabel[career.difficulty] ?? career.difficulty }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[400px]">
                        <thead class="text-slate-400 uppercase text-xs">
                            <tr>
                                <th class="text-left px-6 py-2">Saison</th>
                                <th class="text-left px-2 py-2">Objectif</th>
                                <th class="text-center px-2 py-2">Rang</th>
                                <th class="text-center px-2 py-2">Bilan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="v in career.history" :key="v.season" class="border-t border-slate-50">
                                <td class="px-6 py-2 font-semibold text-slate-600">{{ v.season }}</td>
                                <td class="px-2 py-2 text-slate-500">{{ v.mandate }}</td>
                                <td class="text-center px-2 py-2 font-semibold">
                                    {{ v.rank }}<sup>e</sup>
                                    <span v-if="v.champion"> 🏆</span>
                                </td>
                                <td class="text-center px-2 py-2">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                          :class="v.met ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600'">
                                        {{ v.met ? 'Atteint' : 'Manqué' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3 pb-10">
                    <Link :href="route('mainMenu')"
                          class="px-6 py-3 rounded-xl bg-white border border-slate-200 text-slate-600 font-semibold shadow hover:bg-slate-50 transition text-center">
                        Menu principal
                    </Link>
                    <Link :href="route('game-saves.create')"
                          class="px-6 py-3 rounded-xl bg-teal-600 text-white font-semibold shadow-lg hover:bg-teal-700 transition text-center">
                        Nouvelle partie
                    </Link>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
