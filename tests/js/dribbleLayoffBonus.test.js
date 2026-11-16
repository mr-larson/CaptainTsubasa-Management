import { describe, it, expect } from 'vitest';
import { dribbleLayoffBonus } from '../../resources/js/Pages/Match/engine/resolvers/duelRules.js';
import { DUEL_RULES, MAX_ZONE_INDEX } from '../../resources/js/Pages/Match/engine/constants.js';

// Milieu qui vient de dribbler jusqu'à la ligne d'attaque, avec un attaquant à sa hauteur.
const sameLine = (over = {}) => ({
    attackType: 'pass', carrierRole: 'MF', justDribbled: true,
    zoneIndex: MAX_ZONE_INDEX, hasForwardOnLine: true, ...over,
});

describe('dribbleLayoffBonus', () => {
    it('avantage léger au non-attaquant qui sert un attaquant après son dribble', () => {
        const bonus = dribbleLayoffBonus(sameLine());
        expect(bonus?.kind).toBe('sameLine');
        expect(bonus.factor).toBe(DUEL_RULES.DRIBBLE_LAYOFF_FACTOR);
        expect(dribbleLayoffBonus(sameLine({ carrierRole: 'DF' }))?.kind).toBe('sameLine');
    });

    it('aucun avantage hors de ce cas précis', () => {
        expect(dribbleLayoffBonus(sameLine({ carrierRole: 'FW' }))).toBeNull();
        expect(dribbleLayoffBonus(sameLine({ justDribbled: false }))).toBeNull();
        expect(dribbleLayoffBonus(sameLine({ zoneIndex: MAX_ZONE_INDEX - 1 }))).toBeNull();
        expect(dribbleLayoffBonus(sameLine({ hasForwardOnLine: false }))).toBeNull();
        expect(dribbleLayoffBonus(sameLine({ attackType: 'special' }))).toBeNull();
    });

    it('avantage sur la passe en retrait face au gardien', () => {
        const bonus = dribbleLayoffBonus({ attackType: 'pass', isBackPass: true, carrierRole: 'FW' });
        expect(bonus?.kind).toBe('backPass');
        expect(bonus.factor).toBeGreaterThan(1);
        expect(bonus.factor).toBeLessThanOrEqual(1.1);
        expect(dribbleLayoffBonus({ attackType: 'dribble', isBackPass: true })).toBeNull();
    });
});
