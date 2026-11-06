import { describe, it, expect } from 'vitest';
import { paceForSpeed } from '../../resources/js/Directives/pixelReveal.js';

describe('paceForSpeed (effet pixel selon la vitesse de scroll)', () => {
    it('garde l\'effet complet quand on scrolle lentement', () => {
        const pace = paceForSpeed(0);
        expect(pace.stride).toBe(1);
        expect(pace.stepMs).toBeGreaterThan(0);
        expect(pace.stagger).toBeGreaterThan(0);
    });

    it('estompe l\'effet quand on scrolle vite', () => {
        const slow = paceForSpeed(0.2);
        const fast = paceForSpeed(0.8);
        expect(fast.stepMs).toBeLessThan(slow.stepMs);
        expect(fast.stagger).toBeLessThan(slow.stagger);
        expect(fast.stride).toBeGreaterThan(slow.stride);
    });

    it('affiche le bloc net d\'un coup à pleine vitesse', () => {
        const pace = paceForSpeed(1);
        expect(pace.stepMs).toBe(0);
        expect(pace.stagger).toBe(0);
        expect(pace.stride).toBe(7); // caché → net en un seul palier
    });
});
