// resources/js/Pages/Match/engine/resolvers.js
import {
    TEXTS, DUEL_RULES, FIELD_RULES,
    MAX_ZONE_INDEX, ZONE_BOUNDS_INTERNAL, laneY,
    CRIT_STAMINA_BOOST,
} from './constants.js';
import {
    rollD20WithCrit, rollD20Advantage, resolveCritOutcome,
    buildDuelMeta, buildFieldDuelBreakdown, showDuelDice,
} from './dice.js';
import { staminaFactor, applyStaminaCost } from './stamina.js';
import { specialBaseFor } from './RosterService.js';
import {
    getPlayerId, otherTeam,
    getCellCenter, getPlayerXInternal, getCarrierElement, getKeeperId,
    pickFieldDefender, pickReceiverInCell, pickWeightedPlayerInZone,
    isGoalkeeperId, setBallToKeeperVisual,
} from './field.js';
import {
    setMessage, pushLogEntry,
    updateSideCard, syncRecovererCard, updateTeamCard,
    buildDefenseGKHTML, buildDefenseFieldHTML, setActionBar,
} from './ui.js';

// -----------------------------------------------------------
//   Dépendances injectées
// -----------------------------------------------------------
let _state       = null;
let _roster      = null;
let _ui          = null;
let _TEAMS       = null;
let _rootEl      = null;
let _basePos     = null;

let _advanceTurn                 = null;
let _showAttackBarForCurrentTeam = null;
let _refreshUI                   = null;
let _animateAndThen              = null;
let _scheduleAIDefense           = null;
let _isAITeam                    = null;
let _canUseSpecial               = null;
let _markSpecialUsed             = null;
let _bindActionButtons           = null;
let _resolveFoulOutcome          = null;

export function initResolversModule({
                                        state, roster, ui, TEAMS, rootEl, basePos,
                                        advanceTurn, showAttackBarForCurrentTeam, refreshUI,
                                        animateAndThen, scheduleAIDefense, isAITeam,
                                        canUseSpecial, markSpecialUsed, bindActionButtons,
                                        resolveFoulOutcome,
                                    }) {
    _state      = state;
    _roster     = roster;
    _ui         = ui;
    _TEAMS      = TEAMS;
    _rootEl     = rootEl;
    _basePos    = basePos;

    _advanceTurn                 = advanceTurn;
    _showAttackBarForCurrentTeam = showAttackBarForCurrentTeam;
    _refreshUI                   = refreshUI;
    _animateAndThen              = animateAndThen;
    _scheduleAIDefense           = scheduleAIDefense;
    _isAITeam                    = isAITeam;
    _canUseSpecial               = canUseSpecial;
    _markSpecialUsed             = markSpecialUsed;
    _bindActionButtons           = bindActionButtons;
    _resolveFoulOutcome          = resolveFoulOutcome ?? null;
}

// -----------------------------------------------------------
//   Helpers locaux
// -----------------------------------------------------------
const ball = () => _state.ball;

function resetLastDribbler() {
    if (!_state.lastDribblerId) return;
    const pos = _basePos[_state.lastDribblerId];
    const el  = _rootEl.querySelector('[data-player="' + _state.lastDribblerId + '"]');
    if (pos && el) { el.style.left = pos.x + "%"; el.style.top = pos.y + "%"; }
    _state.lastDribblerId = null;
}

function restoreBasePositions() {
    _rootEl.querySelectorAll(".player").forEach((el) => {
        const base = _basePos[el.dataset.player];
        if (!base) return;
        el.style.left = base.x + "%";
        el.style.top  = base.y + "%";
    });
}

function applyCritBoost(playerId) {
    if (!playerId) return;
    const current = _state.stamina[playerId] ?? 0;
    const max     = _state.staminaMax[playerId] ?? 100;
    _state.stamina[playerId] = Math.min(max, current + CRIT_STAMINA_BOOST);
}

// -----------------------------------------------------------
//   RPS
// -----------------------------------------------------------
export function isGoodDefenseChoice(attackAction, defenseAction) {
    const a = String(attackAction).toLowerCase();
    const d = String(defenseAction).toLowerCase();
    if (["hands", "punch", "gk-special"].includes(d)) return true;
    return (
        (a === "pass"    && d === "intercept") ||
        (a === "dribble" && d === "tackle")    ||
        (a === "shot"    && d === "block")     ||
        (a === "special" && (d === "field-special" || d === "block"))
    );
}

function getCounterTag(attackAction, defenseAction) {
    return isGoodDefenseChoice(attackAction, defenseAction) ? "OK Bon contre" : "X Mauvais choix";
}

function getLogTitleForDuel(attackAction, defenseAction, duelWinner) {
    if (duelWinner === "tie")    return "Duel equilibre";
    if (duelWinner === "attack") {
        if (attackAction === "pass")    return TEXTS.logs.passSuccessTitle;
        if (attackAction === "dribble") return TEXTS.logs.dribbleSuccessTitle;
        return TEXTS.logs.shotGoalTitle;
    }
    if (attackAction === "pass")    return defenseAction === "intercept" ? TEXTS.logs.passFailTitle    : TEXTS.logs.passRecoveredTitle;
    if (attackAction === "dribble") return defenseAction === "tackle"    ? TEXTS.logs.dribbleFailTitle : TEXTS.logs.dribbleRecoveredTitle;
    if (attackAction === "shot")    return defenseAction === "block"     ? TEXTS.logs.shotBlockedTitle : TEXTS.logs.shotRecoveredTitle;
    if (attackAction === "special") return defenseAction === "block"     ? TEXTS.logs.shotBlockedTitle : TEXTS.logs.specialRecoveredTitle;
    return "";
}

function applyDuelBonuses({ attackAction, defenseAction, attackScore, defenseScore, context = {} }) {
    const good = context.isKeeperDuel
        ? ["hands", "punch", "gk-special"].includes(defenseAction)
        : isGoodDefenseChoice(attackAction, defenseAction);
    return good
        ? { attackScore, defenseScore: defenseScore + DUEL_RULES.GOOD_COUNTER_BONUS }
        : { attackScore: attackScore + DUEL_RULES.GENERIC_ATTACK_BONUS, defenseScore };
}

// -----------------------------------------------------------
//   Animations
// -----------------------------------------------------------
function animateGoalThenReset(attackTeam, cb) {
    if (!_ui.ballEl) { if (cb) cb(); return; }
    _ui.ballEl.style.left = FIELD_RULES.GOAL_X[attackTeam] + "%";
    _ui.ballEl.style.top  = FIELD_RULES.GOAL_Y + "%";
    _animateAndThen(() => { if (cb) cb(); });
}

function animateShotToKeeper(defenseTeam, cb) {
    setBallToKeeperVisual(defenseTeam);
    _animateAndThen(() => {
        setTimeout(() => { if (cb) cb(); }, _state._GK_HOLD_MS ?? 300);
    });
}

