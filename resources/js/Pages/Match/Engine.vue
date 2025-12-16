<template>
    <Head title="Match" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Match</H2>
        </template>

        <div class="w-full px-4 lg:px-8 mt-4">
            <div id="game-wrapper" ref="gameRoot" class="mx-auto w-full max-w-[1500px]">
                <!-- TOP BAR : 2/3 + 1/3 -->
                <div
                    id="top-bar"
                    class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4 items-stretch"
                >
                    <!-- Score (2 cols / 3) -->
                    <div
                        id="score-strip"
                        class="lg:col-span-2 grid grid-cols-3 items-center rounded-lg px-6 py-6 text-white shadow-md text-sm bg-gradient-to-b from-neutral-600 to-neutral-800 border-2 border-white"
                    >
                        <div class="flex items-center gap-3 justify-start">
                            <Link
                                :href="route('game-saves.play', { gameSave: engineConfig.gameSaveId })"
                                class="bg-gradient-to-br from-slate-500 to-slate-600 hover:bg-slate-700 font-semibold py-1 px-5 border-2 border-slate-50 rounded-full drop-shadow-md"
                            >
                                Retour
                            </Link>
                            <div id="match-end-actions" class="hidden">
                                <button
                                    id="btn-finish-match"
                                    type="button"
                                    class="bg-gradient-to-br from-teal-400 to-teal-500 hover:bg-teal-700 font-semibold py-1 px-5 border-2 border-teal-50 rounded-full drop-shadow-md"
                                >
                                    Suite
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-center gap-3">
                            <span class="px-3 font-bold" id="team-name-internal">Domicile</span>
                            <span class="px-3 text-lg font-extrabold" id="score-internal">0</span>
                            <span class="px-2">-</span>
                            <span class="px-3 text-lg font-extrabold" id="score-external">0</span>
                            <span class="px-3 font-bold" id="team-name-external">Exterieur</span>
                        </div>

                        <div class="flex items-center justify-end text-xs opacity-80">
                            Tours : <span id="turns-display" class="ml-1">40</span>/40
                        </div>
                    </div>

                    <!-- Control panel (1 col / 3) -->
                    <div
                        id="control-panel"
                        class="lg:col-span-1 h-full rounded-xl bg-white/90 shadow-md px-4 py-3 flex items-center justify-center"
                    >
                        <div class="flex items-center gap-3 justify-center">
                            <button
                                id="mode-one-player"
                                class="rounded-full px-5 py-2 text-xs font-semibold shadow-sm bg-blue-100 text-slate-800 hover:brightness-105 active:translate-y-px"
                            >
                                Mode 2 joueurs
                            </button>

                            <select
                                id="controlled-team-select"
                                class="rounded-full px-8 py-2 text-xs font-semibold bg-slate-50 border border-slate-200 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
                            >
                                <option value="internal">Domicile</option>
                                <option value="external">Exterieur</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- message panel (JS only) -->
                <div id="message-panel" class="visually-hidden">
                    <div id="message-main"></div>
                    <div id="message-sub"></div>
                </div>

                <!-- MAIN ROW -->
                <div
                    id="main-row"
                    class="grid grid-cols-1 lg:grid-cols-10 gap-6 items-stretch"
                >
                    <!-- Stats Cards (HOME / AWAY) -->
                    <div id="left-column" class="lg:col-span-2 min-w-0 flex flex-col justify-around gap-4">

                        <!-- HOME CARD (Domicile) -->
                        <div
                            id="home-card"
                            class="min-w-0 rounded-2xl bg-white p-4 shadow-lg ring-1 ring-white/80 flex flex-col gap-3"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    id="home-portrait"
                                    class="shrink-0 h-[70px] w-[70px] rounded-xl shadow-md bg-gradient-to-br from-slate-100 via-slate-200 to-slate-400 relative overflow-hidden"
                                ></div>

                                <div class="min-w-0 flex-1 text-xs space-y-0.5">
                                    <div id="home-name" class="text-base font-extrabold truncate">—</div>
                                    <div>Poste : <span id="home-role" class="font-semibold">—</span></div>
                                    <div>Numéro : <span id="home-number" class="font-semibold">—</span></div>
                                    <div>Équipe : <span id="home-team" class="font-semibold">Domicile</span></div>
                                </div>
                            </div>

                            <!-- mêmes stats sur les 2 cartes (plus simple + cohérent) -->
                            <div
                                class="grid grid-cols-2 gap-x-3 gap-y-1 rounded-xl bg-slate-100 p-2 text-[11px] ring-1 ring-black/5"
                            >
                                <div class="flex justify-between"><span>Shot :</span> <strong id="home-stat-shot">—</strong></div>
                                <div class="flex justify-between"><span>Block :</span> <strong id="home-stat-block">—</strong></div>
                                <div class="flex justify-between"><span>Pass :</span> <strong id="home-stat-pass">—</strong></div>
                                <div class="flex justify-between"><span>Intercept :</span> <strong id="home-stat-intercept">—</strong></div>
                                <div class="flex justify-between"><span>Dribble :</span> <strong id="home-stat-dribble">—</strong></div>
                                <div class="flex justify-between"><span>Tackle :</span> <strong id="home-stat-tackle">—</strong></div>
                                <div class="flex justify-between"><span>Attack :</span> <strong id="home-stat-attack">—</strong></div>
                                <div class="flex justify-between"><span>Défense :</span> <strong id="home-stat-defense">—</strong></div>

                                <div class="flex justify-between"><span>Arrêt :</span> <strong id="home-stat-hand_save">—</strong></div>
                                <div class="flex justify-between"><span>Poings :</span> <strong id="home-stat-punch_save">—</strong></div>
                            </div>

                            <div
                                class="h-2.5 w-full overflow-hidden rounded-full bg-gradient-to-r from-blue-200 to-white shadow-inner ring-1 ring-black/5"
                            >
                                <div id="home-energy-fill" class="h-full w-full e-high"></div>
                            </div>
                        </div>

                        <!-- AWAY CARD (Extérieur) -->
                        <div
                            id="away-card"
                            class="min-w-0 rounded-2xl bg-white p-4 shadow-lg ring-1 ring-white/80 flex flex-col gap-3"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    id="away-portrait"
                                    class="shrink-0 h-[70px] w-[70px] rounded-xl shadow-md bg-gradient-to-br from-slate-100 via-slate-200 to-slate-400 relative overflow-hidden"
                                ></div>

                                <div class="min-w-0 flex-1 text-xs space-y-0.5">
                                    <div id="away-name" class="text-base font-extrabold truncate">—</div>
                                    <div>Poste : <span id="away-role" class="font-semibold">—</span></div>
                                    <div>Numéro : <span id="away-number" class="font-semibold">—</span></div>
                                    <div>Équipe : <span id="away-team" class="font-semibold">Extérieur</span></div>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-2 gap-x-3 gap-y-1 rounded-xl bg-slate-100 p-2 text-[11px] ring-1 ring-black/5"
                            >
                                <div class="flex justify-between"><span>Shot :</span> <strong id="away-stat-shot">—</strong></div>
                                <div class="flex justify-between"><span>Block :</span> <strong id="away-stat-block">—</strong></div>
                                <div class="flex justify-between"><span>Pass :</span> <strong id="away-stat-pass">—</strong></div>
                                <div class="flex justify-between"><span>Intercept :</span> <strong id="away-stat-intercept">—</strong></div>
                                <div class="flex justify-between"><span>Dribble :</span> <strong id="away-stat-dribble">—</strong></div>
                                <div class="flex justify-between"><span>Tackle :</span> <strong id="away-stat-tackle">—</strong></div>
                                <div class="flex justify-between"><span>Attack :</span> <strong id="away-stat-attack">—</strong></div>
                                <div class="flex justify-between"><span>Défense :</span> <strong id="away-stat-defense">—</strong></div>

                                <div class="flex justify-between"><span>Arrêt :</span> <strong id="away-stat-hand_save">—</strong></div>
                                <div class="flex justify-between"><span>Poings :</span> <strong id="away-stat-punch_save">—</strong></div>
                            </div>

                            <div
                                class="h-2.5 w-full overflow-hidden rounded-full bg-gradient-to-r from-rose-100 to-white shadow-inner ring-1 ring-black/5"
                            >
                                <div id="away-energy-fill" class="h-full w-full e-high"></div>
                            </div>
                        </div>
                    </div>

                    <!-- CENTER field -->
                    <div id="field-wrapper" class="lg:col-span-6 min-w-0 relative">
                        <div id="ai-turn-overlay">Tour de l'IA…</div>

                        <div id="field">
                            <div id="border-rect" class="pitch-line"></div>
                            <div id="halfway-line" class="pitch-line"></div>
                            <div id="center-circle"></div>
                            <div class="penalty-box" id="penalty-left"></div>
                            <div class="penalty-box" id="penalty-right"></div>
                            <div class="goal-area" id="goal-area-left"></div>
                            <div class="goal-area" id="goal-area-right"></div>
                            <div class="spot" id="center-spot"></div>
                            <div class="spot" id="pen-spot-left"></div>
                            <div class="spot" id="pen-spot-right"></div>
                            <div class="goal" id="goal-left"></div>
                            <div class="goal" id="goal-right"></div>

                            <div id="ball"></div>

                            <!-- INTERNAL -->
                            <div class="player internal goalkeeper" data-player="I1" style="left: 7%; top: 50%;">1</div>
                            <div class="player internal" data-player="I2" style="left: 20%; top: 25%;">2</div>
                            <div class="player internal" data-player="I3" style="left: 20%; top: 50%;">3</div>
                            <div class="player internal" data-player="I4" style="left: 20%; top: 75%;">4</div>
                            <div class="player internal" data-player="I5" style="left: 35%; top: 40%;">5</div>
                            <div class="player internal" data-player="I6" style="left: 35%; top: 60%;">6</div>
                            <div class="player internal" data-player="I7" style="left: 55%; top: 20%;">7</div>
                            <div class="player internal" data-player="I8" style="left: 55%; top: 50%;">8</div>
                            <div class="player internal" data-player="I9" style="left: 55%; top: 80%;">9</div>
                            <div class="player internal" data-player="I10" style="left: 75%; top: 35%;">10</div>
                            <div class="player internal" data-player="I11" style="left: 75%; top: 65%;">11</div>

                            <!-- EXTERNAL -->
                            <div class="player external goalkeeper" data-player="E1" style="left: 93%; top: 50%;">1</div>
                            <div class="player external" data-player="E2" style="left: 80%; top: 25%;">2</div>
                            <div class="player external" data-player="E3" style="left: 80%; top: 50%;">3</div>
                            <div class="player external" data-player="E4" style="left: 80%; top: 75%;">4</div>
                            <div class="player external" data-player="E5" style="left: 65%; top: 40%;">5</div>
                            <div class="player external" data-player="E6" style="left: 65%; top: 60%;">6</div>
                            <div class="player external" data-player="E7" style="left: 45%; top: 20%;">7</div>
                            <div class="player external" data-player="E8" style="left: 45%; top: 50%;">8</div>
                            <div class="player external" data-player="E9" style="left: 45%; top: 80%;">9</div>
                            <div class="player external" data-player="E10" style="left: 25%; top: 35%;">10</div>
                            <div class="player external" data-player="E11" style="left: 25%; top: 65%;">11</div>

                            <div id="turn-indicator">00</div>
                        </div>
                    </div>

                    <!-- RIGHT log story -->
                    <div class="lg:col-span-2 min-w-0 flex min-h-0">
                        <div
                            id="log-card"
                            class="w-full h-full min-h-0 rounded-2xl bg-white p-3 shadow-lg ring-1 ring-white/80 text-[11px] flex flex-col gap-2"
                        >
                            <div id="log-current">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="text-[10px] uppercase tracking-widest font-bold text-slate-600">DERNIÈRE ACTION</div>
                                    <div id="duel-dice-display" class="dice-chip"></div>
                                </div>
                                <div id="current-action-title" class="mt-2 font-bold text-slate-900">–</div>
                                <div id="current-action-detail" class="text-slate-600">Les duels et détails apparaîtront ici.</div>
                            </div>

                            <div class="h-px w-full bg-black/10"></div>

                            <div id="log-history" class="flex-1 min-h-0 overflow-y-auto">
                                <div class="text-[10px] uppercase tracking-widest font-bold text-slate-600 mb-2">
                                    Historique (15 derniers coups)
                                </div>
                                <ul id="history-list" class="space-y-1"></ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SKILL BARS -->
                <div id="bars-container">
                    <div id="action-bar"></div>
                    <div id="info-text"></div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, ref, onBeforeUnmount } from 'vue';
import { initMatchEngine } from './engine';
import './engine.css';

const props = defineProps({
    engineConfig: { type: Object, required: true },
});

const gameRoot = ref(null);

onMounted(() => {
    if (!gameRoot.value) return;

    initMatchEngine(gameRoot.value, {
        ...props.engineConfig,
        onMatchEnd: ({ matchId, gameSaveId, scoresByTeamId }) => {
            router.post(
                route('game-saves.matches.finish', { gameSave: gameSaveId, match: matchId }),
                { scoresByTeamId },
                {
                    preserveScroll: true,
                    onSuccess: () => router.visit(route('game-saves.play', { gameSave: gameSaveId })),
                }
            );
        },
    });
});

onBeforeUnmount(() => {});
</script>
