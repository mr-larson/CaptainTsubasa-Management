import { describe, it, expect } from 'vitest';
import { buildMatchStats, computePlaytime, orientToHomeAway } from '../../resources/js/Pages/Match/engine/matchStats.js';

describe('computePlaytime', () => {
    it("donne le match complet aux titulaires sans incident", () => {
        expect(computePlaytime({ internal: [1, 2], external: [3] }, [], 45)).toEqual({ 1: 45, 2: 45, 3: 45 });
    });

    it('arrête le temps du sortant et compte celui du remplaçant', () => {
        const subs = [{ team: 'internal', outPlayerId: 2, inPlayerId: 9, turn: 20 }];
        const pt = computePlaytime({ internal: [1, 2], external: [] }, subs, 45);
        expect(pt[2]).toBe(20);
        expect(pt[9]).toBe(25);
    });

    it("borne le temps de jeu au tour d'une expulsion ou d'une blessure", () => {
        const subs = [{ team: 'internal', outPlayerId: 2, inPlayerId: 9, turn: 20 }];
        const pt = computePlaytime({ internal: [1, 2], external: [] }, subs, 45, { 1: 10, 9: 30 });
        expect(pt[1]).toBe(10); // expulsé au tour 10
        expect(pt[9]).toBe(10); // entré au 20, blessé au 30
    });

    it('ignore les titulaires sans identifiant (slot vide)', () => {
        expect(computePlaytime({ internal: [null, 4], external: [] }, [], 30)).toEqual({ 4: 30 });
    });
});

describe('buildMatchStats', () => {
    const duel = (attackId, action, defenseId, defense, result, team = 'internal') => ({
        attack:  { game_player_id: attackId, team, action },
        defense: { game_player_id: defenseId, team: team === 'internal' ? 'external' : 'internal', action: defense },
        result,
    });

    it('compte tentatives, réussites et duels des deux côtés', () => {
        const stats = buildMatchStats([
            duel(1, 'pass', 7, 'intercept', 'attack'),
            duel(1, 'pass', 7, 'intercept', 'defense'),
        ]);
        expect(stats.players[1].offense.pass).toEqual({ attempts: 2, success: 1 });
        expect(stats.players[7].defense.intercept).toEqual({ attempts: 2, success: 1 });
        expect(stats.teams.home.passes).toBe(2);
        expect(stats.teams.home.duelsWon).toBe(1);
        expect(stats.teams.home.duelsLost).toBe(1);
    });

    it('compte les buts depuis goalEvents et les attribue à la bonne équipe', () => {
        const stats = buildMatchStats([duel(5, 'shot', 1, 'hands', 'attack', 'external')], [{ player_id: 5 }]);
        expect(stats.players[5].offense.goals).toBe(1);
        expect(stats.teams.away.goals).toBe(1);
        expect(stats.teams.away.shots).toBe(1);
        expect(stats.teams.home.goals).toBe(0);
    });
});

describe('orientToHomeAway', () => {
    const stats = () => ({
        teams:    { home: { goals: 2, shots: 9 }, away: { goals: 0, shots: 10 } }, // home = internal (humain)
        starters: { home: [1, 2], away: [3, 4] },
        players:  { 1: { duelsWon: 3 } },
        events:   [
            { team: 'internal', actionType: 'shot-goal', meta: { meta: { attacker: { id: 1, team: 'internal' }, defender: { id: 3, team: 'external' } } } },
            { team: 'external', actionType: 'pass-success' },
            { actionType: 'kickoff' },
        ],
    });

    it("ne change rien quand l'humain joue à domicile", () => {
        const s = stats();
        expect(orientToHomeAway(s, true)).toBe(s);
    });

    it("permute équipes, titulaires et camps quand l'humain joue à l'extérieur", () => {
        const s   = stats();
        const out = orientToHomeAway(s, false);
        expect(out.teams.home.goals).toBe(0);
        expect(out.teams.away.goals).toBe(2);
        expect(out.starters).toEqual({ home: [3, 4], away: [1, 2] });
        expect(out.events[0].team).toBe('external');
        expect(out.events[0].meta.meta.attacker.team).toBe('external');
        expect(out.events[0].meta.meta.defender.team).toBe('internal');
        expect(out.events[1].team).toBe('internal');
        expect(out.events[2]).toEqual({ actionType: 'kickoff' });
        expect(out.players).toBe(s.players);   // clés par joueur : inchangées
        expect(s.teams.home.goals).toBe(2);    // entrée non modifiée
    });
});
