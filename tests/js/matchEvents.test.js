import { describe, it, expect } from 'vitest';
import { eventKind, eventIcon, eventOutcome } from '../../resources/js/Pages/GameSaves/Play/matchEvents.js';

describe('matchEvents', () => {
    it('classe les événements du moteur joué', () => {
        expect(eventKind({ actionType: 'shot-goal' })).toBe('goal');
        expect(eventKind({ actionType: 'special-goal' })).toBe('goal');
        expect(eventKind({ actionType: 'shot-saved' })).toBe('shot');
        expect(eventKind({ actionType: 'pass-success' })).toBe('pass');
        expect(eventKind({ actionType: 'dribble-failed' })).toBe('dribble');
        expect(eventKind({ actionType: 'gk-restart' })).toBe('gk-restart');
        expect(eventKind({ actionType: 'card-yellow' })).toBe('card-yellow');
        expect(eventKind({ actionType: 'unknown', action: 'cross' })).toBe('pass');
        expect(eventKind({ actionType: 'unknown', action: 'dribble' })).toBe('dribble');
        expect(eventKind({ actionType: 'unknown', action: 'special' })).toBe('shot');
        expect(eventKind({ actionType: 'unknown', text: '👑 Captain Reroll — Ozora' })).toBe('heroic');
    });

    it('classe les événements simulés', () => {
        expect(eventKind({ actionType: 'goal' })).toBe('goal');
        expect(eventKind({ actionType: 'long_pass' })).toBe('pass');
        expect(eventKind({ actionType: 'heroic' })).toBe('heroic');
        expect(eventIcon({ actionType: 'unknown' })).toBe('▫️');
    });

    it('unifie les résultats des deux sources', () => {
        expect(eventOutcome({ result: 'attack' })).toBe('success');
        expect(eventOutcome({ result: 'success' })).toBe('success');
        expect(eventOutcome({ result: 'defense' })).toBe('failed');
        expect(eventOutcome({ result: 'failed' })).toBe('failed');
        expect(eventOutcome({ result: 'neutral' })).toBe('neutral');
    });
});
