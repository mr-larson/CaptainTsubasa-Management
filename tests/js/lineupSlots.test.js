import { describe, expect, it } from 'vitest';
import {
    buildLineupForm, interactionKind, moveToSlot, pitchPosition, slotOfPlayer, slotToPlayerMap, swapSlots,
} from '@/Pages/GameSaves/Play/lineupSlots.js';

const form = (map) => Array.from({ length: 11 }, (_, i) => ({ slot: i + 1, player_id: map[i + 1] ?? null }));
const ids = (f) => f.map((r) => r.player_id);

describe('buildLineupForm', () => {
    it('reprend les postes enregistrés des titulaires', () => {
        const f = buildLineupForm([1, 2, 3], { 1: 3, 5: 1, 9: 2 });
        expect(f.find((r) => r.slot === 1).player_id).toBe(3);
        expect(f.find((r) => r.slot === 5).player_id).toBe(1);
        expect(f.find((r) => r.slot === 9).player_id).toBe(2);
        expect(f).toHaveLength(11);
    });

    it('ignore un poste enregistré pour un joueur qui n’est plus titulaire', () => {
        const f = buildLineupForm([1], { 2: 99, 3: 1 });
        expect(ids(f).filter(Boolean)).toEqual([1]);
        expect(f.find((r) => r.slot === 3).player_id).toBe(1);
    });

    it('place dans les postes vides les titulaires sans poste, dans l’ordre', () => {
        const f = buildLineupForm([10, 11, 12], {});
        expect(ids(f).slice(0, 4)).toEqual([10, 11, 12, null]);
    });

    it('ne dépasse pas onze postes', () => {
        const f = buildLineupForm(Array.from({ length: 14 }, (_, i) => i + 1), {});
        expect(ids(f).filter(Boolean)).toHaveLength(11);
    });
});

describe('slotOfPlayer / slotToPlayerMap / pitchPosition', () => {
    it('retrouve le poste d’un joueur', () => {
        const f = form({ 4: 7 });
        expect(slotOfPlayer(f, 7)).toBe(4);
        expect(slotOfPlayer(f, 8)).toBeNull();
        expect(slotOfPlayer(f, null)).toBeNull();
        expect(slotOfPlayer(null, 7)).toBeNull();
    });

    it('ne liste que les postes occupés', () => {
        expect(slotToPlayerMap(form({ 1: 5, 3: 6 }))).toEqual({ 1: 5, 3: 6 });
    });

    it('convertit un poste en coordonnées de terrain, avec le centre par défaut', () => {
        const formation = { slots: { 1: { zone: 0, laneIndex: null }, 2: { zone: 1, laneIndex: 0 } } };
        expect(pitchPosition(formation, 1)).toEqual({ x: 6, y: 50 });
        expect(pitchPosition(formation, 2)).toEqual({ x: 22, y: 10 });
        expect(pitchPosition(formation, 9)).toEqual({ x: 50, y: 50 });
        expect(pitchPosition(null, 1)).toEqual({ x: 50, y: 50 });
    });
});

describe('moveToSlot', () => {
    it('échange avec l’occupant du poste visé', () => {
        const f = form({ 1: 10, 2: 20 });
        expect(moveToSlot(f, 10, 2)).toBe(true);
        expect(ids(f).slice(0, 2)).toEqual([20, 10]);
    });

    it('libère l’ancien poste quand le poste visé est vide', () => {
        const f = form({ 1: 10 });
        expect(moveToSlot(f, 10, '5')).toBe(true);
        expect(f[0].player_id).toBeNull();
        expect(f[4].player_id).toBe(10);
    });

    it('fait entrer un joueur qui n’était pas dans la grille', () => {
        const f = form({ 1: 10 });
        expect(moveToSlot(f, 99, 1)).toBe(true);
        expect(f[0].player_id).toBe(99);
    });

    it('refuse un poste invalide ou déjà occupé par le joueur sans rien changer', () => {
        const f = form({ 1: 10 });
        for (const bad of [0, 12, 'abc', null, undefined, 1]) {
            expect(moveToSlot(f, 10, bad)).toBe(false);
        }
        expect(f[0].player_id).toBe(10);
    });
});

describe('swapSlots', () => {
    it('échange deux titulaires', () => {
        const f = form({ 1: 10, 2: 20 });
        expect(swapSlots(f, 10, 20)).toBe(true);
        expect(ids(f).slice(0, 2)).toEqual([20, 10]);
    });

    it('refuse soi-même, une absence ou un id manquant', () => {
        const f = form({ 1: 10, 2: 20 });
        expect(swapSlots(f, 10, 10)).toBe(false);
        expect(swapSlots(f, 10, 99)).toBe(false);
        expect(swapSlots(f, null, 20)).toBe(false);
        expect(ids(f).slice(0, 2)).toEqual([10, 20]);
    });
});

describe('interactionKind', () => {
    const starter = { is_starter: true };
    const bench = { is_starter: false };

    it('distingue échange, entrée, sortie et ne-rien-faire', () => {
        expect(interactionKind(starter, starter)).toBe('swap');
        expect(interactionKind(bench, starter)).toBe('bench-in');
        expect(interactionKind(starter, bench)).toBe('bench-out');
        expect(interactionKind(bench, bench)).toBeNull();
    });
});
