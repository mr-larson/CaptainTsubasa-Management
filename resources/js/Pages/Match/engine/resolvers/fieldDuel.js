// resources/js/Pages/Match/engine/resolvers/fieldDuel.js
// Duel de terrain générique et relance du gardien.
import { DUEL_RULES, TEXTS, ZONE_BOUNDS_INTERNAL, laneY } from '../constants.js';
import { buildDuelMeta, buildFieldDuelBreakdown, resolveCritOutcome, rollD20WithCrit, showDuelDice } from '../dice.js';
import { applyStaminaCost, staminaFactor } from '../stamina.js';
import { specialBaseFor } from '../RosterService.js';
import { getCarrierElement, getPlayerId, pickFieldDefender, pickReceiverInCell, setBallToKeeperVisual } from '../field.js';
import { highlightDuelPlayers, pushLogEntry, setMessage, syncRecovererCard, updateSideCard, updateTeamCard } from '../ui.js';
import { afterReveals, _moveBall, animateBallToXY, ball, shared, recordDuelEvent, resetLastDribbler } from './context.js';
import { defenseTag, isGoodDefenseChoice } from './duelRules.js';
import { aiShouldReroll, canAttackerReroll, doReroll, showCaptainRerollPrompt, tryHeroicMoment } from './captainReroll.js';

