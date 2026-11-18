import { describe, expect, it } from 'vitest';
import { groupRankLabel, outcomeOf, remainingWeeks, stageLabel, standingOf } from '@/Pages/GameSaves/Play/worldCup.js';

const tournament = {
    stage: 'quarter',
    groups: [
        { key: 'A', rows: [{ team_id: 1, points: 7, qualified: true }, { team_id: 2, points: 4, qualified: true }, { team_id: 3, points: 1, qualified: false }] },
        { key: 'B', rows: [{ team_id: 4, points: 6, qualified: true }] },
    ],
    champion: { team_id: 1 },
    eliminated: { 3: 'group', 5: 'round16', 6: 'quarter', 7: 'semi', 8: 'final' },
    bracket: { third: [{ winner_team_id: 7 }] },
};

describe('worldCup', () => {
    it('labels the stages, with a dash for an unknown one', () => {
        expect(stageLabel('round16')).toBe('Huitièmes de finale');
        expect(stageLabel('nope')).toBe('—');
    });

    it('finds the group position of a team', () => {
        expect(standingOf(tournament, 2)).toEqual({ group: 'A', position: 2, total: 3, points: 4, qualified: true });
        expect(standingOf(tournament, 99)).toBeNull();
        expect(groupRankLabel(tournament, 1)).toBe('1er du groupe A');
        expect(groupRankLabel(tournament, 2)).toBe('2e du groupe A');
        expect(groupRankLabel(tournament, 99)).toBeNull();
    });

    it('describes the journey of a team', () => {
        expect(outcomeOf(tournament, 1)).toEqual({ label: 'Champion du monde', tone: 'amber' });
        expect(outcomeOf(tournament, 7)).toEqual({ label: 'Médaille de bronze', tone: 'amber' });
        expect(outcomeOf(tournament, 8)).toEqual({ label: 'Finaliste', tone: 'amber' });
        expect(outcomeOf(tournament, 6)).toEqual({ label: 'Éliminé en quart de finale', tone: 'slate' });
        expect(outcomeOf(tournament, 5).label).toBe('Éliminé en huitième de finale');
        expect(outcomeOf(tournament, 3).label).toBe('Éliminé en poules');
        expect(outcomeOf(tournament, 2)).toBeNull();
        expect(outcomeOf(null, 1)).toBeNull();
    });

    it('counts the weeks still to be created', () => {
        expect(remainingWeeks({ stage: 'group', groups: new Array(2) })).toBe(2);
        expect(remainingWeeks({ stage: 'group', groups: new Array(4) })).toBe(3);
        expect(remainingWeeks({ stage: 'group', groups: new Array(8) })).toBe(4);
        expect(remainingWeeks({ stage: 'round16', groups: new Array(8) })).toBe(3);
        expect(remainingWeeks({ stage: 'semi', groups: new Array(4) })).toBe(1);
        expect(remainingWeeks({ stage: 'final', groups: new Array(4) })).toBe(0);
    });
});
