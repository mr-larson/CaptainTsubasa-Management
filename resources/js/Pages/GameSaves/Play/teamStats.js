import { positionGroup, STAT_COLORS } from './usePlayerUtils.js';

// Les notes de gardien se comparent entre gardiens des deux effectifs.
const keeperStats = new Set(['hand_save', 'punch_save']);
export const TEAM_STATS = [
    { key: 'speed', label: 'Vitesse' },
    { key: 'stamina', label: 'Endurance' },
    { key: 'luck', label: 'Chance' },
    { key: 'attack', label: 'Attaque' },
    { key: 'defense', label: 'Défense' },
    { key: 'shot', label: 'Tir' },
    { key: 'pass', label: 'Passe' },
    { key: 'cross', label: 'Centre', color: STAT_COLORS.pass },
    { key: 'dribble', label: 'Dribble' },
    { key: 'block', label: 'Contre' },
    { key: 'intercept', label: 'Interc.' },
    { key: 'tackle', label: 'Tacle' },
    { key: 'heading', label: 'Tête' },
    { key: 'hand_save', label: 'Arrêt main' },
    { key: 'punch_save', label: 'Poings' },
];

const statValue = (player, key) => player[key] ?? player.stats?.[key] ?? null;

export const averageTeamStat = (players, key) => {
    const eligible = keeperStats.has(key) ? players.filter(p => positionGroup(p.position) === 'GK') : players;
    const values = eligible
        .map(p => {
            if (key !== 'cross') return statValue(p, key);
            // Même note de centre que le moteur de match, avant bonus de situation.
            const pass = statValue(p, 'pass');
            const attack = statValue(p, 'attack');
            return pass == null || attack == null ? null : 0.7 * pass + 0.3 * attack;
        })
        .filter(value => value != null)
        .map(Number)
        .filter(Number.isFinite);
    return values.length ? Math.round(values.reduce((sum, value) => sum + value, 0) / values.length) : null;
};
