import { describe, expect, it } from 'vitest';
import { formatDate, formatDecimal, formatNumber } from '@/utils/format.js';
import { isGoalkeeper, staminaOf, statOf } from '@/utils/playerStats.js';
import { contractPlayer, playersOf, seasonStatsOf } from '@/Pages/GameSaves/Play/teamRoster.js';

describe('utils/format', () => {
    it('formate les nombres en français et tolère l’absence de valeur', () => {
        expect(formatNumber(1234567).replace(/\s/g, ' ')).toBe('1 234 567');
        expect(formatNumber(null)).toBe('0');
    });

    it('formate un décimal ou renvoie un tiret', () => {
        expect(formatDecimal(3.456)).toBe('3.5');
        expect(formatDecimal(3.456, 2)).toBe('3.46');
        expect(formatDecimal('x')).toBe('—');
    });

    it('renvoie un tiret pour une date absente', () => {
        expect(formatDate(null)).toBe('—');
        expect(formatDate('2026-11-18T10:00:00Z')).toMatch(/18\/11\/2026/);
    });
});

describe('utils/playerStats', () => {
    it('lit l’endurance à plat ou dans stats, 0 par défaut', () => {
        expect(staminaOf({ stamina: '7' })).toBe(7);
        expect(staminaOf({ stats: { stamina: 42 } })).toBe(42);
        expect(staminaOf(null)).toBe(0);
    });

    it('lit une caractéristique dans stats puis à plat', () => {
        expect(statOf({ stats: { shot: 70 }, shot: 10 }, 'shot')).toBe(70);
        expect(statOf({ shot: 10 }, 'shot')).toBe(10);
        expect(statOf({}, 'shot')).toBe(0);
    });

    it('reconnaît un gardien', () => {
        expect(isGoalkeeper({ position: 'Goalkeeper' })).toBe(true);
        expect(isGoalkeeper({ position: 'DEF' })).toBe(false);
        expect(isGoalkeeper(null)).toBe(false);
    });
});

describe('Play/teamRoster', () => {
    it('trouve le joueur d’un contrat sous ses trois formes', () => {
        expect(contractPlayer({ game_player: { id: 1 } }).id).toBe(1);
        expect(contractPlayer({ gamePlayer: { id: 2 } }).id).toBe(2);
        expect(contractPlayer({ player: { id: 3 } }).id).toBe(3);
        expect(contractPlayer({})).toBeNull();
    });

    it('liste les joueurs d’une équipe sans les contrats vides', () => {
        const team = { contracts: [{ gamePlayer: { id: 1 } }, {}, { game_player: { id: 2 } }] };
        expect(playersOf(team).map((p) => p.id)).toEqual([1, 2]);
        expect(playersOf(null)).toEqual([]);
    });

    it('retrouve les stats de saison par clé numérique ou texte', () => {
        expect(seasonStatsOf({ 5: { goals: 2 } }, 5)).toEqual({ goals: 2 });
        expect(seasonStatsOf({ '5': { goals: 2 } }, 5)).toEqual({ goals: 2 });
        expect(seasonStatsOf({}, 9)).toBeNull();
    });
});
