import { describe, it, expect } from 'vitest';
import { runShootout } from '../../resources/js/Pages/Match/engine/shootout.js';

// Effectif factice : slot 1 = gardien, slots 2-11 = tireurs.
const roster = (shot = 70, handSave = 70) => ({
    getPlayerInfo: (team, slot) => ({
        id: (team === 'internal' ? 100 : 200) + slot,
        lastname: `J${slot}`,
        stats: { shot, hand_save: handSave },
    }),
});
const unavailable = (ids = []) => ({
    querySelector: (sel) => {
        const id = sel.match(/data-player="([^"]+)"/)?.[1];
        return { classList: { contains: (c) => c === 'unavailable' && ids.includes(id) } };
    },
});

describe('runShootout', () => {
    it('termine toujours avec un vainqueur et un score cohérent', () => {
        for (let i = 0; i < 2000; i++) {
            const r = runShootout(roster(), unavailable());
            expect(r.score.internal).not.toBe(r.score.external);
            expect(r.winner).toBe(r.score.internal > r.score.external ? 'internal' : 'external');
            const taken = { internal: 0, external: 0 };
            r.kicks.forEach(k => { taken[k.team]++; });
            // Après 5 tirs chacun, les deux équipes ont tiré autant de fois (mort subite).
            if (taken.internal >= 5 && taken.external >= 5) expect(taken.internal).toBe(taken.external);
        }
    });

    it("ne fait pas tirer les joueurs indisponibles", () => {
        const r = runShootout(roster(), unavailable(['I2', 'I3']));
        const shooters = r.kicks.filter(k => k.team === 'internal').map(k => k.player_id);
        expect(shooters).not.toContain(102);
        expect(shooters).not.toContain(103);
    });

    it('favorise nettement le bon tireur face à un gardien faible', () => {
        let wins = 0;
        const strongVsWeak = {
            getPlayerInfo: (team, slot) => ({
                id: slot, lastname: '', stats: team === 'internal' ? { shot: 95, hand_save: 95 } : { shot: 20, hand_save: 20 },
            }),
        };
        for (let i = 0; i < 1000; i++) if (runShootout(strongVsWeak, unavailable()).winner === 'internal') wins++;
        expect(wins).toBeGreaterThan(650);
    });
});
