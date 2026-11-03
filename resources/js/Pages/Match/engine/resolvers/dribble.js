// resources/js/Pages/Match/engine/resolvers/dribble.js
// Dribbles et une-deux.
import { FIELD_RULES, MAX_ZONE_INDEX, TEXTS, laneY } from '../constants.js';
import { getCellCenter, getPlayerId } from '../field.js';
import { pushLogEntry, setMessage, syncRecovererCard } from '../ui.js';
import { playPlayerAnimation } from '../playerAnim.js';
import { _moveBall, ball, shared, resetLastDribbler } from './context.js';
import { getCounterTag, getLogTitleForDuel } from './duelRules.js';
import { runFieldDuel } from './fieldDuel.js';
import { startFreeKick } from './shot.js';

// -----------------------------------------------------------
//   resolveDribble + continueDribble
// -----------------------------------------------------------
export function resolveDribble(attackTeam, defenseTeam, defenseAction, defenderPick = null, isSpecial = false) {
    const b = ball();
    shared.state.keeperRestartMustPass = false;

    if (b.frontOfKeeper) {
        setMessage(TEXTS.ui.dribbleForbiddenMain, TEXTS.ui.dribbleForbiddenSub);
        pushLogEntry("dribbleRefusedTitle", ["dribbleRefusedDetail"], null, shared.state);
        shared.state.phase = "attack"; shared.state.pendingAttack = null;
        return;
    }

    const oldZone    = b.zoneIndex;
    const lane       = b.laneIndex;
    const attackType = isSpecial ? "special" : "dribble";

    const duel = runFieldDuel({ attackTeam, defenseTeam, attackType, defenseAction, defenderPick });

    // Reroll en attente → stocker aussi oldZone/lane pour la continuation
    if (duel.duelResult === "pending_reroll") {
        if (shared.state.pendingCaptainReroll) {
            shared.state.pendingCaptainReroll.oldZone = oldZone;
            shared.state.pendingCaptainReroll.lane    = lane;
            shared.state.pendingCaptainReroll.isSpecial = isSpecial;
        }
        return;
    }

    if (shared.state.pendingFreeKick) { startFreeKick(); return; }

    if (duel.isTie) {
        pushLogEntry(
            isSpecial ? "Duel equilibre (special dribble)" : "Duel equilibre (dribble)",
            ["Defense: " + defenseAction, getCounterTag(attackType, defenseAction)],
            duel.diceTag, shared.state, duel.breakdown ?? null
        );
        shared.state.phase = "attack"; shared.state.pendingAttack = null;
        shared.animateAndThen(() => { shared.advanceTurn(defenseTeam); });
        return;
    }

    if (isSpecial) shared.markSpecialUsed(getPlayerId(attackTeam, b.number));

    continueDribble(attackTeam, defenseTeam, attackType, defenseAction, isSpecial, duel, oldZone, lane);
}

