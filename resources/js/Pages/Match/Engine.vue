<template>
    <Head title="Match de démo" />

    <AuthenticatedLayout>
        <template #header>
            <H2>Match de démo</H2>
        </template>

        <div class="flex justify-center mt-4">
            <div id="game-wrapper" ref="gameRoot" class="w-full">
                <!-- TOP BAR -->
                <div id="top-bar">
                    <!-- Score -->
                    <div id="score-strip">
                        <div id="match-end-actions" class="hidden mt-4 flex justify-center">
                            <button
                                id="btn-finish-match"
                                type="button"
                                class="w-72 bg-teal-400 hover:bg-teal-500 text-center font-semibold py-2 px-6 border-2 border-teal-600 rounded-full drop-shadow-md"
                            >
                                Retour au dashboard
                            </button>
                        </div>
                        <div class="">
                            <Link
                                :href="route('game-saves.play', { gameSave: engineConfig.gameSaveId })"
                                class="bg-slate-500 hover:bg-slate-300 text-center font-semibold py-1 px-5 border-2 border-slate-500 rounded-full drop-shadow-md"
                            >
                                Retour
                            </Link>
                        </div>
                        <span class="team-name" id="team-name-internal">Domicile</span>
                        <span class="score-value" id="score-internal">0</span>
                        <span>-</span>
                        <span class="score-value" id="score-external">0</span>
                        <span class="team-name" id="team-name-external">Exterieur</span>
                        <span class="timer">Tours : <span id="turns-display">00</span>/30</span>
                    </div>

                    <!-- Panneau de contrôle -->
                    <div id="control-panel">
                        <div class="control-row compact">
                            <button id="mode-one-player" class="mode-toggle">
                                Mode 1 joueur (OFF)
                            </button>

                            <select id="controlled-team-select" class="team-select">
                                <option value="internal">Domicile</option>
                                <option value="external">Exterieur</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Panneau de message caché (utilisé par le JS seulement) -->
                <div id="message-panel" class="visually-hidden">
                    <div id="message-main"></div>
                    <div id="message-sub"></div>
                </div>

                <!-- MAIN ROW -->
                <div id="main-row">
                    <!-- Colonne gauche : carte joueur + log -->
                    <div id="left-column">
                        <!-- Player card -->
                        <div id="player-card">
                            <div id="player-header">
                                <div id="portrait"></div>
                                <div id="player-info">
                                    <div id="player-name">Nom complet</div>
                                    <div id="player-role">Poste : <span id="player-role-label">Milieu</span></div>
                                    <div id="player-number">Numéro : <span id="player-number-label">10</span></div>
                                    <div id="player-team">Équipe : <span id="player-team-label">Internal</span></div>
                                </div>
                            </div>
                            <div id="player-stats">
                                <div>Shot : <strong id="stat-shot">—</strong></div>
                                <div>Block : <strong id="stat-block">—</strong></div>
                                <div>Pass : <strong id="stat-pass">—</strong></div>
                                <div>Intercept : <strong id="stat-intercept">—</strong></div>
                                <div>Dribble : <strong id="stat-dribble">—</strong></div>
                                <div>Tackle : <strong id="stat-tackle">—</strong></div>
                                <!-- Gardien -->
                                <div>Arrêt : <strong id="stat-hand_save">—</strong></div>
                                <div>Poings : <strong id="stat-punch_save">—</strong></div>
                            </div>
                            <div id="energy-bar"><div id="energy-fill" class="e-high"></div></div>
                        </div>

                        <!-- Carte de log d'actions -->
                        <div id="log-card">
                            <div id="log-current">
                                <div class="log-header-row">
                                    <div class="log-section-title">DERNIÈRE ACTION</div>
                                    <div id="duel-dice-display" class="dice-chip">
                                        <!-- rempli dynamiquement -->
                                    </div>
                                </div>
                                <div id="current-action-title">–</div>
                                <div id="current-action-detail">Les duels et détails apparaîtront ici.</div>
                            </div>
                            <div id="log-history">
                                <div class="log-section-title">Historique (5 derniers coups)</div>
                                <ul id="history-list">
                                    <!-- rempli dynamiquement -->
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Field -->
                    <div id="field-wrapper">
                        <!-- Overlay IA -->
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

                            <!-- Ball -->
                            <div id="ball"></div>

                            <!-- INTERNAL 3-2-3-2 -->
                            <div class="player internal goalkeeper" data-player="I1" style="left: 7%;  top: 50%;">1</div>

                            <!-- 3 défenseurs -->
                            <div class="player internal" data-player="I2"  style="left: 20%; top: 25%;">2</div>
                            <div class="player internal" data-player="I3"  style="left: 20%; top: 50%;">3</div>
                            <div class="player internal" data-player="I4"  style="left: 20%; top: 75%;">4</div>

                            <!-- 2 milieux défensifs -->
                            <div class="player internal" data-player="I5"  style="left: 35%; top: 40%;">5</div>
                            <div class="player internal" data-player="I6"  style="left: 35%; top: 60%;">6</div>

                            <!-- 3 milieux offensifs -->
                            <div class="player internal" data-player="I7"  style="left: 55%; top: 20%;">7</div>
                            <div class="player internal" data-player="I8"  style="left: 55%; top: 50%;">8</div>
                            <div class="player internal" data-player="I9"  style="left: 55%; top: 80%;">9</div>

                            <!-- 2 attaquants -->
                            <div class="player internal" data-player="I10" style="left: 75%; top: 35%;">10</div>
                            <div class="player internal" data-player="I11" style="left: 75%; top: 65%;">11</div>

                            <!-- EXTERNAL 3-2-3-2 -->
                            <div class="player external goalkeeper" data-player="E1" style="left: 93%; top: 50%;">1</div>

                            <!-- 3 défenseurs -->
                            <div class="player external" data-player="E2"  style="left: 80%; top: 25%;">2</div>
                            <div class="player external" data-player="E3"  style="left: 80%; top: 50%;">3</div>
                            <div class="player external" data-player="E4"  style="left: 80%; top: 75%;">4</div>

                            <!-- 2 milieux défensifs -->
                            <div class="player external" data-player="E5"  style="left: 65%; top: 40%;">5</div>
                            <div class="player external" data-player="E6"  style="left: 65%; top: 60%;">6</div>

                            <!-- 3 milieux offensifs -->
                            <div class="player external" data-player="E7"  style="left: 45%; top: 20%;">7</div>
                            <div class="player external" data-player="E8"  style="left: 45%; top: 50%;">8</div>
                            <div class="player external" data-player="E9"  style="left: 45%; top: 80%;">9</div>

                            <!-- 2 attaquants -->
                            <div class="player external" data-player="E10" style="left: 25%; top: 35%;">10</div>
                            <div class="player external" data-player="E11" style="left: 25%; top: 65%;">11</div>

                            <div id="turn-indicator">00</div>
                        </div>
                    </div>
                </div>

                <!-- SKILL BARS -->
                <div id="bars-container">
                    <div id="action-bar">
                        <!-- Contenu remplacé dynamiquement -->
                    </div>

                    <div id="info-text">
                    </div>
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
        engineConfig: {
            type: Object,
                required: true,
            },
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
                    onSuccess: () => {
                        // ✅ redirection après finish
                        router.visit(route('game-saves.play', { gameSave: gameSaveId }));
                        // ou si tu as une vraie route dashboard :
                        // router.visit(route('game-saves.show', { gameSave: gameSaveId }));
                        // router.visit(route('game-saves.index'));
                    },
                }
            );
        },
    });
});



onBeforeUnmount(() => {
    // destroy éventuel du moteur plus tard
});
</script>