function animateBallToXY(x, y, cb) {
    if (!_ui.ballEl) { if (cb) cb(); return; }
    _ui.ballEl.style.left = x + "%";
    _ui.ballEl.style.top  = y + "%";
    _animateAndThen(() => { if (cb) cb(); });
}

// -----------------------------------------------------------
//   Egalite -> possession defense (jamais GK)
// -----------------------------------------------------------
export function givePossessionOnTie(defenseTeam, defenderIdMaybe = null) {
    const b = ball();
    if (defenderIdMaybe && !isGoalkeeperId(defenderIdMaybe)) {
        const slot = parseInt(defenderIdMaybe.slice(1), 10);
        _moveBall(defenseTeam, slot);
        setMessage(TEXTS.ui.duelTieMain, TEXTS.ui.duelTieSub.replace("{team}", _TEAMS[defenseTeam].label));
        return { team: defenseTeam, number: slot };
    }
    const fallbackId = pickWeightedPlayerInZone(defenseTeam, b.zoneIndex, b.laneIndex);
    if (fallbackId && !isGoalkeeperId(fallbackId)) {
        const slot = parseInt(fallbackId.slice(1), 10);
        _moveBall(defenseTeam, slot);
        setMessage(TEXTS.ui.duelTieMain, TEXTS.ui.duelTieSub.replace("{team}", _TEAMS[defenseTeam].label));
        return { team: defenseTeam, number: slot };
    }
    return { team: b.team, number: b.number };
}

function _moveBall(team, number) {
    _state._moveBallFn?.(team, number);
}

// -----------------------------------------------------------
//   Enregistrement evenements match
// -----------------------------------------------------------
export function recordDuelEvent({ attackTeam, defenseTeam, attackSlot, defenseSlot, attackAction, defenseAction, duelResult, breakdown }) {
    const attackInfo  = _roster.getPlayerInfo(attackTeam, attackSlot);
    const defenseInfo = defenseSlot ? _roster.getPlayerInfo(defenseTeam, defenseSlot) : null;

    _state.actionEvents.push({
        gameSaveId: _state._matchConfig?.gameSaveId ?? null,
        matchId:    _state._matchConfig?.matchId    ?? null,
        turn:       _state.turns,
        context: { zoneIndex: ball().zoneIndex, laneIndex: ball().laneIndex, frontOfKeeper: ball().frontOfKeeper },
        attack: {
            team: attackTeam, slot: attackSlot,
            game_player_id: attackInfo?.id ?? null,
            number: attackInfo?.number ?? attackSlot,
            action: attackAction,
        },
        defense: defenseSlot ? {
            team: defenseTeam, slot: defenseSlot,
            game_player_id: defenseInfo?.id ?? null,
            number: defenseInfo?.number ?? defenseSlot,
            action: defenseAction,
        } : null,
        result:       duelResult,
        resultWinner: breakdown?.result?.winner ?? null,
        diff:         breakdown?.result?.diff   ?? null,
    });
}

// -----------------------------------------------------------
//   CAPTAIN REROLL — helpers
// -----------------------------------------------------------

/**
 * Reset le flag "used this action" au début de chaque nouvelle action.
 * À appeler depuis engine.js avant showAttackBarForCurrentTeam().
 */
export function resetCaptainRerollActionFlag(team) {
    if (_state.captainReroll?.[team]) {
        _state.captainReroll[team].usedOnCurrentAction = false;
    }
}

/**
 * Vérifie si l'attaquant (capitaine) peut relancer.
 */
function canAttackerReroll(attackTeam, attackSlot) {
    const info = _roster.getPlayerInfo(attackTeam, attackSlot);
    if (!info?.isCaptain) return false;
    const reroll = _state.captainReroll?.[attackTeam];
    if (!reroll) return false;
    return reroll.rerollsRemaining > 0 && !reroll.usedOnCurrentAction;
}

/**
 * L'IA décide si elle utilise sa relance selon le contexte du match.
 */
function aiShouldReroll(attackTeam) {
    const reroll = _state.captainReroll?.[attackTeam];
    if (!reroll || reroll.rerollsRemaining <= 0) return false;

    const turn      = _state.turns ?? 0;
    const selfScore = _state.score?.[attackTeam] ?? 0;
    const oppTeam   = attackTeam === "internal" ? "external" : "internal";
    const oppScore  = _state.score?.[oppTeam] ?? 0;

    if (turn >= 35)              return Math.random() < 0.85; // Late game
    if (selfScore < oppScore)    return Math.random() < 0.65; // En retard
    if (selfScore === oppScore)  return Math.random() < 0.50; // Egalité
    return Math.random() < 0.25;                              // En avance → économiser
}

/**
 * Consomme une relance localement et envoie la requête au serveur.
 */
function consumeReroll(attackTeam) {
    const reroll = _state.captainReroll[attackTeam];
    reroll.rerollsRemaining--;
    reroll.usedOnCurrentAction = true;

    const contractId = reroll.contractId;
    const gameSaveId = _state._matchConfig?.gameSaveId;
    if (contractId && gameSaveId) {
        fetch(`/game-saves/${gameSaveId}/captain-reroll/${contractId}`, {
            method:  'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'Content-Type': 'application/json',
            },
        }).catch(() => {/* silencieux — ne pas bloquer le moteur */});
    }

    // Mettre à jour l'indicateur UI (badge sur la card capitaine)
    const el = _rootEl?.querySelector(`[data-captain-reroll="${attackTeam}"]`);
    if (el) el.textContent = reroll.rerollsRemaining;
}

/**
 * Effectue la relance : 2d20 advantage pour l'attaque, 1d20 normal pour la défense.
 * Retourne un objet duel identique à runFieldDuel.
 */
