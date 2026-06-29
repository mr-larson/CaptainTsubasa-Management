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
                        class="lg:col-span-2 grid grid-cols-[auto_1fr_auto] sm:grid-cols-3 items-center gap-2 rounded-xl px-3 py-4 sm:px-6 sm:py-6 text-neutral-800 shadow-md text-sm bg-white/90 shadow-md
                   border border-slate-200/70"
                    >
                        <!-- Left: actions -->
                        <div class="flex items-center gap-3 justify-start">
                            <Link
                                :href="route('game-saves.Play', { gameSave: engineConfig.gameSaveId })"
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
                        <div class="flex items-center justify-center gap-2 sm:gap-4 min-w-0">

                            <!-- INTERNAL (équipe contrôlée) — placée à gauche si domicile, sinon à droite -->
                            <div class="flex items-center gap-2 min-w-0 sm:min-w-[160px]"
                                 :class="internalIsHome ? 'order-1 justify-end' : 'order-3 justify-start flex-row-reverse'">
                                <div class="h-12 w-12 rounded-md overflow-hidden flex items-center justify-center">
                                    <img
                                        v-if="homeLogoUrl"
                                        :src="homeLogoUrl"
                                        :alt="internalIsHome ? 'Logo domicile' : 'Logo extérieur'"
                                        class="h-full w-full object-contain"
                                    />
                                    <span v-else class="text-[10px] opacity-60">—</span>
                                </div>

                                <!-- ⚠️ ID utilisé par engine.js -->
                                <span class="font-bold truncate max-w-[180px] min-w-0" id="team-name-internal">
                  {{ homeName }}
                </span>
                            </div>

                            <!-- SCORE -->
                            <div class="flex items-center gap-2 order-2">
                                <!-- ⚠️ IDs utilisés par engine.js -->
                                <span class="text-lg font-extrabold tabular-nums"
                                      :class="internalIsHome ? 'order-1' : 'order-3'" id="score-internal">0</span>
                                <span class="opacity-80 order-2">-</span>
                                <span class="text-lg font-extrabold tabular-nums"
                                      :class="internalIsHome ? 'order-3' : 'order-1'" id="score-external">0</span>
                            </div>

                            <!-- EXTERNAL (adversaire) — placée à droite si l'interne est à domicile, sinon à gauche -->
                            <div class="flex items-center gap-2 min-w-0 sm:min-w-[160px]"
                                 :class="internalIsHome ? 'order-3 justify-start' : 'order-1 justify-end flex-row-reverse'">
                <span class="font-bold truncate max-w-[180px] min-w-0" id="team-name-external">
                  {{ awayName }}
                </span>

                                <div class="h-12 w-12 rounded-md overflow-hidden flex items-center justify-center">
                                    <img
                                        v-if="awayLogoUrl"
                                        :src="awayLogoUrl"
                                        :alt="internalIsHome ? 'Logo extérieur' : 'Logo domicile'"
                                        class="h-full w-full object-contain"
                                    />
                                    <span v-else class="text-[10px] opacity-60">—</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: turns -->
                        <div class="flex items-center justify-end text-xs opacity-80">
                            <div class="flex items-center gap-2">
                                <svg id="match-clock" width="36" height="36" viewBox="0 0 36 36" class="shrink-0">
                                    <!-- Fond -->
                                    <circle cx="18" cy="18" r="15" fill="white" stroke="#e2e8f0" stroke-width="1.5"/>
                                    <!-- Graduations (statiques) -->
                                    <line x1="18" y1="4"  x2="18" y2="6"  stroke="#cbd5e1" stroke-width="1"/>
                                    <line x1="32" y1="18" x2="30" y2="18" stroke="#cbd5e1" stroke-width="1"/>
                                    <line x1="18" y1="32" x2="18" y2="30" stroke="#cbd5e1" stroke-width="1"/>
                                    <line x1="4"  y1="18" x2="6"  y2="18" stroke="#cbd5e1" stroke-width="1"/>
                                    <!-- Arc de progression -->
                                    <circle id="clock-arc" cx="18" cy="18" r="11"
                                            fill="none" stroke="#14b8a6" stroke-width="2.5"
                                            stroke-linecap="round"
                                            stroke-dasharray="0 69.1"
                                            transform="rotate(-90 18 18)"
                                            style="transition: stroke-dasharray 0.3s ease"/>
                                    <!-- Aiguille -->
                                    <line id="clock-hand" x1="18" y1="18" x2="18" y2="9"
                                          stroke="#0f172a" stroke-width="1.5" stroke-linecap="round"/>
                                    <!-- Centre -->
                                    <circle cx="18" cy="18" r="1.5" fill="#0f172a"/>
                                </svg>
                                <span id="turns-display" class="tabular-nums font-black text-sm text-slate-700">00'</span>
                            </div>
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
                                <option value="internal" :selected="engineConfig.controlledSide === 'internal'">
                                    {{ engineConfig.teams.internal.name }}
                                </option>
                                <option value="external" :selected="engineConfig.controlledSide === 'external'">
                                    {{ engineConfig.teams.external.name }}
                                </option>
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
                    <div id="left-column" class="lg:col-span-2 min-w-0 flex flex-col justify-around gap-4 lg:max-h-[55vh]">

                        <!-- HOME CARD -->
                        <div
                            id="home-card"
                            class="min-w-0 rounded-2xl bg-white p-4 shadow-lg ring-1 ring-white/80 flex flex-col gap-3 relative"
                        >
                            <div id="home-status-bar" class="absolute top-2 right-2 flex items-center gap-1">
                                <div id="home-ball-icon" class="hidden h-5 w-5 flex items-center justify-center text-sm" title="Porte le ballon">⚽</div>
                            </div>

                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    id="home-portrait"
                                    class="shrink-0 h-[90px] w-[90px] rounded shadow-md bg-gradient-to-br from-slate-100 via-slate-200 to-slate-400 relative overflow-hidden"
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
                                <div class="flex justify-between"><span>Tête :</span> <strong id="home-stat-heading">—</strong></div>
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
                            <div id="away-status-bar" class="absolute top-2 right-2 flex items-center gap-1">
                                <div id="away-ball-icon" class="hidden h-5 w-5 flex items-center justify-center text-sm" title="Porte le ballon">⚽</div>
                            </div>

                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    id="away-portrait"
                                    class="shrink-0 h-[90px] w-[90px] rounded shadow-md bg-gradient-to-br from-slate-100 via-slate-200 to-slate-400 relative overflow-hidden"
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
                                <div class="flex justify-between"><span>Tête :</span> <strong id="away-stat-heading">—</strong></div>
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

                            <!-- Joueurs internes -->
                            <template v-for="slot in 11" :key="'I' + slot">
                                <div
                                    v-if="playerPositions['I' + slot]"
                                    class="player internal"
                                    :class="{ goalkeeper: slot === 1 }"
                                    :data-player="'I' + slot"
                                    :data-zone="playerPositions['I' + slot].zone"
                                    :style="{
            left: playerPositions['I' + slot].x + '%',
            top:  playerPositions['I' + slot].y + '%',
        }"
                                ></div>
                            </template>

                            <!-- Joueurs externes -->
                            <template v-for="slot in 11" :key="'E' + slot">
                                <div
                                    v-if="playerPositions['E' + slot]"
                                    class="player external"
                                    :class="{ goalkeeper: slot === 1 }"
                                    :data-player="'E' + slot"
                                    :data-zone="playerPositions['E' + slot].zone"
                                    :style="{
            left: playerPositions['E' + slot].x + '%',
            top:  playerPositions['E' + slot].y + '%',
        }"
                                >{{ slot }}</div>
                            </template>

                            <div id="turn-indicator">00</div>
                        </div>
                    </div>

                    <!-- RIGHT : log -->
                    <!-- RIGHT : log -->
                    <div class="lg:col-span-2 min-w-0 min-h-0 max-h-[55vh] flex flex-col">
                        <div
                            id="log-card"
                            class="w-full flex-1 min-h-0 rounded-2xl bg-white p-3 shadow-lg ring-1 ring-white/80 text-[11px] flex flex-col gap-2"
                        >
                            <!-- ✅ shrink-0 : empêche l’entête de “manger” la place scroll -->
                            <div id="log-current" class="shrink-0">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="text-[10px] uppercase tracking-widest font-bold text-slate-600 whitespace-nowrap">DERNIÈRE ACTION</div>
                                    <div id="duel-dice-display" class="dice-chip"></div>
                                </div>
                                <div id="current-action-title" class="mt-2 font-bold text-slate-900">–</div>
                                <div id="current-action-detail" class="text-slate-600">Les duels et détails apparaîtront ici.</div>
                            </div>

                            <div class="shrink-0 h-px w-full bg-black/10"></div>

                            <!-- ✅ zone scroll : height calculable car parent = flex-1 min-h-0 -->
                            <div id="log-history" class="flex-1 min-h-0 overflow-y-auto">
                                <div class="text-[8px] uppercase tracking-widest font-bold text-slate-600 mb-2 whitespace-nowrap">
                                    HISTORIQUE
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
import { FORMATIONS, DEFAULT_FORMATION } from './engine/formations.js';

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

