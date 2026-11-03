// resources/js/Pages/Match/engine/resolvers/duelRules.js
// Règles pierre-feuille-ciseaux des duels (bon contre, bonus).
import { DUEL_RULES, TEXTS } from '../constants.js';

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
        (a === "one_two" && d === "intercept") ||
        (a === "shot"    && d === "block")     ||
        (a === "special" && (d === "field-special" || d === "block"))
    );
}

export function getCounterTag(attackAction, defenseAction) {
    return isGoodDefenseChoice(attackAction, defenseAction) ? "OK Bon contre" : "X Mauvais choix";
}

export function getLogTitleForDuel(attackAction, defenseAction, duelWinner) {
    if (duelWinner === "tie")    return "Duel equilibre";
    if (duelWinner === "attack") {
        if (attackAction === "pass")    return TEXTS.logs.passSuccessTitle;
        if (attackAction === "dribble") return TEXTS.logs.dribbleSuccessTitle;
        if (attackAction === "one_two") return TEXTS.logs.oneTwoSuccessTitle;
        return TEXTS.logs.shotGoalTitle;
    }
    if (attackAction === "pass")    return defenseAction === "intercept" ? TEXTS.logs.passFailTitle    : TEXTS.logs.passRecoveredTitle;
    if (attackAction === "dribble") return defenseAction === "tackle"    ? TEXTS.logs.dribbleFailTitle : TEXTS.logs.dribbleRecoveredTitle;
    if (attackAction === "one_two") return defenseAction === "intercept" ? TEXTS.logs.oneTwoFailTitle  : TEXTS.logs.oneTwoRecoveredTitle;
    if (attackAction === "shot")    return defenseAction === "block"     ? TEXTS.logs.shotBlockedTitle : TEXTS.logs.shotRecoveredTitle;
    if (attackAction === "special") return defenseAction === "block"     ? TEXTS.logs.shotBlockedTitle : TEXTS.logs.specialRecoveredTitle;
    return "";
}

export function applyDuelBonuses({ attackAction, defenseAction, attackScore, defenseScore, context = {} }) {
    const good = context.isKeeperDuel
        ? ["hands", "punch", "gk-special"].includes(defenseAction)
        : isGoodDefenseChoice(attackAction, defenseAction);
    return good
        ? { attackScore, defenseScore: defenseScore + DUEL_RULES.GOOD_COUNTER_BONUS }
        : { attackScore: attackScore + DUEL_RULES.GENERIC_ATTACK_BONUS, defenseScore };
}
