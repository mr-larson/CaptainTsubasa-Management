import { describe, expect, it } from 'vitest';
import { isValidSquad } from '../../resources/js/Components/WorldCup/SquadPicker.vue';

const squad = {
    minPlayers: 11, maxPlayers: 20,
    players: Array.from({ length: 25 }, (_, i) => ({ id: i + 1, position: i === 0 ? 'Goalkeeper' : 'Defender' })),
};
const ids = n => Array.from({ length: n }, (_, i) => i + 1);

describe('convocation Coupe du monde', () => {
    it('accepte onze à vingt joueurs avec un gardien', () => {
        expect(isValidSquad(squad, ids(11))).toBe(true);
        expect(isValidSquad(squad, ids(20))).toBe(true);
    });
    it('refuse un effectif trop petit ou trop grand', () => {
        expect(isValidSquad(squad, ids(10))).toBe(false);
        expect(isValidSquad(squad, ids(21))).toBe(false);
    });
    it('demande un gardien réellement convoqué', () => {
        expect(isValidSquad(squad, ids(12).slice(1))).toBe(false);
    });
    it('ne compte pas les doublons comme de nouveaux joueurs', () => {
        expect(isValidSquad(squad, [...ids(10), 1])).toBe(false);
    });
    it('refuse les joueurs absents du vivier et une sélection inexistante', () => {
        expect(isValidSquad(squad, [...ids(10), 999])).toBe(false);
        expect(isValidSquad(null, [])).toBe(false);
    });
});

import { reconcileSelectionLineup, isValidSelectionLineup } from '../../resources/js/Components/WorldCup/selectionLineup.js';

describe('composition manuelle de la sélection', () => {
    const slots = Object.fromEntries(ids(11).map(id => [id, id]));
    it('conserve les postes échangés quand un remplaçant est ajouté', () => {
        const swapped = { ...slots, 2: 3, 3: 2 };
        expect(reconcileSelectionLineup(swapped, slots, ids(12))).toEqual(swapped);
    });
    it('remplit uniquement le poste du joueur retiré', () => {
        const selected = ids(12).filter(id => id !== 2);
        const result = reconcileSelectionLineup({ ...slots, 3: 4, 4: 3 }, slots, selected);
        expect(result[2]).toBe(12);
        expect(result[3]).toBe(4);
        expect(result[4]).toBe(3);
        expect(isValidSelectionLineup(result, selected)).toBe(true);
    });
    it('refuse une composition incomplète, dupliquée ou contenant un non convoqué', () => {
        expect(isValidSelectionLineup({ ...slots, 11: 12 }, ids(11))).toBe(false);
        expect(isValidSelectionLineup({ ...slots, 11: 10 }, ids(11))).toBe(false);
        expect(isValidSelectionLineup({ 1: 1 }, ids(11))).toBe(false);
        expect(isValidSelectionLineup({ ...slots, 12: 12 }, ids(12))).toBe(false);
        expect(isValidSelectionLineup(null, ids(11))).toBe(true);
    });
});
