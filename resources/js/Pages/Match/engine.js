// resources/js/Pages/Match/engine.js
// Point d'entrée unique — orchestre tous les sous-modules du dossier engine/

import {
    TEXTS, GAME_RULES, ANIM_MS, ACTION_BAR_FADE_MS, GK_HOLD_MS, POSITION_BONUS,
} from './engine/constants.js';

import { RosterService } from './engine/RosterService.js';

import {
    initStaminaModule, initStamina,
    getStaminaRatio, staminaFactor, applyStaminaCost, updateStaminaUI,
} from './engine/stamina.js';

import {
    initDiceUI, bindDuelTooltipEvents, showDuelDice,
} from './engine/dice.js';

import {
    initFieldModule,
    otherTeam, getPlayerId, getKeeperId, isGoalkeeperId,
    moveBallToPlayer, getCarrierElement, getPlayerXInternal,
    pickFieldDefender, pickWeightedPlayerInZone,
    initBasePositions, applyKickoffPositions, restoreBasePositions,
    decayHeat, markTouch,
} from './engine/field.js';

import {
    initUIModule,
    setMessage, setAIOverlay, updateScoreUI, pushLogEntry,
    updateSideCard, syncRecovererCard, updateTeamCard, updateCardsPower,
    buildAttackActionsHTML, buildDefenseFieldHTML, buildDefenseGKHTML,
    setActionBar, applyRosterToDOM,
} from './engine/ui.js';

import {
    initAIModule,
    computeAIAttackChoice, computeAIDefenseChoice,
} from './engine/ai.js';

import {
    initResolversModule,
    isGoodDefenseChoice, givePossessionOnTie,
    resolvePass, resolveDribble, resolveShot, resolveShotKeeperDuel,
    performKeeperClearance, recordDuelEvent,
} from './engine/resolvers.js';

