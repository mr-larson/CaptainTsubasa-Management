// resources/js/Pages/Match/engine/resolvers/shot.js
// Tirs, coup franc et duel tir contre gardien.
import { DUEL_RULES, FREE_KICK, MAX_ZONE_INDEX, TEXTS } from '../constants.js';
import { buildDuelMeta, buildFieldDuelBreakdown, resolveCritOutcome, rollD20Advantage, rollD20WithCrit, showDuelDice } from '../dice.js';
import { applyStaminaCost, staminaFactor } from '../stamina.js';
import { specialBaseFor } from '../RosterService.js';
import { getKeeperId, getPlayerId, otherTeam } from '../field.js';
import { buildDefenseGKHTML, highlightDuelPlayers, highlightDuelStats, pushLogEntry, setActionBar, setMessage, syncRecovererCard, updateSideCard } from '../ui.js';
import { playPlayerAnimation } from '../playerAnim.js';
import { defensePrompt } from '../turnPrompt.js';
import { afterFieldDuel, afterReveals, givePossessionOnTie, _moveBall, amendLastDuelEvent, animateGoalThenReset, animateShotToKeeper, applyCritBoost, ball, shared, recordDuelEvent, resetLastDribbler } from './context.js';
import { applyDuelBonuses, defenseTag, getCounterTag, getLogTitleForDuel } from './duelRules.js';
import { aiShouldReroll, consumeReroll } from './captainReroll.js';
import { performKeeperClearance, runFieldDuel } from './fieldDuel.js';

// -----------------------------------------------------------
//   resolveShot + continueShot
// -----------------------------------------------------------
export function resolveShot(attackTeam, defenseTeam, defenseAction, isSpecial = false, defenderPick = null) {
    const b          = ball();
    shared.state.keeperRestartMustPass = false;
    const originZone = b.zoneIndex;
    const originLane = b.laneIndex;
    const attackType = isSpecial ? "special" : "shot";

    if (b.frontOfKeeper) {
        // ── Tir face au gardien
        const move = shared.roster.getSpecialMove(attackTeam, b.number, "attack", "shot");
        const gkAttBase = isSpecial
            ? (move ? specialBaseFor(move, attackTeam, b.number, shared.roster) : shared.roster.attackBaseFor("special", attackTeam, b.number))
            : shared.roster.attackBaseFor("shot", attackTeam, b.number);
        resolveShotKeeperDuel({ stage: "keeper", attackTeam, defenseTeam, originZone, originLane, isSpecial, gkAttackBase: gkAttBase, directShot: true, logParts: ["Zone " + (originZone + 1)] }, defenseAction);
        return;
    }

    const duel = runFieldDuel({ attackTeam, defenseTeam, attackType, defenseAction, defenderPick });
    afterFieldDuel(duel, result => {
        if (shared.state.pendingFreeKick) { startFreeKick(); return; }

        if (result.isTie) {
            givePossessionOnTie(defenseTeam, result.defenderId);
            pushLogEntry("Duel équilibré (tir)", [defenseTag(defenseAction), getCounterTag(attackType, defenseAction)], result.diceTag, shared.state, result.breakdown ?? null);
            shared.state.phase = "attack"; shared.state.pendingAttack = null;
            shared.animateAndThen(() => { shared.advanceTurn(defenseTeam); });
            return;
        }

        continueShot(attackTeam, defenseTeam, defenseAction, isSpecial, result, originZone, originLane);
    });
}