function doReroll(ctx) {
    const {
        attackTeam, defenseTeam, attackType, defenseAction,
        attackBaseRaw, defenseBaseRaw, attackStamF, defenseStamF,
        defenderId, defenderSlot, clearanceBonus,
    } = ctx;

    consumeReroll(attackTeam);

    const b = ball();

    const newAroll = rollD20Advantage();  // 2d20 — prend le meilleur
    const newDroll = rollD20WithCrit();   // 1d20 normal

    let newAttackScore  = attackBaseRaw * attackStamF;
    let newDefenseScore = defenseBaseRaw * defenseStamF;

    if (clearanceBonus) newAttackScore += clearanceBonus;

    newAttackScore  += newAroll.bonus;
    newDefenseScore += newDroll.bonus;

    const isGood = isGoodDefenseChoice(attackType, defenseAction);
    if (isGood) newDefenseScore += DUEL_RULES.GOOD_COUNTER_BONUS;
    else        newAttackScore  += DUEL_RULES.GENERIC_ATTACK_BONUS;

    const newCritWinner = resolveCritOutcome(newAroll, newDroll);

    const meta = buildDuelMeta(
        { attackTeam, attackSlot: b.number, attackAction: attackType, defenseTeam, defenseSlot: defenderSlot, defenseAction },
        _roster, TEXTS
    );

    const breakdown = buildFieldDuelBreakdown({
        attackBaseRaw, defenseBaseRaw,
        attackStamF, defenseStamF,
        aRoll: newAroll, dRoll: newDroll, isGood,
        attackScore: newAttackScore, defenseScore: newDefenseScore,
        clearanceBonus: clearanceBonus ?? 0, meta,
    });

    showDuelDice(newAttackScore, newDefenseScore, newAroll, newDroll, breakdown);

    const newDiceTag   = newAttackScore.toFixed(1) + "-" + newDefenseScore.toFixed(1);
    const newRawResult = newCritWinner ?? (newAttackScore > newDefenseScore ? "attack" : newAttackScore < newDefenseScore ? "defense" : "tie");

    const captainInfo  = _roster.getPlayerInfo(attackTeam, b.number);
    const captainName  = ((captainInfo?.firstname ?? '') + ' ' + (captainInfo?.lastname ?? '')).trim();
    const rerollsLeft  = _state.captainReroll[attackTeam].rerollsRemaining;
    const resultLabel  = newRawResult === "attack" ? "✓ Réussi!" : "✗ Echec";

    pushLogEntry(
        `👑 Captain Reroll — ${captainName}`,
        [
            `2d20 (${newAroll.roll1}, ${newAroll.roll2}) → ${newAroll.roll}`,
            `${resultLabel} — Score: ${newDiceTag}`,
            `Relances restantes : ${rerollsLeft}`,
        ],
        newDiceTag,
        _state
    );

    if (newRawResult === "tie") {
        givePossessionOnTie(defenseTeam, defenderId);
        return { isTie: true, duelResult: "tie", defenderId, defenderSlot, diceTag: newDiceTag };
    }
    return { isTie: false, duelResult: newRawResult, defenderId, defenderSlot, diceTag: newDiceTag };
}

/**
 * Affiche le prompt "Relancer ?" dans la barre d'action.
 * Injecte deux boutons et attache les handlers.
 */
function showCaptainRerollPrompt(attackTeam) {
    const b           = ball();
    const captainInfo = _roster.getPlayerInfo(attackTeam, b.number);
    const captainName = ((captainInfo?.firstname ?? '') + ' ' + (captainInfo?.lastname ?? '')).trim();
    const rerollsLeft = _state.captainReroll[attackTeam].rerollsRemaining;

    const html = `
        <div class="flex flex-col items-center gap-3 p-3 w-full">
            <div class="text-center">
                <p class="text-sm font-bold text-amber-600">👑 Captain Reroll!</p>
                <p class="text-xs text-slate-500">${captainName} peut relancer les dés</p>
                <p class="text-xs text-slate-400">${rerollsLeft} relance${rerollsLeft > 1 ? 's' : ''} restante${rerollsLeft > 1 ? 's' : ''} ce match</p>
            </div>
            <div class="flex gap-2 w-full justify-center">
                <button
                    data-action="captain-reroll-confirm"
                    class="flex-1 max-w-[140px] px-3 py-2 rounded-xl text-xs font-bold bg-amber-400 text-white hover:bg-amber-500 transition-all shadow-sm"
                >
                    ⚡ Relancer (2d20)
                </button>
                <button
                    data-action="captain-reroll-skip"
                    class="flex-1 max-w-[140px] px-3 py-2 rounded-xl text-xs font-semibold bg-slate-200 text-slate-600 hover:bg-slate-300 transition-all"
                >
                    Accepter
                </button>
            </div>
        </div>`;

    setActionBar(html, "mode-captain-reroll", b, _roster, () => {
        _rootEl.querySelector('[data-action="captain-reroll-confirm"]')
            ?.addEventListener('click', onCaptainRerollConfirm);
        _rootEl.querySelector('[data-action="captain-reroll-skip"]')
            ?.addEventListener('click', onCaptainRerollSkip);
    }, false);
}

function onCaptainRerollConfirm() {
    const ctx = _state.pendingCaptainReroll;
    if (!ctx) return;
    _state.pendingCaptainReroll = null;

    const duel = doReroll(ctx);
    continuePendingAction(ctx, duel);
}

function onCaptainRerollSkip() {
    const ctx = _state.pendingCaptainReroll;
    if (!ctx) return;
    _state.pendingCaptainReroll = null;

    // Marquer comme utilisé pour bloquer un éventuel second prompt
    if (_state.captainReroll?.[ctx.attackTeam]) {
        _state.captainReroll[ctx.attackTeam].usedOnCurrentAction = true;
    }

    // Continuer avec le résultat "defense" original
    continuePendingAction(ctx, {
        isTie: false,
        duelResult: "defense",
        defenderId: ctx.defenderId,
        defenderSlot: ctx.defenderSlot,
        diceTag: ctx.diceTag,
    });
}

/**
 * Reprend le flux de l'action (pass/dribble/shot) après décision reroll.
 */
function continuePendingAction(ctx, duel) {
    const { attackTeam, defenseTeam, attackType, defenseAction, isSpecial } = ctx;

    if (attackType === "pass" || attackType === "special") {
        continuePass(attackTeam, defenseTeam, attackType, defenseAction, isSpecial, duel);
    } else if (attackType === "dribble") {
        continueDribble(attackTeam, defenseTeam, attackType, defenseAction, isSpecial, duel, ctx.oldZone, ctx.lane);
    } else if (attackType === "shot") {
        continueShot(attackTeam, defenseTeam, defenseAction, isSpecial, duel, ctx.originZone, ctx.originLane);
    }
}

