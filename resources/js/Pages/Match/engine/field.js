// resources/js/Pages/Match/engine/field.js
import { ZONE_BOUNDS_INTERNAL, laneY, MAX_ZONE_INDEX,} from './constants.js';
import { getStamina, getStaminaMax } from './stamina.js';
import { FORMATIONS, DEFAULT_FORMATION } from './formations.js';

// -----------------------------------------------------------
//   Dépendances injectées à l'init
// -----------------------------------------------------------
let _rootEl  = null;
let _roster  = null;
let _state   = null;
let _ui      = null;

export function initFieldModule(rootEl, roster, state, ui) {
    _rootEl = rootEl;
    _roster = roster;
    _state  = state;
    _ui     = ui;
}
// Retourne le facteur de densité d'une zone pour une équipe donnée.
// Plus une formation a de joueurs dans une zone, plus le facteur est élevé.
// Normalisé autour de 1.0 (moyenne = 1, max ~1.5, min ~0.5)
function getZoneDensityFactor(team, zoneIndex) {
    const formKey   = _state._matchConfig?.teams?.[team]?.formation ?? DEFAULT_FORMATION;
    const formation = FORMATIONS[formKey] ?? FORMATIONS[DEFAULT_FORMATION];

    // Calcul automatique depuis les slots
    const density = {};
    Object.values(formation.slots).forEach(def => {
        if (def.laneIndex === null) return; // ignore GK
        density[def.zone] = (density[def.zone] ?? 0) + 1;
    });

    const zoneCount = density[zoneIndex] ?? 0;
    const avg = [1, 2, 3, 4].reduce((s, z) => s + (density[z] ?? 0), 0) / 4;

    return avg > 0 ? zoneCount / avg : 1;
}
// -----------------------------------------------------------
//   Helpers généraux
// -----------------------------------------------------------
export function otherTeam(t) {
    return t === "internal" ? "external" : "internal";
}

export function getPlayerId(team, number) {
    return (team === "internal" ? "I" : "E") + String(number);
}

export function isGoalkeeperId(playerId) {
    return playerId === "I1" || playerId === "E1";
}

export function getCarrierElement(team, number) {
    return _rootEl.querySelector(`[data-player="${getPlayerId(team, number)}"]`);
}

export function getKeeperId(team) {
    const sel = team === "internal"
        ? ".player.internal.goalkeeper"
        : ".player.external.goalkeeper";
    const el  = _rootEl.querySelector(sel);
    return el ? el.dataset.player : null;
}

// -----------------------------------------------------------
//   Coordonnées grille
// -----------------------------------------------------------
export function getCellCenter(team, zoneIndex, laneIndex) {
    const ZONE_X = {
        internal: { 0: 10, 1: 20, 2: 35, 3: 55, 4: 75 },
        external: { 0: 90, 1: 80, 2: 65, 3: 45, 4: 25 },
    };
    return { x: ZONE_X[team][zoneIndex], y: laneY[laneIndex] };
}

export function getXInternal(team, x) {
    return team === "internal" ? x : (100 - x);
}

export function getPlayerXInternal(team, el) {
    const x = parseFloat(el.style.left);
    return Number.isNaN(x) ? null : getXInternal(team, x);
}

export function getPlayerZoneFromDOM(playerId) {
    const el = _rootEl.querySelector(`.player[data-player="${playerId}"]`);
    if (!el) return 0;

    const zAttr = el.dataset.zone;
    if (zAttr !== undefined) {
        const z = parseInt(zAttr, 10);
        if (Number.isFinite(z)) return Math.max(0, Math.min(MAX_ZONE_INDEX, z));
    }

    // Fallback compat via style.left
    const x = parseFloat(el.style.left);
    if (Number.isNaN(x)) return 0;

    const team       = playerId.startsWith("I") ? "internal" : "external";
    const xInternal  = getXInternal(team, x);
    const xClamped   = Math.max(0, Math.min(100, xInternal));
    let   zoneIndex  = 0;

    for (let i = 0; i < ZONE_BOUNDS_INTERNAL.length - 1; i++) {
        const isLast = (i === ZONE_BOUNDS_INTERNAL.length - 2);
        const inside = isLast
            ? (xClamped >= ZONE_BOUNDS_INTERNAL[i] && xClamped <= ZONE_BOUNDS_INTERNAL[i + 1])
            : (xClamped >= ZONE_BOUNDS_INTERNAL[i] && xClamped <  ZONE_BOUNDS_INTERNAL[i + 1]);
        if (inside) { zoneIndex = i; break; }
    }
    return zoneIndex;
}

// -----------------------------------------------------------
//   Heat (anti-biais touches)
// -----------------------------------------------------------
export function heatOf(playerId) {
    return _state.touchHeat[playerId] ?? 0;
}