export function continueShot(attackTeam, defenseTeam, defenseAction, isSpecial, duel, originZone, originLane) {
    const b          = ball();
    const attackType = isSpecial ? "special" : "shot";

    if (duel.duelResult === "defense") {
        const shooterSlot = b.number;  // capturer avant _moveBall
        const number = duel.defenderSlot ?? (duel.defenderId ? parseInt(duel.defenderId.slice(1), 10) : 6);

        // ── Le tireur frappe, le défenseur block/intercept
        playPlayerAnimation(attackTeam, shooterSlot, 'shoot');
        playPlayerAnimation(defenseTeam, number, 'tackle');

        _moveBall(defenseTeam, number);
        syncRecovererCard(defenseTeam, number);

        const isBlock = defenseAction === "block";
        setMessage(
            isBlock ? TEXTS.ui.shotBlockedMain : TEXTS.ui.shotRecoveredMain,
            (isBlock ? TEXTS.ui.shotBlockedSub : TEXTS.ui.shotRecoveredSub)
                .replace("{team}", shared.TEAMS[defenseTeam].label).replace("{number}", number)
        );
        pushLogEntry(getLogTitleForDuel(attackType, defenseAction, "defense"), [defenseTag(defenseAction), getCounterTag(attackType, defenseAction)], duel.diceTag, shared.state, duel.breakdown ?? null);
        shared.state.phase = "attack"; shared.state.pendingAttack = null;
        shared.animateAndThen(() => { shared.advanceTurn(defenseTeam); });
        return;
    }

    pushLogEntry(TEXTS.ui.shotOnTargetMain, ["Zone " + (originZone + 1), defenseTag(defenseAction), getCounterTag(attackType, defenseAction)], duel.diceTag, shared.state, duel.breakdown ?? null);
    setMessage(TEXTS.ui.shotOnTargetMain, TEXTS.ui.shotOnTargetSub.replace("{team}", shared.TEAMS[defenseTeam].label));

    const distancePenalty = isSpecial ? 0
        : Math.max(0, MAX_ZONE_INDEX - originZone) * DUEL_RULES.SHOT_DISTANCE_PENALTY_PER_LINE;
    let gkAttackBase;
    if (isSpecial) {
        const move = shared.roster.getSpecialMove(attackTeam, b.number, "attack", "shot");
        gkAttackBase = move
            ? specialBaseFor(move, attackTeam, b.number, shared.roster)
            : shared.roster.attackBaseFor("special", attackTeam, b.number);
    } else {
        gkAttackBase = shared.roster.attackBaseFor("shot", attackTeam, b.number) - distancePenalty;
    }

    // ── Animation du tireur AVANT le déplacement du ballon
    playPlayerAnimation(attackTeam, b.number, 'shoot');

    b.zoneIndex = MAX_ZONE_INDEX; b.laneIndex = originLane;

    shared.state.pendingShotContext = { stage: "keeper", attackTeam, defenseTeam, originZone, originLane, isSpecial, gkAttackBase, distancePenalty, logParts: ["Zone " + (originZone + 1)] };

    animateShotToKeeper(defenseTeam, () => {
        const defPrefix = defenseTeam === "internal" ? "home" : "away";
        updateSideCard(defPrefix, defenseTeam, 1);
        highlightDuelPlayers(attackTeam, b.number, defenseTeam, 1);
        highlightDuelStats(attackTeam, "shot", true);
        shared.state.pendingDefenseContext = { defenseTeam, defenderId: getKeeperId(defenseTeam), defenderSlot: 1 };

        setActionBar(buildDefenseGKHTML(defenseTeam, shared.roster), "mode-defense-" + defenseTeam, b, shared.roster, shared.bindActionButtons, false);
        setMessage(TEXTS.ui.shotOnTargetMain, defensePrompt(shared.TEAMS[defenseTeam].label, shared.isAITeam(defenseTeam), true));

        shared.state.phase = "defense";
        shared.state.pendingAttack = attackType;
        if (shared.isAITeam(defenseTeam)) shared.scheduleAIDefense(attackType, defenseTeam);
    });
}

