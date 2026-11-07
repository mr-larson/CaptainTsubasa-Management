// resources/js/Pages/Match/engine.js
// Point d'entrée unique — orchestre tous les sous-modules du dossier engine/

import {
    TEXTS, GAME_RULES, ANIM_MS, ACTION_BAR_FADE_MS, GK_HOLD_MS, POSITION_BONUS,
    PASSIVE_STAMINA_DRAIN_PER_TURN,
} from './engine/constants.js';

import { RosterService } from './engine/RosterService.js';

import {
    initStaminaModule, initStamina,
    getStaminaRatio, staminaFactor, applyStaminaCost, updateStaminaUI,
} from './engine/stamina.js';

import {
    initDiceUI, bindDuelTooltipEvents, showDuelDice, showSpecialTooltip, hideBreakdownTooltip,
    disposeDiceUI, runAfterDiceAnimation,
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
    initUIModule, disposeUIModule,
    setMessage, setAIOverlay, updateScoreUI, pushLogEntry, highlightDuelStats,
    updateSideCard, highlightDuelPlayers, updateTeamCard, updateCardsPower,
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
    resolvePass, resolveDribble, resolveOneTwo, resolveShot, resolveShotKeeperDuel,
    resolveCross, resolveLongPass,
    performKeeperClearance, recordDuelEvent,
    resetCaptainRerollActionFlag,
} from './engine/resolvers.js';

import { buildMatchStats, computePlaytime, orientToHomeAway } from './engine/matchStats.js';
import { runShootout } from './engine/shootout.js';
import { attackPrompt, defensePrompt } from './engine/turnPrompt.js';

import {
    initFoulsModule, resolveFoulOutcome,
} from './engine/fouls.js';

import {
    initSubstitutionsModule, performSubstitution, aiCheckSubstitutions,
} from './engine/substitutions.js';