// ...

// ==========================
//  Helpers généraux
// ==========================
const buildLogoUrl = (path) => {
    if (!path) return null;

    // URL absolue
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    // Chemin déjà rooté
    if (path.startsWith('/')) {
        return path;
    }

    // 🔧 Cas spécifique actuel : "teams/xxx.webp" => "/images/teams/xxx.webp"
    if (path.startsWith('teams/')) {
        return `/images/${path}`;
        // => "teams/musashi.webp" -> "/images/teams/musashi.webp"
    }

    // Sinon, on considère que c'est un chemin relatif à la racine
    return `/${path}`;
};

// ==========================
//  Helpers (logos / names)
//  ✅ Source de vérité : engineConfig.teams.internal/external
// ==========================
const homeName = computed(() => props.engineConfig?.teams?.internal?.name ?? 'Domicile');
const awayName = computed(() => props.engineConfig?.teams?.external?.name ?? 'Extérieur');

// L'équipe à domicile doit apparaître en premier (à gauche) dans le bandeau de
// score, que le joueur contrôle le domicile (internal) ou l'extérieur.
const internalIsHome = computed(() => props.engineConfig?.internalIsHome ?? true);

const homeLogoUrl = computed(() => {
    const p = props.engineConfig?.teams?.internal?.logo_path;
    return buildLogoUrl(p);
});

