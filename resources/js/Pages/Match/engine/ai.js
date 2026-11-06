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

    // Une-deux : partenaire de duo sur le terrain ET assez d'endurance pour
    // encaisser le coût élevé (9) sans finir le match à sec.
    const canOneTwo = !!_roster.getDuoPartnerOnField(team, slot)
        && getStaminaRatio(playerId) >= 0.35;

    // Le coup d'envoi n'accepte qu'une passe simple (handleAttackClick refuse
    // le reste) : proposer une passe spéciale bloquerait l'IA.
    if (_state.isKickoff) return "pass";

    if (_state.keeperRestartMustPass) {
        return canUse(passSp) ? specialAction(passSp) : "pass";
    }

    if (ball.frontOfKeeper) {
        return canUse(shotSp) ? specialAction(shotSp) : "shot";
    }

    const z = ball.zoneIndex;

    if (z <= 1) {
        if (canUse(passSp) && Math.random() < 0.30) return specialAction(passSp);
        if (canUse(dribSp) && Math.random() < 0.25) return specialAction(dribSp);
        if (canOneTwo    && Math.random() < 0.20) return "one_two";
        return Math.random() < 0.65 ? "pass" : "dribble";
    }

    if (z === 2) {
        if (canUse(dribSp) && Math.random() < 0.40) return specialAction(dribSp);
        if (canUse(passSp) && Math.random() < 0.20) return specialAction(passSp);
        if (canOneTwo    && Math.random() < 0.30) return "one_two";
        return Math.random() < 0.55 ? "dribble" : "pass";
    }

    if (z === 3) {
        if (canUse(shotSp) && Math.random() < 0.25) return specialAction(shotSp);
        if (canUse(dribSp) && Math.random() < 0.20) return specialAction(dribSp);
        if (canOneTwo    && Math.random() < 0.25) return "one_two";
        return Math.random() < 0.60 ? "dribble" : "shot";
    }

    // zone 4 → tir
    if (canUse(shotSp) && Math.random() < 0.50) return specialAction(shotSp);
    return "shot";
}

// -----------------------------------------------------------
//   Choix défense IA
// -----------------------------------------------------------
export function computeAIDefenseChoice(defendingTeam, opts, specialCooldown, turns) {
    const { isKeeperDuel = false, ball = null } = opts;
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

    // L'IA ne voit pas l'action choisie par l'attaquant : elle la devine,
    // comme le joueur humain, puis joue le contre de sa supposition.
    const counter = COUNTER_OF[guessAttack(ball)];

    if (canSpecial) {
        if (specials.some(m => m.base_action === counter) && Math.random() < 0.40) return "field-special";
        if (Math.random() < 0.25) return "field-special";
    }

    return counter;
}

const COUNTER_OF = { pass: "intercept", dribble: "tackle", shot: "block" };

/**
 * Supposition de l'IA sur l'action adverse, à partir de ce qu'elle voit :
 * la zone du ballon (pas de tir avant le milieu offensif, tir probable dans la
 * dernière zone) et le profil du porteur (il joue plus souvent son point fort).
 * Tirage pondéré, pas la plus probable à coup sûr : elle peut se tromper.
 */
export function guessAttack(ball) {
    const attackTeam = ball?.team ?? (_state.currentTeam);
    const stats = _roster.getPlayerInfo(attackTeam, ball?.number)?.stats ?? {};
    const zone  = Math.min(4, (ball?.zoneIndex ?? 0) + 1); // 1=DEF … 4=ATT
    const stat  = (k) => Math.max(1, Number(stats[k] ?? 0));
    const avg   = (stat("pass") + stat("dribble") + stat("shot")) / 3;

    const weights = {
        pass:    1.0 * stat("pass") / avg,
        dribble: 0.8 * stat("dribble") / avg,
        shot:    zone >= 3 ? (zone === 4 ? 1.4 : 0.6) * stat("shot") / avg : 0,
    };

    const total = weights.pass + weights.dribble + weights.shot;
    let r = Math.random() * total;
    for (const [action, w] of Object.entries(weights)) {
        if ((r -= w) < 0) return action;
    }
    return "pass";
}