// -----------------------------------------------------------
//   startFreeKick — coup de pied arrêté (coup franc-tir)
//   Déclenché par les résolveurs quand `shared.state.pendingFreeKick` est posé
//   (faute grave en zone dangereuse, cf. fouls.js). Le tireur fauté frappe
//   directement vers le gardien adverse, avec un bonus offensif — on réutilise
//   le duel tir-vs-gardien existant (resolveShotKeeperDuel via pendingShotContext).
// -----------------------------------------------------------
export function startFreeKick() {
    const fk = shared.state.pendingFreeKick;
    shared.state.pendingFreeKick = null;
    if (!fk) return;

    const b           = ball();
    const attackTeam  = fk.team;
    const defenseTeam = otherTeam(attackTeam);
    const takerSlot   = fk.takerSlot;
    const originZone  = fk.zoneIndex;
    const originLane  = fk.laneIndex;

    // Le tireur est le joueur qui a subi la faute.
    b.team   = attackTeam;
    b.number = takerSlot;
    b.frontOfKeeper = false;
    shared.state.phase = "attack";
    shared.state.pendingAttack = null;
    shared.state.pendingDefenseContext = null;

    const takerInfo = shared.roster.getPlayerInfo(attackTeam, takerSlot);
    const takerNum  = takerInfo?.number ?? takerSlot;

    // Bannière plein terrain + message + entrée de déroulé (le matchLog capte zone/lane).
    setMessage(
        TEXTS.ui.freeKickMain,
        TEXTS.ui.freeKickSub.replace("{team}", shared.TEAMS[attackTeam].label).replace("{number}", takerNum)
    );
    pushLogEntry("freeKickTitle", [`${shared.TEAMS[attackTeam].label} — n°${takerNum}`, "Zone " + (originZone + 1)], null, shared.state);

    afterReveals(() => {
        // Animation du tireur + base offensive bonifiée (coup franc = occasion franche).
        playPlayerAnimation(attackTeam, takerSlot, 'shoot');
        const gkAttackBase = shared.roster.attackBaseFor("shot", attackTeam, takerSlot) + FREE_KICK.ATTACK_BONUS;

        // Ballon placé devant le but, puis duel gardien (flux existant).
        b.zoneIndex = MAX_ZONE_INDEX; b.laneIndex = originLane;

        shared.state.pendingShotContext = {
            stage: "keeper", attackTeam, defenseTeam,
            originZone, originLane, isSpecial: false,
            gkAttackBase, logParts: ["Coup franc"], isFreeKick: true,
        };

        animateShotToKeeper(defenseTeam, () => {
            const defPrefix = defenseTeam === "internal" ? "home" : "away";
            updateSideCard(defPrefix, defenseTeam, 1);
            highlightDuelPlayers(attackTeam, b.number, defenseTeam, 1);
            highlightDuelStats(attackTeam, "shot", true);
            shared.state.pendingDefenseContext = { defenseTeam, defenderId: getKeeperId(defenseTeam), defenderSlot: 1 };

            setActionBar(buildDefenseGKHTML(defenseTeam, shared.roster), "mode-defense-" + defenseTeam, b, shared.roster, shared.bindActionButtons, false);
            setMessage(TEXTS.ui.freeKickMain, defensePrompt(shared.TEAMS[defenseTeam].label, shared.isAITeam(defenseTeam), true));

            shared.state.phase = "defense";
            shared.state.pendingAttack = "shot";
            if (shared.isAITeam(defenseTeam)) shared.scheduleAIDefense("shot", defenseTeam);
        });
    });
}

function _showKeeperRerollPrompt(defenseTeam, gkInfo, onConfirm, onSkip) {
    const reroll      = shared.state.captainReroll[defenseTeam];
    const captainName = ((gkInfo?.firstname ?? '') + ' ' + (gkInfo?.lastname ?? '')).trim();
    const rerollsLeft = reroll.rerollsRemaining;

    const html = `
        <div class="flex flex-col items-center gap-3 p-3 w-full">
            <div class="text-center">
                <p class="text-sm font-bold text-amber-600">👑 Captain Reroll — Gardien !</p>
                <p class="text-xs text-slate-500">${captainName} peut tenter un nouvel arrêt</p>
                <p class="text-xs text-slate-400">${rerollsLeft} relance${rerollsLeft > 1 ? 's' : ''} restante${rerollsLeft > 1 ? 's' : ''}</p>
            </div>
            <div class="flex gap-2 w-full justify-center">
                <button data-action="captain-reroll-confirm"
                    class="flex-1 max-w-[140px] px-3 py-2 rounded-xl text-xs font-bold bg-amber-400 text-white hover:bg-amber-500 transition-all shadow-sm">
                    🧤 Relancer (2d20)
                </button>
                <button data-action="captain-reroll-skip"
                    class="flex-1 max-w-[140px] px-3 py-2 rounded-xl text-xs font-semibold bg-slate-200 text-slate-600 hover:bg-slate-300 transition-all">
                    Accepter le but
                </button>
            </div>
        </div>`;

    const b = ball();
    setActionBar(html, "mode-captain-reroll-gk", b, shared.roster, () => {
        shared.rootEl.querySelector('[data-action="captain-reroll-confirm"]')
            ?.addEventListener('click', () => onConfirm());
        shared.rootEl.querySelector('[data-action="captain-reroll-skip"]')
            ?.addEventListener('click', () => onSkip());
    }, false);
}

