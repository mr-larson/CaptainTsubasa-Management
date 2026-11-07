// resources/js/Pages/Match/engine/resolvers/dribble.js
// Dribbles et une-deux.
import { FIELD_RULES, MAX_ZONE_INDEX, TEXTS, laneY } from '../constants.js';
import { getCellCenter, getPlayerId } from '../field.js';
import { pushLogEntry, setMessage, syncRecovererCard } from '../ui.js';
import { playPlayerAnimation } from '../playerAnim.js';
import { afterFieldDuel, givePossessionOnTie, _moveBall, ball, shared, resetLastDribbler } from './context.js';
import { defenseTag, getCounterTag, getLogTitleForDuel } from './duelRules.js';
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
    afterFieldDuel(duel, result => {
        if (shared.state.pendingFreeKick) { startFreeKick(); return; }

        if (result.isTie) {
            givePossessionOnTie(defenseTeam, result.defenderId);
            pushLogEntry(
                isSpecial ? "Duel équilibré (dribble spécial)" : "Duel équilibré (dribble)",
                [defenseTag(defenseAction), getCounterTag(attackType, defenseAction)],
                result.diceTag, shared.state, result.breakdown ?? null
            );
            shared.state.phase = "attack"; shared.state.pendingAttack = null;
            shared.animateAndThen(() => { shared.advanceTurn(defenseTeam); });
            return;
        }

        if (isSpecial) shared.markSpecialUsed(getPlayerId(attackTeam, b.number));

        continueDribble(attackTeam, defenseTeam, attackType, defenseAction, isSpecial, result, oldZone, lane);
    });
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
                : (isSpecial ? "Dribble spécial réussi !" : "Dribble réussi !");
            setMessage(successMsg, shared.TEAMS[attackTeam].label + " avance en zone " + (newZone + 1));
            pushLogEntry(isOneTwo ? "oneTwoSuccessTitle" : "dribbleSuccessTitle", [duoDetail, isSpecial ? "(dribble spécial)" : null, "Zone " + (newZone + 1), defenseTag(defenseAction), getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, shared.state, duel.breakdown ?? null);
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
            pushLogEntry("frontOfKeeperTitle", [duoDetail, isSpecial ? "(dribble spécial)" : null, "Zone " + (oldZone + 1), defenseTag(defenseAction), getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, shared.state, duel.breakdown ?? null);
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
        : (defenseAction === "tackle" ? "Dribble stoppé" : TEXTS.logs.dribbleRecoveredTitle);
    setMessage(msgTitle, shared.TEAMS[defenseTeam].label + " récupère avec le n°" + slot);
    pushLogEntry(logTitle, [duoDetail, isSpecial ? "(dribble spécial)" : null, defenseTag(defenseAction), getCounterTag(attackType, defenseAction)].filter(Boolean), duel.diceTag, shared.state, duel.breakdown ?? null);
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
    afterFieldDuel(duel, result => {
        if (shared.state.pendingFreeKick) { startFreeKick(); return; }

        if (result.isTie) {
            givePossessionOnTie(defenseTeam, result.defenderId);
            pushLogEntry(
                "Duel équilibré (une-deux)",
                [defenseTag(defenseAction), getCounterTag(attackType, defenseAction)],
                result.diceTag, shared.state, result.breakdown ?? null
            );
            shared.state.phase = "attack"; shared.state.pendingAttack = null;
            shared.animateAndThen(() => { shared.advanceTurn(defenseTeam); });
            return;
        }

        continueDribble(attackTeam, defenseTeam, attackType, defenseAction, false, result, oldZone, lane);
    });
}
