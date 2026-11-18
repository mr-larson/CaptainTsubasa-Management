// Géométrie des radars à 6 axes (profil d'un joueur, profil moyen d'une équipe) : points des valeurs,
// grilles et axes. Les composants ne gardent que la taille (centre, rayon, décalage des libellés).

/** Les six axes du radar, dans le sens horaire à partir du haut. */
export const RADAR_STATS = [
    { key: 'shot',    label: 'Tir'     },
    { key: 'pass',    label: 'Passe'   },
    { key: 'dribble', label: 'Dribble' },
    { key: 'defense', label: 'Défense' },
    { key: 'speed',   label: 'Vitesse' },
    { key: 'stamina', label: 'Endurance' },
];

export const RADAR_GRID_SCALES = [0.25, 0.5, 0.75, 1.0];

const angleOf = (index) => (Math.PI * 2 * index / RADAR_STATS.length) - Math.PI / 2;

/**
 * Points du polygone et position des libellés.
 *
 * @param {((key: string) => number) | null} valueOf  valeur 0-100 d'un axe ; null = radar vide (tout au centre)
 * @param {{cx: number, cy: number, r: number, labelOffset: number}} size
 */
export function radarPoints(valueOf, { cx, cy, r, labelOffset }) {
    return RADAR_STATS.map((stat, i) => {
        const angle = angleOf(i);
        const val = valueOf ? Math.min(valueOf(stat.key) / 100, 1) : 0;
        return {
            x: cx + r * val * Math.cos(angle),
            y: cy + r * val * Math.sin(angle),
            lx: cx + (r + labelOffset) * Math.cos(angle),
            ly: cy + (r + labelOffset) * Math.sin(angle),
            label: stat.label,
        };
    });
}

/** Attribut `points` d'un polygone SVG. */
export const polygonOf = (points) => points.map((p) => `${p.x},${p.y}`).join(' ');

/** Polygones des cercles de la grille (un par échelle). */
export function radarGrids({ cx, cy, r }) {
    return RADAR_GRID_SCALES.map((scale) => RADAR_STATS.map((_, i) => {
        const angle = angleOf(i);
        return `${cx + r * scale * Math.cos(angle)},${cy + r * scale * Math.sin(angle)}`;
    }).join(' '));
}

/** Extrémité de chaque axe, du centre vers le bord. */
export function radarAxes({ cx, cy, r }) {
    return RADAR_STATS.map((_, i) => {
        const angle = angleOf(i);
        return { x2: cx + r * Math.cos(angle), y2: cy + r * Math.sin(angle) };
    });
}
