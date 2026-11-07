import { describe, expect, it } from 'vitest';
import { RosterService, specialActionStat, specialBaseFor } from '../../resources/js/Pages/Match/engine/RosterService.js';

describe('progression des techniques spéciales', () => {
    it('utilise la technique choisie et suit les gains de sa statistique de base', () => {
        const shot = { mode: 'attack', base_action: 'shot' };
        const pass = { mode: 'attack', base_action: 'pass' };
        const stats = { attack: 20, shot: 30, pass: 40, hand_save: 35 };
        const rosters = { internal: new Map([[8, { stats, specialMoves: [shot, pass] }]]) };
        const roster = new RosterService({ rosters, statCoef: 0.6, positionBonus: {} });

        expect(roster.getSpecialMove('internal', 8, 'attack', 'pass')).toBe(pass);
        const before = specialBaseFor(pass, 'internal', 8, roster);
        stats.pass += 5;

        expect(specialActionStat(pass, 'internal', 8, roster)).toBe(45);
        expect(specialBaseFor(pass, 'internal', 8, roster) - before).toBeCloseTo(3);
        expect(specialActionStat(shot, 'internal', 8, roster)).toBe(30);
        expect(specialActionStat({ mode: 'defense', base_action: 'hands' }, 'internal', 8, roster)).toBe(35);
    });
});
