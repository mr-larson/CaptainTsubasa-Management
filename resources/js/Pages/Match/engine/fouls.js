// resources/js/Pages/Match/engine/fouls.js
// Résolution des fautes / cartons consécutifs à un duel.

import { FREE_KICK } from './constants.js';

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

function expelPlayer(domId) {
    const el = _rootEl.querySelector(`[data-player="${domId}"]`);
    if (el) el.classList.add('unavailable');
}

function giveYellowCard(defenderId, defenderDbId, defenderName) {
    _state.foulEvents.push({ type: 'card', player_id: defenderDbId, card_type: 'yellow' });

    if (countYellows(defenderDbId) >= 2) {
        expelPlayer(defenderId);
        _pushLogEntry('foulCardTitle', [`🟨🟨 ${defenderName} — Double jaune ! Expulsé !`], null, _state);
    } else {
        _pushLogEntry('foulCardTitle', [`🟨 ${defenderName} — Carton jaune`], null, _state);
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

    const isCritFailDefense = dRoll?.critFail ?? false;
    const isTie             = duelResult === 'tie';

    if (isCritFailDefense) {
        _state.foulEvents.push({ type: 'foul', fouler_player_id: defenderDbId, victim_player_id: attackerDbId, is_crit_fail: true });
        const r = Math.random();

        if (r < 0.15) {
            _state.foulEvents.push({ type: 'card', player_id: defenderDbId, card_type: 'red' });
            expelPlayer(defenderId);
            _pushLogEntry('foulCardTitle', [`🟥 ${defenderName} — Carton rouge ! Expulsé !`], null, _state);
        } else if (r < 0.90) {
            giveYellowCard(defenderId, defenderDbId, defenderName);
        } else {
            _pushLogEntry('foulTitle', [`⚠️ ${defenderName} — Faute`], null, _state);
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
    }

    if (isTie && Math.random() < 0.25) {
        _state.foulEvents.push({ type: 'foul', fouler_player_id: defenderDbId, victim_player_id: attackerDbId, is_crit_fail: false });
        if (Math.random() < 0.20) {
            giveYellowCard(defenderId, defenderDbId, defenderName);
        }
    }

    if (defenderId) {
        const defTeam   = defenderId.startsWith('I') ? 'internal' : 'external';
        const defSlot   = parseInt(defenderId.slice(1), 10);
        const defPrefix = defTeam === 'internal' ? 'home' : 'away';
        _updateSideCard(defPrefix, defTeam, defSlot);
    }
}