// -----------------------------------------------------------
//   runFieldDuel
// -----------------------------------------------------------
export function runFieldDuel({ attackTeam, defenseTeam, attackType, defenseAction, defenderPick = null }) {
    const b          = ball();
    const attackerId = getPlayerId(attackTeam, b.number);

    const picked = defenderPick ?? pickFieldDefender(defenseTeam, b.zoneIndex, b.laneIndex);
    if (!picked) {
        givePossessionOnTie(defenseTeam);
        return { isTie: true, duelResult: "tie", diceTag: "" };
    }

    const { defenderId, defenderSlot } = picked;

    updateSideCard(attackTeam  === "internal" ? "home" : "away", attackTeam,  b.number);
    updateSideCard(defenseTeam === "internal" ? "home" : "away", defenseTeam, defenderSlot);
    syncRecovererCard(defenseTeam, defenderSlot);
    updateTeamCard(b);

    // Bases attaque
    let attackBaseRaw;
    if (attackType === "special") {
        const moves = _roster.getSpecialMoves(attackTeam, b.number).filter(m => m?.mode === "attack");
        attackBaseRaw = moves.length > 0
            ? specialBaseFor(moves[0], attackTeam, b.number, _roster)
            : _roster.attackBaseFor("special", attackTeam, b.number);
    } else {
        attackBaseRaw = _roster.attackBaseFor(attackType, attackTeam, b.number);
    }

    // Bases defense
    let defenseBaseRaw;
    if (defenseAction === "field-special") {
        const moves = _roster.getSpecialMoves(defenseTeam, defenderSlot).filter(m => m?.mode === "defense");
        defenseBaseRaw = moves.length > 0
            ? specialBaseFor(moves[0], defenseTeam, defenderSlot, _roster)
            : _roster.defenseBaseFor("field-special", defenseTeam, defenderSlot, false);
    } else {
        defenseBaseRaw = _roster.defenseBaseFor(defenseAction, defenseTeam, defenderSlot, false);
    }

    const attackStamF  = staminaFactor(attackerId);
    const defenseStamF = staminaFactor(defenderId);

    let attackScore  = attackBaseRaw  * attackStamF;
    let defenseScore = defenseBaseRaw * defenseStamF;

    const clearanceBonus = _state.pendingClearanceBonus > 0 ? _state.pendingClearanceBonus : 0;
    if (_state.pendingClearanceBonus > 0) { attackScore += _state.pendingClearanceBonus; _state.pendingClearanceBonus = 0; }

    const aRoll      = rollD20WithCrit();
    const dRoll      = rollD20WithCrit();
    const critWinner = resolveCritOutcome(aRoll, dRoll);

    attackScore  += aRoll.bonus;
    defenseScore += dRoll.bonus;

    const isGood = isGoodDefenseChoice(attackType, defenseAction);
    if (isGood) defenseScore += DUEL_RULES.GOOD_COUNTER_BONUS;
    else        attackScore  += DUEL_RULES.GENERIC_ATTACK_BONUS;

    if (aRoll.critSuccess) applyCritBoost(attackerId);
    if (dRoll.critSuccess) applyCritBoost(defenderId);

    const meta = buildDuelMeta(
        { attackTeam, attackSlot: b.number, attackAction: attackType, defenseTeam, defenseSlot: defenderSlot, defenseAction },
        _roster, TEXTS
    );

    const breakdown = buildFieldDuelBreakdown({
        attackBaseRaw, defenseBaseRaw,
        attackStamF, defenseStamF,
        aRoll, dRoll, isGood,
        attackScore, defenseScore,
        clearanceBonus, meta,
    });

    recordDuelEvent({
        attackTeam, defenseTeam,
        attackSlot: b.number, defenseSlot: defenderSlot,
        attackAction: attackType, defenseAction,
        duelResult: critWinner ?? (attackScore > defenseScore ? "attack" : attackScore < defenseScore ? "defense" : "tie"),
        breakdown,
    });

    if (_resolveFoulOutcome) {
        const finalResult = critWinner ?? (attackScore > defenseScore ? "attack" : attackScore < defenseScore ? "defense" : "tie");
        _resolveFoulOutcome({ attackerId, defenderId, duelResult: finalResult, aRoll, dRoll });
    }

    showDuelDice(attackScore, defenseScore, aRoll, dRoll, breakdown);
    applyStaminaCost(attackerId, "attack", attackType);
    applyStaminaCost(defenderId, "defenseField", defenseAction);

    const checkInjury = (pid) => {
        if (!pid) return;
        const stamina = _state.stamina[pid] ?? 0;
        if (stamina <= 0 && Math.random() < 0.30) {
            const team  = pid.startsWith('I') ? 'internal' : 'external';
            const slot  = parseInt(pid.slice(1), 10);
            const dbId  = _roster.getPlayerInfo(team, slot)?.id ?? null;
            const name  = _roster.getPlayerInfo(team, slot)?.lastname ?? pid;
            _state.foulEvents.push({ type: 'injury', player_id: dbId, severity: 'light' });
            const el = _rootEl.querySelector(`[data-player="${pid}"]`);
            if (el) el.classList.add('unavailable');
            pushLogEntry('foulInjuryTitle', [`🤕 ${name} — Blessure (épuisement)`], null, _state);
        }
    };
    checkInjury(attackerId);
    checkInjury(defenderId);

    if (attackType    === "special")       _markSpecialUsed(attackerId);
    if (defenseAction === "field-special") _markSpecialUsed(defenderId);

    const diceTag    = attackScore.toFixed(1) + "-" + defenseScore.toFixed(1);
    if (critWinner) {
        // Si crit defense ET capitaine peut reroller
        if (critWinner === "defense" && canAttackerReroll(attackTeam, b.number)) {
            if (_isAITeam(attackTeam)) {
                if (aiShouldReroll(attackTeam)) {
                    return doReroll({
                        attackTeam, defenseTeam, attackType, defenseAction,
                        attackBaseRaw, defenseBaseRaw, attackStamF, defenseStamF,
                        defenderId, defenderSlot, clearanceBonus,
                    });
                }
                _state.captainReroll[attackTeam].usedOnCurrentAction = true;
            } else {
                _state.pendingCaptainReroll = {
                    attackTeam, defenseTeam, attackType, defenseAction,
                    attackBaseRaw, defenseBaseRaw, attackStamF, defenseStamF,
                    defenderId, defenderSlot, diceTag, clearanceBonus,
                    isSpecial: attackType === "special",
                    oldZone: b.zoneIndex, lane: b.laneIndex,
                    originZone: b.zoneIndex, originLane: b.laneIndex,
                };
                showCaptainRerollPrompt(attackTeam);
                return { isTie: false, duelResult: "pending_reroll", defenderId, defenderSlot, diceTag };
            }
        }
        return { isTie: false, duelResult: critWinner, defenderId, defenderSlot, diceTag };
    }

    const diff = attackScore - defenseScore;
    if (diff === 0) {
        givePossessionOnTie(defenseTeam, defenderId);
        return { isTie: true, duelResult: "tie", defenderId, defenderSlot, diceTag };
    }

    const rawResult = diff > 0 ? "attack" : "defense";

    // ── CAPTAIN REROLL ──────────────────────────────────────────
    if (rawResult === "defense" && canAttackerReroll(attackTeam, b.number)) {
        if (_isAITeam(attackTeam)) {
            if (aiShouldReroll(attackTeam)) {
                return doReroll({
                    attackTeam, defenseTeam, attackType, defenseAction,
                    attackBaseRaw, defenseBaseRaw, attackStamF, defenseStamF,
                    defenderId, defenderSlot, clearanceBonus,
                });
            }
            // IA décline → marquer quand même pour ne pas re-proposer
            _state.captainReroll[attackTeam].usedOnCurrentAction = true;
        } else {
            // Joueur humain : stocker le contexte et afficher le prompt
            _state.pendingCaptainReroll = {
                attackTeam, defenseTeam, attackType, defenseAction,
                attackBaseRaw, defenseBaseRaw, attackStamF, defenseStamF,
                defenderId, defenderSlot, diceTag, clearanceBonus,
                isSpecial: attackType === "special",
                oldZone:   b.zoneIndex,
                lane:      b.laneIndex,
                originZone: b.zoneIndex,
                originLane: b.laneIndex,
            };
            showCaptainRerollPrompt(attackTeam);
            return { isTie: false, duelResult: "pending_reroll", defenderId, defenderSlot, diceTag };
        }
    }
    // ── FIN CAPTAIN REROLL ──────────────────────────────────────

    return { isTie: false, duelResult: rawResult, defenderId, defenderSlot, diceTag };
}