// -----------------------------------------------------------
//   runFieldDuel
// -----------------------------------------------------------
export function runFieldDuel({ attackTeam, defenseTeam, attackType, defenseAction, defenderPick = null, specialAction = "shot" }) {
    const b          = ball();
    const attackerId = getPlayerId(attackTeam, b.number);

    if (defenseAction === "simulation-failed") {
        // La simulation a remplacé le contre de champ. Le tir passe néanmoins
        // par son duel habituel contre le gardien.
        applyStaminaCost(attackerId, "attack", attackType);
        if (attackType === "special") shared.markSpecialUsed(attackerId, "attack", specialAction);
        recordDuelEvent({ attackTeam, defenseTeam, attackSlot: b.number, attackAction: attackType, duelResult: "attack" });
        return {
            isTie: false, duelResult: "attack", attackerId,
            breakdown: { sections: [{ title: "Action après une simulation", rows: [
                ["Défense", "La simulation ratée remplace le contre de champ."],
                ["Résolution", "L’action de champ se poursuit sans jet de duel."],
            ] }] },
        };
    }

    const picked = defenderPick ?? pickFieldDefender(defenseTeam, b.zoneIndex, b.laneIndex);
    if (!picked) {
        return { isTie: true, duelResult: "tie", diceTag: "", attackerId };
    }

    const { defenderId, defenderSlot } = picked;
    highlightDuelPlayers(attackTeam, b.number, defenseTeam, defenderSlot);

    updateSideCard(attackTeam  === "internal" ? "home" : "away", attackTeam,  b.number);
    updateSideCard(defenseTeam === "internal" ? "home" : "away", defenseTeam, defenderSlot);
    syncRecovererCard(defenseTeam, defenderSlot);
    updateTeamCard(b);

    const [defenseKind, defenseBase] = defenseAction.split(":");
    const isSpecialCross = attackType === "cross" && specialAction === "cross";
    // Bases attaque
    let attackBaseRaw;
    if (attackType === "special" || isSpecialCross) {
        const move = shared.roster.getSpecialMove(attackTeam, b.number, "attack", specialAction);
        attackBaseRaw = move
            ? specialBaseFor(move, attackTeam, b.number, shared.roster)
            : shared.roster.attackBaseFor(attackType, attackTeam, b.number);
    } else if (attackType === "one_two") {
        // Une-deux : moyenne des passes du duo (repli passe simple si le
        // partenaire a disparu entre le clic et la résolution).
        const duo = shared.roster.getDuoPartnerOnField(attackTeam, b.number);
        attackBaseRaw = duo
            ? shared.roster.oneTwoBaseFor(attackTeam, b.number, duo.slot)
            : shared.roster.attackBaseFor("pass", attackTeam, b.number);
    } else {
        attackBaseRaw = shared.roster.attackBaseFor(attackType, attackTeam, b.number);
    }

    // Bases defense
    let defenseBaseRaw;
    if (defenseKind === "field-special") {
        const move = shared.roster.getSpecialMove(defenseTeam, defenderSlot, "defense", defenseBase);
        defenseBaseRaw = move
            ? specialBaseFor(move, defenseTeam, defenderSlot, shared.roster)
            : shared.roster.defenseBaseFor("field-special", defenseTeam, defenderSlot, false);
    } else {
        defenseBaseRaw = shared.roster.defenseBaseFor(defenseAction, defenseTeam, defenderSlot, false);
    }

    const zoneIdx = b.zoneIndex;
    const attackFactors = [["Zone (Attaque)", shared.roster.attackZoneMultiplier(attackTeam, b.number, zoneIdx)]];
    const defenseFactors = [["Zone (Défense)", shared.roster.defenseZoneMultiplier(defenseTeam, defenderSlot, zoneIdx)]];
    if (attackType === "dribble") {
        attackFactors.push(["Vitesse", shared.roster.speedDuelMultiplier(attackTeam, b.number)]);
    }
    const counter = defenseBase ?? defenseAction;
    if (counter === "tackle" || counter === "intercept") {
        defenseFactors.push(["Vitesse", shared.roster.speedDuelMultiplier(defenseTeam, defenderSlot)]);
    } else if (counter === "block") {
        defenseFactors.push(["Profondeur (Contre)", shared.roster.blockEdgeMultiplier(defenseTeam, defenderSlot, zoneIdx)]);
    }

    const attackStamF  = staminaFactor(attackerId);
    const defenseStamF = staminaFactor(defenderId);

    let attackScore  = attackFactors.reduce((score, [, factor]) => score * factor, attackBaseRaw) * attackStamF;
    let defenseScore = defenseFactors.reduce((score, [, factor]) => score * factor, defenseBaseRaw) * defenseStamF;

    const clearanceBonus = shared.state.pendingClearanceBonus > 0 ? shared.state.pendingClearanceBonus : 0;
    if (shared.state.pendingClearanceBonus > 0) { attackScore += shared.state.pendingClearanceBonus; shared.state.pendingClearanceBonus = 0; }

    const HOME_BONUS = 0.5; // Léger avantage domicile
    const aRoll      = rollD20WithCrit(shared.state.criticalsEnabled);
    const dRoll      = rollD20WithCrit(shared.state.criticalsEnabled);

    // Bonus domicile : +1 sur le score final de l'équipe qui joue à domicile
    if (attackTeam  === shared.homeTeam) attackScore  += HOME_BONUS;
    if (defenseTeam === shared.homeTeam) defenseScore += HOME_BONUS;
    const critWinner = resolveCritOutcome(aRoll, dRoll);

    attackScore  += aRoll.bonus;
    defenseScore += dRoll.bonus;

    const isGood = isGoodDefenseChoice(attackType, defenseAction);
    if (isGood === true) defenseScore += DUEL_RULES.GOOD_COUNTER_BONUS;
    if (isGood === false) attackScore += DUEL_RULES.GENERIC_ATTACK_BONUS;


    const meta = buildDuelMeta(
        { attackTeam, attackSlot: b.number, attackAction: attackType, defenseTeam, defenseSlot: defenderSlot, defenseAction },
        shared.roster, TEXTS
    );

    const HOME_BONUS_VALUE = 0.5;
    const breakdown = buildFieldDuelBreakdown({
        attackBaseRaw, defenseBaseRaw,
        attackStamF, defenseStamF, attackFactors, defenseFactors,
        aRoll, dRoll, isGood,
        attackScore, defenseScore,
        clearanceBonus, meta,
        critWinner,
        homeBonus:  HOME_BONUS_VALUE,
        homeSide:   attackTeam === shared.homeTeam ? 'attack' : defenseTeam === shared.homeTeam ? 'defense' : null,
    });

    recordDuelEvent({
        attackTeam, defenseTeam,
        attackSlot: b.number, defenseSlot: defenderSlot,
        attackAction: attackType, defenseAction,
        duelResult: critWinner ?? (attackScore > defenseScore ? "attack" : attackScore < defenseScore ? "defense" : "tie"),
        breakdown,
    });

    showDuelDice(attackScore, defenseScore, aRoll, dRoll, breakdown);

    if (shared.resolveFoulOutcome) {
        const finalResult = critWinner ?? (attackScore > defenseScore ? "attack" : attackScore < defenseScore ? "defense" : "tie");
        shared.resolveFoulOutcome({ attackerId, defenderId, duelResult: finalResult, aRoll, dRoll });
    }

    const discountAttack = applyStaminaCost(attackerId, "attack", isSpecialCross ? "special-cross" : attackType, aRoll.critSuccess);
    const discountDefense = applyStaminaCost(defenderId, "defenseField", defenseKind, dRoll.critSuccess);

    const checkInjury = (pid) => {
        if (!pid) return;
        const stamina = shared.state.stamina[pid] ?? 0;
        if (stamina > 0) return;
        const roll = Math.random();
        const injuryThreshold = 0.30;
        if (roll < injuryThreshold) {
            const team  = pid.startsWith('I') ? 'internal' : 'external';
            const slot  = parseInt(pid.slice(1), 10);
            const dbId  = shared.roster.getPlayerInfo(team, slot)?.id ?? null;
            const name  = shared.roster.getPlayerInfo(team, slot)?.lastname ?? pid;
            shared.state.foulEvents.push({ type: 'injury', player_id: dbId, severity: 'light' });
            shared.state.exitTurns[dbId] ??= shared.state.turns;
            const el = shared.rootEl.querySelector(`[data-player="${pid}"]`);
            if (el) el.classList.add('unavailable');
            pushLogEntry('foulInjuryTitle', [`🤕 ${name} — Blessure (épuisement)`], null, shared.state, {
                sections: [{ title: "🤕 Blessure par épuisement", rows: [
                    ["Endurance restante", stamina],
                    ["Tirage", `${(roll * 100).toFixed(2)} %`],
                    ["Blessure si <", `${injuryThreshold * 100} %`],
                ] }],
            });
        }
    };
    checkInjury(attackerId);
    checkInjury(defenderId);

    if (attackType === "special" || isSpecialCross) shared.markSpecialUsed(attackerId, "attack", specialAction);
    if (defenseKind === "field-special") shared.markSpecialUsed(defenderId, "defense", defenseBase);

    const diceTag    = attackScore.toFixed(1) + "-" + defenseScore.toFixed(1);
    if (critWinner) {
        // Moment héroïque : relance gratuite avant le reroll capitaine
        if (critWinner === "defense" && tryHeroicMoment(attackTeam, b.number)) {
            return doReroll({
                attackTeam, defenseTeam, attackType, defenseAction,
                attackBaseRaw, defenseBaseRaw, attackStamF, defenseStamF, attackFactors, defenseFactors,
                defenderId, defenderSlot, clearanceBonus, specialAction, discountAttack, discountDefense,
            }, true);
        }
        // Si crit defense ET capitaine peut reroller
        if (critWinner === "defense" && canAttackerReroll(attackTeam, b.number)) {
            if (shared.isAITeam(attackTeam)) {
                if (aiShouldReroll(attackTeam)) {
                    return doReroll({
                        attackTeam, defenseTeam, attackType, defenseAction,
                        attackBaseRaw, defenseBaseRaw, attackStamF, defenseStamF, attackFactors, defenseFactors,
                        defenderId, defenderSlot, clearanceBonus, specialAction, discountAttack, discountDefense,
                    });
                }
                shared.state.captainReroll[attackTeam].usedOnCurrentAction = true;
            } else {
                shared.state.pendingCaptainReroll = {
                    attackTeam, defenseTeam, attackType, defenseAction,
                    attackBaseRaw, defenseBaseRaw, attackStamF, defenseStamF, attackFactors, defenseFactors,
                    defenderId, defenderSlot, diceTag, clearanceBonus, breakdown, specialAction, discountAttack, discountDefense,
                };
                afterReveals(() => showCaptainRerollPrompt(attackTeam));
                return { isTie: false, duelResult: "pending_reroll", defenderId, defenderSlot, diceTag, attackerId, breakdown };
            }
        }
        return { isTie: false, duelResult: critWinner, defenderId, defenderSlot, diceTag, attackerId, breakdown };
    }

    const diff = attackScore - defenseScore;
    if (diff === 0) {
        return { isTie: true, duelResult: "tie", defenderId, defenderSlot, diceTag, attackerId, breakdown };
    }

    const rawResult = diff > 0 ? "attack" : "defense";

    // ── MOMENT HÉROÏQUE ─────────────────────────────────────────
    if (rawResult === "defense" && tryHeroicMoment(attackTeam, b.number)) {
        return doReroll({
            attackTeam, defenseTeam, attackType, defenseAction,
            attackBaseRaw, defenseBaseRaw, attackStamF, defenseStamF, attackFactors, defenseFactors,
            defenderId, defenderSlot, clearanceBonus, specialAction, discountAttack, discountDefense,
        }, true);
    }

    // ── CAPTAIN REROLL ──────────────────────────────────────────
    if (rawResult === "defense" && canAttackerReroll(attackTeam, b.number)) {
        if (shared.isAITeam(attackTeam)) {
            if (aiShouldReroll(attackTeam)) {
                return doReroll({
                    attackTeam, defenseTeam, attackType, defenseAction,
                    attackBaseRaw, defenseBaseRaw, attackStamF, defenseStamF, attackFactors, defenseFactors,
                    defenderId, defenderSlot, clearanceBonus, specialAction, discountAttack, discountDefense,
                });
            }
            // IA décline → marquer quand même pour ne pas re-proposer
            shared.state.captainReroll[attackTeam].usedOnCurrentAction = true;
        } else {
            // Joueur humain : stocker le contexte et afficher le prompt
            shared.state.pendingCaptainReroll = {
                attackTeam, defenseTeam, attackType, defenseAction,
                attackBaseRaw, defenseBaseRaw, attackStamF, defenseStamF, attackFactors, defenseFactors,
                defenderId, defenderSlot, diceTag, clearanceBonus, breakdown, specialAction, discountAttack, discountDefense,
            };
            afterReveals(() => showCaptainRerollPrompt(attackTeam));
            return { isTie: false, duelResult: "pending_reroll", defenderId, defenderSlot, diceTag, attackerId, breakdown };
        }
    }
    // ── FIN CAPTAIN REROLL ──────────────────────────────────────

    return { isTie: false, duelResult: rawResult, defenderId, defenderSlot, diceTag, attackerId, breakdown };
}

