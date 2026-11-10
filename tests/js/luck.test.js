import { describe, expect, it } from 'vitest';
import { incidentMultiplier, resolveSimulationRoll, simulationThreshold } from '../../resources/js/Pages/Match/engine/luck.js';
import { applyDuelBonuses } from '../../resources/js/Pages/Match/engine/resolvers/duelRules.js';

describe('Chance et simulation', () => {
    it('produit les seuils annoncés et exige de les dépasser', () => {
        for (const [luck, threshold] of [[20, 15], [50, 10], [80, 5]]) {
            expect(simulationThreshold(luck)).toBe(threshold);
            expect(resolveSimulationRoll(luck, threshold, () => 0.99).success).toBe(false);
            expect(resolveSimulationRoll(luck, threshold + 1).success).toBe(true);
        }
    });

    it('sanctionne le 1 naturel et réserve le jaune aux échecs sous 10', () => {
        expect(resolveSimulationRoll(80, 1).card).toBe('red');
        expect(resolveSimulationRoll(50, 7, () => 0.49).card).toBe('yellow');
        expect(resolveSimulationRoll(50, 7, () => 0.50).card).toBeNull();
        expect(resolveSimulationRoll(80, 7, () => 0).card).toBeNull();
        expect(resolveSimulationRoll(20, 10, () => 0).card).toBeNull();
    });

    it('conserve le tirage de sanction réellement utilisé pour l’historique', () => {
        let draws = 0;
        const yellowRoll = () => { draws++; return 0.50; };
        expect(resolveSimulationRoll(50, 7, yellowRoll)).toMatchObject({
            effectiveLuck: 50, roll: 7, threshold: 10, success: false,
            sanctionRoll: 0.50, card: null,
        });
        expect(resolveSimulationRoll(50, 1, yellowRoll).sanctionRoll).toBeNull();
        expect(resolveSimulationRoll(80, 7, yellowRoll).sanctionRoll).toBeNull();
        expect(draws).toBe(1);
    });

    it('limite la protection et exclut la simulation du bonus de contre', () => {
        expect([20, 50, 80].map(incidentMultiplier)).toEqual([1.2, 1, 0.8]);
        expect(applyDuelBonuses({
            attackAction: 'pass', defenseAction: 'simulation-failed', attackScore: 30, defenseScore: 28,
        })).toEqual({ attackScore: 30, defenseScore: 28 });
    });
});
