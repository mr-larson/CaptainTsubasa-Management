// resources/js/Pages/Match/engine/resolvers.js
// Point d'entrée des résolutions d'actions. Le code est découpé par thème dans
// ./resolvers/ ; ce fichier injecte les dépendances et réexporte l'API publique.
import { shared } from './resolvers/context.js';

export function initResolversModule({
                                        state, roster, ui, TEAMS, rootEl, basePos,
                                        advanceTurn, showAttackBarForCurrentTeam, refreshUI,
                                        animateAndThen, afterFieldMotion, scheduleAIDefense, isAITeam,
                                        canUseSpecial, markSpecialUsed, bindActionButtons,
                                        resolveFoulOutcome,
                                    }) {
    shared.state = state;
    shared.roster = roster;
    shared.ui = ui;
    shared.TEAMS = TEAMS;
    shared.rootEl = rootEl;
    shared.basePos = basePos;

    shared.advanceTurn = advanceTurn;
    shared.showAttackBarForCurrentTeam = showAttackBarForCurrentTeam;
    shared.refreshUI = refreshUI;
    shared.animateAndThen = animateAndThen;
    shared.afterFieldMotion = afterFieldMotion;
    shared.scheduleAIDefense = scheduleAIDefense;
    shared.isAITeam = isAITeam;
    shared.canUseSpecial = canUseSpecial;
    shared.markSpecialUsed = markSpecialUsed;
    shared.bindActionButtons = bindActionButtons;
    shared.resolveFoulOutcome = resolveFoulOutcome ?? null;
    shared.homeTeam = state.homeTeam ?? 'internal';

}

export { isGoodDefenseChoice } from './resolvers/duelRules.js';
export { givePossessionOnTie, recordDuelEvent } from './resolvers/context.js';
export { resolvePass, resolveCross, resolveLongPass } from './resolvers/pass.js';
export { resolveDribble, resolveOneTwo } from './resolvers/dribble.js';
export { resolveShot, resolveShotKeeperDuel } from './resolvers/shot.js';
export { performKeeperClearance } from './resolvers/fieldDuel.js';
export { resetCaptainRerollActionFlag } from './resolvers/captainReroll.js';