// -----------------------------------------------------------
//   performKeeperClearance
// -----------------------------------------------------------
export function performKeeperClearance(defenseTeam, defenseAction, afterClearance = null) {
    const b       = ball();
    const GK_HOLD = shared.state._GK_HOLD_MS ?? 300;
    const keeperEl = shared.rootEl.querySelector(
        defenseTeam === "internal"
            ? '.player.internal.goalkeeper[data-player="I1"]'
            : '.player.external.goalkeeper[data-player="E1"]'
    );

    const fallback = () => {
        const receiver = pickReceiverInCell(defenseTeam, b.zoneIndex, b.laneIndex, 6, null);
        b.frontOfKeeper = false;
        resetLastDribbler();
        _moveBall(defenseTeam, receiver);
        if (afterClearance) afterClearance();
    };

    if (!keeperEl) { fallback(); return; }

    const kx = parseFloat(keeperEl.style.left);
    const ky = parseFloat(keeperEl.style.top);
    const xInternal = defenseTeam === "internal" ? kx : (100 - kx);

    let originZone = 0;
    for (let i = 0; i < ZONE_BOUNDS_INTERNAL.length - 1; i++) {
        if (xInternal >= ZONE_BOUNDS_INTERNAL[i] && xInternal <= ZONE_BOUNDS_INTERNAL[i + 1]) { originZone = i; break; }
    }
    let originLane = 0, best = Infinity;
    laneY.forEach((vy, i) => { const d = Math.abs(vy - ky); if (d < best) { best = d; originLane = i; } });

    shared.state.pendingClearanceBonus = defenseAction === "hands" ? 5 : defenseAction === "punch" ? 4 : 7;

    const forward = (n) => defenseTeam === "internal"
        ? Math.min(3, originZone + n)
        : Math.max(0, originZone - n);

    const r = Math.random();
    let targetZone;
    if      (defenseAction === "hands") targetZone = forward(r < 0.6 ? 2 : 3);
    else if (defenseAction === "punch") targetZone = forward(1);
    else                                targetZone = forward(r < 0.15 ? 2 : r < 0.7 ? 3 : 4);

    let laneOptions = [originLane];
    if (defenseAction !== "punch") {
        if (originLane > 0)                laneOptions.push(originLane - 1);
        if (originLane < laneY.length - 1) laneOptions.push(originLane + 1);
    }
    if (defenseAction === "gk-special") laneOptions = [0, 1, 2];

    const laneRoll = Math.random();
    const targetLane = laneOptions[Math.floor(laneRoll * laneOptions.length)];
    const receiver   = pickReceiverInCell(defenseTeam, targetZone, targetLane, 6, null);

    setMessage(TEXTS.ui.keeperRestartMain, TEXTS.ui.keeperRestartSub + " (#" + receiver + ")");
    pushLogEntry("keeperRestartMain", [
        defenseTag(defenseAction),
        "Bonus +" + shared.state.pendingClearanceBonus,
        "Vers zone " + (targetZone + 1) + ", ligne " + (targetLane + 1),
        "Receveur : n°" + receiver,
    ], null, shared.state, {
        sections: [{ title: "Relance du gardien", rows: [
            ["Technique", defenseTag(defenseAction).replace("Défense : ", "")],
            ...(defenseAction === "punch" ? [] : [["Tirage de distance", `${(r * 100).toFixed(2)} %`]]),
            ...(laneOptions.length > 1 ? [["Tirage de ligne", `${(laneRoll * 100).toFixed(2)} %`]] : []),
            ["Destination", `Zone ${targetZone + 1}, ligne ${targetLane + 1}`],
            ["Receveur", `n°${receiver}`],
            ["Bonus pour la prochaine action", `+${shared.state.pendingClearanceBonus}`],
        ] }],
    }, { team: defenseTeam });

    setBallToKeeperVisual(defenseTeam);

    const receiverEl = getCarrierElement(defenseTeam, receiver);
    if (!receiverEl) { fallback(); return; }

    const rx = parseFloat(receiverEl.style.left);
    const ry = parseFloat(receiverEl.style.top);

    const st = shared.state;
    st.isAnimating = true;
    setTimeout(() => {
        if (st.disposed) return;
        shared.afterFieldMotion(() => {
            animateBallToXY(rx, ry, () => {
                b.frontOfKeeper = false;
                resetLastDribbler();
                _moveBall(defenseTeam, receiver);
                afterClearance?.();
            });
        });
    }, GK_HOLD);
}
