<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    gameSave: { type: Object, required: true },
    recap:    { type: Object, default: null },
});

const isStarting = ref(false);

const verdict   = computed(() => props.recap?.career_verdict ?? null);
const isGameOver = computed(() => ['fired', 'won'].includes(verdict.value?.outcome));

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
        <div class="min-h-full bg-slate-50 p-2 sm:p-4">
            <div class="max-w-4xl mx-auto space-y-6">

                <div class="text-center py-6">
                    <p class="text-sm uppercase tracking-widest text-slate-400">Fin de la saison {{ recap?.season ?? gameSave.season }}</p>
                    <h1 class="text-3xl font-bold text-slate-800 mt-1">Bilan de la saison</h1>
                </div>

                <!-- Verdict de la direction -->
                <div v-if="verdict"
                     class="rounded-2xl shadow-lg p-5 border-l-4"
                     :class="verdict.outcome === 'won' ? 'bg-emerald-50 border-emerald-500'
                         : verdict.outcome === 'fired' ? 'bg-rose-50 border-rose-500'
                         : verdict.met ? 'bg-teal-50 border-teal-500'
                         : 'bg-amber-50 border-amber-500'">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-widest font-bold"
                               :class="verdict.outcome === 'won' ? 'text-emerald-600'
                                   : verdict.outcome === 'fired' ? 'text-rose-600'
                                   : verdict.met ? 'text-teal-600' : 'text-amber-600'">
                                Verdict de la direction
                            </p>
                            <h3 class="text-lg font-bold text-slate-800 mt-1">
                                <template v-if="verdict.outcome === 'won'">🏆 Mission accomplie — {{ verdict.titles_won }} titre(s) !</template>
                                <template v-else-if="verdict.outcome === 'fired'">📉 La direction met fin à ton mandat</template>
                                <template v-else-if="verdict.met">✅ Objectif atteint</template>
                                <template v-else>⚠️ Objectif manqué</template>
                            </h3>
                            <p class="text-sm text-slate-600 mt-1">
                                Objectif : <strong>{{ verdict.mandate }}</strong> ·
                                classement final : <strong>{{ verdict.rank }}<sup>e</sup></strong>
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-[10px] uppercase tracking-wider text-slate-400">Confiance</p>
                            <p class="text-2xl font-black"
                               :class="verdict.confidence <= 25 ? 'text-rose-500' : 'text-slate-700'">
                                {{ verdict.confidence }}
                            </p>
                            <p class="text-xs font-semibold"
                               :class="verdict.delta >= 0 ? 'text-emerald-600' : 'text-rose-500'">
                                {{ verdict.delta >= 0 ? '+' : '' }}{{ verdict.delta }}
                            </p>
                        </div>
                    </div>
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
                    <div class="px-4 sm:px-6 py-4 border-b border-slate-100">
                        <h3 class="font-semibold text-slate-700">Classement final & primes</h3>
                    </div>
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[500px]">
                        <thead class="text-slate-400 uppercase text-xs">
                            <tr>
                                <th class="text-left px-6 py-2">#</th>
                                <th class="text-left px-2 py-2">Équipe</th>
                                <th class="text-center px-2 py-2">V</th>
                                <th class="text-center px-2 py-2">N</th>
                                <th class="text-center px-2 py-2">D</th>
                                <th class="text-center px-2 py-2">Diff</th>
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
                                <td class="text-center px-2 py-2"
                                    :class="(row.goal_diff ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-500'">
                                    {{ (row.goal_diff ?? 0) > 0 ? '+' : '' }}{{ row.goal_diff ?? 0 }}
                                </td>
                                <td class="text-center px-2 py-2 font-semibold">{{ row.points }}</td>
                                <td class="text-right px-6 py-2 text-emerald-600 font-medium">+{{ row.prize.toLocaleString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>

                <!-- Joueurs en rupture avec le coach / le club -->
                <div v-if="recap?.transfer_requests?.length" class="bg-white rounded-2xl shadow-lg overflow-hidden border-l-4 border-rose-400">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="font-semibold text-slate-700">⚠️ Joueurs en rupture</h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Ces joueurs finissent la saison en rupture. Ceux fâchés contre le coach
                            refuseront d'être draftés par ton équipe la saison prochaine.
                        </p>
                    </div>
                    <ul class="divide-y divide-slate-50">
                        <li v-for="req in recap.transfer_requests" :key="req.player_id" class="px-6 py-3 flex items-center gap-3">
                            <img v-if="req.photo_path" :src="`/storage/${req.photo_path}`" class="w-10 h-10 rounded-full object-cover bg-slate-100" />
                            <div v-else class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-300">👤</div>
                            <div class="flex-1">
                                <p class="font-semibold text-slate-700">{{ req.name }}</p>
                                <p class="text-xs text-rose-500">
                                    {{ req.reason === 'coach' ? 'Fâché contre le coach — refusera ta draft' : 'Révolté contre le club' }}
                                    · moral {{ req.morale }} · relation {{ req.affinity > 0 ? '+' : '' }}{{ req.affinity }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="text-center pb-10">
                    <p class="text-sm text-slate-500 mb-3">
                        <template v-if="isGameOver">
                            Ta carrière s'achève ici. Découvre le bilan de ton passage sur le banc.
                        </template>
                        <template v-else>
                            Tous les contrats arrivent à expiration. Une nouvelle draft va commencer —
                            l'ordre de sélection est inversé par rapport au classement.
                        </template>
                    </p>
                    <button
                        @click="startNewSeason"
                        :disabled="isStarting"
                        class="px-8 py-3 rounded-xl text-white font-semibold shadow-lg transition disabled:opacity-50"
                        :class="isGameOver ? 'bg-slate-700 hover:bg-slate-800' : 'bg-emerald-600 hover:bg-emerald-700'"
                    >
                        <template v-if="isStarting">Préparation…</template>
                        <template v-else-if="isGameOver">Voir le bilan de carrière →</template>
                        <template v-else>{{ `Démarrer la saison ${(recap?.season ?? gameSave.season) + 1}` }}</template>
                    </button>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
