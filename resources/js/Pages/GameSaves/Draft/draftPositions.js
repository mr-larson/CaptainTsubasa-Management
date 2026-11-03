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
    return {
        GK:  'bg-yellow-100 text-yellow-700',
        DEF: 'bg-blue-100 text-blue-700',
        MID: 'bg-green-100 text-green-700',
        ATT: 'bg-red-100 text-red-700',
    }[positionGroup(pos)] ?? 'bg-slate-100 text-slate-700';
}
