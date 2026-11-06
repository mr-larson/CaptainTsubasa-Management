// resources/js/Pages/Match/engine/resolvers/pass.js
// Passes, centres et passes longues.
import { MAX_ZONE_INDEX, TEXTS, laneY } from '../constants.js';
import { getCarrierElement, getPlayerId, getPlayerXInternal, pickReceiverInCell } from '../field.js';
import { pushLogEntry, setMessage, syncRecovererCard } from '../ui.js';
import { playPlayerAnimation } from '../playerAnim.js';
import { _moveBall, ball, shared, resetLastDribbler, restoreBasePositions } from './context.js';
import { defenseTag, getCounterTag, getLogTitleForDuel } from './duelRules.js';

import { runFieldDuel } from './fieldDuel.js';
import { startFreeKick } from './shot.js';

// -----------------------------------------------------------
//   resolvePass + continuePass
// -----------------------------------------------------------
export function resolvePass(attackTeam, defenseTeam, defenseAction, defenderPick = null, isSpecial = false) {
    const b          = ball();
    const wasKickoff = shared.state.isKickoff;
    shared.state.isKickoff             = false;
    shared.state.keeperRestartMustPass = false;

    const attackType = isSpecial ? "special" : "pass";

    const duel = runFieldDuel({ attackTeam, defenseTeam, attackType, defenseAction, defenderPick });

    // Reroll en attente → on stoppe ici, continuePendingAction reprendra
    if (duel.duelResult === "pending_reroll") return;

    // Coup de pied arrêté obtenu pendant le duel → on bascule sur le coup franc-tir.
    if (shared.state.pendingFreeKick) { startFreeKick(); return; }

    if (duel.isTie) {
        pushLogEntry(
            isSpecial ? "Duel équilibré (passe spéciale)" : "Duel équilibré (passe)",
            [defenseTag(defenseAction), getCounterTag(attackType, defenseAction)],
            duel.diceTag, shared.state, duel.breakdown ?? null
        );
        shared.state.phase = "attack"; shared.state.pendingAttack = null;
        shared.animateAndThen(() => { shared.advanceTurn(defenseTeam); });
        return;
    }

    if (isSpecial) shared.markSpecialUsed(getPlayerId(attackTeam, b.number));

    // Kickoff
    if (wasKickoff) {
        if (duel.duelResult === "attack") {
            const receiver = [5, 6][Math.floor(Math.random() * 2)];
            setMessage(isSpecial ? "Passe spéciale réussie !" : "Remise en jeu réussie !", shared.TEAMS[attackTeam].label + " joue vers le n°" + receiver);
            pushLogEntry("kickoffTitle", [isSpecial ? "(passe spéciale)" : null, "Vers le n°" + receiver, defenseTag(defenseAction), getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, shared.state, duel.breakdown ?? null);
            shared.animateAndThen(() => { restoreBasePositions(); _moveBall(attackTeam, receiver); shared.advanceTurn(attackTeam); });
        } else {
            const receiver = duel.defenderSlot ?? 6;
            const verb     = defenseAction === "intercept" ? "intercepte" : "récupère";
            setMessage("Remise en jeu ratée !", shared.TEAMS[defenseTeam].label + " " + verb + " avec le n°" + receiver);
            pushLogEntry("kickoffTitle", [isSpecial ? "(passe spéciale)" : null, defenseTag(defenseAction), getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, shared.state, duel.breakdown ?? null);
            shared.animateAndThen(() => { restoreBasePositions(); _moveBall(defenseTeam, receiver); shared.advanceTurn(defenseTeam); });
        }
        return;
    }

    continuePass(attackTeam, defenseTeam, attackType, defenseAction, isSpecial, duel);
}

export function continuePass(attackTeam, defenseTeam, attackType, defenseAction, isSpecial, duel) {
    const b = ball();

    // Passe en retrait (depuis un face-à-face avec le gardien) : vers un partenaire
    // une zone plus bas, sans contrainte « vers l'avant ».
    const isBackPass = !!shared.state.backPass;
    shared.state.backPass = false;

    let targetZone = b.zoneIndex;
    let targetLane = b.laneIndex;
    if (isBackPass) {
        targetZone = Math.max(0, b.zoneIndex - 1);
        targetLane = [0, 1, 2][Math.floor(Math.random() * 3)];
    } else if (b.zoneIndex < MAX_ZONE_INDEX) {
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
        const receiver = pickReceiverInCell(attackTeam, targetZone, targetLane, b.number, b.number,
            isBackPass
                ? { ignoreLaneForInitialPick: true }
                : { forwardOnly: true, forwardFromXInternal: carrierXInternal, ignoreLaneForInitialPick: true });

        // ── Animations passeur + receveur
        playPlayerAnimation(attackTeam, b.number, 'passer');
        playPlayerAnimation(attackTeam, receiver, 'receiver');

        _moveBall(attackTeam, receiver);
        setMessage(isSpecial ? "Passe spéciale réussie !" : TEXTS.logs.passSuccessTitle, shared.TEAMS[attackTeam].label + " trouve le n°" + receiver);
        pushLogEntry("passSuccessTitle", [isSpecial ? "(passe spéciale)" : null, "Vers le n°" + receiver, defenseTag(defenseAction), getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, shared.state, duel.breakdown ?? null);
        shared.animateAndThen(() => { shared.advanceTurn(attackTeam); });
        return;
    }

    const passerSlot = b.number;  // capturer avant que _moveBall ne change b
    resetLastDribbler();
    const receiver = duel.defenderSlot ?? (duel.defenderId ? parseInt(duel.defenderId.slice(1), 10) : 6);

    // ── Animations passeur + intercepteur
    playPlayerAnimation(attackTeam, passerSlot, 'passer');
    playPlayerAnimation(defenseTeam, receiver, 'receiver');

    _moveBall(defenseTeam, receiver);
    syncRecovererCard(defenseTeam, receiver);

    const logTitle = getLogTitleForDuel(attackType, defenseAction, "defense");
    const msgTitle = defenseAction === "intercept" ? TEXTS.logs.passFailTitle : TEXTS.logs.passRecoveredTitle;
    setMessage(msgTitle, shared.TEAMS[defenseTeam].label + " récupère avec le n°" + receiver);
    pushLogEntry(logTitle, [isSpecial ? "(passe spéciale)" : null, defenseTag(defenseAction), getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, shared.state, duel.breakdown ?? null);
    shared.animateAndThen(() => { shared.advanceTurn(defenseTeam); });
}

// -----------------------------------------------------------
//   resolveCross / resolveLongPass
// -----------------------------------------------------------
export function resolveCross(attackTeam, defenseTeam) {
    const b = ball();
    const duel = runFieldDuel({ attackTeam, defenseTeam, attackType: "cross", defenseAction: "heading" });
    if (duel.duelResult === "pending_reroll") return;
    if (shared.state.pendingFreeKick) { startFreeKick(); return; }

    if (duel.isTie) {
        pushLogEntry("Duel équilibré (centre)", [defenseTag("heading"), getCounterTag("cross", "heading")], duel.diceTag, shared.state, duel.breakdown ?? null);
        shared.state.phase = "attack"; shared.state.pendingAttack = null;
        shared.animateAndThen(() => { shared.advanceTurn(defenseTeam); });
        return;
    }

    if (duel.duelResult === "attack") {
        resetLastDribbler();
        const passerSlot       = b.number;
        const targetZone       = MAX_ZONE_INDEX;
        const targetLane       = [0, 1, 2][Math.floor(Math.random() * 3)];
        const receiver = pickReceiverInCell(attackTeam, targetZone, targetLane, passerSlot, passerSlot, { forwardOnly: true });

        playPlayerAnimation(attackTeam, passerSlot, 'passer');
        playPlayerAnimation(attackTeam, receiver, 'receiver');

        _moveBall(attackTeam, receiver);
        setMessage("Centre réussi !", shared.TEAMS[attackTeam].label + " trouve le n°" + receiver + " dans la surface");
        pushLogEntry("Centre réussi", ["Vers le n°" + receiver, defenseTag("heading"), getCounterTag("cross", "heading")], duel.diceTag, shared.state, duel.breakdown ?? null);
        shared.animateAndThen(() => { shared.advanceTurn(attackTeam); });
        return;
    }

    const passerSlot = b.number;
    resetLastDribbler();
    const receiver = duel.defenderSlot ?? 5;

    playPlayerAnimation(attackTeam, passerSlot, 'passer');
    playPlayerAnimation(defenseTeam, receiver, 'receiver');

    _moveBall(defenseTeam, receiver);
    syncRecovererCard(defenseTeam, receiver);

    setMessage("Centre repoussé !", shared.TEAMS[defenseTeam].label + " dégage avec le n°" + receiver);
    pushLogEntry("Centre repoussé (tête)", [defenseTag("heading"), getCounterTag("cross", "heading")], duel.diceTag, shared.state, duel.breakdown ?? null);
    shared.animateAndThen(() => { shared.advanceTurn(defenseTeam); });
}

export function resolveLongPass(attackTeam, defenseTeam) {
    const b = ball();
    const duel = runFieldDuel({ attackTeam, defenseTeam, attackType: "long_pass", defenseAction: "intercept" });
    if (duel.duelResult === "pending_reroll") return;
    if (shared.state.pendingFreeKick) { startFreeKick(); return; }

    if (duel.isTie) {
        pushLogEntry("Duel équilibré (passe longue)", [defenseTag("intercept"), getCounterTag("long_pass", "intercept")], duel.diceTag, shared.state, duel.breakdown ?? null);
        shared.state.phase = "attack"; shared.state.pendingAttack = null;
        shared.animateAndThen(() => { shared.advanceTurn(defenseTeam); });
        return;
    }

    if (duel.duelResult === "attack") {
        resetLastDribbler();
        const passerSlot = b.number;
        const targetZone = 2; // zone MOF (index 0..4 → 2)
        const targetLane = [0, 1, 2][Math.floor(Math.random() * 3)];
        const receiver = pickReceiverInCell(attackTeam, targetZone, targetLane, passerSlot, passerSlot, { forwardOnly: true });

        playPlayerAnimation(attackTeam, passerSlot, 'passer');
        playPlayerAnimation(attackTeam, receiver, 'receiver');

        _moveBall(attackTeam, receiver);
        setMessage("Passe longue réussie !", shared.TEAMS[attackTeam].label + " trouve le n°" + receiver);
        pushLogEntry("Passe longue réussie", ["Vers le n°" + receiver, defenseTag("intercept"), getCounterTag("long_pass", "intercept")], duel.diceTag, shared.state, duel.breakdown ?? null);
        shared.animateAndThen(() => { shared.advanceTurn(attackTeam); });
        return;
    }

    const passerSlot = b.number;
    resetLastDribbler();
    const receiver = duel.defenderSlot ?? 6;

    playPlayerAnimation(attackTeam, passerSlot, 'passer');
    playPlayerAnimation(defenseTeam, receiver, 'receiver');

    _moveBall(defenseTeam, receiver);
    syncRecovererCard(defenseTeam, receiver);

    setMessage("Passe longue interceptée !", shared.TEAMS[defenseTeam].label + " récupère avec le n°" + receiver);
    pushLogEntry("Passe longue interceptée", [defenseTag("intercept"), getCounterTag("long_pass", "intercept")], duel.diceTag, shared.state, duel.breakdown ?? null);
    shared.animateAndThen(() => { shared.advanceTurn(defenseTeam); });
}
