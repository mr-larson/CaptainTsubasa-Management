// resources/js/Pages/Match/engine/resolvers/captainReroll.js
// Relance du capitaine et « dépassement de soi ».
import { DUEL_RULES, GAME_RULES, HEROIC_CHANCE, HEROIC_MORALE_THRESHOLD, MORALE_DEFAULT, TEXTS } from '../constants.js';
import { buildDuelMeta, buildFieldDuelBreakdown, resolveCritOutcome, rollD20Advantage, rollD20WithCrit, showDuelDice } from '../dice.js';
import { getPlayerId } from '../field.js';
import { pushLogEntry, setActionBar } from '../ui.js';
import { amendLastDuelEvent, ball, shared } from './context.js';
import { isGoodDefenseChoice } from './duelRules.js';

// -----------------------------------------------------------
//   CAPTAIN REROLL — helpers
// -----------------------------------------------------------

/**
 * Reset le flag "used this action" au début de chaque nouvelle action.
 * À appeler depuis engine.js avant showAttackBarForCurrentTeam().
 */
export function resetCaptainRerollActionFlag(team) {
    if (shared.state.captainReroll?.[team]) {
        shared.state.captainReroll[team].usedOnCurrentAction = false;
    }
}

/**
 * Moment héroïque ("Dépassement de soi") : un joueur à plus de 85 de moral
 * a une chance de relancer gratuitement un duel perdu, une fois par match.
 * Retourne true si le moment se déclenche (et le consomme).
 */
export function tryHeroicMoment(attackTeam, attackSlot) {
    const info = shared.roster.getPlayerInfo(attackTeam, attackSlot);
    if (!info?.id) return false;
    if (Number(info.morale ?? MORALE_DEFAULT) <= HEROIC_MORALE_THRESHOLD) return false;

    shared.state.heroicUsed ??= {};
    const playerId = getPlayerId(attackTeam, attackSlot);
    if (shared.state.heroicUsed[playerId]) return false;

    if (Math.random() >= HEROIC_CHANCE) return false;

    shared.state.heroicUsed[playerId] = true;
    return true;
}

/**
 * Vérifie si l'attaquant (capitaine) peut relancer.
 */
export function canAttackerReroll(attackTeam, attackSlot) {
    const info = shared.roster.getPlayerInfo(attackTeam, attackSlot);
    if (!info?.isCaptain) return false;
    const reroll = shared.state.captainReroll?.[attackTeam];
    if (!reroll) return false;
    return reroll.rerollsRemaining > 0 && !reroll.usedOnCurrentAction;
}

/**
 * L'IA décide si elle utilise sa relance selon le contexte du match.
 */
export function aiShouldReroll(attackTeam) {
    const reroll = shared.state.captainReroll?.[attackTeam];
    if (!reroll || reroll.rerollsRemaining <= 0) return false;

    const turn      = shared.state.turns ?? 0;
    const selfScore = shared.state.score?.[attackTeam] ?? 0;
    const oppTeam   = attackTeam === "internal" ? "external" : "internal";
    const oppScore  = shared.state.score?.[oppTeam] ?? 0;

    // Fin de match : derniers ~22 % des tours (23 sur 30 par défaut), quelle que soit la durée réglée.
    if (turn >= Math.round((shared.state.maxTurns || GAME_RULES.MAX_TURNS) * 0.78)) return Math.random() < 0.85;
    if (selfScore < oppScore)    return Math.random() < 0.65; // En retard
    if (selfScore === oppScore)  return Math.random() < 0.50; // Egalité
    return Math.random() < 0.25;                              // En avance → économiser
}

/**
 * Consomme une relance localement et envoie la requête au serveur.
 */
