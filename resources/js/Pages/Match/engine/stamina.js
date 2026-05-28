// resources/js/Pages/Match/engine/stamina.js
import {
    ENDURANCE_DEFAULT,
    STAMINA_FACTORS,
    STAMINA_COST_GLOBAL_SCALE,
    STATS,
} from './constants.js';

// -----------------------------------------------------------
//   Accesseurs état stamina (state injecté à l'init)
// -----------------------------------------------------------
let _state  = null;
let _roster = null;
let _rootEl = null;

export function initStaminaModule(state, roster, rootEl) {
    _state  = state;
    _roster = roster;
    _rootEl = rootEl;
}

// -----------------------------------------------------------
//   Helpers de base
// -----------------------------------------------------------
export function getStamina(playerId) {
    if (!playerId) return 0;
    if (!(_state.stamina[playerId] >= 0)) _state.stamina[playerId] = ENDURANCE_DEFAULT;
    return _state.stamina[playerId];
}

export function getStaminaMax(playerId) {
    if (!playerId) return ENDURANCE_DEFAULT;
    if (!(_state.staminaMax[playerId] >= 0)) _state.staminaMax[playerId] = ENDURANCE_DEFAULT;
    return _state.staminaMax[playerId];
}

export function getStaminaRatio(playerId) {
    const v = getStamina(playerId);
    const m = getStaminaMax(playerId);
    return m > 0 ? (v / m) : 0;
}

export function getStaminaTier(playerId) {
    const r = getStaminaRatio(playerId);
    if (r >= 0.75) return "high";
    if (r >= 0.50) return "mid";
    if (r >= 0.25) return "low";
    return "crit";
}

export function staminaFactor(playerId) {
    const r = getStaminaRatio(playerId);
    if (r >= 0.75) return STAMINA_FACTORS.HIGH;
    if (r >= 0.50) return STAMINA_FACTORS.MID;
    if (r >= 0.25) return STAMINA_FACTORS.LOW;
    if (r > 0)     return STAMINA_FACTORS.CRIT;
    return STAMINA_FACTORS.EXHAUSTED;
}

function staminaCostMultiplierFor(category) {
    if (category === "defenseGK") return 0.60;
    return 1.0;
}

function getSpeedStaminaReduction(playerId) {
    if (!playerId || !_roster) return 1.0;
    const team = playerId.startsWith("I") ? "internal" : "external";
    const slot = parseInt(playerId.slice(1), 10);
    const speedStat = _roster.getStat(team, slot, "speed");
    return 1.0 - (speedStat / 100) * 0.1;
}

// -----------------------------------------------------------
//   Application coût
// -----------------------------------------------------------
export function applyStaminaCost(playerId, category, actionKey) {
    if (!playerId) return;
    const cfg      = STATS?.[category]?.[actionKey];
    const baseCost = cfg ? cfg.cost : 0;
    const speedReduction = getSpeedStaminaReduction(playerId);
    const cost     = Math.max(0, Math.round(
        baseCost * staminaCostMultiplierFor(category) * STAMINA_COST_GLOBAL_SCALE * speedReduction
    ));
    _state.stamina[playerId] = Math.max(0, getStamina(playerId) - cost);
    updateStaminaUI(playerId);
}

// -----------------------------------------------------------
//   UI
// -----------------------------------------------------------
export function updateStaminaUI(playerId, ball, updateTeamCardFn) {
    if (!playerId || !_rootEl) return;

    const ratio = getStaminaRatio(playerId);
    const el    = _rootEl.querySelector(`.player[data-player="${playerId}"]`);

    if (el) {
        el.classList.add("show-endurance");
        const bar = el.querySelector(".endurance-shell .endurance-bar");
        if (bar) bar.style.width = `${Math.max(10, ratio * 100)}%`;

        el.classList.remove("e-high", "e-mid", "e-low", "e-crit");
        el.classList.add(`e-${getStaminaTier(playerId)}`);
    }

    // Notifie la card du porteur si c'est lui
    if (ball && updateTeamCardFn) {
        const { team, number } = ball;
        const carrier = (team === "internal" ? "I" : "E") + String(number);
        if (carrier === playerId) updateTeamCardFn();
    }
}

export function initStamina(ball, updateTeamCardFn) {
    _rootEl.querySelectorAll(".player").forEach((el) => {
        const id   = el.dataset.player;
        if (!id) return;

        const team = id.startsWith("I") ? "internal" : "external";
        const slot = parseInt(id.slice(1), 10);
        const max  = _roster.clampStat(_roster.getStat(team, slot, "stamina")) || ENDURANCE_DEFAULT;

        _state.staminaMax[id] = max;
        _state.stamina[id]    = max;

        if (!el.querySelector(".endurance-shell")) {
            const shell = document.createElement("div");
            shell.className = "endurance-shell";
            const bar = document.createElement("div");
            bar.className = "endurance-bar";
            shell.appendChild(bar);
            el.appendChild(shell);
        }

        updateStaminaUI(id, ball, updateTeamCardFn);
    });
}
