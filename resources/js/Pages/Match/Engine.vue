<template>
    <Head title="Match" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Match</H2>
        </template>

        <div class="w-full px-4 lg:px-8 mt-4">
            <div id="duel-dice-tooltip" class="dice-tooltip hidden" aria-hidden="true"></div>
            <div id="game-wrapper" ref="gameRoot" class="mx-auto w-full max-w-[1500px]">

                <!-- =======================================================
                     TOP BAR : score (2/3) + control panel (1/3)
                     ======================================================= -->
                <div id="top-bar" class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4 items-stretch">

                    <!-- ======================
                         SCORE STRIP (2 cols)
                         ====================== -->
                    <div
                        id="score-strip"
                        class="lg:col-span-2 grid grid-cols-3 items-center rounded-xl px-6 py-6 text-neutral-800 shadow-md text-sm bg-white/90 shadow-md
                   border border-slate-200/70"
                    >
                        <!-- Left: actions -->
                        <div class="flex items-center gap-3 justify-start">
                            <Link
                                :href="route('game-saves.play', { gameSave: engineConfig.gameSaveId })"
                                class="bg-gradient-to-br from-slate-500 to-slate-600 hover:from-slate-600 hover:to-slate-700
                       font-semibold text-white py-1 px-5 border border-white/30 rounded-full drop-shadow-md"
                            >
                                Retour
                            </Link>

                            <!-- ⚠️ Ce bloc est togglé en JS (engine.js) -->
                            <div id="match-end-actions" class="hidden">
                                <button
                                    id="btn-finish-match"
                                    type="button"
                                    class="bg-gradient-to-br from-teal-400 to-teal-500 hover:from-teal-500 hover:to-teal-600
                         font-semibold text-white py-1 px-5 border border-white/30 rounded-full drop-shadow-md"
                                >
                                    Suite
                                </button>
                            </div>
                        </div>

                        <!-- Center: score + logos -->
                        <div class="flex items-center justify-center gap-4">

                            <!-- HOME / INTERNAL -->
                            <div class="flex items-center gap-2 min-w-[160px] justify-end">
                                <div class="h-12 w-12 rounded-md overflow-hidden flex items-center justify-center">
                                    <img
                                        v-if="homeLogoUrl"
                                        :src="homeLogoUrl"
                                        alt="Logo domicile"
                                        class="h-full w-full object-contain"
                                    />
                                    <span v-else class="text-[10px] opacity-60">—</span>
                                </div>

                                <!-- ⚠️ ID utilisé par engine.js -->
                                <span class="font-bold truncate max-w-[180px]" id="team-name-internal">
                  {{ homeName }}
                </span>
                            </div>

                            <!-- SCORE -->
                            <div class="flex items-center gap-2">
                                <!-- ⚠️ IDs utilisés par engine.js -->
                                <span class="text-lg font-extrabold tabular-nums" id="score-internal">0</span>
                                <span class="opacity-80">-</span>
                                <span class="text-lg font-extrabold tabular-nums" id="score-external">0</span>
                            </div>

                            <!-- AWAY / EXTERNAL -->
                            <div class="flex items-center gap-2 min-w-[160px] justify-start">
                <span class="font-bold truncate max-w-[180px]" id="team-name-external">
                  {{ awayName }}
                </span>

                                <div class="h-12 w-12 rounded-md overflow-hidden flex items-center justify-center">
                                    <img
                                        v-if="awayLogoUrl"
                                        :src="awayLogoUrl"
                                        alt="Logo extérieur"
                                        class="h-full w-full object-contain"
                                    />
                                    <span v-else class="text-[10px] opacity-60">—</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: turns -->
                        <div class="flex items-center justify-end text-xs opacity-80">
                            Tours : <span id="turns-display" class="ml-1 tabular-nums">00</span>/40
                        </div>
                    </div>

                    <!-- ======================
                         CONTROL PANEL (1 col)
                         ====================== -->
                    <div
                        id="control-panel"
                        class="lg:col-span-1 h-full rounded-xl bg-white/90 shadow-md px-4 py-3 flex items-center justify-center
                   border border-slate-200/70"
                    >
                        <div class="flex items-center gap-3 justify-center">
                            <!-- ⚠️ ID utilisé par engine.js -->
                            <button
                                id="mode-one-player"
                                class="rounded-full px-5 py-2 text-xs font-semibold shadow-sm bg-slate-100 border border-slate-200 text-slate-800 hover:bg-slate-200 active:translate-y-px"
                            >
                                Mode 2 joueurs
                            </button>

                            <!-- ⚠️ ID utilisé par engine.js -->
                            <select
                                id="controlled-team-select"
                                class="rounded-full px-8 py-2 text-xs font-semibold bg-slate-100 border border-slate-200 shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-slate-300"
                            >
                                <option value="internal">Domicile</option>
                                <option value="external">Exterieur</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- =======================================================
                     MESSAGE PANEL (JS only)
                     ======================================================= -->
                <div id="message-panel" class="visually-hidden">
                    <div id="message-main"></div>
                    <div id="message-sub"></div>
                </div>

                <!-- =======================================================
                     MAIN ROW : left cards / field / right log
                     ======================================================= -->
                <div id="main-row" class="grid grid-cols-1 lg:grid-cols-10 gap-6 items-stretch">

                    <!-- LEFT COLUMN : Stats cards -->
                    <div id="left-column" class="lg:col-span-2 min-w-0 flex flex-col justify-around gap-4">

                        <!-- HOME CARD -->
                        <div
                            id="home-card"
                            class="min-w-0 rounded-2xl bg-white p-4 shadow-lg ring-1 ring-white/80 flex flex-col gap-3 relative"
                        >
                            <div
                                id="home-ball-icon"
                                class="hidden absolute top-2 right-2 h-5 w-5 flex items-center justify-center text-sm"
                                title="Porte le ballon"
                            >⚽</div>

                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    id="home-portrait"
                                    class="shrink-0 h-[80px] w-[80px] rounded shadow-md bg-gradient-to-br from-slate-100 via-slate-200 to-slate-400 relative overflow-hidden"
                                ></div>

                                <div class="min-w-0 flex-1 text-xs space-y-0.5">
                                    <div id="home-name" class="text-base font-bold truncate">—</div>
                                    <div>Poste : <span id="home-role" class="font-semibold">—</span></div>
                                    <div>Numéro : <span id="home-number" class="font-semibold">—</span></div>
                                    <div>Équipe : <span id="home-team" class="font-semibold">{{ homeName }}</span></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-x-3 gap-y-1 rounded-xl bg-slate-100 p-2 text-[11px] ring-1 ring-black/5">
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

                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gradient-to-r from-blue-200 to-white shadow-inner ring-1 ring-black/5">
                                <div id="home-energy-fill" class="h-full w-full e-high"></div>
                            </div>
                        </div>

                        <!-- AWAY CARD -->
                        <div
                            id="away-card"
                            class="min-w-0 rounded-2xl bg-white p-4 shadow-lg ring-1 ring-white/80 flex flex-col gap-3 relative"
                        >
                            <div
                                id="away-ball-icon"
                                class="hidden absolute top-2 right-2 h-5 w-5 flex items-center justify-center text-sm"
                                title="Porte le ballon"
                            >⚽</div>

                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    id="away-portrait"
                                    class="shrink-0 h-[80px] w-[80px] rounded shadow-md bg-gradient-to-br from-slate-100 via-slate-200 to-slate-400 relative overflow-hidden"
                                ></div>

                                <div class="min-w-0 flex-1 text-xs space-y-0.5">
                                    <div id="away-name" class="text-base font-bold truncate">—</div>
                                    <div>Poste : <span id="away-role" class="font-semibold">—</span></div>
                                    <div>Numéro : <span id="away-number" class="font-semibold">—</span></div>
                                    <div>Équipe : <span id="away-team" class="font-semibold">{{ awayName }}</span></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-x-3 gap-y-1 rounded-xl bg-slate-100 p-2 text-[11px] ring-1 ring-black/5">
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

                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gradient-to-r from-rose-100 to-white shadow-inner ring-1 ring-black/5">
                                <div id="away-energy-fill" class="h-full w-full e-high"></div>
                            </div>
                        </div>
                    </div>

                    <!-- CENTER : field -->
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

                            <!-- ZONE 0 : GARDIEN -->
                            <div class="player internal goalkeeper" data-player="I1" data-zone="0" style="left: 10%; top: 50%;">1</div>

                            <!-- ZONE 1 : DÉFENSEURS -->
                            <div class="player internal" data-player="I2" data-zone="1" style="left: 20%; top: 25%;">2</div>
                            <div class="player internal" data-player="I3" data-zone="1" style="left: 20%; top: 50%;">3</div>
                            <div class="player internal" data-player="I4" data-zone="1" style="left: 20%; top: 75%;">4</div>

                            <!-- ZONE 2 : MILIEUX DÉFENSIFS -->
                            <div class="player internal" data-player="I5" data-zone="2" style="left: 35%; top: 40%;">5</div>
                            <div class="player internal" data-player="I6" data-zone="2" style="left: 35%; top: 60%;">6</div>

                            <!-- ZONE 3 : MILIEUX OFFENSIFS -->
                            <div class="player internal" data-player="I7" data-zone="3" style="left: 55%; top: 20%;">7</div>
                            <div class="player internal" data-player="I8" data-zone="3" style="left: 55%; top: 50%;">8</div>
                            <div class="player internal" data-player="I9" data-zone="3" style="left: 55%; top: 80%;">9</div>

                            <!-- ZONE 4 : ATTAQUANTS -->
                            <div class="player internal" data-player="I10" data-zone="4" style="left: 75%; top: 35%;">10</div>
                            <div class="player internal" data-player="I11" data-zone="4" style="left: 75%; top: 65%;">11</div>

                            <!-- ZONE 0 : GARDIEN -->
                            <div class="player external goalkeeper" data-player="E1" data-zone="0" style="left: 90%; top: 50%;">1</div>

                            <!-- ZONE 1 : DÉFENSEURS -->
                            <div class="player external" data-player="E2" data-zone="1" style="left: 80%; top: 25%;">2</div>
                            <div class="player external" data-player="E3" data-zone="1" style="left: 80%; top: 50%;">3</div>
                            <div class="player external" data-player="E4" data-zone="1" style="left: 80%; top: 75%;">4</div>

                            <!-- ZONE 2 : MILIEUX DÉFENSIFS -->
                            <div class="player external" data-player="E5" data-zone="2" style="left: 65%; top: 40%;">5</div>
                            <div class="player external" data-player="E6" data-zone="2" style="left: 65%; top: 60%;">6</div>

                            <!-- ZONE 3 : MILIEUX OFFENSIFS -->
                            <div class="player external" data-player="E7" data-zone="3" style="left: 45%; top: 20%;">7</div>
                            <div class="player external" data-player="E8" data-zone="3" style="left: 45%; top: 50%;">8</div>
                            <div class="player external" data-player="E9" data-zone="3" style="left: 45%; top: 80%;">9</div>

                            <!-- ZONE 4 : ATTAQUANTS -->
                            <div class="player external" data-player="E10" data-zone="4" style="left: 25%; top: 35%;">10</div>
                            <div class="player external" data-player="E11" data-zone="4" style="left: 25%; top: 65%;">11</div>

                            <div id="turn-indicator">00</div>
                        </div>
                    </div>

                    <!-- RIGHT : log -->
                    <!-- RIGHT : log -->
                    <div class="lg:col-span-2 min-w-0 min-h-0 h-full flex flex-col">
                        <div
                            id="log-card"
                            class="w-full flex-1 min-h-0 rounded-2xl bg-white p-3 shadow-lg ring-1 ring-white/80 text-[11px] flex flex-col gap-2"
                        >
                            <!-- ✅ shrink-0 : empêche l’entête de “manger” la place scroll -->
                            <div id="log-current" class="shrink-0">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="text-[10px] uppercase tracking-widest font-bold text-slate-600">DERNIÈRE ACTION</div>
                                    <div id="duel-dice-display" class="dice-chip"></div>
                                </div>
                                <div id="current-action-title" class="mt-2 font-bold text-slate-900">–</div>
                                <div id="current-action-detail" class="text-slate-600">Les duels et détails apparaîtront ici.</div>
                            </div>

                            <div class="shrink-0 h-px w-full bg-black/10"></div>

                            <!-- ✅ zone scroll : height calculable car parent = flex-1 min-h-0 -->
                            <div id="log-history" class="flex-1 min-h-0 overflow-y-auto">
                                <div class="text-[10px] uppercase tracking-widest font-bold text-slate-600 mb-2">
                                    Historique (15 derniers coups)
                                </div>
                                <ul id="history-list" class="space-y-1"></ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =======================================================
                     SKILL BARS (injecté par engine.js)
                     ======================================================= -->
                <div id="bars-container">
                    <div id="action-bar"></div>
                    <div id="info-text"></div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