export function continueDribble(attackTeam, defenseTeam, attackType, defenseAction, isSpecial, duel, oldZone, lane) {
    const b         = ball();
    const carrierId = getPlayerId(attackTeam, b.number);
    const carrierEl = shared.rootEl.querySelector('[data-player="' + carrierId + '"]');

    // Une-deux : même progression qu'un dribble, mais textes/animations de duo
    const isOneTwo  = attackType === "one_two";
    const duo       = isOneTwo ? shared.roster.getDuoPartnerOnField(attackTeam, b.number) : null;
    const duoDetail = isOneTwo
        ? (duo?.label ? "⚡ " + duo.label : "Avec " + (duo?.partnerInfo?.lastname ?? "son partenaire"))
        : null;

    if (duel.duelResult === "attack") {
        // ── Animation du dribbleur (feinte latérale) + du partenaire de duo
        playPlayerAnimation(attackTeam, b.number, 'dribble');
        if (duo) playPlayerAnimation(attackTeam, duo.slot, 'dribble');

        resetLastDribbler();
        shared.state.lastDribblerId = carrierId;

        if (oldZone < MAX_ZONE_INDEX) {
            const newZone = Math.min(MAX_ZONE_INDEX, oldZone + 1);
            const center  = getCellCenter(attackTeam, newZone, lane);
            if (carrierEl && shared.ui.ballEl) {
                const currentY        = parseFloat(carrierEl.style.top);
                carrierEl.style.left  = center.x + "%";
                carrierEl.style.top   = currentY + "%";
                shared.ui.ballEl.style.left = center.x + "%";
                shared.ui.ballEl.style.top  = currentY + "%";
            }
            if (carrierEl) carrierEl.dataset.zone = String(newZone);
            b.zoneIndex = newZone; b.laneIndex = lane; b.frontOfKeeper = false;

            const successMsg = isOneTwo
                ? (duo?.label ? duo.label + " !" : "Une-deux réussi !")
                : (isSpecial ? "Dribble special reussi !" : "Dribble reussi !");
            setMessage(successMsg, shared.TEAMS[attackTeam].label + " avance en zone " + (newZone + 1));
            pushLogEntry(isOneTwo ? "oneTwoSuccessTitle" : "dribbleSuccessTitle", [duoDetail, isSpecial ? "(Special dribble)" : null, "Zone " + (newZone + 1), "Defense: " + defenseAction, getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, shared.state, duel.breakdown ?? null);
            shared.animateAndThen(() => { shared.advanceTurn(attackTeam); });
            return;
        }

        if (oldZone === MAX_ZONE_INDEX) {
            const xFront = FIELD_RULES.GK_FRONT_X[attackTeam];
            const y      = laneY[lane];
            if (carrierEl && shared.ui.ballEl) {
                carrierEl.style.left  = xFront + "%"; carrierEl.style.top  = y + "%";
                shared.ui.ballEl.style.left = xFront + "%"; shared.ui.ballEl.style.top = y + "%";
            }
            if (carrierEl) carrierEl.dataset.zone = String(oldZone);
            b.zoneIndex = oldZone; b.laneIndex = lane; b.frontOfKeeper = true;

            setMessage(TEXTS.ui.frontOfKeeperMain, TEXTS.ui.frontOfKeeperSub);
            pushLogEntry("frontOfKeeperTitle", [duoDetail, isSpecial ? "(Special dribble)" : null, "Zone " + (oldZone + 1), "Defense: " + defenseAction, getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, shared.state, duel.breakdown ?? null);
            shared.animateAndThen(() => { shared.advanceTurn(attackTeam); });
            return;
        }
    }

    const dribblerSlot = b.number;  // capturer avant _moveBall
    resetLastDribbler();
    const slot = duel.defenderSlot ?? (duel.defenderId ? parseInt(duel.defenderId.slice(1), 10) : 6);

// ── L'attaquant a feinté, le défenseur a tacklé
    playPlayerAnimation(attackTeam, dribblerSlot, 'dribble');
    playPlayerAnimation(defenseTeam, slot, 'tackle');

    _moveBall(defenseTeam, slot);
    syncRecovererCard(defenseTeam, slot);

    const logTitle = getLogTitleForDuel(attackType, defenseAction, "defense");
    const msgTitle = isOneTwo
        ? (defenseAction === "intercept" ? TEXTS.logs.oneTwoFailTitle : TEXTS.logs.oneTwoRecoveredTitle)
        : (defenseAction === "tackle" ? "Dribble stoppe" : TEXTS.logs.dribbleRecoveredTitle);
    setMessage(msgTitle, shared.TEAMS[defenseTeam].label + " recupere avec le n " + slot);
    pushLogEntry(logTitle, [duoDetail, isSpecial ? "(Special dribble)" : null, "Defense: " + defenseAction, getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, shared.state, duel.breakdown ?? null);
    shared.animateAndThen(() => { shared.advanceTurn(defenseTeam); });
}

// -----------------------------------------------------------
//   resolveOneTwo — une-deux de duo (progression type dribble,
//   résolu sur la passe, contré à l'interception)
// -----------------------------------------------------------
export function resolveOneTwo(attackTeam, defenseTeam, defenseAction, defenderPick = null) {
    const b = ball();
    shared.state.keeperRestartMustPass = false;

    if (b.frontOfKeeper) {
        setMessage(TEXTS.ui.dribbleForbiddenMain, TEXTS.ui.dribbleForbiddenSub);
        pushLogEntry("dribbleRefusedTitle", ["dribbleRefusedDetail"], null, shared.state);
        shared.state.phase = "attack"; shared.state.pendingAttack = null;
        return;
    }

    // Sécurité : sans partenaire sur le terrain, l'action n'a pas lieu d'être
    if (!shared.roster.getDuoPartnerOnField(attackTeam, b.number)) {
        shared.state.phase = "attack"; shared.state.pendingAttack = null;
        shared.showAttackBarForCurrentTeam();
        return;
    }

    const oldZone    = b.zoneIndex;
    const lane       = b.laneIndex;
    const attackType = "one_two";

    const duel = runFieldDuel({ attackTeam, defenseTeam, attackType, defenseAction, defenderPick });

    if (duel.duelResult === "pending_reroll") {
        if (shared.state.pendingCaptainReroll) {
            shared.state.pendingCaptainReroll.oldZone   = oldZone;
            shared.state.pendingCaptainReroll.lane      = lane;
            shared.state.pendingCaptainReroll.isSpecial = false;
        }
        return;
    }

    if (shared.state.pendingFreeKick) { startFreeKick(); return; }

    if (duel.isTie) {
        pushLogEntry(
            "Duel equilibre (une-deux)",
            ["Defense: " + defenseAction, getCounterTag(attackType, defenseAction)],
            duel.diceTag, shared.state, duel.breakdown ?? null
        );
        shared.state.phase = "attack"; shared.state.pendingAttack = null;
        shared.animateAndThen(() => { shared.advanceTurn(defenseTeam); });
        return;
    }

    continueDribble(attackTeam, defenseTeam, attackType, defenseAction, false, duel, oldZone, lane);
}
