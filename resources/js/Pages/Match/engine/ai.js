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
// Cooldown PAR TECHNIQUE : specialCooldown[playerId] = { [moveKey]: turnDispoÀPartirDe }
function moveIsReady(playerId, specialCooldown, turns, move) {
    if (!move) return false;
    const readyAt = specialCooldown[playerId]?.[move.key];
    return !readyAt || turns >= readyAt;
}

// Vrai si AU MOINS UNE des techniques fournies est disponible (hors cooldown)
function canUseSpecial(playerId, specialCooldown, turns, moves = []) {
    return moves.some(move => moveIsReady(playerId, specialCooldown, turns, move));
}

// -----------------------------------------------------------
//   Choix attaque IA
// -----------------------------------------------------------
export function computeAIAttackChoice(ball, specialCooldown) {
    const team     = _state.currentTeam;
    const slot     = ball.number;
    const playerId = getPlayerId(team, slot);
    const turns    = _state.turns;

    const specials = _roster.getSpecialMoves(team, slot).filter(m => m.mode === "attack");

    // Helper : retourne le bon type d'action selon le base_action du move
    const specialAction = (move) => {
        if (move?.base_action === "pass")    return "special-pass";
        if (move?.base_action === "dribble") return "special-dribble";
        return "special"; // base_action === "shot" ou autre
    };

    const passSp = specials.find(m => m.base_action === "pass");
    const dribSp = specials.find(m => m.base_action === "dribble");
    const shotSp = specials.find(m => m.base_action === "shot");

    // Disponibilité PAR TECHNIQUE (cooldown indépendant pour chaque move)
    const canUse = (move) => moveIsReady(playerId, specialCooldown, turns, move);

    if (_state.isKickoff || _state.keeperRestartMustPass) {
        return canUse(passSp) ? specialAction(passSp) : "pass";
    }

    if (ball.frontOfKeeper) {
        return canUse(shotSp) ? specialAction(shotSp) : "shot";
    }

    const z = ball.zoneIndex;

    if (z <= 1) {
        if (canUse(passSp) && Math.random() < 0.30) return specialAction(passSp);
        if (canUse(dribSp) && Math.random() < 0.25) return specialAction(dribSp);
        return Math.random() < 0.65 ? "pass" : "dribble";
    }

    if (z === 2) {
        if (canUse(dribSp) && Math.random() < 0.40) return specialAction(dribSp);
        if (canUse(passSp) && Math.random() < 0.20) return specialAction(passSp);
        return Math.random() < 0.55 ? "dribble" : "pass";
    }

    if (z === 3) {
        if (canUse(shotSp) && Math.random() < 0.25) return specialAction(shotSp);
        if (canUse(dribSp) && Math.random() < 0.20) return specialAction(dribSp);
        return Math.random() < 0.60 ? "dribble" : "shot";
    }

    // zone 4 → tir
    if (canUse(shotSp) && Math.random() < 0.50) return specialAction(shotSp);
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
    const canSpecial = canUseSpecial(defenderId, specialCooldown, turns, specials);

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
        case "special":
        case "special-pass":
        case "special-dribble": return r < 0.65 ? "field-special" : "block";
        default:        return "intercept";
    }
}
