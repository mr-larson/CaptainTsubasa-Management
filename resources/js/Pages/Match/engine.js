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
    setActionBar, applyRosterToDOM, resetLogHistory
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
        substitutions:      [],
        substitutionCount:  0,
        MAX_SUBSTITUTIONS:  3,
        defensePreview: null,
        _matchConfig:    matchConfig,
        _GK_HOLD_MS:     GK_HOLD_MS,
        _moveBallFn:     null,
        _applyKickoffFn: null,
        _performSubstitution: null,
    };

    const ball = state.ball;

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
    //   REMPLACEMENTS
    // ==========================
    function performSubstitution(team, outSlot, inSlot) {
        if (state.substitutionCount >= state.MAX_SUBSTITUTIONS) return false;
        if (outSlot === inSlot) return false;

        const outId = getPlayerId(team, outSlot);
        const outEl = rootEl.querySelector(`[data-player="${outId}"]`);
        if (!outEl) return false;

        // Remplaçant peut être slot 12+ (pas de pion DOM)
        const inId  = getPlayerId(team, inSlot);
        const inEl  = rootEl.querySelector(`[data-player="${inId}"]`);

        if (inEl) {
            // Remplaçant a un pion sur le terrain → ancien comportement
            if (inEl.classList.contains('unavailable')) return false;
            inEl.style.left = outEl.style.left;
            inEl.style.top  = outEl.style.top;
            if (outEl.dataset.zone) inEl.dataset.zone = outEl.dataset.zone;
            outEl.classList.add('unavailable');
        } else {
            // Remplaçant slot 12+ → mettre à jour le pion du sortant
            const inInfo = roster.getPlayerInfo(team, inSlot);
            if (!inInfo) return false;
            roster.rosters[team].set(outSlot, { ...inInfo, isStarter: true });
            roster.rosters[team].set(inSlot, { ...inInfo, isAvailable: false });
            const subNumber = 11 + state.substitutionCount + 1; // 12, 13, 14...
            outEl.textContent       = String(subNumber);
            outEl.dataset.jersey    = String(subNumber);
            outEl.dataset.firstname = inInfo.firstname;
            outEl.dataset.lastname  = inInfo.lastname;
            outEl.dataset.position  = inInfo.position;
            const inStamina = inInfo.stats?.stamina ?? 80;
            state.stamina[outId]    = inStamina;
            state.staminaMax[outId] = inStamina;
            updateStaminaUI(outId, ball, () => updateTeamCard(ball));
        }

        if (state.ball.team === team && state.ball.number === outSlot) {
            state._moveBallFn(team, outSlot);
        }

        const outInfo = roster.getPlayerInfo(team, outSlot);
        const inInfo  = roster.getPlayerInfo(team, inSlot);

        state.substitutions.push({
            team, outSlot, inSlot,
            turn:        state.turns,
            outPlayerId: outInfo?.id ?? null,
            inPlayerId:  inInfo?.id  ?? null,
        });
        state.substitutionCount++;

        pushLogEntry('substitutionTitle', [
            `🔄 ${outInfo?.lastname ?? '#' + outSlot} → ${inInfo?.lastname ?? '#' + inSlot}`,
        ], null, state);

        refreshUI();
        return true;
    }
    // IA — remplacements automatiques stamina < 50%
    function aiCheckSubstitutions() {
        if (state.isGameOver) return;
        if (state.substitutionCount >= state.MAX_SUBSTITUTIONS) return;

        for (const aiTeam of ['internal', 'external']) {
            if (!isAITeam(aiTeam)) continue;

            for (let outSlot = 1; outSlot <= 11; outSlot++) {
                const outId = getPlayerId(aiTeam, outSlot);
                const outEl = rootEl.querySelector(`[data-player="${outId}"]`);
                if (!outEl || outEl.classList.contains('unavailable')) continue;

                const stMax = state.staminaMax[outId] ?? 100;
                const stCur = state.stamina[outId]    ?? stMax;
                const ratio = stMax > 0 ? stCur / stMax : 1;
                if (ratio >= 0.5) continue;

                const outInfo = roster.getPlayerInfo(aiTeam, outSlot);
                const outPos  = outInfo?.position ?? '';

                let bestInSlot = null;
                let bestRatio  = 0;

                const subsPool = roster.getSubs(aiTeam);
                for (const { slot: inSlot, info: inInfo } of subsPool) {
                    if (inInfo.isAvailable === false) continue;
                    // Vérifier que ce remplaçant n'a pas déjà été utilisé
                    if (state.substitutions.some(s => s.inSlot === inSlot && s.team === aiTeam)) continue;

                    const inStMax = state.staminaMax[getPlayerId(aiTeam, inSlot)] ?? 100;
                    const inStCur = state.stamina[getPlayerId(aiTeam, inSlot)]    ?? inStMax;
                    const inRatio = inStMax > 0 ? inStCur / inStMax : 1;

                    const samePos = inInfo.position === outPos;
                    if (samePos && inRatio > bestRatio) {
                        bestRatio  = inRatio;
                        bestInSlot = inSlot;
                    } else if (!bestInSlot) {
                        bestInSlot = inSlot;
                    }
                }

                if (bestInSlot !== null) {
                    performSubstitution(aiTeam, outSlot, bestInSlot);
                    break;
                }
            }
        }
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

        aiCheckSubstitutions();
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
    //   FAUTES / CARTONS
    // ==========================
    function resolveFoulOutcome({ attackerId, defenderId, duelResult, aRoll, dRoll }) {
        const getDbId = (domId) => {
            if (!domId) return null;
            const team = domId.startsWith('I') ? 'internal' : 'external';
            const slot = parseInt(domId.slice(1), 10);
            return roster.getPlayerInfo(team, slot)?.id ?? null;
        };

        // Récupérer les noms pour les logs
        const getPlayerName = (domId) => {
            if (!domId) return '?';
            const team = domId.startsWith('I') ? 'internal' : 'external';
            const slot = parseInt(domId.slice(1), 10);
            const info = roster.getPlayerInfo(team, slot);
            return info ? `${info.firstname} ${info.lastname}`.trim() : domId;
        };

        const attackerDbId   = getDbId(attackerId);
        const defenderDbId   = getDbId(defenderId);
        const defenderName   = getPlayerName(defenderId);

        const isCritFailDefense = dRoll?.critFail ?? false;
        const isTie             = duelResult === 'tie';

        if (isCritFailDefense) {
            state.foulEvents.push({ type: 'foul', fouler_player_id: defenderDbId, victim_player_id: attackerDbId, is_crit_fail: true });
            const r = Math.random();
            if (r < 0.15) {
                state.foulEvents.push({ type: 'card', player_id: defenderDbId, card_type: 'red' });
                const el = rootEl.querySelector(`[data-player="${defenderId}"]`);
                if (el) el.classList.add('unavailable');
                pushLogEntry('foulCardTitle', [`🟥 ${defenderName} — Carton rouge ! Expulsé !`], null, state);
            } else if (r < 0.90) {
                state.foulEvents.push({ type: 'card', player_id: defenderDbId, card_type: 'yellow' });
                const matchYellows = state.foulEvents.filter(
                    e => e.type === 'card' && e.card_type === 'yellow' && e.player_id === defenderDbId
                ).length;
                if (matchYellows >= 2) {
                    const el = rootEl.querySelector(`[data-player="${defenderId}"]`);
                    if (el) el.classList.add('unavailable');
                    pushLogEntry('foulCardTitle', [`🟨🟨 ${defenderName} — Double jaune ! Expulsé !`], null, state);
                } else {
                    pushLogEntry('foulCardTitle', [`🟨 ${defenderName} — Carton jaune`], null, state);
                }
            } else {
                pushLogEntry('foulTitle', [`⚠️ ${defenderName} — Faute`], null, state);
            }
        }

        if (isTie && Math.random() < 0.25) {
            state.foulEvents.push({ type: 'foul', fouler_player_id: defenderDbId, victim_player_id: attackerDbId, is_crit_fail: false });
            if (Math.random() < 0.20) {
                state.foulEvents.push({ type: 'card', player_id: defenderDbId, card_type: 'yellow' });
                const matchYellows = state.foulEvents.filter(
                    e => e.type === 'card' && e.card_type === 'yellow' && e.player_id === defenderDbId
                ).length;
                if (matchYellows >= 2) {
                    const el = rootEl.querySelector(`[data-player="${defenderId}"]`);
                    if (el) el.classList.add('unavailable');
                    pushLogEntry('foulCardTitle', [`🟨🟨 ${defenderName} — Double jaune ! Expulsé !`], null, state);
                } else {
                    pushLogEntry('foulCardTitle', [`🟨 ${defenderName} — Carton jaune`], null, state);
                }
            }
        }
    }

    // ==========================
    //   INIT RESOLVERS
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

    // Brancher performSubstitution dans state pour ui.js
    state._performSubstitution = performSubstitution;

    // ==========================
    //   KICKOFF AUTO
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

        window.__matchState = state;
        window.__matchRoster = roster;

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

        state.turns        = 0;
        state.currentTeam  = "internal";
        state.score        = { internal: 0, external: 0 };
        state.phase        = "attack";
        state.pendingAttack= null;
        state.isAnimating  = false;
        state.lastDribblerId = null;
        state.isKickoff    = true;
        state.isGameOver   = false;
        state.pendingShotContext    = null;
        state.pendingDefenseContext = null;
        state.pendingClearanceBonus = 0;
        state.foulEvents        = [];
        resetLogHistory();
        state.substitutions     = [];
        state.substitutionCount = 0;

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
            setTimeout(() => { ui.controlledTeamSelect.value = controlledTeam; }, 0);
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
