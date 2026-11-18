import { describe, expect, it } from 'vitest';
import { RADAR_STATS, polygonOf, radarAxes, radarGrids, radarPoints } from '@/Pages/GameSaves/Play/radarGeometry.js';

// Formules d'origine des deux composants, recopiées telles quelles : le module partagé doit donner
// exactement les mêmes nombres.
const angle = (i) => (Math.PI * 2 * i / RADAR_STATS.length) - Math.PI / 2;

const oldPlayerPoints = (p) => RADAR_STATS.map((s, i) => {
    const CX = 90, CY = 90, R = 68;
    const val = Math.min(Number(p[s.key] ?? p.stats?.[s.key] ?? 0) / 100, 1);
    return {
        x: CX + R * val * Math.cos(angle(i)), y: CY + R * val * Math.sin(angle(i)),
        lx: CX + (R + 16) * Math.cos(angle(i)), ly: CY + (R + 16) * Math.sin(angle(i)), label: s.label,
    };
});

const oldTeamPoints = (avg, empty) => {
    if (empty) return RADAR_STATS.map((s, i) => ({ x: 80, y: 80, lx: 80 + 74 * Math.cos(angle(i)), ly: 80 + 74 * Math.sin(angle(i)), label: s.label }));
    const cx = 80, cy = 80, r = 60;
    return RADAR_STATS.map((s, i) => {
        const val = Math.min(avg(s.key) / 100, 1);
        return {
            x: cx + r * val * Math.cos(angle(i)), y: cy + r * val * Math.sin(angle(i)),
            lx: cx + (r + 14) * Math.cos(angle(i)), ly: cy + (r + 14) * Math.sin(angle(i)), label: s.label,
        };
    });
};

const oldGrids = (cx, cy, r) => [0.25, 0.5, 0.75, 1.0].map((scale) => RADAR_STATS.map((_, i) => `${cx + r * scale * Math.cos(angle(i))},${cy + r * scale * Math.sin(angle(i))}`).join(' '));
const oldAxes = (cx, cy, r) => RADAR_STATS.map((_, i) => ({ x2: cx + r * Math.cos(angle(i)), y2: cy + r * Math.sin(angle(i)) }));

const PLAYER = { cx: 90, cy: 90, r: 68, labelOffset: 16 };
const TEAM = { cx: 80, cy: 80, r: 60, labelOffset: 14 };

describe('radarGeometry', () => {
    const player = { shot: 80, pass: 55, dribble: 130, defense: 20, speed: 77, stats: { stamina: 64 } };

    it('reproduit le radar d’un joueur (valeurs à plat ou dans stats, plafonnées à 100)', () => {
        const valueOf = (key) => Number(player[key] ?? player.stats?.[key] ?? 0);
        expect(radarPoints(valueOf, PLAYER)).toEqual(oldPlayerPoints(player));
    });

    it('reproduit le radar moyen d’une équipe et le radar vide', () => {
        const avg = (key) => ({ shot: 61.5, pass: 70, dribble: 48.25, defense: 55, speed: 66, stamina: 90 }[key]);
        expect(radarPoints(avg, TEAM)).toEqual(oldTeamPoints(avg, false));
        expect(radarPoints(null, TEAM)).toEqual(oldTeamPoints(avg, true));
    });

    it('reproduit les grilles et les axes pour les deux tailles', () => {
        expect(radarGrids(PLAYER)).toEqual(oldGrids(90, 90, 68));
        expect(radarGrids(TEAM)).toEqual(oldGrids(80, 80, 60));
        expect(radarAxes(PLAYER)).toEqual(oldAxes(90, 90, 68));
        expect(radarAxes(TEAM)).toEqual(oldAxes(80, 80, 60));
    });

    it('assemble le polygone SVG', () => {
        const points = radarPoints(() => 50, TEAM);
        expect(polygonOf(points)).toBe(points.map((p) => `${p.x},${p.y}`).join(' '));
        expect(polygonOf(points).split(' ')).toHaveLength(6);
    });
});
