<script setup>
import NationFlag from '@/Components/NationFlag.vue';
// Écran de fin de tournoi (Coupe du Monde) : podium, parcours de chaque manager, buteurs, chiffres clés.
// Domaine : Coupe du Monde. Rendu par GameSeasonController::tournamentEnd (route game-saves.tournament-end),
// atteint à la clôture de la dernière semaine (WeekClosingService::OUTCOME_TOURNAMENT_END) ou depuis le Dashboard.
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import H2 from '@/Components/H2.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { roundTitle } from '@/Pages/GameSaves/Play/matchResults.js';

const props = defineProps({
    gameSave: { type: Object, required: true },
    summary:  { type: Object, required: true },
});

const MEDALS = { 1: '🥇', 2: '🥈', 3: '🥉', 4: '4e' };
const winner = computed(() => props.summary.managers.find(m => m.is_champion) ?? null);
const biggest = computed(() => props.summary.figures.biggest_win);

/** Classes du badge d'un résultat de match vu par le manager. */
const badge = (g) => g.for > g.against || g.shootout === true
    ? 'bg-emerald-500 text-white'
    : (g.for === g.against && g.shootout === null ? 'bg-slate-300 text-slate-700' : 'bg-rose-500 text-white');
const letter = (g) => g.for > g.against || g.shootout === true ? 'V' : (g.for === g.against && g.shootout === null ? 'N' : 'D');
</script>

<template>
    <Head title="Fin du tournoi" />

    <AuthenticatedLayout>
        <template #header>
            <H2>🌍 Fin de la Coupe du Monde</H2>
        </template>

        <div class="min-h-full p-2 sm:p-4 bg-gradient-to-b from-amber-50 to-slate-50">
            <div class="max-w-4xl mx-auto space-y-6">

                <!-- Champion -->
                <div class="text-center py-10 rounded-3xl shadow-xl text-white bg-gradient-to-br from-amber-500 to-indigo-600">
                    <div class="text-6xl mb-3">🏆</div>
                    <p class="text-sm uppercase tracking-[0.3em] opacity-80">Champion du monde</p>
                    <h1 class="text-3xl font-black mt-2 px-4"><NationFlag :flag="summary.champion?.flag" :url="summary.champion?.flag_url" :name="summary.champion?.name" /> {{ summary.champion?.name }}</h1>
                    <p v-if="winner" class="mt-3 text-sm opacity-90">
                        Siège {{ winner.seat }} : tu as mené {{ winner.name }} au titre mondial.
                    </p>
                </div>

                <!-- Podium -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div v-for="team in summary.podium" :key="team.rank"
                         class="rounded-2xl border bg-white p-4 text-center shadow-card"
                         :class="team.rank === 1 ? 'border-amber-300' : 'border-slate-200'">
                        <div class="text-2xl">{{ MEDALS[team.rank] }}</div>
                        <div class="text-3xl my-1"><NationFlag :flag="team.flag" :url="team.flag_url" :name="team.name" /></div>
                        <div class="text-sm font-bold text-slate-700">{{ team.name }}</div>
                    </div>
                </div>

                <!-- Parcours des managers -->
                <div v-for="manager in summary.managers" :key="manager.team_id"
                     class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div>
                            <div class="text-xs font-bold text-indigo-500 uppercase tracking-widest">
                                Siège {{ manager.seat }}
                            </div>
                            <div class="text-lg font-bold text-slate-700"><NationFlag :flag="manager.flag" :url="manager.flag_url" :name="manager.name" /> {{ manager.name }}</div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm font-bold"
                              :class="manager.is_champion ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'">
                            {{ manager.result }}
                        </span>
                    </div>

                    <div class="text-xs text-slate-400 mb-3">
                        {{ manager.record.played }} matchs · {{ manager.record.wins }} V · {{ manager.record.draws }} N ·
                        {{ manager.record.losses }} D · {{ manager.record.gf }} buts pour · {{ manager.record.ga }} contre
                    </div>

                    <ul class="divide-y divide-slate-100">
                        <li v-for="(g, i) in manager.games" :key="i" class="flex items-center gap-3 py-2 text-sm">
                            <span class="w-6 h-6 rounded-full text-2xs font-bold flex items-center justify-center" :class="badge(g)">{{ letter(g) }}</span>
                            <span class="w-40 text-slate-400 text-xs">{{ roundTitle({ round: g.round }) }}</span>
                            <span class="flex-1 text-slate-700"><NationFlag :flag="g.flag" :url="g.flag_url" :name="g.opponent" /> {{ g.opponent }}</span>
                            <span class="font-bold text-slate-700 tabular-nums">{{ g.for }} - {{ g.against }}</span>
                            <span v-if="g.shootout !== null" class="text-2xs text-slate-400">t.a.b.</span>
                        </li>
                    </ul>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Buteurs -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                        <div class="text-sm font-bold text-slate-700 mb-3">Meilleurs buteurs</div>
                        <ol v-if="summary.scorers.length" class="space-y-2">
                            <li v-for="(s, i) in summary.scorers" :key="i" class="flex items-center gap-2 text-sm">
                                <span class="w-5 text-slate-300 font-bold">{{ i + 1 }}</span>
                                <span class="flex-1 text-slate-700">{{ s.name }} <span class="text-slate-400"><NationFlag :flag="s.flag" :url="s.flag_url" :name="s.team" /></span></span>
                                <span class="font-bold text-indigo-600">{{ s.goals }}</span>
                            </li>
                        </ol>
                        <p v-else class="text-xs text-slate-400">
                            Aucun but individuel n'a été enregistré pendant ce tournoi.
                        </p>
                    </div>

                    <!-- Chiffres clés -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                        <div class="text-sm font-bold text-slate-700 mb-3">Le tournoi en chiffres</div>
                        <dl class="grid grid-cols-3 gap-2 text-center">
                            <div><dt class="text-2xs text-slate-400">Matchs</dt><dd class="text-xl font-black text-slate-700">{{ summary.figures.matches }}</dd></div>
                            <div><dt class="text-2xs text-slate-400">Buts</dt><dd class="text-xl font-black text-slate-700">{{ summary.figures.goals }}</dd></div>
                            <div><dt class="text-2xs text-slate-400">Par match</dt><dd class="text-xl font-black text-slate-700">{{ summary.figures.average }}</dd></div>
                        </dl>
                        <p v-if="biggest" class="mt-3 text-xs text-slate-500">
                            Plus large score : {{ biggest.home }} {{ biggest.home_score }} - {{ biggest.away_score }} {{ biggest.away }}
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap justify-center gap-3 pb-6">
                    <Link :href="route('game-saves.Play', gameSave.id)"
                          class="px-5 py-2.5 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Revoir le tournoi
                    </Link>
                    <Link :href="route('game-saves.create')"
                          class="px-5 py-2.5 rounded-xl bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700">
                        Nouvelle Coupe du Monde
                    </Link>
                    <Link :href="route('game-saves.index')"
                          class="px-5 py-2.5 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Mes parties
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
