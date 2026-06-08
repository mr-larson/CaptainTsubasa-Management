<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    gameSave: { type: Object, required: true },
    recap:    { type: Object, default: null },
});

const isStarting = ref(false);

function startNewSeason() {
    if (isStarting.value) return;
    isStarting.value = true;

    router.post(route('game-saves.season-end.continue', props.gameSave.id), {}, {
        onError: () => { isStarting.value = false; },
    });
}
</script>

<template>
    <Head title="Fin de saison" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-50 p-4">
            <div class="max-w-4xl mx-auto space-y-6">

                <div class="text-center py-6">
                    <p class="text-sm uppercase tracking-widest text-slate-400">Fin de la saison {{ recap?.season ?? gameSave.season }}</p>
                    <h1 class="text-3xl font-bold text-slate-800 mt-1">Bilan de la saison</h1>
                </div>

                <!-- Champion -->
                <div v-if="recap?.champion" class="bg-gradient-to-br from-amber-400 to-amber-600 rounded-2xl shadow-xl p-6 text-center text-white">
                    <p class="text-sm uppercase tracking-widest opacity-80">Champion</p>
                    <div class="flex items-center justify-center gap-3 mt-2">
                        <img v-if="recap.champion.logo_path" :src="`/storage/${recap.champion.logo_path}`" class="w-12 h-12 rounded-full bg-white/20 object-contain" />
                        <h2 class="text-2xl font-bold">{{ recap.champion.name }}</h2>
                    </div>
                </div>

                <!-- MVP -->
                <div v-if="recap?.mvp" class="bg-white rounded-2xl shadow-lg p-6 flex items-center gap-4">
                    <img v-if="recap.mvp.photo_path" :src="`/storage/${recap.mvp.photo_path}`" class="w-16 h-16 rounded-full object-cover bg-slate-100" />
                    <div>
                        <p class="text-xs uppercase tracking-widest text-slate-400">MVP de la saison</p>
                        <h3 class="text-xl font-bold text-slate-800">{{ recap.mvp.name }}</h3>
                        <p class="text-sm text-slate-500">
                            {{ recap.mvp.team_name }} · {{ recap.mvp.position }} ·
                            {{ recap.mvp.goals }} but(s)
                        </p>
                    </div>
                </div>

                <!-- Classement final & primes -->
                <div v-if="recap?.standings" class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="font-semibold text-slate-700">Classement final & primes</h3>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="text-slate-400 uppercase text-xs">
                            <tr>
                                <th class="text-left px-6 py-2">#</th>
                                <th class="text-left px-2 py-2">Équipe</th>
                                <th class="text-center px-2 py-2">V</th>
                                <th class="text-center px-2 py-2">N</th>
                                <th class="text-center px-2 py-2">D</th>
                                <th class="text-center px-2 py-2">Pts</th>
                                <th class="text-right px-6 py-2">Prime</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in recap.standings" :key="row.team_id"
                                class="border-t border-slate-50"
                                :class="{ 'bg-amber-50': row.rank === 1 }">
                                <td class="px-6 py-2 font-semibold text-slate-600">{{ row.rank }}</td>
                                <td class="px-2 py-2 flex items-center gap-2">
                                    <img v-if="row.logo_path" :src="`/storage/${row.logo_path}`" class="w-6 h-6 rounded-full object-contain bg-slate-100" />
                                    {{ row.name }}
                                </td>
                                <td class="text-center px-2 py-2">{{ row.wins }}</td>
                                <td class="text-center px-2 py-2">{{ row.draws }}</td>
                                <td class="text-center px-2 py-2">{{ row.losses }}</td>
                                <td class="text-center px-2 py-2 font-semibold">{{ row.points }}</td>
                                <td class="text-right px-6 py-2 text-emerald-600 font-medium">+{{ row.prize.toLocaleString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="text-center pb-10">
                    <p class="text-sm text-slate-500 mb-3">
                        Tous les contrats arrivent à expiration. Une nouvelle draft va commencer —
                        l'ordre de sélection est inversé par rapport au classement.
                    </p>
                    <button
                        @click="startNewSeason"
                        :disabled="isStarting"
                        class="px-8 py-3 rounded-xl bg-emerald-600 text-white font-semibold shadow-lg hover:bg-emerald-700 transition disabled:opacity-50"
                    >
                        {{ isStarting ? 'Préparation…' : `Démarrer la saison ${(recap?.season ?? gameSave.season) + 1}` }}
                    </button>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
