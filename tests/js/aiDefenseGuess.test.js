import { describe, it, expect, vi } from 'vitest';
import { computeAIAttackChoice, initAIModule, guessAttack } from '../../resources/js/Pages/Match/engine/ai.js';
import { initStaminaModule } from '../../resources/js/Pages/Match/engine/stamina.js';

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

it('utilise le style tactique et ménage les joueurs à court d’endurance', () => {
    const team = { tacticalStyle: 'counter', managementPhilosophy: 'economist' };
    const state = {
        currentTeam: 'internal',
        turns: 2,
        stamina: { I8: 40 },
        staminaMax: { I8: 40 },
        _matchConfig: { teams: { internal: team } },
    };
    const roster = {
        getSpecialMoves: () => [{ key: 'special-shot', mode: 'attack', base_action: 'shot' }],
        getDuoPartnerOnField: () => null,
        getPlayerInfo: () => ({ stats: { shot: 30, pass: 30, dribble: 30 } }),
    };
    initStaminaModule(state, roster, null);
    initAIModule(state, roster);
    const random = vi.spyOn(Math, 'random').mockReturnValue(0.1);
    try {
        const ball = { team: 'internal', number: 8, zoneIndex: 0 };
        expect(computeAIAttackChoice(ball, {})).toBe('long_pass');
        team.tacticalStyle = 'possession';
        expect(computeAIAttackChoice(ball, {})).toBe('pass');
        expect(computeAIAttackChoice({ ...ball, frontOfKeeper: true }, {})).toBe('special');
        state.stamina.I8 = 20;
        expect(computeAIAttackChoice({ ...ball, frontOfKeeper: true }, {})).toBe('shot');
    } finally {
        random.mockRestore();
    }
});