// ==========================
//   EXPORT PRINCIPAL
// ==========================
export function initMatchEngine(rootEl, config = {}) {
    if (!rootEl) return;

    const matchConfig = config || {};

    const TEAMS = {
        internal: { id: "internal", label: matchConfig.teams?.internal?.name ?? TEXTS.teams.internal },
        external: { id: "external", label: matchConfig.teams?.external?.name ?? TEXTS.teams.external },
    };

    const controlMode    = matchConfig.controlMode ?? "both";
    let onePlayerMode    = (controlMode === "single");
    let controlledTeam   = matchConfig.controlledSide ?? "internal";

    const $ = (sel) => rootEl.querySelector(sel);

    // ==========================
    //   ROSTER
    // ==========================
    const roster = RosterService.create(matchConfig, {
        statCoef: 0.6,
        positionBonus: POSITION_BONUS,
    });

    // ==========================
    //   UI REFS
    // ==========================
    const ui = {
        ballEl:               $("#ball"),
        scoreInternalEl:      $("#score-internal"),
        scoreExternalEl:      $("#score-external"),
        turnsDisplayEl:       $("#turns-display"),
        turnIndicatorEl:      $("#turn-indicator"),
        msgMainEl:            $("#message-main"),
        msgSubEl:             $("#message-sub"),
        actionBarEl:          $("#action-bar"),
        teamNameInternalEl:   $("#team-name-internal"),
        teamNameExternalEl:   $("#team-name-external"),
        homeBallIconEl:       $("#home-ball-icon"),
        awayBallIconEl:       $("#away-ball-icon"),
        matchEndActionsEl:    $("#match-end-actions"),
        finishMatchBtn:       $("#btn-finish-match"),
        currentActionTitleEl: $("#current-action-title"),
        currentActionDetailEl:$("#current-action-detail"),
        duelDiceEl:           $("#duel-dice-display"),
        historyListEl:        $("#history-list"),
        modeOnePlayerBtn:     $("#mode-one-player"),
        controlledTeamSelect: $("#controlled-team-select"),
        aiOverlayEl:          $("#ai-turn-overlay"),
    };

    // ==========================
    //   STATE
    // ==========================
    const basePositions = {};

    const state = {
        ball: { team: "internal", zoneIndex: 1, laneIndex: 1, number: 8, frontOfKeeper: false },
        currentTeam:   "internal",
        score:         { internal: 0, external: 0 },
        turns:         0,
        phase:         "attack",
        pendingAttack: null,
        isAnimating:   false,
        isKickoff:     true,
        keeperRestartMustPass: false,
        isGameOver:    false,
        pendingShotContext:    null,
        pendingClearanceBonus: 0,
        pendingDefenseContext: null,
        lastDribblerId: null,
        stamina:        {},
        staminaMax:     {},
        specialCooldown:{},
        SPECIAL_COOLDOWN_TURNS: 2,
        touchHeat:      {},
        actionEvents:   [],
        foulEvents:     [],
        defensePreview: null,
        // Références injectées pour les modules (évite imports circulaires)
        _matchConfig:    matchConfig,
        _GK_HOLD_MS:     GK_HOLD_MS,
        _moveBallFn:     null, // branché après init
        _applyKickoffFn: null, // branché après init
    };

    const ball = state.ball; // raccourci

    // ==========================
    //   HELPERS LOCAUX
    // ==========================
    function isAITeam(team) {
        return onePlayerMode && team !== controlledTeam;
    }

    function canUseSpecial(playerId) {
        if (!playerId) return false;
        return state.turns >= (state.specialCooldown[playerId] ?? 0);
    }

    function markSpecialUsed(playerId) {
        if (!playerId) return;
        state.specialCooldown[playerId] = state.turns + state.SPECIAL_COOLDOWN_TURNS;
    }

    function animateAndThen(cb) {
        if (!ui.ballEl) { if (cb) cb(); return; }
        state.isAnimating = true;
        ui.ballEl.classList.add("ball-kick");
        setTimeout(() => {
            ui.ballEl.classList.remove("ball-kick");
            state.isAnimating = false;
            if (cb) cb();
        }, ANIM_MS);
    }

    function refreshUI() {
        updateScoreUI(state);
        updateTeamCard(ball);
    }

    // ==========================
    //   INIT MODULES
    // ==========================
    initStaminaModule(state, roster, rootEl);
    initDiceUI(ui.duelDiceEl);
    initFieldModule(rootEl, roster, state, ui);
    initUIModule(rootEl, roster, ui, state, TEAMS);
    initAIModule(state, roster);

    // Branche le callback moveBall pour resolvers (évite import circulaire field ↔ resolvers)
    state._moveBallFn = (team, number) => moveBallToPlayer(team, number, () => updateTeamCard(ball));
    state._applyKickoffFn = () => applyKickoffPositions(basePositions);

    // ==========================
    //   STATS DE FIN DE MATCH
    // ==========================
    function emptyPlayerMatchStats() {
        return {
            offense: {
                pass:    { attempts: 0, success: 0 },
                shot:    { attempts: 0, success: 0 },
                dribble: { attempts: 0, success: 0 },
                special: { attempts: 0, success: 0 },
            },
            defense: {
                intercept: { attempts: 0, success: 0 },
                tackle:    { attempts: 0, success: 0 },
                block:     { attempts: 0, success: 0 },
                hands:     { attempts: 0, success: 0 },
                punch:     { attempts: 0, success: 0 },
                gkSpecial: { attempts: 0, success: 0 },
            },
            duelsWon: 0, duelsLost: 0,
        };
    }

    function buildMatchStats(events) {
        const out = {
            players: {},
            teams: {
                home: { goals: 0, shots: 0, passes: 0, dribbles: 0, duelsWon: 0, duelsLost: 0 },
                away: { goals: 0, shots: 0, passes: 0, dribbles: 0, duelsWon: 0, duelsLost: 0 },
            },
        };

        for (const ev of events) {
            if (ev.attack?.game_player_id) {
                const pid = ev.attack.game_player_id;
                if (!out.players[pid]) out.players[pid] = emptyPlayerMatchStats();
                const act = ev.attack.action;
                if (act === "pass")    out.players[pid].offense.pass.attempts++;
                if (act === "shot")    out.players[pid].offense.shot.attempts++;
                if (act === "dribble") out.players[pid].offense.dribble.attempts++;
                if (act === "special") out.players[pid].offense.special.attempts++;

                const t = ev.attack.team === "internal" ? "home" : "away";
                if (ev.result === "attack") {
                    if (act === "pass")    out.players[pid].offense.pass.success++;
                    if (act === "dribble") out.players[pid].offense.dribble.success++;
                    if (act === "shot" || act === "special") out.players[pid].offense.shot.success++;
                    out.players[pid].duelsWon++;
                    out.teams[t].duelsWon++;
                    if (act === "shot" || act === "special") out.teams[t].shots++;
                } else if (ev.result === "defense") {
                    out.players[pid].duelsLost++;
                    out.teams[t].duelsLost++;
                    if (act === "shot" || act === "special") out.teams[t].shots++;
                }
            }

            if (ev.defense?.game_player_id) {
                const pid = ev.defense.game_player_id;
                if (!out.players[pid]) out.players[pid] = emptyPlayerMatchStats();
                const def = ev.defense.action;
                if (def === "intercept")  out.players[pid].defense.intercept.attempts++;
                if (def === "tackle")     out.players[pid].defense.tackle.attempts++;
                if (def === "block")      out.players[pid].defense.block.attempts++;
                if (def === "hands")      out.players[pid].defense.hands.attempts++;
                if (def === "punch")      out.players[pid].defense.punch.attempts++;
                if (def === "gk-special") out.players[pid].defense.gkSpecial.attempts++;

                if (ev.result === "defense") {
                    if (def === "intercept")  out.players[pid].defense.intercept.success++;
                    if (def === "tackle")     out.players[pid].defense.tackle.success++;
                    if (def === "block")      out.players[pid].defense.block.success++;
                    if (def === "hands")      out.players[pid].defense.hands.success++;
                    if (def === "punch")      out.players[pid].defense.punch.success++;
                    if (def === "gk-special") out.players[pid].defense.gkSpecial.success++;
                    out.players[pid].duelsWon++;
                } else if (ev.result === "attack") {
                    out.players[pid].duelsLost++;
                }
            }
        }
        return out;
    }

    // ==========================
    //   ADVANCE TURN
    // ==========================
    function advanceTurn(newTeam) {
        if (state.isGameOver) return;

        decayHeat();
        state.currentTeam = newTeam;
        state.turns++;

        if (state.turns >= GAME_RULES.MAX_TURNS) {
            state.isGameOver = true;

            setMessage(
                TEXTS.ui.matchEndMain,
                `${TEXTS.ui.matchEndPrefix}${state.score.internal} - ${state.score.external}`
            );
            pushLogEntry("matchEndTitle", [`Score final ${state.score.internal} - ${state.score.external}`], null, state);

            if (ui.actionBarEl) {
                ui.actionBarEl.classList.remove("fade-in");
                ui.actionBarEl.classList.add("fade-out");
                setTimeout(() => { ui.actionBarEl.innerHTML = ""; }, ACTION_BAR_FADE_MS);
            }

            refreshUI();
            if (ui.matchEndActionsEl) ui.matchEndActionsEl.classList.remove("hidden");

            if (ui.finishMatchBtn) {
                ui.finishMatchBtn.onclick = () => {
                    const internalTeamId = matchConfig?.sides?.internalTeamId;
                    const externalTeamId = matchConfig?.sides?.externalTeamId;
                    if (!internalTeamId || !externalTeamId) return;

                    const payload = {
                        matchId:    matchConfig.matchId,
                        gameSaveId: matchConfig.gameSaveId,
                        scoresByTeamId: {
                            [internalTeamId]: state.score.internal,
                            [externalTeamId]: state.score.external,
                        },
                        playerActions: state.actionEvents,
                        foulEvents:    state.foulEvents,
                    };

                    if (typeof matchConfig.onMatchEnd === "function") {
                        payload.match_stats = buildMatchStats(state.actionEvents);
                        matchConfig.onMatchEnd(payload);
                    }
                };
            }
            return;
        }

        setMessage(`${TEAMS[state.currentTeam].label} a la balle`, TEXTS.ui.chooseAttackSub);
        state.phase = "attack";
        state.pendingAttack = null;
        state.pendingShotContext = null;
        state.pendingDefenseContext = null;

        showAttackBarForCurrentTeam();
        refreshUI();
    }

    // ==========================
    //   IA — SCHEDULE
    // ==========================
    function scheduleAIAttack() {
        if (!isAITeam(state.currentTeam) || state.phase !== "attack" || state.isGameOver) return;
        const AI_THINK_MS = 150;
        setAIOverlay(true, TEXTS.ui.aiAttackTurn);
        const action = computeAIAttackChoice(ball, state.specialCooldown);
        setTimeout(() => { setAIOverlay(false); handleAttackClick(action); }, AI_THINK_MS);
    }

    function scheduleAIDefense(attackAction, defendingTeam) {
        if (!isAITeam(defendingTeam) || state.phase !== "defense" || !state.pendingAttack || state.isGameOver) return;
        const AI_THINK_MS = 150;
        setAIOverlay(true, TEXTS.ui.aiDefenseTurn);

        const isKeeperDuel =
            (state.pendingShotContext && state.pendingShotContext.stage === "keeper") ||
            ball.frontOfKeeper;

        const defense = computeAIDefenseChoice(
            attackAction, defendingTeam,
            { isKeeperDuel },
            state.specialCooldown, state.turns
        );
        setTimeout(() => { setAIOverlay(false); handleDefenseClick(defense); }, AI_THINK_MS);
    }

    // ==========================
    //   FAUTES / CARTONS / BLESSURES
    // ==========================
    function resolveFoulOutcome({ attackerId, defenderId, duelResult, aRoll, dRoll }) {
        const isCritFailAttack  = aRoll?.critFail  ?? false;
        const isCritFailDefense = dRoll?.critFail  ?? false;
        const isTie             = duelResult === 'tie';

        // CritFail attaquant → 30% blessure légère/modérée
        if (isCritFailAttack && Math.random() < 0.30) {
            const severity = Math.random() < 0.7 ? 'light' : 'moderate';
            state.foulEvents.push({ type: 'injury', player_id: attackerId, severity });
            pushLogEntry('foulInjuryTitle', ['🤕 Blessure (' + (severity === 'light' ? 'légère' : 'modérée') + ')', 'Attaquant touché'], null, state);
        }

        // CritFail défenseur → faute + carton possible
        if (isCritFailDefense) {
            state.foulEvents.push({ type: 'foul', fouler_player_id: defenderId, victim_player_id: attackerId, is_crit_fail: true });
            const r = Math.random();
            if (r < 0.15) {
                // Carton rouge → grisé immédiatement
                state.foulEvents.push({ type: 'card', player_id: defenderId, card_type: 'red' });
                const el = rootEl.querySelector(`[data-player="${defenderId}"]`);
                if (el) el.classList.add('unavailable');
                pushLogEntry('foulCardTitle', ['🟥 Carton rouge ! Expulsé !', 'Faute grave'], null, state);
            } else if (r < 0.60) {
                // Carton jaune → compter, griser si 2e
                state.foulEvents.push({ type: 'card', player_id: defenderId, card_type: 'yellow' });
                const matchYellows = state.foulEvents.filter(
                    e => e.type === 'card' && e.card_type === 'yellow' && e.player_id === defenderId
                ).length;
                if (matchYellows >= 2) {
                    const el = rootEl.querySelector(`[data-player="${defenderId}"]`);
                    if (el) el.classList.add('unavailable');
                    pushLogEntry('foulCardTitle', ['🟨🟨 Double jaune ! Expulsé !', 'Faute dangereuse'], null, state);
                } else {
                    pushLogEntry('foulCardTitle', ['🟨 Carton jaune', 'Faute dangereuse'], null, state);
                }
            } else {
                pushLogEntry('foulTitle', ['⚠️ Faute (crit)', 'Défenseur fautif'], null, state);
            }
        }

// Tie → faute simple 25% + carton jaune 20%
        if (isTie && Math.random() < 0.25) {
            state.foulEvents.push({ type: 'foul', fouler_player_id: defenderId, victim_player_id: attackerId, is_crit_fail: false });
            if (Math.random() < 0.20) {
                state.foulEvents.push({ type: 'card', player_id: defenderId, card_type: 'yellow' });
                const matchYellows = state.foulEvents.filter(
                    e => e.type === 'card' && e.card_type === 'yellow' && e.player_id === defenderId
                ).length;
                if (matchYellows >= 2) {
                    const el = rootEl.querySelector(`[data-player="${defenderId}"]`);
                    if (el) el.classList.add('unavailable');
                    pushLogEntry('foulCardTitle', ['🟨🟨 Double jaune ! Expulsé !', 'Faute'], null, state);
                } else {
                    pushLogEntry('foulCardTitle', ['🟨 Carton jaune', 'Faute'], null, state);
                }
            }
        }
    }

    // ==========================
    //   INIT RESOLVERS (après avoir toutes les fonctions locales)
    // ==========================
    initResolversModule({
        state, roster, ui, TEAMS, rootEl,
        basePos: basePositions,
        advanceTurn,
        showAttackBarForCurrentTeam: () => showAttackBarForCurrentTeam(),
        refreshUI,
        animateAndThen,
        scheduleAIDefense,
        isAITeam,
        canUseSpecial,
        markSpecialUsed,
        resolveFoulOutcome,
        bindActionButtons: () => {
            rootEl.querySelectorAll(".skill-card").forEach(btn =>
                btn.addEventListener("click", () => handleAttackClick(btn.dataset.action))
            );
            rootEl.querySelectorAll(".def-card").forEach(btn =>
                btn.addEventListener("click", () => handleDefenseClick(btn.dataset.defense))
            );
        },
    });

    // ==========================
    //   KICKOFF AUTO (passe obligatoire)
    // ==========================
    function resolveKickoffPass(attackTeam) {
        if (!state.isKickoff) return;
        state.isKickoff = false;

        const number     = [5, 6][Math.floor(Math.random() * 2)];
        const attackerId = getPlayerId(attackTeam, ball.number);
        applyStaminaCost(attackerId, "attack", "pass");

        setMessage(TEXTS.logs.kickoffTitle, `${TEAMS[attackTeam].label} joue court vers le n°${number}.`);
        pushLogEntry("kickoffTitle", [`Vers n°${number}`, "Auto"], null, state);

        animateAndThen(() => {
            restoreBasePositions(basePositions);
            state._moveBallFn(attackTeam, number);
            advanceTurn(attackTeam);
            showAttackBarForCurrentTeam();
            refreshUI();
        });
    }

    // ==========================
    //   DEFENDER PREVIEW
    // ==========================
    function setDefenderPreviewFor(action, defenseTeam) {
        const prefix       = defenseTeam === "internal" ? "home" : "away";
        const isKeeperStage =
            (state.pendingShotContext && state.pendingShotContext.stage === "keeper") ||
            (ball.frontOfKeeper && (action === "shot" || action === "special"));

        if (isKeeperStage) { updateSideCard(prefix, defenseTeam, 1); return; }

        if (state.pendingDefenseContext?.defenderSlot) {
            updateSideCard(prefix, defenseTeam, state.pendingDefenseContext.defenderSlot);
            return;
        }

        const picked = pickFieldDefender(defenseTeam, ball.zoneIndex, ball.laneIndex);

        state.defensePreview = picked ? {
            attackAction: action, defenseTeam,
            ballSnapshot: {
                team: ball.team, number: ball.number,
                zoneIndex: ball.zoneIndex, laneIndex: ball.laneIndex,
                frontOfKeeper: ball.frontOfKeeper,
            },
            picked,
        } : null;

        updateSideCard(prefix, defenseTeam, picked?.defenderSlot || 6);
    }

    // ==========================
    //   SHOW ATTACK BAR
    // ==========================
    function showAttackBarForCurrentTeam() {
        if (state.isGameOver) return;

        const bindFn = () => {
            rootEl.querySelectorAll(".skill-card").forEach(btn =>
                btn.addEventListener("click", () => handleAttackClick(btn.dataset.action))
            );
            rootEl.querySelectorAll(".def-card").forEach(btn =>
                btn.addEventListener("click", () => handleDefenseClick(btn.dataset.defense))
            );
        };

        setActionBar(
            buildAttackActionsHTML(ball, roster),
            `mode-attack-${state.currentTeam}`,
            ball, roster, bindFn, state.isKickoff
        );

        const defTeam      = otherTeam(state.currentTeam);
        const defaultAction= state.isKickoff ? "pass" : (ball.frontOfKeeper ? "shot" : "pass");
        setDefenderPreviewFor(defaultAction, defTeam);

        if (isAITeam(state.currentTeam)) scheduleAIAttack();
        updateCardsPower(ball);
    }

    // ==========================
    //   HANDLERS CLICKS
    // ==========================
    function handleAttackClick(action) {
        if (state.isGameOver || state.isAnimating) return;
        if (state.turns >= GAME_RULES.MAX_TURNS || state.phase !== "attack") return;
        if (!["shot","pass","dribble","special"].includes(action)) return;

        if (state.isKickoff) {
            if (action !== "pass") return;
            resolveKickoffPass(state.currentTeam);
            return;
        }

        if (state.keeperRestartMustPass && action !== "pass") {
            setMessage(TEXTS.ui.keeperRestartMain, TEXTS.ui.keeperRestartSub + " — passe obligatoire.");
            return;
        }

        if (ball.frontOfKeeper && action !== "shot" && action !== "special") return;

        if (action === "special") {
            const attackerId = getPlayerId(state.currentTeam, ball.number);
            if (!canUseSpecial(attackerId)) {
                setMessage(TEXTS.ui.specialCooldownMain, TEXTS.ui.specialCooldownSub);
                state.phase = "attack"; state.pendingAttack = null;
                return;
            }
        }

        state.pendingAttack = action;
        state.phase = "defense";

        const defTeam          = otherTeam(state.currentTeam);
        const isKeeperChoiceUI = (action === "shot" || action === "special") && ball.frontOfKeeper;

        if (!isKeeperChoiceUI) {
            const snap = state.defensePreview;
            const snapOk = snap &&
                snap.attackAction === action &&
                snap.defenseTeam  === defTeam &&
                snap.ballSnapshot?.team         === ball.team &&
                snap.ballSnapshot?.number       === ball.number &&
                snap.ballSnapshot?.zoneIndex    === ball.zoneIndex &&
                snap.ballSnapshot?.laneIndex    === ball.laneIndex &&
                snap.ballSnapshot?.frontOfKeeper=== ball.frontOfKeeper;

            const picked = snapOk ? snap.picked : pickFieldDefender(defTeam, ball.zoneIndex, ball.laneIndex);
            state.pendingDefenseContext = { defenseTeam: defTeam, ...picked };
            state.defensePreview = null;
        } else {
            state.pendingDefenseContext = { defenseTeam: defTeam, defenderId: getKeeperId(defTeam), defenderSlot: 1 };
        }

        const defPrefix = defTeam === "internal" ? "home" : "away";
        updateSideCard(defPrefix, defTeam, state.pendingDefenseContext.defenderSlot || 6);

        const bindFn = () => {
            rootEl.querySelectorAll(".skill-card").forEach(btn =>
                btn.addEventListener("click", () => handleAttackClick(btn.dataset.action))
            );
            rootEl.querySelectorAll(".def-card").forEach(btn =>
                btn.addEventListener("click", () => handleDefenseClick(btn.dataset.defense))
            );
        };

        let html;
        if (action === "shot" || action === "special") {
            if (ball.frontOfKeeper) {
                html = buildDefenseGKHTML(defTeam, roster);
                setMessage(
                    `${TEAMS[state.currentTeam].label} prépare un ${action === "special" ? "TIR SPÉCIAL" : "TIR"} !`,
                    `${TEAMS[defTeam].label} (gardien) : Arrêt main / Poing / Special.`
                );
            } else {
                html = buildDefenseFieldHTML(defTeam, state.pendingDefenseContext.defenderSlot, roster);
                setMessage(
                    `${TEAMS[state.currentTeam].label} tente un ${action === "special" ? "TIR SPÉCIAL" : "TIR"} !`,
                    `${TEAMS[defTeam].label} : Block / Intercept / Tackle / Special.`
                );
            }
        } else {
            html = buildDefenseFieldHTML(defTeam, state.pendingDefenseContext.defenderSlot, roster);
            setMessage(
                `${TEAMS[state.currentTeam].label} prépare un ${action.toUpperCase()} !`,
                `${TEAMS[defTeam].label} : Block / Intercept / Tackle / Special.`
            );
        }

        setActionBar(html, `mode-defense-${defTeam}`, ball, roster, bindFn, false);
        if (isAITeam(defTeam)) scheduleAIDefense(action, defTeam);
    }

    function handleDefenseClick(defense) {
        if (state.turns >= GAME_RULES.MAX_TURNS || state.isAnimating || state.phase !== "defense" || !state.pendingAttack) return;

        const isKeeperDuel =
            (state.pendingShotContext && state.pendingShotContext.stage === "keeper") ||
            ball.frontOfKeeper;

        if (isKeeperDuel && !["hands","punch","gk-special"].includes(defense)) defense = "hands";

        const attackTeam  = state.currentTeam;
        const defenseTeam = otherTeam(state.currentTeam);
        const attack      = state.pendingAttack;

        if (defense === "field-special") {
            const defenderId = state.pendingDefenseContext?.defenderId ?? null;
            if (defenderId && !canUseSpecial(defenderId)) defense = "block";
        }
        if (defense === "gk-special") {
            const keeperId = getKeeperId(defenseTeam);
            if (keeperId && !canUseSpecial(keeperId)) defense = "hands";
        }

        if ((attack === "shot" || attack === "special") &&
            state.pendingShotContext?.stage === "keeper") {
            resolveShotKeeperDuel(state.pendingShotContext, defense);
            state.pendingShotContext = null;
            return;
        }

        state.phase = "attack";
        state.pendingAttack = null;
        const defenderPick = state.pendingDefenseContext;
        state.pendingDefenseContext = null;

        if (attack === "pass")         resolvePass(attackTeam, defenseTeam, defense, defenderPick);
        else if (attack === "dribble") resolveDribble(attackTeam, defenseTeam, defense, defenderPick);
        else if (attack === "shot")    resolveShot(attackTeam, defenseTeam, defense, false, defenderPick);
        else if (attack === "special") resolveShot(attackTeam, defenseTeam, defense, true,  defenderPick);
    }

    // ==========================
    //   INIT
    // ==========================
    function init() {
        initBasePositions(basePositions);
        applyRosterToDOM(roster, rootEl);
        initStamina(ball, () => updateTeamCard(ball));

        rootEl.querySelectorAll(".player").forEach((el) => {
            el.addEventListener("click", () => {
                const id     = el.dataset.player;
                const team   = id?.startsWith("I") ? "internal" : "external";
                const number = parseInt(id?.slice(1), 10);
                updateSideCard(team === "internal" ? "home" : "away", team, number);
            });
        });

        bindDuelTooltipEvents();

        // Reset state
        state.turns        = 0;
        state.currentTeam  = "internal";
        state.score        = { internal: 0, external: 0 };
        state.phase        = "attack";
        state.pendingAttack= null;
        state.isAnimating  = false;
        state.lastDribblerId = null;
        state.isKickoff    = true;
        state.keeperRestartMustPass = false;
        state.isGameOver   = false;
        state.pendingShotContext    = null;
        state.pendingDefenseContext = null;
        state.pendingClearanceBonus = 0;
        state.foulEvents   = [];

        applyKickoffPositions(basePositions);
        state._moveBallFn("internal", 8);

        updateSideCard("home", "internal", 8);
        updateSideCard("away", "external", 8);

        setMessage(TEXTS.ui.gameStartMain, TEXTS.ui.gameStartSub);

        state.actionEvents.push({
            gameSaveId: matchConfig.gameSaveId ?? null,
            matchId:    matchConfig.matchId    ?? null,
            turn: 0, type: "kickoff",
            context: { team: "internal" },
            attack: null, defense: null, result: null, resultWinner: null, diff: null,
        });

        showAttackBarForCurrentTeam();
        refreshUI();

        if (ui.modeOnePlayerBtn) {
            const sync = () => {
                ui.modeOnePlayerBtn.classList.toggle("active", onePlayerMode);
                ui.modeOnePlayerBtn.textContent = onePlayerMode ? "Mode 1 joueur" : "Mode 2 joueurs";
            };
            sync();
            ui.modeOnePlayerBtn.addEventListener("click", () => { onePlayerMode = !onePlayerMode; sync(); setAIOverlay(false); });
        }

        if (ui.controlledTeamSelect) {
            ui.controlledTeamSelect.value = controlledTeam;
            ui.controlledTeamSelect.addEventListener("change", () => {
                controlledTeam = ui.controlledTeamSelect.value === "external" ? "external" : "internal";
            });
        }

        if (ui.teamNameInternalEl) ui.teamNameInternalEl.textContent = TEAMS.internal.label;
        if (ui.teamNameExternalEl) ui.teamNameExternalEl.textContent = TEAMS.external.label;

        const homeCard = rootEl.querySelector("#home-card");
        const awayCard = rootEl.querySelector("#away-card");
        homeCard?.classList.replace("team-external", "team-internal") || homeCard?.classList.add("team-internal");
        awayCard?.classList.replace("team-internal", "team-external") || awayCard?.classList.add("team-external");
    }

    init();
}
