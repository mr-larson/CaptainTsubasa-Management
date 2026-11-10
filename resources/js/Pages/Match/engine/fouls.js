// resources/js/Pages/Match/engine/fouls.js
// Résolution des fautes / cartons consécutifs à un duel.

import { FREE_KICK } from './constants.js';
import { runAfterDiceAnimation } from './dice.js';
import { LUCK_RULES, incidentMultiplier } from './luck.js';

let _state  = null;
let _roster = null;
let _rootEl = null;
let _updateSideCard = null;
let _pushLogEntry   = null;

export function initFoulsModule({ state, roster, rootEl, updateSideCard, pushLogEntry }) {
    _state          = state;
    _roster         = roster;
    _rootEl         = rootEl;
    _updateSideCard = updateSideCard;
    _pushLogEntry   = pushLogEntry;
}

function getDbId(domId) {
    if (!domId) return null;
    const team = domId.startsWith('I') ? 'internal' : 'external';
    const slot = parseInt(domId.slice(1), 10);
    return _roster.getPlayerInfo(team, slot)?.id ?? null;
}

function getPlayerName(domId) {
    if (!domId) return '?';
    const team = domId.startsWith('I') ? 'internal' : 'external';
    const slot = parseInt(domId.slice(1), 10);
    const info = _roster.getPlayerInfo(team, slot);
    return info ? `${info.firstname} ${info.lastname}`.trim() : domId;
}

function countYellows(playerDbId) {
    return _state.foulEvents.filter(
        e => e.type === 'card' && e.card_type === 'yellow' && e.player_id === playerDbId
    ).length;
}

function expelPlayer(domId, dbId) {
    const el = _rootEl.querySelector(`[data-player="${domId}"]`);
    if (el) runAfterDiceAnimation(() => el.classList.add('unavailable'));
    if (dbId != null) _state.exitTurns[dbId] ??= _state.turns;
}

function giveYellowCard(defenderId, defenderDbId, defenderName, breakdown) {
    _state.foulEvents.push({ type: 'card', player_id: defenderDbId, card_type: 'yellow' });

    if (countYellows(defenderDbId) >= 2) {
        expelPlayer(defenderId, defenderDbId);
        _pushLogEntry('foulCardTitle', [`🟨🟨 ${defenderName} — Double jaune ! Expulsé !`], null, _state, breakdown);
    } else {
        _pushLogEntry('foulCardTitle', [`🟨 ${defenderName} — Carton jaune`], null, _state, breakdown);
    }
}

export function giveMatchCard(defenderId, card, breakdown = null) {
    const id = getDbId(defenderId);
    if (id == null) return;
    const name = getPlayerName(defenderId);
    if (card === 'yellow') {
        giveYellowCard(defenderId, id, name, breakdown);
    } else if (card === 'red') {
        _state.foulEvents.push({ type: 'card', player_id: id, card_type: 'red' });
        expelPlayer(defenderId, id);
        _pushLogEntry('foulCardTitle', [`🟥 ${name} — Carton rouge ! Expulsé !`], null, _state, breakdown);
    }
}

/**
 * Résout les conséquences disciplinaires (faute / carton) d'un duel défensif
 * en cas d'échec critique de la défense ou d'égalité.
 */
export function resolveFoulOutcome({ attackerId, defenderId, duelResult, dRoll }) {
    const attackerDbId = getDbId(attackerId);
    const defenderDbId = getDbId(defenderId);
    const defenderName = getPlayerName(defenderId);
    const defTeam = defenderId?.startsWith('I') ? 'internal' : 'external';
    const defSlot = Number(defenderId?.slice(1));
    const luck = _roster.getPlayerInfo(defTeam, defSlot)?.stats?.luck ?? 50;
    const multiplier = incidentMultiplier(luck);
    const percent = (value) => `${(value * 100).toFixed(2)} %`;
    const details = (rows) => ({ sections: [{ title: "⚠️ Discipline", rows: [
        ["Défenseur", defenderName],
        ["Dé de défense", dRoll?.roll ?? "—"],
        ["Chance", `${luck}/100`],
        ["Multiplicateur de risque", multiplier.toFixed(3)],
        ...rows,
    ] }] });

    const isCritFailDefense = dRoll?.critFail ?? false;
    const isTie             = duelResult === 'tie';

    if (isCritFailDefense) {
        _state.foulEvents.push({ type: 'foul', fouler_player_id: defenderDbId, victim_player_id: attackerDbId, is_crit_fail: true });
        const r = Math.random();
        const yellowLimit = LUCK_RULES.criticalRed + LUCK_RULES.criticalYellow * multiplier;
        const breakdown = details([
            ["Déclencheur", "Échec critique de la défense"],
            ["Tirage de sanction", percent(r)],
            ["Rouge direct si <", percent(LUCK_RULES.criticalRed)],
            ["Jaune sinon si <", percent(yellowLimit)],
        ]);

        if (r < LUCK_RULES.criticalRed) {
            giveMatchCard(defenderId, 'red', breakdown);
        } else if (r < yellowLimit) {
            giveMatchCard(defenderId, 'yellow', breakdown);
        } else {
            _pushLogEntry('foulTitle', [`⚠️ ${defenderName} — Faute`], null, _state, breakdown);
        }

        // ── Coup de pied arrêté : faute grave dans le tiers offensif → coup franc-tir.
        // On pose un drapeau data-only ; le déclenchement du flux est piloté par les
        // résolveurs (resolvers.js), comme le pattern `pending_reroll`.
        const b = _state.ball;
        if (b && b.zoneIndex >= FREE_KICK.MIN_ZONE_INDEX && !b.frontOfKeeper) {
            const attackTeam = attackerId?.startsWith('I') ? 'internal' : 'external';
            _state.pendingFreeKick = {
                team:      attackTeam,
                takerSlot: b.number,
                zoneIndex: b.zoneIndex,
                laneIndex: b.laneIndex,
            };
        }
    } else if (isTie) {
        const foulRoll = Math.random();
        if (foulRoll < 0.25) {
            _state.foulEvents.push({ type: 'foul', fouler_player_id: defenderDbId, victim_player_id: attackerDbId, is_crit_fail: false });
            const cardRoll = Math.random();
            const yellowLimit = LUCK_RULES.tieYellow * multiplier;
            const breakdown = details([
                ["Déclencheur", "Égalité du duel"],
                ["Tirage de faute", `${percent(foulRoll)} (seuil : < 25 %)`],
                ["Tirage de sanction", percent(cardRoll)],
                ["Jaune si <", percent(yellowLimit)],
            ]);
            if (cardRoll < yellowLimit) {
                giveYellowCard(defenderId, defenderDbId, defenderName, breakdown);
            } else {
                _pushLogEntry('foulTitle', [`⚠️ ${defenderName} — Faute`], null, _state, breakdown);
            }
        }
    }

    if (defenderId) {
        const defTeam   = defenderId.startsWith('I') ? 'internal' : 'external';
        const defSlot   = parseInt(defenderId.slice(1), 10);
        const defPrefix = defTeam === 'internal' ? 'home' : 'away';
        _updateSideCard(defPrefix, defTeam, defSlot);
    }
}