function _continueKeeperDuelResult(duelResult, attackTeam, defenseTeam, isSpecial, originZone, logParts, diceTag, breakdown, defenseAction, directShot) {
    const b = ball();
    b.frontOfKeeper = false;
    resetLastDribbler();

    if (directShot) playPlayerAnimation(attackTeam, b.number, 'shoot');

    // ── Animation du plongeon du gardien si arrêt
    if (duelResult === "defense") {
        playPlayerAnimation(defenseTeam, 1, 'save', { direction: Math.random() < 0.5 ? -1 : 1 });
    }

    if (duelResult === "attack") {
        shared.state.score[attackTeam]++;
        const scorerInfo = shared.roster.getPlayerInfo(attackTeam, b.number);
        if (scorerInfo?.id) {
            shared.state.goalEvents = shared.state.goalEvents ?? [];
            shared.state.goalEvents.push({ player_id: scorerInfo.id, turn: shared.state.turns });
        }
        setMessage(
            (isSpecial ? TEXTS.ui.goalSpecialMain : TEXTS.ui.goalMain).replace("{team}", shared.TEAMS[attackTeam].label),
            TEXTS.ui.goalSub.replace("{scoreInternal}", shared.state.score.internal).replace("{scoreExternal}", shared.state.score.external)
        );

        const scorerName2 = (() => {
            const info = shared.roster.getPlayerInfo(attackTeam, b.number);
            if (!info) return null;
            return `${info.lastname} n°${info.number} (${shared.TEAMS[attackTeam].label})`;
        })();
        pushLogEntry(
            isSpecial ? "shotGoalSpecialTitle" : "shotGoalTitle",
            [scorerName2, "Zone " + (originZone + 1)].concat(logParts).filter(Boolean),
            diceTag, shared.state, breakdown
        );

        animateGoalThenReset(attackTeam, () => {
            shared.state.isKickoff = true;
            shared.state.keeperRestartMustPass = false;
            shared.state._applyKickoffFn?.();
            _moveBall(defenseTeam, 8);
            shared.advanceTurn(defenseTeam);
        });
        return;
    }

    pushLogEntry("shotSavedTitle", ["Zone " + (originZone + 1)].concat(logParts), diceTag, shared.state, breakdown);
    performKeeperClearance(defenseTeam, defenseAction, () => {
        shared.advanceTurn(defenseTeam);
    });
}

