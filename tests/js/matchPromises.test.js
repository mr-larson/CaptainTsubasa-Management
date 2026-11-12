import { describe, it, expect } from 'vitest';
import { promiseReminders } from '../../resources/js/Pages/Match/engine/promises.js';
import { RosterService } from '../../resources/js/Pages/Match/engine/RosterService.js';

const pending = (type, fields = {}) => ({ type, status: 'pending', start_week: 2, due_week: 4, ...fields });

describe('rappels des promesses en match', () => {
    it('signale le repos et ignore les promesses résolues ou hors période', () => {
        const reminders = promiseReminders([
            pending('rest', { start_week: 3 }), pending('rest', { status: 'kept' }),
            pending('rest', { due_week: 2 }), pending('rest', { start_week: 4 }),
        ], { week: 3 });
        expect(reminders).toEqual([{ icon: '💤', text: 'Repos promis — ne pas faire jouer' }]);
    });
    it('affiche la progression du temps de jeu et distingue un objectif atteint', () => {
        const promises = [pending('playing_time', { target_turns: 12 })];
        expect(promiseReminders(promises, { playedTurns: 8 })[0].text).toContain('8/12 tours — à préserver');
        expect(promiseReminders(promises, { playedTurns: 12 })[0].text).toContain('12/12 tours ✓');
    });
    it('rappelle que la titularisation concerne le onze de départ', () => {
        expect(promiseReminders([pending('starter', { played_matches: 2, target_matches: 4 })])[0].text)
            .toBe('Titularisations promises : 2/4 matchs (onze de départ)');
    });
    it('transporte les promesses des titulaires et remplaçants avec le joueur', () => {
        const promises = [pending('rest')];
        const roster = RosterService.create({ teams: { internal: { players: [
            { id: 1, is_starter: true, promises }, { id: 2, is_starter: false, promises },
        ] } } }, { statCoef: 0.6, positionBonus: {} });
        expect(roster.getPlayerInfo('internal', 1).promises).toEqual(promises);
        expect(roster.getPlayerInfo('internal', 12).promises).toEqual(promises);
    });
});
