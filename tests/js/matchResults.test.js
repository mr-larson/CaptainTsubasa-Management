import { describe, expect, it } from 'vitest';
import {
    FORM_STYLE, playedMatchesOf, playedResultFor, recentForm, recentFormByTeam, resultFor, roundShort, roundTitle,
} from '@/Pages/GameSaves/Play/matchResults.js';

const match = (id, week, home, away, hs, as, extra = {}) => ({
    id, week, home_team_id: home, away_team_id: away, home_score: hs, away_score: as, status: 'played', ...extra,
});

describe('libellés de tour', () => {
    it('donne le tour de Coupe du Monde ou la semaine de ligue', () => {
        expect(roundShort({ round: 'group_b', week: 2 })).toBe('Pl.B');
        expect(roundShort({ round: 'final', week: 9 })).toBe('F');
        expect(roundShort({ week: 3 })).toBe('S3');
        expect(roundTitle({ round: 'semi', week: 8 })).toBe('Demi-finale');
        expect(roundTitle({ week: 3 })).toBe('Semaine 3');
        expect(roundTitle(null)).toBe('Semaine undefined');
    });
});

describe('resultFor', () => {
    it('lit le résultat du côté de l’équipe, à domicile ou à l’extérieur', () => {
        expect(resultFor(match(1, 1, 1, 2, 3, 1), 1)).toEqual({ label: 'V', scored: 3, conceded: 1 });
        expect(resultFor(match(1, 1, 1, 2, 3, 1), 2)).toEqual({ label: 'D', scored: 1, conceded: 3 });
        expect(resultFor(match(2, 1, 1, 2, 2, 2), 2).label).toBe('N');
    });

    it('tolère des scores absents et des ids en texte', () => {
        expect(resultFor(match(1, 1, '1', 2, null, undefined), 1)).toEqual({ label: 'N', scored: 0, conceded: 0 });
    });

    it('ne renvoie un résultat que pour un match joué', () => {
        expect(playedResultFor(match(1, 1, 1, 2, 1, 0, { status: 'scheduled' }), 1)).toBeNull();
        expect(playedResultFor(null, 1)).toBeNull();
        expect(playedResultFor(match(1, 1, 1, 2, 1, 0), 1).label).toBe('V');
    });
});

describe('forme récente', () => {
    const matches = [
        match(5, 5, 1, 2, 0, 1), match(1, 1, 1, 2, 2, 0), match(3, 3, 3, 1, 1, 1),
        match(2, 2, 2, 1, 0, 0, { status: 'scheduled' }), match(4, 4, 1, 3, 3, 0), match(6, 6, 3, 1, 0, 2),
        match(7, 7, 1, 2, 1, 0),
    ];

    it('trie par semaine et ignore les matchs non joués', () => {
        expect(playedMatchesOf(matches, 1).map((m) => m.week)).toEqual([1, 3, 4, 5, 6, 7]);
    });

    it('garde les cinq derniers résultats, le plus récent en dernier', () => {
        const form = recentForm(matches, 1);
        expect(form.map((r) => r.week)).toEqual([3, 4, 5, 6, 7]);
        expect(form.map((r) => r.label)).toEqual(['N', 'V', 'D', 'V', 'V']);
        expect(recentForm(matches, null)).toEqual([]);
    });

    it('classe par saison avant la semaine', () => {
        const mixed = [match(1, 1, 1, 2, 1, 0, { season: 2 }), match(2, 9, 1, 2, 0, 1, { season: 1 })];
        expect(playedMatchesOf(mixed, 1).map((m) => m.season)).toEqual([1, 2]);
    });

    it('calcule la forme de toutes les équipes comme équipe par équipe', () => {
        const byTeam = recentFormByTeam(matches);
        for (const id of [1, 2, 3]) {
            expect(byTeam.get(id)).toEqual(recentForm(matches, id));
        }
        expect(byTeam.get(99)).toBeUndefined();
    });
});

describe('FORM_STYLE', () => {
    it('couvre V, N et D', () => {
        expect(Object.keys(FORM_STYLE)).toEqual(['V', 'N', 'D']);
    });
});