export function markTouch(playerId) {
    if (!playerId) return;
    _state.touchHeat[playerId] = (_state.touchHeat[playerId] ?? 0) + 1;
}

export function decayHeat() {
    for (const k in _state.touchHeat) {
        _state.touchHeat[k] = Math.max(0, _state.touchHeat[k] - 0.35);
    }
}

// -----------------------------------------------------------
//   Sélection joueurs pondérée
// -----------------------------------------------------------
export function pickWeightedPlayerInZone(team, zoneIndex, laneIndex, opts = {}) {
    const { excludeIds = [], topK = 4, ignoreLane = false } = opts;

    const selector = team === "internal" ? ".player.internal" : ".player.external";
    const center   = ignoreLane
        ? getCellCenter(team, zoneIndex, 1)
        : getCellCenter(team, zoneIndex, laneIndex);

    const candidates = [];
    _rootEl.querySelectorAll(selector).forEach((el) => {
        if (el.classList.contains("goalkeeper")) return;
        const id = el.dataset.player;
        if (!id || excludeIds.includes(id)) return;

        const zAttr = el.dataset.zone;
        if (zAttr !== undefined) {
            const z = parseInt(zAttr, 10);
            if (Number.isFinite(z) && z !== zoneIndex) return;
        }

        const x = parseFloat(el.style.left);
        const y = parseFloat(el.style.top);
        if (Number.isNaN(x) || Number.isNaN(y)) return;

        const dx = x - center.x;
        const dy = ignoreLane ? 0 : (y - center.y);
        candidates.push({ id, d2: dx * dx + dy * dy });
    });

    if (!candidates.length) return null;

    candidates.sort((a, b) => a.d2 - b.d2);
    const pool = candidates.slice(0, Math.min(topK, candidates.length));
    const EPS  = 1e-6;

    const weights = pool.map(({ id, d2 }) => {
        const distW        = 1 / (d2 + EPS);
        const stMax        = getStaminaMax(id) || 100;
        const staminaR     = stMax > 0 ? getStamina(id) / stMax : 1;
        const heatPenalty  = 1 / (1 + heatOf(id) * 0.75);

        // Bonus de densité : une zone bien couverte par la formation
        // augmente la probabilité de trouver un défenseur disponible
        const densityBonus = getZoneDensityFactor(team, zoneIndex);

        return distW * (0.85 + 0.15 * staminaR) * heatPenalty * densityBonus;
    });

    const sum = weights.reduce((a, b) => a + b, 0);
    let r = Math.random() * (sum || 1);
    for (let i = 0; i < pool.length; i++) {
        r -= weights[i];
        if (r <= 0) return pool[i].id;
    }
    return pool[pool.length - 1].id;
}

// Sélectionne un receveur dans une cellule donnée en fonction de divers critères, avec des solutions de repli si nécessaire.
export function pickReceiverInCell(team, zoneIndex, laneIndex, fallbackNumber, excludeNumber = null, opts = {}) {
    const { forwardOnly = false, forwardFromXInternal = null, ignoreLaneForInitialPick = false } = opts;

    const excludeId = excludeNumber ? getPlayerId(team, excludeNumber) : null;

    let receiverId = pickWeightedPlayerInZone(team, zoneIndex, laneIndex, {
        topK: 6, excludeIds: excludeId ? [excludeId] : [], ignoreLane: ignoreLaneForInitialPick,
    });

    if (!receiverId) {
        receiverId = pickWeightedPlayerInZone(team, zoneIndex, laneIndex, {
            topK: 8, excludeIds: excludeId ? [excludeId] : [], ignoreLane: true,
        });
    }

    if (forwardOnly && receiverId && forwardFromXInternal !== null) {
        const el = getCarrierElement(team, parseInt(receiverId.slice(1), 10));
        if (el) {
            const rx = getPlayerXInternal(team, el);
            if (rx !== null && rx < forwardFromXInternal - 0.01) receiverId = null;
        }
    }

    if (receiverId && excludeNumber !== null && parseInt(receiverId.slice(1), 10) === excludeNumber) {
        receiverId = null;
    }

    if (!receiverId) {
        const selector = team === "internal" ? ".player.internal" : ".player.external";
        let pool = Array.from(_rootEl.querySelectorAll(selector)).filter(el => !el.classList.contains("goalkeeper"));

        if (!pool.length) return fallbackNumber;
        if (excludeNumber !== null) {
            const filtered = pool.filter(el => parseInt(el.dataset.player.slice(1), 10) !== excludeNumber);
            if (filtered.length) pool = filtered;
        }
        if (forwardOnly && forwardFromXInternal !== null) {
            const fw = pool.filter(el => {
                const x = getPlayerXInternal(team, el);
                return x !== null && x >= forwardFromXInternal - 0.01;
            });
            if (fw.length) pool = fw;
        }
        return parseInt(pool[Math.floor(Math.random() * pool.length)].dataset.player.slice(1), 10);
    }

    return parseInt(receiverId.slice(1), 10);
}

