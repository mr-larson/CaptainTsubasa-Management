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

        expect(specialActionStat(pass, 'internal', 8, roster)).toBe(48);
        expect(specialBaseFor(pass, 'internal', 8, roster) - before).toBeCloseTo(3);
        expect(specialActionStat(shot, 'internal', 8, roster)).toBe(33);
    });

    it.each([
        ['attack', 'shot', 'shot', 'shot'],
        ['attack', 'pass', 'pass', 'pass'],
        ['attack', 'dribble', 'dribble', 'dribble'],
        ['defense', 'block', 'block', 'block'],
        ['defense', 'intercept', 'intercept', 'intercept'],
        ['defense', 'tackle', 'tackle', 'tackle'],
        ['defense', 'hand_save', 'hand_save', 'hands'],
        ['defense', 'punch', 'punch_save', 'punch'],
    ])('conserve le bonus de la technique %s %s après entraînement', (mode, baseAction, stat, action) => {
        const isKeeper = ['hands', 'punch'].includes(action);
        const slot = isKeeper ? 1 : 8;
        const stats = { attack: 30, defense: 50, [stat]: 22 };
        const roster = new RosterService({
            rosters: { internal: new Map([[slot, { stats }]]) },
            statCoef: 0.6,
            positionBonus: {},
        });
        const move = { mode, base_action: baseAction };
        const specialBase = () => specialBaseFor(move, 'internal', slot, roster);
        const normalBase = () => mode === 'attack'
            ? roster.attackBaseFor(action, 'internal', slot)
            : roster.defenseBaseFor(action, 'internal', slot, isKeeper);

        const bonus = mode === 'attack' ? 5 : 6;
        expect(specialActionStat(move, 'internal', slot, roster)).toBe(22 + bonus);
        const gap = specialBase() - normalBase();
        expect(gap).toBeCloseTo(bonus * 0.6);
        stats[stat] = 25;
        expect(specialActionStat(move, 'internal', slot, roster)).toBe(25 + bonus);
        expect(specialBase() - normalBase()).toBeCloseTo(gap);
    });

    it('applique le bonus minimum quand la stat globale est faible', () => {
        const stats = { attack: 5, shot: 22 };
        const roster = new RosterService({
            rosters: { internal: new Map([[8, { stats }]]) },
            statCoef: 0.6,
            positionBonus: {},
        });
        const move = { mode: 'attack', base_action: 'shot' };
        expect(specialActionStat(move, 'internal', 8, roster)).toBe(24);
    });
});
