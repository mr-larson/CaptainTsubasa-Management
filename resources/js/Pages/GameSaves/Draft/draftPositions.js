import { positionGroupClass } from '@/Enums/positionBadges.js';
// Regroupement des postes en 4 familles pour le draft (filtres, badges).

export function positionGroup(pos) {
    const p = (pos ?? '').toUpperCase();
    if (p.includes('GK') || p.includes('GOAL')) return 'GK';
    if (p.includes('DEF') || p.includes('BACK')) return 'DEF';
    if (p.includes('MDF') || p.includes('MID') || p.includes('MOF')) return 'MID';
    if (p.includes('ATT') || p.includes('FOR')) return 'ATT';
    return 'MID';
}

export function positionColor(pos) {
    return positionGroupClass(positionGroup(pos));
}