export function consumeReroll(attackTeam) {
    const reroll = shared.state.captainReroll[attackTeam];
    reroll.rerollsRemaining--;
    reroll.usedOnCurrentAction = true;

    const contractId = reroll.contractId;
    const gameSaveId = shared.state._matchConfig?.gameSaveId;
    if (contractId && gameSaveId) {
        // axios envoie le jeton CSRF (cookie XSRF-TOKEN) ; l'ancien fetch lisait une
        // balise <meta name="csrf-token"> absente du layout → requête refusée (419).
        window.axios.post(route('game-saves.captain-reroll.use', { gameSave: gameSaveId, contract: contractId }))
            .catch(() => {/* silencieux — ne pas bloquer le moteur */});
    }

    // Mettre à jour l'indicateur UI (badge sur la card capitaine)
    const el = shared.rootEl?.querySelector(`[data-captain-reroll="${attackTeam}"]`);
    if (el) el.textContent = reroll.rerollsRemaining;
}

/**
 * Effectue la relance : 2d20 advantage pour l'attaque, 1d20 normal pour la défense.
 * Retourne un objet duel identique à runFieldDuel.
 * `heroic` = relance gratuite "Dépassement de soi" (ne consomme pas de reroll capitaine).
 */
export function doReroll(ctx, heroic = false) {
    const {
        attackTeam, defenseTeam, attackType, defenseAction,
        attackBaseRaw, defenseBaseRaw, attackStamF, defenseStamF,
        defenderId, defenderSlot, clearanceBonus,
    } = ctx;

    if (!heroic) consumeReroll(attackTeam);

    const b = ball();

    const newAroll = rollD20Advantage();  // 2d20 — prend le meilleur
    const newDroll = rollD20WithCrit();   // 1d20 normal

    let newAttackScore  = attackBaseRaw * attackStamF;
    let newDefenseScore = defenseBaseRaw * defenseStamF;

    if (clearanceBonus) newAttackScore += clearanceBonus;

    newAttackScore  += newAroll.bonus;
    newDefenseScore += newDroll.bonus;

    const isGood = isGoodDefenseChoice(attackType, defenseAction);
    if (isGood) newDefenseScore += DUEL_RULES.GOOD_COUNTER_BONUS;
    else        newAttackScore  += DUEL_RULES.GENERIC_ATTACK_BONUS;

    // Même bonus domicile que le duel initial (runFieldDuel).
    const HOME_BONUS = 0.5;
    if (attackTeam  === shared.homeTeam) newAttackScore  += HOME_BONUS;
    if (defenseTeam === shared.homeTeam) newDefenseScore += HOME_BONUS;

    const newCritWinner = resolveCritOutcome(newAroll, newDroll);

    const meta = buildDuelMeta(
        { attackTeam, attackSlot: b.number, attackAction: attackType, defenseTeam, defenseSlot: defenderSlot, defenseAction },
        shared.roster, TEXTS
    );

    const breakdown = buildFieldDuelBreakdown({
        attackBaseRaw, defenseBaseRaw,
        attackStamF, defenseStamF,
        aRoll: newAroll,
        dRoll: newDroll,
        isGood,
        attackScore: newAttackScore,
        defenseScore: newDefenseScore,
        clearanceBonus, meta,
        critWinner: newCritWinner,
        captainReroll: !heroic,
        heroic,
        homeBonus: HOME_BONUS,
        homeSide: attackTeam === shared.homeTeam ? 'attack' : defenseTeam === shared.homeTeam ? 'defense' : null,
    });

    showDuelDice(newAttackScore, newDefenseScore, newAroll, newDroll, breakdown);

    const newDiceTag   = newAttackScore.toFixed(1) + "-" + newDefenseScore.toFixed(1);
    const newRawResult = newCritWinner ?? (newAttackScore > newDefenseScore ? "attack" : newAttackScore < newDefenseScore ? "defense" : "tie");
    amendLastDuelEvent(newRawResult, breakdown);

    const attackerInfo = shared.roster.getPlayerInfo(attackTeam, b.number);
    const attackerName = ((attackerInfo?.firstname ?? '') + ' ' + (attackerInfo?.lastname ?? '')).trim();
    const resultLabel  = newRawResult === "attack" ? "✓ Réussi !" : "✗ Échec";

    if (heroic) {
        pushLogEntry(
            `🔥 Dépassement de soi — ${attackerName}`,
            [
                `Porté par son moral, il relance le duel !`,
                `2d20 (${newAroll.roll1}, ${newAroll.roll2}) → ${newAroll.roll}`,
                `${resultLabel} — Score: ${newDiceTag}`,
            ],
            newDiceTag,
            shared.state,
            breakdown
        );
    } else {
        const rerollsLeft = shared.state.captainReroll[attackTeam].rerollsRemaining;
        pushLogEntry(
            `👑 Captain Reroll — ${attackerName}`,
            [
                `2d20 (${newAroll.roll1}, ${newAroll.roll2}) → ${newAroll.roll}`,
                `${resultLabel} — Score: ${newDiceTag}`,
                `Relances restantes : ${rerollsLeft}`,
            ],
            newDiceTag,
            shared.state,
            breakdown
        );
    }

    if (newRawResult === "tie") {
        return { isTie: true, duelResult: "tie", defenderId, defenderSlot, diceTag: newDiceTag, attackerId: getPlayerId(attackTeam, b.number), breakdown };
    }
    return { isTie: false, duelResult: newRawResult, defenderId, defenderSlot, diceTag: newDiceTag, attackerId: getPlayerId(attackTeam, b.number), breakdown };
}