// -----------------------------------------------------------
//   performKeeperClearance
// -----------------------------------------------------------
export function performKeeperClearance(defenseTeam, defenseAction, afterClearance = null) {
    const b       = ball();
    const GK_HOLD = _state._GK_HOLD_MS ?? 300;
    const keeperEl = _rootEl.querySelector(
        defenseTeam === "internal"
            ? '.player.internal.goalkeeper[data-player="I1"]'
            : '.player.external.goalkeeper[data-player="E1"]'
    );

    const fallback = () => {
        const receiver = pickReceiverInCell(defenseTeam, b.zoneIndex, b.laneIndex, 6, null);
        b.frontOfKeeper = false;
        resetLastDribbler();
        _moveBall(defenseTeam, receiver);
        if (afterClearance) afterClearance();
    };

    if (!keeperEl) { fallback(); return; }

    const kx = parseFloat(keeperEl.style.left);
    const ky = parseFloat(keeperEl.style.top);
    const xInternal = defenseTeam === "internal" ? kx : (100 - kx);

    let originZone = 0;
    for (let i = 0; i < ZONE_BOUNDS_INTERNAL.length - 1; i++) {
        if (xInternal >= ZONE_BOUNDS_INTERNAL[i] && xInternal <= ZONE_BOUNDS_INTERNAL[i + 1]) { originZone = i; break; }
    }
    let originLane = 0, best = Infinity;
    laneY.forEach((vy, i) => { const d = Math.abs(vy - ky); if (d < best) { best = d; originLane = i; } });

    _state.pendingClearanceBonus = defenseAction === "hands" ? 5 : defenseAction === "punch" ? 4 : 7;

    const forward = (n) => defenseTeam === "internal"
        ? Math.min(3, originZone + n)
        : Math.max(0, originZone - n);

    const r = Math.random();
    let targetZone;
    if      (defenseAction === "hands") targetZone = forward(r < 0.6 ? 2 : 3);
    else if (defenseAction === "punch") targetZone = forward(1);
    else                                targetZone = forward(r < 0.15 ? 2 : r < 0.7 ? 3 : 4);

    let laneOptions = [originLane];
    if (defenseAction !== "punch") {
        if (originLane > 0)                laneOptions.push(originLane - 1);
        if (originLane < laneY.length - 1) laneOptions.push(originLane + 1);
    }
    if (defenseAction === "gk-special") laneOptions = [0, 1, 2];

    const targetLane = laneOptions[Math.floor(Math.random() * laneOptions.length)];
    const receiver   = pickReceiverInCell(defenseTeam, targetZone, targetLane, 6, null);

    setMessage(TEXTS.ui.keeperRestartMain, TEXTS.ui.keeperRestartSub + " (#" + receiver + ")");
    pushLogEntry("keeperRestartMain", [
        "Action: " + defenseAction,
        "Bonus +" + _state.pendingClearanceBonus,
        "Vers zone " + (targetZone + 1) + ", ligne " + (targetLane + 1),
        "Receveur: #" + receiver,
    ], null, _state);

    _ui.ballEl.style.left  = kx + "%";
    _ui.ballEl.style.top   = ky + "%";
    _ui.ballEl.textContent = "1";

    const receiverEl = getCarrierElement(defenseTeam, receiver);
    if (!receiverEl) { fallback(); return; }

    const rx = parseFloat(receiverEl.style.left);
    const ry = parseFloat(receiverEl.style.top);

    setTimeout(() => {
        animateBallToXY(rx, ry, () => {
            b.frontOfKeeper = false;
            resetLastDribbler();
            _moveBall(defenseTeam, receiver);
            if (afterClearance) afterClearance();
        });
    }, GK_HOLD);
}

