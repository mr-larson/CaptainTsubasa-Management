import { describe, it, expect } from 'vitest';
import { initAIModule, guessAttack } from '../../resources/js/Pages/Match/engine/ai.js';

// Porteur factice : seules ses stats d'attaque comptent pour la supposition.
const withCarrier = (stats) => initAIModule(
    { currentTeam: 'internal' },
    { getPlayerInfo: () => ({ stats }) },
);

const sample = (ball, n = 2000) => {
    const counts = { pass: 0, dribble: 0, shot: 0 };
    for (let i = 0; i < n; i++) counts[guessAttack(ball)]++;
    return counts;
};

describe('guessAttack (supposition de l\'IA en défense)', () => {
    it('ne suppose jamais un tir loin du but', () => {
        withCarrier({ pass: 30, dribble: 30, shot: 90 });
        const counts = sample({ team: 'internal', number: 9, zoneIndex: 0 });
        expect(counts.shot).toBe(0);
    });

    it('suppose plus souvent un tir dans la dernière zone face à un bon tireur', () => {
        withCarrier({ pass: 20, dribble: 20, shot: 40 });
        const counts = sample({ team: 'internal', number: 9, zoneIndex: 3 });
        expect(counts.shot).toBeGreaterThan(counts.pass);
        expect(counts.shot).toBeGreaterThan(counts.dribble);
    });

    it('reste une supposition : les autres actions restent possibles', () => {
        withCarrier({ pass: 20, dribble: 20, shot: 40 });
        const counts = sample({ team: 'internal', number: 9, zoneIndex: 3 });
        expect(counts.pass).toBeGreaterThan(0);
        expect(counts.dribble).toBeGreaterThan(0);
    });
});