/**
 * Affiche le prompt "Relancer ?" dans la barre d'action.
 * Injecte deux boutons et attache les handlers.
 */
export function showCaptainRerollPrompt(attackTeam) {
    const b           = ball();
    const captainInfo = shared.roster.getPlayerInfo(attackTeam, b.number);
    const captainName = ((captainInfo?.firstname ?? '') + ' ' + (captainInfo?.lastname ?? '')).trim();
    const rerollsLeft = shared.state.captainReroll[attackTeam].rerollsRemaining;

    const html = `
        <div class="flex flex-col items-center gap-3 p-3 w-full">
            <div class="text-center">
                <p class="text-sm font-bold text-amber-600">👑 Captain Reroll!</p>
                <p class="text-xs text-slate-500">${captainName} peut relancer les dés</p>
                <p class="text-xs text-slate-400">${rerollsLeft} relance${rerollsLeft > 1 ? 's' : ''} restante${rerollsLeft > 1 ? 's' : ''} ce match</p>
            </div>
            <div class="flex gap-2 w-full justify-center">
                <button
                    data-action="captain-reroll-confirm"
                    class="flex-1 max-w-[140px] px-3 py-2 rounded-xl text-xs font-bold bg-amber-400 text-white hover:bg-amber-500 transition-all shadow-sm"
                >
                    ⚡ Relancer (2d20)
                </button>
                <button
                    data-action="captain-reroll-skip"
                    class="flex-1 max-w-[140px] px-3 py-2 rounded-xl text-xs font-semibold bg-slate-200 text-slate-600 hover:bg-slate-300 transition-all"
                >
                    Accepter
                </button>
            </div>
        </div>`;

    setActionBar(html, "mode-captain-reroll", b, shared.roster, () => {
        shared.rootEl.querySelector('[data-action="captain-reroll-confirm"]')
            ?.addEventListener('click', onCaptainRerollConfirm);
        shared.rootEl.querySelector('[data-action="captain-reroll-skip"]')
            ?.addEventListener('click', onCaptainRerollSkip);
    }, false);
}

function onCaptainRerollConfirm() {
    const ctx = shared.state.pendingCaptainReroll;
    if (!ctx) return;
    shared.state.pendingCaptainReroll = null;

    const duel = doReroll(ctx);
    ctx.resume(duel);
}

function onCaptainRerollSkip() {
    const ctx = shared.state.pendingCaptainReroll;
    if (!ctx) return;
    shared.state.pendingCaptainReroll = null;

    // Marquer comme utilisé pour bloquer un éventuel second prompt
    if (shared.state.captainReroll?.[ctx.attackTeam]) {
        shared.state.captainReroll[ctx.attackTeam].usedOnCurrentAction = true;
    }

    // Continuer avec le résultat "defense" original
    ctx.resume({
        isTie: false,
        duelResult: "defense",
        defenderId: ctx.defenderId,
        defenderSlot: ctx.defenderSlot,
        diceTag: ctx.diceTag,
        breakdown: ctx.breakdown,
    });
}