// ==========================
//  Imports Vue / Inertia
// ==========================
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, onBeforeUnmount, ref, computed } from 'vue';
import H2 from '@/Components/H2.vue';

// ==========================
//  Engine
// ==========================
import { initMatchEngine } from './engine';
import './engine.css';

// ==========================
//  Props Inertia
// ==========================
const props = defineProps({
    engineConfig: { type: Object, required: true },
});

// ==========================
//  Refs
// ==========================
const gameRoot = ref(/** @type {HTMLElement|null} */ (null));

// Cleanup
/** @type {null | (() => void)} */
let cleanup = null;


// ==========================
//  Helpers (logos / names)
//  ✅ Source de vérité : engineConfig.teams.internal/external
// ==========================
const homeName = computed(() => props.engineConfig?.teams?.internal?.name ?? 'Domicile');
const awayName = computed(() => props.engineConfig?.teams?.external?.name ?? 'Extérieur');

const homeLogoUrl = computed(() => {
    const p = props.engineConfig?.teams?.internal?.logo_path;
    return p ? `/${p}` : null;
});

const awayLogoUrl = computed(() => {
    const p = props.engineConfig?.teams?.external?.logo_path;
    return p ? `/${p}` : null;
});

// ==========================
//  Lifecycle : mount / unmount
// ==========================
onMounted(() => {
    if (!gameRoot.value) return;

    // initMatchEngine peut (optionnellement) retourner une fonction cleanup
    cleanup = initMatchEngine(gameRoot.value, {
        ...props.engineConfig,

        // Callback fin de match : engine.js appelle ça à la fin
        onMatchEnd: ({ matchId, gameSaveId, scoresByTeamId, playerActions }) => {
            router.post(
                route('game-saves.matches.finish', { gameSave: gameSaveId, match: matchId }),
                {
                    scoresByTeamId,
                    playerActions,
                },
                {
                    preserveScroll: true,
                    onSuccess: () => router.visit(route('game-saves.play', { gameSave: gameSaveId })),
                }
            );
        },
    });
});

onBeforeUnmount(() => {
    // ✅ si ton engine a besoin de détacher des listeners / timers
    if (typeof cleanup === 'function') cleanup();
});
</script>
