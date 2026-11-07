// resources/js/Pages/Match/engine/resolvers/context.js
// Contexte partagé et helpers communs (positions, animations, enregistrement des duels).
import { CRIT_STAMINA_BOOST, FIELD_RULES, TEXTS } from '../constants.js';
import { runAfterDiceAnimation } from '../dice.js';
import { applyBallMotion, isGoalkeeperId, pickWeightedPlayerInZone, setBallToKeeperVisual } from '../field.js';
import { setMessage } from '../ui.js';

// Dépendances injectées par initResolversModule (resolvers.js), partagées par
// tous les modules de résolution.
export const shared = {
    state: null, roster: null, ui: null, TEAMS: null, rootEl: null, basePos: null,
    advanceTurn: null,            // rafraîchit déjà barre d'actions et UI : ne pas les rappeler après
    showAttackBarForCurrentTeam: null, refreshUI: null, animateAndThen: null, afterFieldMotion: null,
    scheduleAIDefense: null, isAITeam: null, canUseSpecial: null, markSpecialUsed: null,
    bindActionButtons: null, resolveFoulOutcome: null, homeTeam: 'internal',
};

// -----------------------------------------------------------
//   Helpers locaux
// -----------------------------------------------------------
export const ball = () => shared.state.ball;

// Verrouille les commandes pendant que les révélations précèdent le jeu sur le terrain.
export function afterReveals(callback) {
    const state = shared.state;
    state.isAnimating = true;
    runAfterDiceAnimation(() => {
        if (state.disposed) return;
        state.isAnimating = false;
        callback();
    });
}

export function afterFieldDuel(duel, callback) {
    const resume = result => afterReveals(() => callback(result));
    if (duel.duelResult === "pending_reroll") {
        shared.state.pendingCaptainReroll.resume = resume;
        return;
    }
    resume(duel);
}

export function resetLastDribbler() {
    if (!shared.state.lastDribblerId) return;
    const pos = shared.basePos[shared.state.lastDribblerId];
    const el  = shared.rootEl.querySelector('[data-player="' + shared.state.lastDribblerId + '"]');
    if (pos && el) { el.style.left = pos.x + "%"; el.style.top = pos.y + "%"; }
    shared.state.lastDribblerId = null;
}

export function restoreBasePositions() {
    shared.rootEl.querySelectorAll(".player").forEach((el) => {
        const base = shared.basePos[el.dataset.player];
        if (!base) return;
        el.style.left = base.x + "%";
        el.style.top  = base.y + "%";
    });
}

export function applyCritBoost(playerId) {
    if (!playerId) return;
    const current = shared.state.stamina[playerId] ?? 0;
    const max     = shared.state.staminaMax[playerId] ?? 100;
    shared.state.stamina[playerId] = Math.min(max, current + CRIT_STAMINA_BOOST);
}

// -----------------------------------------------------------
//   Animations
// -----------------------------------------------------------
export function animateGoalThenReset(attackTeam, cb) {
    if (!shared.ui.ballEl) { if (cb) cb(); return; }
    applyBallMotion(FIELD_RULES.GOAL_X[attackTeam], FIELD_RULES.GOAL_Y);
    shared.animateAndThen(() => { if (cb) cb(); });
}

export function animateShotToKeeper(defenseTeam, cb) {
    setBallToKeeperVisual(defenseTeam);
    const st = shared.state;
    shared.animateAndThen(() => {
        st.isAnimating = true;
        setTimeout(() => {
            if (st.disposed) return;
            st.isAnimating = false;
            cb?.();
        }, st._GK_HOLD_MS ?? 300);
    });
}

export function animateBallToXY(x, y, cb) {
    if (!shared.ui.ballEl) { if (cb) cb(); return; }
    applyBallMotion(x, y);
    shared.animateAndThen(() => { if (cb) cb(); });
}

// -----------------------------------------------------------
//   Egalite -> possession defense (jamais GK)
// -----------------------------------------------------------
export function givePossessionOnTie(defenseTeam, defenderIdMaybe = null) {
    const b = ball();
    if (defenderIdMaybe && !isGoalkeeperId(defenderIdMaybe)) {
        const slot = parseInt(defenderIdMaybe.slice(1), 10);
        _moveBall(defenseTeam, slot);
        setMessage(TEXTS.ui.duelTieMain, TEXTS.ui.duelTieSub.replace("{team}", shared.TEAMS[defenseTeam].label));
        return { team: defenseTeam, number: slot };
    }
    const fallbackId = pickWeightedPlayerInZone(defenseTeam, b.zoneIndex, b.laneIndex);
    if (fallbackId && !isGoalkeeperId(fallbackId)) {
        const slot = parseInt(fallbackId.slice(1), 10);
        _moveBall(defenseTeam, slot);
        setMessage(TEXTS.ui.duelTieMain, TEXTS.ui.duelTieSub.replace("{team}", shared.TEAMS[defenseTeam].label));
        return { team: defenseTeam, number: slot };
    }
    return { team: b.team, number: b.number };
}

export function _moveBall(team, number) {
    shared.state._moveBallFn?.(team, number);
}

// -----------------------------------------------------------
//   Enregistrement evenements match
// -----------------------------------------------------------
export function recordDuelEvent({ attackTeam, defenseTeam, attackSlot, defenseSlot, attackAction, defenseAction, duelResult, breakdown }) {
    const attackInfo  = shared.roster.getPlayerInfo(attackTeam, attackSlot);
    const defenseInfo = defenseSlot ? shared.roster.getPlayerInfo(defenseTeam, defenseSlot) : null;

    shared.state.actionEvents.push({
        gameSaveId: shared.state._matchConfig?.gameSaveId ?? null,
        matchId:    shared.state._matchConfig?.matchId    ?? null,
        turn:       shared.state.turns,
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

/**
 * Après une relance (capitaine, gardien, dépassement de soi), le duel déjà
 * enregistré par recordDuelEvent porte l'ancien résultat : on le corrige pour
 * que match_stats reflète l'issue réelle.
 */
export function amendLastDuelEvent(duelResult, breakdown) {
    const last = shared.state.actionEvents[shared.state.actionEvents.length - 1];
    if (!last || last.turn !== shared.state.turns || !last.attack) return;
    last.result       = duelResult;
    last.resultWinner = breakdown?.result?.winner ?? duelResult;
    last.diff         = breakdown?.result?.diff   ?? last.diff;
}