// ==========================
//   EXPORT PRINCIPAL
// ==========================
export function initMatchEngine(rootEl, config = {}) {
    if (!rootEl) return;

    const matchConfig = config || {};
    const MAX_TURNS = matchConfig.maxTurns ?? GAME_RULES.MAX_TURNS; // réglable 10-80 par save

    const TEAMS = {
        internal: { id: "internal", label: matchConfig.teams?.internal?.name ?? TEXTS.teams.internal },
        external: { id: "external", label: matchConfig.teams?.external?.name ?? TEXTS.teams.external },
    };

    const controlMode    = matchConfig.controlMode ?? "both";
    let onePlayerMode    = (controlMode === "single");
    let controlledTeam   = matchConfig.controlledSide ?? "internal";
    const internalIsHome = matchConfig.internalIsHome ?? true;

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
        maxTurns: MAX_TURNS, // lu par ui.js (horloge) et resolvers.js (fin de match de l'IA)
        ball: { team: "internal", zoneIndex: 1, laneIndex: 1, number: 8, frontOfKeeper: false },
        currentTeam:   "internal",
        homeTeam: internalIsHome ? 'internal' : 'external',
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
        pendingCaptainReroll:  null,
        pendingFreeKick:       null,
        lastDribblerId: null,
        stamina:        {},
        staminaMax:     {},
        specialCooldown:{},
        SPECIAL_COOLDOWN_TURNS: 2,
        touchHeat:      {},
        matchLog:       [],
        actionEvents:   [],
        foulEvents:     [],
        exitTurns:      {},   // game_player_id → tour de sortie forcée (expulsion, blessure)
        substitutions:      [],
        substitutionCount:  { internal: 0, external: 0 },
        MAX_SUBSTITUTIONS:  3,
        defensePreview: null,
        _matchConfig:    matchConfig,
        _GK_HOLD_MS:     GK_HOLD_MS,
        _moveBallFn:     null,
        _applyKickoffFn: null,
        _performSubstitution: null,

        // ── Captain Reroll ───────────────────────────────────────
        captainReroll: {
            internal: { contractId: null, rerollsRemaining: 0, usedOnCurrentAction: false },
            external: { contractId: null, rerollsRemaining: 0, usedOnCurrentAction: false },
        },
    };

    const ball = state.ball;

    // ==========================
    //   INIT CAPTAIN REROLL
    //   (depuis matchConfig.teams)
    // ==========================
    ;['internal', 'external'].forEach(team => {
        const captain = matchConfig.teams?.[team]?.players?.find(p => p.is_captain);
        if (captain) {
            state.captainReroll[team] = {
                contractId:          captain.contract_id ?? null,
                rerollsRemaining:    captain.captain_rerolls_remaining ?? 3,
                usedOnCurrentAction: false,
            };
        }
    });

    // ==========================
    //   HELPERS LOCAUX
    // ==========================
    function isAITeam(team) {
        return onePlayerMode && team !== controlledTeam;
    }

    // ── Cooldown PAR TECHNIQUE ───────────────────────────────
    // state.specialCooldown[playerId] = { [moveKey]: turnDispoÀPartirDe }
    // Chaque technique (Feuille morte, Passe fantôme, Dribble tornado,
    // GK hand special, …) a son propre cooldown (champ `cooldown` du JSON
    // special_moves), indépendant des autres techniques du joueur.
    function teamSlotFromPlayerId(playerId) {
        if (!playerId) return null;
        return {
            team: playerId.startsWith("I") ? "internal" : "external",
            slot: parseInt(playerId.slice(1), 10),
        };
    }

    function specialMoveFor(playerId, mode, baseAction) {
        const ts = teamSlotFromPlayerId(playerId);
        return ts ? roster.getSpecialMove(ts.team, ts.slot, mode, baseAction) : null;
    }

    function isMoveOffCooldown(playerId, move) {
        if (!move) return false;
        const readyAt = state.specialCooldown[playerId]?.[move.key];
        return !readyAt || state.turns >= readyAt;
    }

    function canUseSpecial(playerId, mode = "attack", baseAction = null) {
        return isMoveOffCooldown(playerId, specialMoveFor(playerId, mode, baseAction));
    }

    function markSpecialUsed(playerId, mode = "attack", baseAction = null) {
        const move = specialMoveFor(playerId, mode, baseAction);
        if (!move) return;
        state.specialCooldown[playerId] = state.specialCooldown[playerId] ?? {};
        state.specialCooldown[playerId][move.key] = state.turns + (move.cooldown ?? state.SPECIAL_COOLDOWN_TURNS);
    }

    function afterFieldMotion(callback) {
        // Lire les animations du terrain inclut les transitions déclenchées
        // par les positions qui viennent d'être écrites dans le DOM.
        const animations = ui.ballEl?.parentElement?.getAnimations?.({ subtree: true }) ?? [];
        const motion = animations.filter(({ effect, transitionProperty, animationName }) => {
            const target = effect?.target;
            if (target !== ui.ballEl && !target?.classList?.contains("player")) return false;
            return transitionProperty === "left" || transitionProperty === "top" || animationName?.startsWith("player-anim-");
        });
        if (!motion.length) { callback(); return; }

        Promise.allSettled(motion.map(animation => animation.finished)).then(() => {
            if (!state.disposed) callback();
        });
    }

    function animateAndThen(cb) {
        state.isAnimating = true;
        ui.ballEl?.classList.add("ball-kick");
        setTimeout(() => {
            if (state.disposed) return;
            ui.ballEl?.classList.remove("ball-kick");
            afterFieldMotion(() => {
                state.isAnimating = false;
                cb?.();
            });
        }, ANIM_MS);
    }

    function refreshUI() {
        updateScoreUI(state);
        updateTeamCard(ball);
    }

    // Lie les boutons d'action (attaque/défense) générés dans la barre d'actions
    // à leurs handlers. Réutilisé à chaque (re)génération de la barre.
    function bindActionButtons() {
        rootEl.querySelectorAll(".skill-card-main, .skill-card-special").forEach(btn =>
            btn.addEventListener("click", () => { if (touchTipFirst(btn)) handleAttackClick(btn.dataset.action); })
        );
        rootEl.querySelectorAll(".def-card-main, .def-card-special").forEach(btn =>
            btn.addEventListener("click", () => { if (touchTipFirst(btn)) handleDefenseClick(btn.dataset.defense); })
        );

        // Tooltip (nom complet, description, recharge) au survol des slots spéciaux.
        rootEl.querySelectorAll("[data-special]").forEach(btn => {
            let move = null;
            try { move = JSON.parse(btn.dataset.special); } catch { /* données invalides, tooltip ignorée */ }
            if (!move) return;
            btn.addEventListener("mouseenter", () => showSpecialTooltip(move, btn));
            btn.addEventListener("mouseleave", () => hideBreakdownTooltip());
        });
    }

    // Au toucher, il n'y a pas de survol : le 1er tap sur une technique spéciale
    // affiche sa description, un 2e tap sur le même bouton (dans les 4 s) la joue.
    let lastPointerType = 'mouse';
    rootEl.addEventListener('pointerdown', (e) => { lastPointerType = e.pointerType; }, { capture: true });
    function touchTipFirst(btn) {
        if (lastPointerType !== 'touch' || !btn.dataset.special) return true;
        if (btn.dataset.tipShown === '1') return true;
        let move = null;
        try { move = JSON.parse(btn.dataset.special); } catch { return true; }
        showSpecialTooltip(move, btn);
        btn.dataset.tipShown = '1';
        setTimeout(() => { delete btn.dataset.tipShown; hideBreakdownTooltip(); }, 4000);
        return false;
    }

    // ==========================
    //   INIT MODULES
    // ==========================
    initStaminaModule(state, roster, rootEl);
    initDiceUI(ui.duelDiceEl);
    initFieldModule(rootEl, roster, state, ui);
    initUIModule(rootEl, roster, ui, state, TEAMS);
    initAIModule(state, roster);

    initFoulsModule({
        state, roster, rootEl,
        updateSideCard, pushLogEntry,
    });

    initSubstitutionsModule({
        state, roster, rootEl,
        getPlayerId, updateStaminaUI, updateTeamCard, pushLogEntry, refreshUI, isAITeam,
    });

    state._moveBallFn = (team, number) => moveBallToPlayer(team, number, () => updateTeamCard(ball));
    state._applyKickoffFn = () => applyKickoffPositions(basePositions);

    // ==========================
    //   ADVANCE TURN
    // ==========================
    function advanceTurn(newTeam) {
        state.isAnimating = true;
        afterFieldMotion(() => runAfterDiceAnimation(() => {
            if (state.disposed) return;
            state.isAnimating = false;
            finishTurn(newTeam);
        }));
    }

    function finishTurn(newTeam) {
        if (state.isGameOver) return;

        decayHeat();
        state.currentTeam = newTeam;
        state.turns++;

        // Usure passive : chaque joueur sur le terrain perd un peu d'endurance à
        // chaque tour (en plus des coûts d'action). Rend la fatigue de fin de
        // match visible et valorise la stat `stamina` (= endurance max de départ).
        if (PASSIVE_STAMINA_DRAIN_PER_TURN > 0) {
            rootEl.querySelectorAll(".player").forEach((el) => {
                const id = el.dataset.player;
                if (!id) return;
                const current = state.stamina[id];
                if (!(current > 0)) return;
                state.stamina[id] = Math.max(0, current - PASSIVE_STAMINA_DRAIN_PER_TURN);
                // Rafraîchit seulement la barre d'endurance du joueur ; la carte
                // du porteur est resynchronisée par le flux normal du tour.
                updateStaminaUI(id);
            });
        }

        if (state.turns >= MAX_TURNS) {
            state.isGameOver = true;

            setMessage(
                TEXTS.ui.matchEndMain,
                `${TEXTS.ui.matchEndPrefix}${state.score.internal} - ${state.score.external}`
            );
            pushLogEntry("matchEndTitle", [`Score final ${state.score.internal} - ${state.score.external}`], null, state);

            // Phase à élimination directe : une égalité se départage aux tirs au but.
            if (matchConfig.isKnockout && state.score.internal === state.score.external) {
                const so = runShootout(roster, rootEl);
                state.shootout = so;
                so.kicks.forEach((k, i) => {
                    pushLogEntry(`🥅 Tir au but ${i + 1} — ${TEAMS[k.team].label}`, [`${k.name} : ${k.scored ? '✓ But' : '✗ Raté'}`], null, state);
                });
                pushLogEntry("🏆 Tirs au but", [`${TEAMS[so.winner].label} l'emporte ${so.score[so.winner]} - ${so.score[so.winner === 'internal' ? 'external' : 'internal']}`], null, state);
                setMessage(
                    TEXTS.ui.matchEndMain,
                    `${TEXTS.ui.matchEndPrefix}${state.score.internal} - ${state.score.external} (t.a.b. ${so.score.internal} - ${so.score.external})`
                );
            }

            if (ui.actionBarEl) {
                ui.actionBarEl.classList.remove("fade-in");
                ui.actionBarEl.classList.add("fade-out");
                setTimeout(() => { ui.actionBarEl.innerHTML = ""; }, ACTION_BAR_FADE_MS);
            }

            refreshUI();
            if (ui.matchEndActionsEl) ui.matchEndActionsEl.classList.remove("hidden");
            // « Suite » remplace « Retour » : un seul bouton, qui ne déborde plus sur le logo.
            rootEl.ownerDocument.getElementById("btn-back-to-game")?.classList.add("hidden");

            if (ui.finishMatchBtn) {
                ui.finishMatchBtn.onclick = submitResult;
            }
            return;
        }

        setMessage(...attackPrompt(TEAMS[state.currentTeam].label, isAITeam(state.currentTeam)));
        highlightDuelStats();
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
        setTimeout(() => { if (state.disposed) return; setAIOverlay(false); handleAttackClick(action); }, AI_THINK_MS);
    }

    function scheduleAIDefense(attackAction, defendingTeam) {
        if (!isAITeam(defendingTeam) || state.phase !== "defense" || !state.pendingAttack || state.isGameOver) return;
        const AI_THINK_MS = 150;
        setAIOverlay(true, TEXTS.ui.aiDefenseTurn);

        const isKeeperDuel =
            (state.pendingShotContext && state.pendingShotContext.stage === "keeper") ||
            ball.frontOfKeeper;

        // L'action choisie (attackAction) n'est PAS transmise : l'IA la devine.
        const defense = computeAIDefenseChoice(
            defendingTeam,
            { isKeeperDuel, ball },
            state.specialCooldown, state.turns
        );
        setTimeout(() => { if (state.disposed) return; setAIOverlay(false); handleDefenseClick(defense); }, AI_THINK_MS);
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
        afterFieldMotion,
        scheduleAIDefense,
        isAITeam,
        canUseSpecial,
        markSpecialUsed,
        resolveFoulOutcome,
        bindActionButtons,
    });

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
        });
    }

    // ==========================
    //   DEFENDER PREVIEW
    // ==========================
    function setDefenderPreviewFor(action, defenseTeam) {
        const prefix       = defenseTeam === "internal" ? "home" : "away";
        const isKeeperStage =
            (state.pendingShotContext && state.pendingShotContext.stage === "keeper") ||
            (ball.frontOfKeeper && ["shot", "special", "special-pass", "special-dribble"].includes(action));

        if (isKeeperStage) {
            updateSideCard(prefix, defenseTeam, 1);
            highlightDuelPlayers(ball.team, ball.number, defenseTeam, 1);
            return;
        }

        if (state.pendingDefenseContext?.defenderSlot) {
            const { defenderSlot } = state.pendingDefenseContext;
            updateSideCard(prefix, defenseTeam, defenderSlot);
            highlightDuelPlayers(ball.team, ball.number, defenseTeam, defenderSlot);
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
        highlightDuelPlayers(ball.team, ball.number, defenseTeam, picked?.defenderSlot);
    }

    // ==========================
    //   SHOW ATTACK BAR
    // ==========================
    function showAttackBarForCurrentTeam() {
        if (state.isGameOver) return;

        // ── Reset du flag reroll capitaine avant chaque nouvelle action ──
        resetCaptainRerollActionFlag(state.currentTeam);

        setActionBar(
            buildAttackActionsHTML(ball, roster),
            `mode-attack-${state.currentTeam}`,
            ball, roster, bindActionButtons, state.isKickoff
        );

        const defTeam      = otherTeam(state.currentTeam);
        const defaultAction= state.isKickoff ? "pass" : (ball.frontOfKeeper ? "shot" : "pass");
        setDefenderPreviewFor(defaultAction, defTeam);
        highlightDuelStats(state.isKickoff ? null : state.currentTeam, ball.frontOfKeeper);

        if (isAITeam(state.currentTeam)) scheduleAIAttack();
        updateCardsPower(ball);
    }

    // ==========================
    //   HANDLERS CLICKS
    // ==========================
    function handleAttackClick(action) {
        if (state.isGameOver || state.isAnimating) return;
        if (state.turns >= MAX_TURNS || state.phase !== "attack") return;
        if (!["shot","pass","dribble","one_two","special","special-pass","special-dribble","cross","long_pass"].includes(action)) return;

        if (state.isKickoff) {
            if (action !== "pass") return;
            resolveKickoffPass(state.currentTeam);
            return;
        }

        // Face au gardien : tir, technique spéciale, ou passe en retrait (le porteur
        // renonce au face-à-face et remet à un partenaire, la passe reste interceptable).
        if (ball.frontOfKeeper && action === "pass") {
            ball.frontOfKeeper = false;
            state.backPass = true;
        } else if (ball.frontOfKeeper && !["shot", "special", "special-pass", "special-dribble"].includes(action)) return;

        // Une-deux : exige un partenaire de duo sur le terrain
        if (action === "one_two" && !roster.getDuoPartnerOnField(state.currentTeam, ball.number)) return;

        if (action === "cross" || action === "long_pass") {
            const defTeam = otherTeam(state.currentTeam);
            state.phase = "defense";
            state.pendingAttack = action;
            if (action === "cross") resolveCross(state.currentTeam, defTeam);
            else resolveLongPass(state.currentTeam, defTeam);
            return;
        }

        const specialAction = { special: "shot", "special-pass": "pass", "special-dribble": "dribble" }[action];
        if (specialAction) {
            const attackerId = getPlayerId(state.currentTeam, ball.number);
            if (!canUseSpecial(attackerId, "attack", specialAction)) {
                setMessage(TEXTS.ui.specialCooldownMain, TEXTS.ui.specialCooldownSub);
                state.phase = "attack"; state.pendingAttack = null;
                return;
            }
        }

        state.pendingAttack = action;
        state.phase = "defense";

        const defTeam          = otherTeam(state.currentTeam);
        const isKeeperChoiceUI = (action === "shot" || action === "special" || action === "special-pass" || action === "special-dribble") && ball.frontOfKeeper;

        if (!isKeeperChoiceUI) {
            const snap = state.defensePreview;
            // Le défenseur annoncé (fiche latérale, stats sur les cartes) défend
            // quelle que soit l'action choisie : on ne retire plus au sort.
            const snapOk = snap &&
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
        const { defenderSlot } = state.pendingDefenseContext;
        updateSideCard(defPrefix, defTeam, defenderSlot || 6);
        highlightDuelPlayers(ball.team, ball.number, defTeam, defenderSlot);
        highlightDuelStats(state.currentTeam, isKeeperChoiceUI);

        let html;
        // Après
        const specialMoves = roster.getSpecialMoves(state.currentTeam, ball.number).filter(m => m?.mode === "attack");
        const specialLabel = specialMoves[0]?.label ?? "ACTION SPÉCIALE";

        if (action === "shot" || action === "special") {
            if (ball.frontOfKeeper) {
                html = buildDefenseGKHTML(defTeam, roster);
                setMessage(
                    `${TEAMS[state.currentTeam].label} : ${action === "special" ? specialLabel.toUpperCase() : "TIR"} !`,
                    defensePrompt(TEAMS[defTeam].label, isAITeam(defTeam), true)
                );
            } else {
                html = buildDefenseFieldHTML(defTeam, state.pendingDefenseContext.defenderSlot, roster);
                setMessage(
                    `${TEAMS[state.currentTeam].label} : ${action === "special" ? specialLabel.toUpperCase() : "TIR"} !`,
                    defensePrompt(TEAMS[defTeam].label, isAITeam(defTeam))
                );
            }
        } else if (action === "special-pass") {
            html = buildDefenseFieldHTML(defTeam, state.pendingDefenseContext.defenderSlot, roster);
            setMessage(
                `${TEAMS[state.currentTeam].label} : ${specialLabel.toUpperCase()} !`,
                defensePrompt(TEAMS[defTeam].label, isAITeam(defTeam))
            );
        } else if (action === "special-dribble") {
            html = buildDefenseFieldHTML(defTeam, state.pendingDefenseContext.defenderSlot, roster);
            setMessage(
                `${TEAMS[state.currentTeam].label} : ${specialLabel.toUpperCase()} !`,
                defensePrompt(TEAMS[defTeam].label, isAITeam(defTeam))
            );
        } else if (action === "one_two") {
            const duo = roster.getDuoPartnerOnField(state.currentTeam, ball.number);
            html = buildDefenseFieldHTML(defTeam, state.pendingDefenseContext.defenderSlot, roster);
            setMessage(
                `${TEAMS[state.currentTeam].label} prépare un UNE-DEUX${duo?.label ? ` (${duo.label})` : ""} !`,
                defensePrompt(TEAMS[defTeam].label, isAITeam(defTeam))
            );
        } else {
            html = buildDefenseFieldHTML(defTeam, state.pendingDefenseContext.defenderSlot, roster);
            setMessage(
                `${TEAMS[state.currentTeam].label} tente : ${(TEXTS.cards.attack[action]?.title ?? action).toUpperCase()} !`,
                defensePrompt(TEAMS[defTeam].label, isAITeam(defTeam))
            );
        }
        if (!isAITeam(defTeam) && !isKeeperChoiceUI) {
            setMessage(`${TEAMS[state.currentTeam].label} passe à l'attaque !`, defensePrompt(TEAMS[defTeam].label, false));
        }

        setActionBar(html, `mode-defense-${defTeam}`, ball, roster, bindActionButtons, false);
        if (isAITeam(defTeam)) scheduleAIDefense(action, defTeam);
    }

    function handleDefenseClick(defense) {
        if (state.turns >= MAX_TURNS || state.isAnimating || state.phase !== "defense" || !state.pendingAttack) return;

        const isKeeperDuel =
            (state.pendingShotContext && state.pendingShotContext.stage === "keeper") ||
            ball.frontOfKeeper;

        if (isKeeperDuel && !["hands","punch","gk-special"].includes(defense)) defense = "hands";

        const attackTeam  = state.currentTeam;
        const defenseTeam = otherTeam(state.currentTeam);
        const attack      = state.pendingAttack;

        if (defense === "field-special") {
            const defenderId = state.pendingDefenseContext?.defenderId ?? null;
            if (defenderId && !canUseSpecial(defenderId, "defense")) defense = "block";
        }
        if (defense === "gk-special") {
            const keeperId = getKeeperId(defenseTeam);
            if (keeperId && !canUseSpecial(keeperId, "defense")) defense = "hands";
        }

        if ((attack === "shot" || attack === "special" || attack === "special-pass" || attack === "special-dribble") &&
            state.pendingShotContext?.stage === "keeper") {
            // Sort de la phase « defense » avant la résolution : pendant la
            // relance du gardien (300 ms), un 2e clic ferait retirer l'attaquant.
            const ctx = state.pendingShotContext;
            state.phase = "resolving";
            state.pendingAttack = null;
            state.pendingShotContext = null;
            resolveShotKeeperDuel(ctx, defense);
            return;
        }

        state.phase = "attack";
        state.pendingAttack = null;
        const defenderPick = state.pendingDefenseContext;
        state.pendingDefenseContext = null;

        if (attack === "pass")              resolvePass(attackTeam, defenseTeam, defense, defenderPick, false);
        else if (attack === "dribble")      resolveDribble(attackTeam, defenseTeam, defense, defenderPick, false);
        else if (attack === "one_two")      resolveOneTwo(attackTeam, defenseTeam, defense, defenderPick);
        else if (attack === "shot")         resolveShot(attackTeam, defenseTeam, defense, false, defenderPick);
        else if (attack === "special")      resolveShot(attackTeam, defenseTeam, defense, true,  defenderPick);
        else if (attack === "special-pass") resolvePass(attackTeam, defenseTeam, defense, defenderPick, true);
        else if (attack === "special-dribble") resolveDribble(attackTeam, defenseTeam, defense, defenderPick, true);
    }

    // ==========================
    //   ENVOI DU RÉSULTAT
    // ==========================
    // Bouton « Suite » en fin de match, ou abandon en cours de match (Engine.vue :
    // quitter valide le score du moment, pour ne pas pouvoir rejouer un match perdu).
    function submitResult() {
        const internalTeamId = matchConfig?.sides?.internalTeamId;
        const externalTeamId = matchConfig?.sides?.externalTeamId;
        if (!internalTeamId || !externalTeamId) return;
        // Un seul envoi du résultat (double clic) ; réactivé si le serveur refuse.
        if (state.resultSubmitted) return;
        state.resultSubmitted = true;
        if (ui.finishMatchBtn) ui.finishMatchBtn.disabled = true;

        const payload = {
            matchId:    matchConfig.matchId,
            gameSaveId: matchConfig.gameSaveId,
            scoresByTeamId: {
                [internalTeamId]: state.score.internal,
                [externalTeamId]: state.score.external,
            },
            playerActions: state.actionEvents,
            foulEvents:    state.foulEvents,
            onFailed:      () => {
                state.resultSubmitted = false;
                if (ui.finishMatchBtn) ui.finishMatchBtn.disabled = false;
            },
        };

        if (typeof matchConfig.onMatchEnd === "function") {
            payload.match_stats = buildMatchStats(state.actionEvents, state.goalEvents);
            // Déroulé complet du match (action par action), pour le résumé post-match
            payload.match_stats.events = state.matchLog;
            // 11 de départ + temps de jeu par joueur (promesses temps de jeu / titularisation)
            payload.match_stats.starters = {
                home: state.startersByTeam?.internal ?? [],
                away: state.startersByTeam?.external ?? [],
            };
            if (state.shootout) {
                const sideId = { internal: internalTeamId, external: externalTeamId };
                payload.match_stats.shootout = {
                    winner_team_id: sideId[state.shootout.winner],
                    scores: {
                        [internalTeamId]: state.shootout.score.internal,
                        [externalTeamId]: state.shootout.score.external,
                    },
                };
            }
            payload.match_stats.playtime = computePlaytime(
                state.startersByTeam, state.substitutions, state.turns, state.exitTurns
            );
            // Repère domicile / extérieur (l'humain peut jouer à l'extérieur)
            payload.match_stats = orientToHomeAway(payload.match_stats, internalIsHome);
            matchConfig.onMatchEnd(payload);
        }
    }

    // ==========================
    //   INIT
    // ==========================
    function init() {

        window.__matchState = state;
        window.__matchRoster = roster;
        state._isAITeam = isAITeam; // lu par ui.js (droit de remplacer)

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
        state.pendingCaptainReroll  = null;
        state.pendingFreeKick       = null;
        state.pendingClearanceBonus = 0;
        state.foulEvents        = [];
        resetLogHistory();
        state.substitutions     = [];
        state.substitutionCount = { internal: 0, external: 0 };

        // 11 de départ figé (avant tout remplacement) : sert au calcul du temps
        // de jeu par joueur et à la promesse de titularisation. Un titulaire
        // indisponible (blessé, suspendu, consigné) n'a pas joué.
        const startersOf = (team) => Array.from({ length: 11 }, (_, i) => roster.getPlayerInfo(team, i + 1))
            .filter(info => info?.id != null && info.isAvailable !== false)
            .map(info => info.id);
        state.startersByTeam = {
            internal: startersOf("internal"),
            external: startersOf("external"),
        };

        applyKickoffPositions(basePositions);
        const kickoffTeam = matchConfig.isControlledHome ? "internal" : "external";
        state.currentTeam = kickoffTeam;
        state._moveBallFn(kickoffTeam, 8);

        updateSideCard("home", "internal", 8);
        updateSideCard("away", "external", 8);

        setMessage(
            TEXTS.ui.gameStartMain,
            `${attackPrompt(TEAMS[kickoffTeam].label, isAITeam(kickoffTeam))[0]} · coup d'envoi : passe obligatoire.`
        );

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

    // Appelé par Engine.vue au démontage : les timers en vol voient `disposed`
    // et s'arrêtent, les listeners globaux sont retirés et l'état de module
    // (toast, file de révélation) ne fuit pas dans le match suivant.
    function destroy() {
        state.disposed = true;
        disposeUIModule();
        disposeDiceUI();
        hideBreakdownTooltip();
        if (window.__matchState === state) delete window.__matchState;
        if (window.__matchRoster === roster) delete window.__matchRoster;
    }
    // Match commencé et résultat pas encore envoyé : quitter doit valider le score.
    destroy.isInProgress = () => !state.disposed && !state.resultSubmitted && state.turns > 0;
    destroy.finishNow    = () => { state.isGameOver = true; submitResult(); };
    destroy.scoreLabel   = () => `${state.score.internal} - ${state.score.external}`;
    return destroy;
}
