import { positionGroup } from './usePlayerUtils.js';
import { TEAM_STATS } from './teamStats.js';

// Mêmes coefficients que l’évaluation par poste de l’IA.
const positionWeights = {
    GK: { hand_save: 35, punch_save: 25, block: 15, intercept: 10, defense: 10, speed: 5 },
    DEF: { tackle: 25, block: 20, intercept: 20, defense: 15, heading: 10, speed: 10 },
    MID: { pass: 30, dribble: 20, attack: 20, defense: 15, speed: 15 },
    ATT: { shot: 30, attack: 25, dribble: 20, speed: 15, heading: 10 },
};

export function playerStat(player, key) {
    if (key === 'cross') return 0.7 * playerStat(player, 'pass') + 0.3 * playerStat(player, 'attack');
    return Number(player?.[key] ?? player?.stats?.[key] ?? (key === 'morale' ? 60 : 0));
}

export function positionRating(player) {
    const weights = positionWeights[positionGroup(player.position)] ?? positionWeights.MID;
    return Math.round(Object.entries(weights).reduce((sum, [key, weight]) => sum + playerStat(player, key) * weight, 0) / 100);
}

export const recruitmentSorts = [
    { key: 'name', label: 'Nom (A → Z)' },
    { key: 'overall', label: 'Note globale' },
    { key: 'profile', label: 'Meilleur profil au poste' },
    { key: 'ready', label: 'Prêt à jouer' },
    { key: 'value', label: 'Rapport qualité-prix' },
    { key: 'cost', label: 'Coût (moins cher)' },
    { key: 'morale', label: 'Moral' },
    ...TEAM_STATS,
];

export function compareRecruits(a, b, key, cost, overall) {
    const byName = () => `${a.lastname ?? ''} ${a.firstname ?? ''}`.localeCompare(`${b.lastname ?? ''} ${b.firstname ?? ''}`, 'fr');
    if (key === 'name') return byName();
    if (key === 'cost') return cost(a) - cost(b) || byName();
    if (key === 'ready') return playerStat(b, 'stamina') - playerStat(a, 'stamina')
        || playerStat(b, 'morale') - playerStat(a, 'morale') || byName();
    const value = p => {
        if (key === 'overall') return overall(p);
        if (key === 'profile') return positionRating(p);
        if (key === 'value') return positionRating(p) / Math.max(1, cost(p));
        return playerStat(p, key);
    };
    return value(b) - value(a) || byName();
}
