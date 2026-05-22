// resources/js/Pages/Match/engine/ai.js
import { MAX_ZONE_INDEX } from './constants.js';
import { getStaminaRatio } from './stamina.js';
import { getPlayerId, getKeeperId } from './field.js';

// -----------------------------------------------------------
//   Dépendances injectées
// -----------------------------------------------------------
let _state  = null;
let _roster = null;

export function initAIModule(state, roster) {
    _state  = state;
    _roster = roster;
}

// -----------------------------------------------------------
//   Helpers spéciaux
// -----------------------------------------------------------
function canUseSpecial(playerId, specialCooldown, turns) {
    return turns >= (specialCooldown[playerId] ?? 0);
}

// -----------------------------------------------------------
//   Choix attaque IA
// -----------------------------------------------------------
export function computeAIAttackChoice(ball, specialCooldown) {
    const team     = _state.currentTeam;
    const slot     = ball.number;
    const playerId = getPlayerId(team, slot);
    const turns    = _state.turns;

    const specials    = _roster.getSpecialMoves(team, slot).filter(m => m.mode === "attack");
    const hasShotSp   = specials.some(m => m.base_action === "shot");
    const hasPassSp   = specials.some(m => m.base_action === "pass");
    const hasDribSp   = specials.some(m => m.base_action === "dribble");
    const allowSpecial= canUseSpecial(playerId, specialCooldown, turns) && specials.length > 0;

    if (_state.isKickoff || _state.keeperRestartMustPass) {
        return (hasPassSp && allowSpecial) ? "special" : "pass";
    }

    if (ball.frontOfKeeper) {
        return (hasShotSp && allowSpecial) ? "special" : "shot";
    }

    const z  = ball.zoneIndex;

    if (z <= 1) {
        if (hasPassSp && allowSpecial && Math.random() < 0.30) return "special";
        if (hasDribSp && allowSpecial && Math.random() < 0.25) return "special";
        return Math.random() < 0.65 ? "pass" : "dribble";
    }

    if (z === 2) {
        if (hasDribSp && allowSpecial && Math.random() < 0.40) return "special";
        if (hasPassSp && allowSpecial && Math.random() < 0.20) return "special";
        return Math.random() < 0.55 ? "dribble" : "pass";
    }

    if (z === 3) {
        if (hasShotSp && allowSpecial && Math.random() < 0.25) return "special";
        if (hasDribSp && allowSpecial && Math.random() < 0.20) return "special";
        return Math.random() < 0.60 ? "dribble" : "shot";
    }

    // zone 4 → tir
    if (hasShotSp && allowSpecial && Math.random() < 0.50) return "special";
    return "shot";
}

// -----------------------------------------------------------
//   Choix défense IA
// -----------------------------------------------------------
export function computeAIDefenseChoice(attackAction, defendingTeam, opts, specialCooldown, turns) {
    const { isKeeperDuel = false } = opts;
    const slot       = isKeeperDuel ? 1 : (_state.pendingDefenseContext?.defenderSlot || 6);
    const defenderId = isKeeperDuel
        ? getKeeperId(defendingTeam)
        : getPlayerId(defendingTeam, slot);

    const specials   = _roster.getSpecialMoves(defendingTeam, slot).filter(m => m.mode === "defense");
    const canSpecial = specials.length > 0 && canUseSpecial(defenderId, specialCooldown, turns);

    if (isKeeperDuel) {
        if (canSpecial && Math.random() < 0.35) return "gk-special";
        return Math.random() < 0.60 ? "hands" : "punch";
    }

    if (canSpecial) {
        const rpsMap = { shot: "block", pass: "intercept", dribble: "tackle" };
        const goodBase = rpsMap[attackAction];
        if (goodBase && specials.some(m => m.base_action === goodBase) && Math.random() < 0.40) return "field-special";
        if (Math.random() < 0.25) return "field-special";
    }

    return _computeDefenseFallback(attackAction);
}

function _computeDefenseFallback(attackAction) {
    const r = Math.random();
    switch (attackAction) {
        case "pass":    return r < 0.7 ? "intercept" : (r < 0.9 ? "tackle" : "block");
        case "dribble": return r < 0.7 ? "tackle"    : (r < 0.9 ? "intercept" : "block");
        case "shot":    return r < 0.75 ? "block" : "intercept";
        case "special": return r < 0.65 ? "field-special" : "block";
        default:        return "intercept";
    }
}