// -----------------------------------------------------------
//   resolveShotKeeperDuel
// -----------------------------------------------------------
export function resolveShotKeeperDuel(ctx, defenseAction) {
    const { attackTeam, defenseTeam, originZone, isSpecial, gkAttackBase, logParts, directShot } = ctx;
    const b          = ball();
    const attackerId = getPlayerId(attackTeam, b.number);
    const keeperId   = getKeeperId(defenseTeam);
    highlightDuelPlayers(attackTeam, b.number, defenseTeam, keeperId ? 1 : null);

    // `attack` : boost offensif selon la zone du tir (réutilisé par tir en jeu,
    // tir face au gardien et coup franc — point unique pour les 3 chemins).
    let attackScore = gkAttackBase
        * shared.roster.attackZoneMultiplier(attackTeam, b.number, originZone)
        * staminaFactor(attackerId);
    const gkFactor  = keeperId ? staminaFactor(keeperId) : 1.0;

    let defenseBase;
    if (defenseAction === "gk-special") {
        const move = shared.roster.getSpecialMove(defenseTeam, 1, "defense");
        defenseBase = move
            ? specialBaseFor(move, defenseTeam, 1, shared.roster)
            : shared.roster.defenseBaseFor("gk-special", defenseTeam, 1, true);
    } else {
        defenseBase = shared.roster.defenseBaseFor(defenseAction, defenseTeam, 1, true);
    }

    let defenseScore = defenseBase * gkFactor;

    const aRoll      = rollD20WithCrit();
    const dRoll      = rollD20WithCrit();
    const critWinner = resolveCritOutcome(aRoll, dRoll);

    attackScore  += aRoll.bonus;
    defenseScore += dRoll.bonus;

    // Bonus domicile
    const HOME_BONUS = 0.5;
    if (attackTeam  === shared.homeTeam) attackScore  += HOME_BONUS;
    if (defenseTeam === shared.homeTeam) defenseScore += HOME_BONUS;

    // Score avant dés (zone, endurance, domicile) : réutilisé tel quel par les
    // relances du gardien pour qu'elles ne faussent pas le tir.
    const attackPreDice  = attackScore  - aRoll.bonus;
    const defensePreDice = defenseScore - dRoll.bonus;
    const rescoreKeeperDuel = (newDroll) => applyDuelBonuses({
        attackAction: isSpecial ? "special" : "shot",
        defenseAction,
        attackScore:  attackPreDice  + aRoll.bonus,
        defenseScore: defensePreDice + newDroll.bonus,
        context: { isKeeperDuel: true },
    });

    ({ attackScore, defenseScore } = applyDuelBonuses({
        attackAction: isSpecial ? "special" : "shot",
        defenseAction, attackScore, defenseScore,
        context: { isKeeperDuel: true },
    }));

    if (aRoll.critSuccess) applyCritBoost(attackerId);
    if (dRoll.critSuccess && keeperId) applyCritBoost(keeperId);

    const diceTag = attackScore.toFixed(1) + "-" + defenseScore.toFixed(1);

    const meta = buildDuelMeta(
        { attackTeam, attackSlot: b.number, attackAction: isSpecial ? "special" : "shot", defenseTeam, defenseSlot: 1, defenseAction },
        shared.roster, TEXTS
    );

    const isGoodGK = ["hands", "punch", "gk-special"].includes(defenseAction);
    const HOME_BONUS_VALUE = 0.5;
    const breakdown = buildFieldDuelBreakdown({
        attackBaseRaw:  gkAttackBase,
        defenseBaseRaw: defenseBase,
        attackStamF:    staminaFactor(attackerId),
        defenseStamF:   gkFactor,
        aRoll, dRoll,
        isGood: isGoodGK,
        attackScore, defenseScore,
        meta,
        critWinner,
        homeBonus:  HOME_BONUS_VALUE,
        homeSide:   attackTeam === shared.homeTeam ? 'attack' : defenseTeam === shared.homeTeam ? 'defense' : null,
    });

    recordDuelEvent({
        attackTeam, defenseTeam,
        attackSlot: b.number, defenseSlot: 1,
        attackAction: isSpecial ? "special" : "shot", defenseAction,
        duelResult: breakdown.result.winner, breakdown,
        context: { isKeeperDuel: true },
    });

    showDuelDice(attackScore, defenseScore, aRoll, dRoll, breakdown);
    applyStaminaCost(attackerId, "attack", isSpecial ? "special" : "shot");

    const checkInjury = (pid) => {
        if (!pid) return;
        const stamina = shared.state.stamina[pid] ?? 0;
        if (stamina <= 0 && Math.random() < 0.40) {
            const team  = pid.startsWith('I') ? 'internal' : 'external';
            const slot  = parseInt(pid.slice(1), 10);
            const dbId  = shared.roster.getPlayerInfo(team, slot)?.id ?? null;
            const name  = shared.roster.getPlayerInfo(team, slot)?.lastname ?? pid;
            shared.state.foulEvents.push({ type: 'injury', player_id: dbId, severity: 'light' });
            shared.state.exitTurns[dbId] ??= shared.state.turns;
            const el = shared.rootEl.querySelector(`[data-player="${pid}"]`);
            if (el) el.classList.add('unavailable');
            pushLogEntry('foulInjuryTitle', [`🤕 ${name} — Blessure (épuisement)`], null, shared.state);
        }
    };
    checkInjury(attackerId);
    checkInjury(keeperId);
    if (keeperId) applyStaminaCost(keeperId, "defenseGK", defenseAction);
    if (isSpecial) shared.markSpecialUsed(attackerId, "attack", "shot");
    if (defenseAction === "gk-special" && keeperId) shared.markSpecialUsed(keeperId, "defense");

    if (!critWinner && attackScore === defenseScore) {
        afterReveals(() => {
            b.frontOfKeeper = false;
            resetLastDribbler();
            if (directShot) playPlayerAnimation(attackTeam, b.number, 'shoot');
            pushLogEntry("shotGKEqualTitle", ["Zone " + (originZone + 1)], diceTag, shared.state, breakdown);
            performKeeperClearance(defenseTeam, "hands", () => { shared.advanceTurn(defenseTeam); });
        });
        return;
    }

    const revealResult = (result, tag, resultBreakdown, clearanceAction) => {
        afterReveals(() => {
            _continueKeeperDuelResult(result, attackTeam, defenseTeam, isSpecial, originZone, logParts, tag, resultBreakdown, clearanceAction, directShot);
        });
    };

    let duelResult = critWinner ?? (attackScore > defenseScore ? "attack" : "defense");

    // ── CAPTAIN REROLL — Gardien capitaine qui encaisse un tir ──
    if (duelResult === "attack" && keeperId) {
        const gkInfo = shared.roster.getPlayerInfo(defenseTeam, 1);
        if (gkInfo?.isCaptain) {
            const reroll = shared.state.captainReroll?.[defenseTeam];
            if (reroll && reroll.rerollsRemaining > 0 && !reroll.usedOnCurrentAction) {
                if (shared.isAITeam(defenseTeam)) {
                    // IA : décision automatique
                    if (aiShouldReroll(defenseTeam)) {
                        // Reroll GK : 2d20 advantage pour la défense
                        consumeReroll(defenseTeam);
                        const newDroll       = rollD20Advantage();
                        let newDefenseScore  = defenseBase * gkFactor + newDroll.bonus;
                        let newAttackScore   = attackScore - dRoll.bonus + aRoll.bonus; // recalcul sans le vieux dRoll
                        // Réappliquer les bonus
                        ({ attackScore: newAttackScore, defenseScore: newDefenseScore } = rescoreKeeperDuel(newDroll));
                        const newCrit = resolveCritOutcome(aRoll, newDroll);
                        duelResult = newCrit ?? (newAttackScore > newDefenseScore ? "attack" : "defense");
                        const rerollBreakdown = { ...breakdown, captainReroll: true };
                        amendLastDuelEvent(duelResult, { result: { winner: duelResult, diff: newAttackScore - newDefenseScore } });
                        showDuelDice(newAttackScore, newDefenseScore, aRoll, newDroll, rerollBreakdown);
                        pushLogEntry(`👑 Captain Reroll GK — ${gkInfo.lastname}`, [
                            `2d20 (${newDroll.roll1}, ${newDroll.roll2}) → ${newDroll.roll}`,
                            duelResult === "defense" ? "✓ Arrêt !" : "✗ But quand même",
                        ], null, shared.state, rerollBreakdown);
                    }
                } else {
                    // Joueur humain : stocker le contexte GK et afficher le prompt
                    shared.state.pendingCaptainReroll = {
                        attackTeam, defenseTeam,
                        attackType: isSpecial ? "special" : "shot",
                        defenseAction, isSpecial,
                        // Données pour recalculer
                        gkAttackBase, attackerId, keeperId,
                        gkFactor, defenseBase,
                        aRoll, dRoll,
                        originZone, logParts,
                        isKeeperReroll: true,
                    };
                    afterReveals(() => _showKeeperRerollPrompt(defenseTeam, gkInfo, () => {
                        // Confirm
                        if (!shared.state.pendingCaptainReroll) return;
                        shared.state.pendingCaptainReroll = null;
                        consumeReroll(defenseTeam);
                        const newDroll2      = rollD20Advantage();
                        let newAScore, newDScore;
                        ({ attackScore: newAScore, defenseScore: newDScore } = rescoreKeeperDuel(newDroll2));
                        const newCrit2  = resolveCritOutcome(aRoll, newDroll2);
                        const newResult = newCrit2 ?? (newAScore > newDScore ? "attack" : "defense");
                        amendLastDuelEvent(newResult, { result: { winner: newResult, diff: newAScore - newDScore } });
                        const rerollBreakdown = { ...breakdown, captainReroll: true };
                        showDuelDice(newAScore, newDScore, aRoll, newDroll2, rerollBreakdown);
                        pushLogEntry(`👑 Captain Reroll GK — ${gkInfo.lastname}`, [
                            `2d20 (${newDroll2.roll1}, ${newDroll2.roll2}) → ${newDroll2.roll}`,
                            newResult === "defense" ? "✓ Arrêt !" : "✗ But quand même",
                        ], null, shared.state, rerollBreakdown);
                        revealResult(newResult, newAScore.toFixed(1) + "-" + newDScore.toFixed(1), rerollBreakdown, "hands");
                    }, () => {
                        // Skip
                        if (!shared.state.pendingCaptainReroll) return;
                        shared.state.pendingCaptainReroll = null;
                        if (shared.state.captainReroll?.[defenseTeam]) {
                            shared.state.captainReroll[defenseTeam].usedOnCurrentAction = true;
                        }
                        revealResult(duelResult, diceTag, breakdown, "hands");
                    }));
                    return; // Attendre le choix du joueur
                }
            }
        }
    }
    revealResult(duelResult, diceTag, breakdown, defenseAction);
}