const awayLogoUrl = computed(() => {
    const p = props.engineConfig?.teams?.external?.logo_path;
    return buildLogoUrl(p);
});

// ==========================
//  Lifecycle : mount / unmount
// ==========================
onMounted(() => {
    const captain = props.engineConfig.teams.internal.players.find(p => p.is_captain);
    console.log('Captain:', captain?.lastname, 'isCaptain:', captain?.is_captain);
    if (!gameRoot.value) return;

    cleanup = initMatchEngine(gameRoot.value, {
        ...props.engineConfig,
        onMatchEnd: ({ matchId, gameSaveId, scoresByTeamId, playerActions, match_stats, foulEvents }) => {
            router.post(
                route('game-saves.matches.finish', { gameSave: gameSaveId, match: matchId }),
                {
                    scoresByTeamId,
                    playerActions,
                    match_stats,
                    foulEvents: foulEvents ?? [],
                },
                {
                    preserveScroll: true,
                    onSuccess: () => router.visit(route('game-saves.Play', { gameSave: gameSaveId })),
                }
            );
        },
    });
});

onBeforeUnmount(() => {
    if (typeof cleanup === 'function') cleanup();
});
// Calcule les positions CSS (left%, top%) de tous les joueurs
// selon la formation de chaque équipe
const playerPositions = computed(() => {
    const positions = {};
    const ZONE_X = {
        internal: { 0: 10, 1: 20, 2: 35, 3: 55, 4: 75 },
        external: { 0: 90, 1: 80, 2: 65, 3: 45, 4: 25 },
    };
    const LANE_Y = { 0: 10, 1: 28, 2: 50, 3: 72, 4: 90 };

    for (const side of ['internal', 'external']) {
        const prefix    = side === 'internal' ? 'I' : 'E';
        const formKey   = props.engineConfig?.teams?.[side]?.formation ?? DEFAULT_FORMATION;
        const formation = FORMATIONS[formKey] ?? FORMATIONS[DEFAULT_FORMATION];

        for (let slot = 1; slot <= 11; slot++) {
            const def = formation.slots[slot];
            if (!def) continue;
            positions[`${prefix}${slot}`] = {
                x:    ZONE_X[side][def.zone] ?? 50,
                y:    LANE_Y[def.laneIndex]  ?? 50,
                zone: def.zone,
            };
        }
    }
    return positions;
});
</script>