// -----------------------------------------------------------
//   Sélection défenseur
// -----------------------------------------------------------
export function mapAttackZoneToDefenseZone(originZone) {
    if (originZone <= 0) return 1;
    return Math.max(1, Math.min(4, 5 - originZone));
}

export function pickFieldDefender(defenseTeam, originZone, originLane) {
    const defZone    = mapAttackZoneToDefenseZone(originZone);
    const defenderId = pickWeightedPlayerInZone(defenseTeam, defZone, originLane, { topK: 8, ignoreLane: true });
    if (!defenderId) return null;
    return {
        defenderId,
        defenderSlot: parseInt(defenderId.slice(1), 10),
        defZone,
    };
}

// -----------------------------------------------------------
//   Déplacement ballon
// -----------------------------------------------------------
export function moveBallToPlayer(team, number, updateTeamCardFn) {
    const ball = _state.ball;
    if (!_ui.ballEl) return;

    let targetNumber = number;
    let el = getCarrierElement(team, targetNumber);
    if (!el) return;

    let x = parseFloat(el.style.left);
    let y = parseFloat(el.style.top);
    if (Number.isNaN(x) || Number.isNaN(y)) return;

    _ui.ballEl.style.left = x + "%";
    _ui.ballEl.style.top  = y + "%";

    const info = _roster.getPlayerInfo(team, targetNumber);
    _ui.ballEl.textContent = info ? String(info.number) : String(targetNumber);

    ball.team   = team;
    ball.number = targetNumber;

    // Gardien sans frontOfKeeper → redirection
    if (ball.number === 1 && !ball.frontOfKeeper) {
        const safe   = pickReceiverInCell(team, ball.zoneIndex, ball.laneIndex, 6, 1);
        const safeEl = getCarrierElement(team, safe);
        if (safeEl) {
            const sx = parseFloat(safeEl.style.left);
            const sy = parseFloat(safeEl.style.top);
            _ui.ballEl.style.left = sx + "%";
            _ui.ballEl.style.top  = sy + "%";
            const safeInfo = _roster.getPlayerInfo(team, safe);
            _ui.ballEl.textContent = safeInfo ? String(safeInfo.number) : String(safe);
            ball.number   = safe;
            targetNumber  = safe;
            x = sx; y = sy;
        }
    }

    markTouch(getPlayerId(team, ball.number));
    ball.frontOfKeeper = false;

    const zoneIndex = getPlayerZoneFromDOM(getPlayerId(team, ball.number));
    let bestLane = 0, bestDist = Infinity;
    laneY.forEach((vy, i) => { const d = Math.abs(vy - y); if (d < bestDist) { bestDist = d; bestLane = i; } });

    ball.zoneIndex = zoneIndex;
    ball.laneIndex = bestLane;
    _state.defensePreview = null;

    if (updateTeamCardFn) updateTeamCardFn();
}

export function setBallToKeeperVisual(defenseTeam) {
    const sel = defenseTeam === "internal"
        ? '.player.internal.goalkeeper[data-player="I1"]'
        : '.player.external.goalkeeper[data-player="E1"]';
    const keeperEl = _rootEl.querySelector(sel);
    if (!keeperEl || !_ui.ballEl) return;
    _ui.ballEl.style.left    = `${parseFloat(keeperEl.style.left)}%`;
    _ui.ballEl.style.top     = `${parseFloat(keeperEl.style.top)}%`;
    _ui.ballEl.textContent   = "1";
}

// -----------------------------------------------------------
//   Positions de base
// -----------------------------------------------------------
export function initBasePositions(basePositions) {
    _rootEl.querySelectorAll(".player").forEach((el) => {
        basePositions[el.dataset.player] = {
            x: parseFloat(el.style.left),
            y: parseFloat(el.style.top),
        };
    });
}

export function applyKickoffPositions(basePositions) {
    _rootEl.querySelectorAll(".player").forEach((el) => {
        const base = basePositions[el.dataset.player];
        if (!base) return;
        let x = base.x;
        if (el.classList.contains("internal") && x > 50) x = 48;
        if (el.classList.contains("external") && x < 50) x = 52;
        el.style.left = x + "%";
        el.style.top  = base.y + "%";
    });
}

export function restoreBasePositions(basePositions) {
    _rootEl.querySelectorAll(".player").forEach((el) => {
        const base = basePositions[el.dataset.player];
        if (!base) return;
        el.style.left = base.x + "%";
        el.style.top  = base.y + "%";
    });
}