// -----------------------------------------------------------
//   resolvePass + continuePass
// -----------------------------------------------------------
export function resolvePass(attackTeam, defenseTeam, defenseAction, defenderPick = null, isSpecial = false) {
    const b          = ball();
    const wasKickoff = _state.isKickoff;
    _state.isKickoff             = false;
    _state.keeperRestartMustPass = false;

    const attackType = isSpecial ? "special" : "pass";

    const duel = runFieldDuel({ attackTeam, defenseTeam, attackType, defenseAction, defenderPick });

    // Reroll en attente → on stoppe ici, continuePendingAction reprendra
    if (duel.duelResult === "pending_reroll") return;

    if (duel.isTie) {
        pushLogEntry(
            isSpecial ? "Duel equilibre (special pass)" : "Duel equilibre (pass)",
            ["Defense: " + defenseAction, getCounterTag(attackType, defenseAction)],
            duel.diceTag, _state
        );
        _state.phase = "attack"; _state.pendingAttack = null;
        _animateAndThen(() => { _advanceTurn(defenseTeam); _showAttackBarForCurrentTeam(); _refreshUI(); });
        return;
    }

    if (isSpecial) _markSpecialUsed(getPlayerId(attackTeam, b.number));

    // Kickoff
    if (wasKickoff) {
        if (duel.duelResult === "attack") {
            const receiver = [5, 6][Math.floor(Math.random() * 2)];
            setMessage(isSpecial ? "Passe speciale reussie !" : "Remise en jeu reussie !", _TEAMS[attackTeam].label + " joue vers le n " + receiver);
            pushLogEntry("kickoffTitle", [isSpecial ? "(Special pass)" : null, "Vers n " + receiver, "Defense: " + defenseAction, getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, _state);
            _animateAndThen(() => { restoreBasePositions(); _moveBall(attackTeam, receiver); _advanceTurn(attackTeam); _showAttackBarForCurrentTeam(); _refreshUI(); });
        } else {
            const receiver = duel.defenderSlot ?? 6;
            const verb     = defenseAction === "intercept" ? "intercepte" : "recupere";
            setMessage("Remise en jeu ratee !", _TEAMS[defenseTeam].label + " " + verb + " avec le n " + receiver);
            pushLogEntry("kickoffTitle", [isSpecial ? "(Special pass)" : null, "Defense: " + defenseAction, getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, _state);
            _animateAndThen(() => { restoreBasePositions(); _moveBall(defenseTeam, receiver); _advanceTurn(defenseTeam); _showAttackBarForCurrentTeam(); _refreshUI(); });
        }
        return;
    }

    continuePass(attackTeam, defenseTeam, attackType, defenseAction, isSpecial, duel);
}

function continuePass(attackTeam, defenseTeam, attackType, defenseAction, isSpecial, duel) {
    const b = ball();

    let targetZone = b.zoneIndex;
    let targetLane = b.laneIndex;
    if (b.zoneIndex < MAX_ZONE_INDEX) {
        targetZone = b.zoneIndex + 1;
        const opts = [b.laneIndex];
        if (b.laneIndex > 0)                opts.push(b.laneIndex - 1);
        if (b.laneIndex < laneY.length - 1) opts.push(b.laneIndex + 1);
        targetLane = opts[Math.floor(Math.random() * opts.length)];
    } else {
        targetLane = [0, 1, 2][Math.floor(Math.random() * 3)];
    }

    if (duel.duelResult === "attack") {
        resetLastDribbler();
        const carrierEl        = getCarrierElement(attackTeam, b.number);
        const carrierXInternal = carrierEl ? getPlayerXInternal(attackTeam, carrierEl) : null;
        const receiver = pickReceiverInCell(attackTeam, targetZone, targetLane, b.number, b.number, { forwardOnly: true, forwardFromXInternal: carrierXInternal, ignoreLaneForInitialPick: true });

        _moveBall(attackTeam, receiver);
        setMessage(isSpecial ? "Passe speciale reussie !" : TEXTS.logs.passSuccessTitle, _TEAMS[attackTeam].label + " trouve le n " + receiver);
        pushLogEntry("passSuccessTitle", [isSpecial ? "(Special pass)" : null, "Vers n " + receiver, "Defense: " + defenseAction, getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, _state);
        _animateAndThen(() => { _advanceTurn(attackTeam); _showAttackBarForCurrentTeam(); _refreshUI(); });
        return;
    }

    resetLastDribbler();
    const receiver = duel.defenderSlot ?? (duel.defenderId ? parseInt(duel.defenderId.slice(1), 10) : 6);
    _moveBall(defenseTeam, receiver);
    syncRecovererCard(defenseTeam, receiver);

    const logTitle = getLogTitleForDuel(attackType, defenseAction, "defense");
    const msgTitle = defenseAction === "intercept" ? TEXTS.logs.passFailTitle : TEXTS.logs.passRecoveredTitle;
    setMessage(msgTitle, _TEAMS[defenseTeam].label + " recupere avec le n " + receiver);
    pushLogEntry(logTitle, [isSpecial ? "(Special pass)" : null, "Defense: " + defenseAction, getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, _state);
    _animateAndThen(() => { _advanceTurn(defenseTeam); _showAttackBarForCurrentTeam(); _refreshUI(); });
}

// -----------------------------------------------------------
//   resolveDribble + continueDribble
// -----------------------------------------------------------
export function resolveDribble(attackTeam, defenseTeam, defenseAction, defenderPick = null, isSpecial = false) {
    const b = ball();
    _state.keeperRestartMustPass = false;

    if (b.frontOfKeeper) {
        setMessage(TEXTS.ui.dribbleForbiddenMain, TEXTS.ui.dribbleForbiddenSub);
        pushLogEntry("dribbleRefusedTitle", ["dribbleRefusedDetail"], null, _state);
        _state.phase = "attack"; _state.pendingAttack = null;
        return;
    }

    const oldZone    = b.zoneIndex;
    const lane       = b.laneIndex;
    const attackType = isSpecial ? "special" : "dribble";

    const duel = runFieldDuel({ attackTeam, defenseTeam, attackType, defenseAction, defenderPick });

    // Reroll en attente → stocker aussi oldZone/lane pour la continuation
    if (duel.duelResult === "pending_reroll") {
        if (_state.pendingCaptainReroll) {
            _state.pendingCaptainReroll.oldZone = oldZone;
            _state.pendingCaptainReroll.lane    = lane;
            _state.pendingCaptainReroll.isSpecial = isSpecial;
        }
        return;
    }

    if (duel.isTie) {
        pushLogEntry(
            isSpecial ? "Duel equilibre (special dribble)" : "Duel equilibre (dribble)",
            ["Defense: " + defenseAction, getCounterTag(attackType, defenseAction)],
            duel.diceTag, _state
        );
        _state.phase = "attack"; _state.pendingAttack = null;
        _animateAndThen(() => { _advanceTurn(defenseTeam); _showAttackBarForCurrentTeam(); _refreshUI(); });
        return;
    }

    if (isSpecial) _markSpecialUsed(getPlayerId(attackTeam, b.number));

    continueDribble(attackTeam, defenseTeam, attackType, defenseAction, isSpecial, duel, oldZone, lane);
}

function continueDribble(attackTeam, defenseTeam, attackType, defenseAction, isSpecial, duel, oldZone, lane) {
    const b         = ball();
    const carrierId = getPlayerId(attackTeam, b.number);
    const carrierEl = _rootEl.querySelector('[data-player="' + carrierId + '"]');

    if (duel.duelResult === "attack") {
        resetLastDribbler();
        _state.lastDribblerId = carrierId;

        if (oldZone < MAX_ZONE_INDEX) {
            const newZone = Math.min(MAX_ZONE_INDEX, oldZone + 1);
            const center  = getCellCenter(attackTeam, newZone, lane);
            if (carrierEl && _ui.ballEl) {
                const currentY        = parseFloat(carrierEl.style.top);
                carrierEl.style.left  = center.x + "%";
                carrierEl.style.top   = currentY + "%";
                _ui.ballEl.style.left = center.x + "%";
                _ui.ballEl.style.top  = currentY + "%";
            }
            if (carrierEl) carrierEl.dataset.zone = String(newZone);
            b.zoneIndex = newZone; b.laneIndex = lane; b.frontOfKeeper = false;

            setMessage(isSpecial ? "Dribble special reussi !" : "Dribble reussi !", _TEAMS[attackTeam].label + " avance en zone " + (newZone + 1));
            pushLogEntry("dribbleSuccessTitle", [isSpecial ? "(Special dribble)" : null, "Zone " + (newZone + 1), "Defense: " + defenseAction, getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, _state);
            _animateAndThen(() => { _advanceTurn(attackTeam); _showAttackBarForCurrentTeam(); _refreshUI(); });
            return;
        }

        if (oldZone === MAX_ZONE_INDEX) {
            const xFront = FIELD_RULES.GK_FRONT_X[attackTeam];
            const y      = laneY[lane];
            if (carrierEl && _ui.ballEl) {
                carrierEl.style.left  = xFront + "%"; carrierEl.style.top  = y + "%";
                _ui.ballEl.style.left = xFront + "%"; _ui.ballEl.style.top = y + "%";
            }
            if (carrierEl) carrierEl.dataset.zone = String(oldZone);
            b.zoneIndex = oldZone; b.laneIndex = lane; b.frontOfKeeper = true;

            setMessage(TEXTS.ui.frontOfKeeperMain, TEXTS.ui.frontOfKeeperSub);
            pushLogEntry("frontOfKeeperTitle", [isSpecial ? "(Special dribble)" : null, "Zone " + (oldZone + 1), "Defense: " + defenseAction, getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, _state);
            _animateAndThen(() => { _advanceTurn(attackTeam); _showAttackBarForCurrentTeam(); _refreshUI(); });
            return;
        }
    }

    resetLastDribbler();
    const slot = duel.defenderSlot ?? (duel.defenderId ? parseInt(duel.defenderId.slice(1), 10) : 6);
    _moveBall(defenseTeam, slot);
    syncRecovererCard(defenseTeam, slot);

    const logTitle = getLogTitleForDuel(attackType, defenseAction, "defense");
    const msgTitle = defenseAction === "tackle" ? "Dribble stoppe" : TEXTS.logs.dribbleRecoveredTitle;
    setMessage(msgTitle, _TEAMS[defenseTeam].label + " recupere avec le n " + slot);
    pushLogEntry(logTitle, [isSpecial ? "(Special dribble)" : null, "Defense: " + defenseAction, getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, _state);
    _animateAndThen(() => { _advanceTurn(defenseTeam); _showAttackBarForCurrentTeam(); _refreshUI(); });
}

// -----------------------------------------------------------
//   resolveShot + continueShot
// -----------------------------------------------------------
export function resolveShot(attackTeam, defenseTeam, defenseAction, isSpecial = false, defenderPick = null) {
    const b          = ball();
    _state.keeperRestartMustPass = false;
    const originZone = b.zoneIndex;
    const originLane = b.laneIndex;
    const attackType = isSpecial ? "special" : "shot";

    if (b.frontOfKeeper) {
        const moves     = _roster.getSpecialMoves(attackTeam, b.number).filter(m => m?.mode === "attack");
        const gkAttBase = isSpecial
            ? (moves.length > 0 ? specialBaseFor(moves[0], attackTeam, b.number, _roster) : _roster.attackBaseFor("special", attackTeam, b.number))
            : _roster.attackBaseFor("shot", attackTeam, b.number);
        resolveShotKeeperDuel({ stage: "keeper", attackTeam, defenseTeam, originZone, originLane, isSpecial, gkAttackBase: gkAttBase, logParts: ["Zone " + (originZone + 1)] }, defenseAction);
        return;
    }

    const duel = runFieldDuel({ attackTeam, defenseTeam, attackType, defenseAction, defenderPick });

    if (duel.duelResult === "pending_reroll") {
        if (_state.pendingCaptainReroll) {
            _state.pendingCaptainReroll.originZone = originZone;
            _state.pendingCaptainReroll.originLane = originLane;
            _state.pendingCaptainReroll.isSpecial  = isSpecial;
            _state.pendingCaptainReroll.attackType = attackType;
        }
        return;
    }

    if (duel.isTie) {
        pushLogEntry("Duel equilibre (shot)", ["Defense: " + defenseAction, getCounterTag(attackType, defenseAction)], duel.diceTag, _state);
        _state.phase = "attack"; _state.pendingAttack = null;
        _animateAndThen(() => { _advanceTurn(defenseTeam); _showAttackBarForCurrentTeam(); _refreshUI(); });
        return;
    }

    continueShot(attackTeam, defenseTeam, defenseAction, isSpecial, duel, originZone, originLane);
}

function continueShot(attackTeam, defenseTeam, defenseAction, isSpecial, duel, originZone, originLane) {
    const b          = ball();
    const attackType = isSpecial ? "special" : "shot";

    if (duel.duelResult === "defense") {
        const number = duel.defenderSlot ?? (duel.defenderId ? parseInt(duel.defenderId.slice(1), 10) : 6);
        _moveBall(defenseTeam, number);
        syncRecovererCard(defenseTeam, number);

        const isBlock = defenseAction === "block";
        setMessage(
            isBlock ? TEXTS.ui.shotBlockedMain : TEXTS.ui.shotRecoveredMain,
            (isBlock ? TEXTS.ui.shotBlockedSub : TEXTS.ui.shotRecoveredSub)
                .replace("{team}", _TEAMS[defenseTeam].label).replace("{number}", number)
        );
        pushLogEntry(getLogTitleForDuel(attackType, defenseAction, "defense"), ["Defense: " + defenseAction, getCounterTag(attackType, defenseAction)], duel.diceTag, _state);
        _state.phase = "attack"; _state.pendingAttack = null;
        _animateAndThen(() => { _advanceTurn(defenseTeam); _showAttackBarForCurrentTeam(); _refreshUI(); });
        return;
    }

    pushLogEntry(TEXTS.ui.shotOnTargetMain, ["Zone " + (originZone + 1), "Defense: " + defenseAction, getCounterTag(attackType, defenseAction)], duel.diceTag, _state);
    setMessage(TEXTS.ui.shotOnTargetMain, TEXTS.ui.shotOnTargetSub.replace("{team}", _TEAMS[defenseTeam].label));

    const zonesToGoal = Math.max(0, MAX_ZONE_INDEX - originZone);
    let gkAttackBase;
    if (isSpecial) {
        const moves = _roster.getSpecialMoves(attackTeam, b.number).filter(m => m?.mode === "attack");
        gkAttackBase = moves.length > 0
            ? specialBaseFor(moves[0], attackTeam, b.number, _roster)
            : _roster.attackBaseFor("special", attackTeam, b.number);
    } else {
        gkAttackBase = _roster.attackBaseFor("shot", attackTeam, b.number) - (zonesToGoal * DUEL_RULES.SHOT_DISTANCE_PENALTY_PER_LINE);
    }

    const center = getCellCenter(attackTeam, MAX_ZONE_INDEX, originLane);
    b.zoneIndex = MAX_ZONE_INDEX; b.laneIndex = originLane;
    if (_ui.ballEl) { _ui.ballEl.style.left = center.x + "%"; _ui.ballEl.style.top = center.y + "%"; }

    _state.pendingShotContext = { stage: "keeper", attackTeam, defenseTeam, originZone, originLane, isSpecial, gkAttackBase, logParts: ["Zone " + (originZone + 1)] };

    animateShotToKeeper(defenseTeam, () => {
        const defPrefix = defenseTeam === "internal" ? "home" : "away";
        updateSideCard(defPrefix, defenseTeam, 1);
        _state.pendingDefenseContext = { defenseTeam, defenderId: getKeeperId(defenseTeam), defenderSlot: 1 };

        setActionBar(buildDefenseGKHTML(defenseTeam, _roster), "mode-defense-" + defenseTeam, b, _roster, _bindActionButtons, false);
        setMessage(TEXTS.ui.shotOnTargetMain, TEXTS.ui.shotGKChoiceSub.replace("{team}", _TEAMS[defenseTeam].label));

        _state.phase = "defense";
        _state.pendingAttack = attackType;
        if (_isAITeam(defenseTeam)) _scheduleAIDefense(attackType, defenseTeam);
    });
}

// -----------------------------------------------------------
//   resolveShotKeeperDuel
// -----------------------------------------------------------
export function resolveShotKeeperDuel(ctx, defenseAction) {
    const { attackTeam, defenseTeam, originZone, isSpecial, gkAttackBase, logParts } = ctx;
    const b          = ball();
    const attackerId = getPlayerId(attackTeam, b.number);
    const keeperId   = getKeeperId(defenseTeam);

    let attackScore = gkAttackBase * staminaFactor(attackerId);
    const gkFactor  = keeperId ? staminaFactor(keeperId) : 1.0;

    let defenseBase;
    if (defenseAction === "gk-special") {
        const moves = _roster.getSpecialMoves(defenseTeam, 1).filter(m => m?.mode === "defense");
        defenseBase = moves.length > 0
            ? specialBaseFor(moves[0], defenseTeam, 1, _roster)
            : _roster.defenseBaseFor("gk-special", defenseTeam, 1, true);
    } else {
        defenseBase = _roster.defenseBaseFor(defenseAction, defenseTeam, 1, true);
    }

    let defenseScore = defenseBase * gkFactor;

    const aRoll      = rollD20WithCrit();
    const dRoll      = rollD20WithCrit();
    const critWinner = resolveCritOutcome(aRoll, dRoll);

    attackScore  += aRoll.bonus;
    defenseScore += dRoll.bonus;

    ({ attackScore, defenseScore } = applyDuelBonuses({
        attackAction: isSpecial ? "special" : "shot",
        defenseAction, attackScore, defenseScore,
        context: { isKeeperDuel: true },
    }));

    if (aRoll.critSuccess) applyCritBoost(attackerId);
    if (dRoll.critSuccess && keeperId) applyCritBoost(keeperId);

    const diceTag = attackScore.toFixed(1) + "-" + defenseScore.toFixed(1);

    const meta = buildDuelMeta(
        { attackTeam, attackSlot: b.number, attackAction: isSpecial ? "special" : "shot", defenseTeam, defenseSlot: 1, defenseAction },
        _roster, TEXTS
    );

    const breakdown = {
        meta,
        rolls: {
            aTag: aRoll.critSuccess ? "20!" : (aRoll.critFail ? "1!" : String(aRoll.roll)),
            dTag: dRoll.critSuccess ? "20!" : (dRoll.critFail ? "1!" : String(dRoll.roll)),
            aBonus: aRoll.bonus, dBonus: dRoll.bonus,
        },
        attack:  { base: gkAttackBase, staminaFactor: staminaFactor(attackerId), additions: [], total: attackScore },
        defense: { base: _roster.defenseBaseFor(defenseAction, defenseTeam, 1, true), staminaFactor: gkFactor, additions: [], total: defenseScore },
        result:  {
            bonusRuleLabel: "",
            critWinner,
            diff:   attackScore - defenseScore,
            winner: critWinner ?? (attackScore > defenseScore ? "attack" : attackScore < defenseScore ? "defense" : "tie"),
        },
    };

    recordDuelEvent({
        attackTeam, defenseTeam,
        attackSlot: b.number, defenseSlot: 1,
        attackAction: isSpecial ? "special" : "shot", defenseAction,
        duelResult: breakdown.result.winner, breakdown,
        context: { isKeeperDuel: true },
    });

    showDuelDice(attackScore, defenseScore, aRoll, dRoll, breakdown);
    applyStaminaCost(attackerId, "attack", isSpecial ? "special" : "shot");

    const checkInjury = (pid) => {
        if (!pid) return;
        const stamina = _state.stamina[pid] ?? 0;
        if (stamina <= 0 && Math.random() < 0.40) {
            const team  = pid.startsWith('I') ? 'internal' : 'external';
            const slot  = parseInt(pid.slice(1), 10);
            const dbId  = _roster.getPlayerInfo(team, slot)?.id ?? null;
            const name  = _roster.getPlayerInfo(team, slot)?.lastname ?? pid;
            _state.foulEvents.push({ type: 'injury', player_id: dbId, severity: 'light' });
            const el = _rootEl.querySelector(`[data-player="${pid}"]`);
            if (el) el.classList.add('unavailable');
            pushLogEntry('foulInjuryTitle', [`🤕 ${name} — Blessure (épuisement)`], null, _state);
        }
    };
    checkInjury(attackerId);
    checkInjury(keeperId);
    if (keeperId) applyStaminaCost(keeperId, "defenseGK", defenseAction);
    if (isSpecial) _markSpecialUsed(attackerId);
    if (defenseAction === "gk-special" && keeperId) _markSpecialUsed(keeperId);

    b.frontOfKeeper = false;
    resetLastDribbler();

    if (!critWinner && attackScore === defenseScore) {
        pushLogEntry("shotGKEqualTitle", ["Zone " + (originZone + 1)], diceTag, _state);
        performKeeperClearance(defenseTeam, "hands", () => { _advanceTurn(defenseTeam); _showAttackBarForCurrentTeam(); _refreshUI(); });
        return;
    }

    const duelResult = critWinner ?? (attackScore > defenseScore ? "attack" : "defense");

    if (duelResult === "attack") {
        _state.score[attackTeam]++;
        const scorerInfo = _roster.getPlayerInfo(attackTeam, ball().number);
        if (scorerInfo?.id) {
            _state.goalEvents = _state.goalEvents ?? [];
            _state.goalEvents.push({ player_id: scorerInfo.id, turn: _state.turns });
        }
        setMessage(
            (isSpecial ? TEXTS.ui.goalSpecialMain : TEXTS.ui.goalMain).replace("{team}", _TEAMS[attackTeam].label),
            TEXTS.ui.goalSub.replace("{scoreInternal}", _state.score.internal).replace("{scoreExternal}", _state.score.external)
        );
        pushLogEntry(isSpecial ? "shotGoalSpecialTitle" : "shotGoalTitle", ["Zone " + (originZone + 1)].concat(logParts), diceTag, _state);

        animateGoalThenReset(attackTeam, () => {
            _state.isKickoff = true;
            _state.keeperRestartMustPass = false;
            _state._applyKickoffFn?.();
            _moveBall(defenseTeam, 8);
            _advanceTurn(defenseTeam);
            _showAttackBarForCurrentTeam();
            _refreshUI();
        });
        return;
    }

    pushLogEntry("shotSavedTitle", ["Zone " + (originZone + 1)].concat(logParts), diceTag, _state);
    performKeeperClearance(defenseTeam, defenseAction, () => { _advanceTurn(defenseTeam); _showAttackBarForCurrentTeam(); _refreshUI(); });
}
