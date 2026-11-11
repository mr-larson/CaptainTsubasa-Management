// Couleurs de poste : source unique pour tout le jeu (BDD, draft, effectif, banc, choix d'équipe).
// Gardien ambre · Défenseur bleu · Milieu émeraude · Attaquant rose.
export const POSITION_GROUP_COLORS = {
    GK:  'bg-amber-100 text-amber-700',
    DEF: 'bg-blue-100 text-blue-700',
    MID: 'bg-emerald-100 text-emerald-700',
    ATT: 'bg-rose-100 text-rose-700',
};

// Familles de postes, dans l'ordre d'affichage (filtres, compteurs, listes groupées).
export const POSITION_GROUPS = [
    { key: 'GK',  short: 'GK',  label: 'Gardien',  plural: 'Gardiens' },
    { key: 'DEF', short: 'DEF', label: 'Défense',  plural: 'Défenseurs' },
    { key: 'MID', short: 'MID', label: 'Milieu',   plural: 'Milieux' },
    { key: 'ATT', short: 'ATT', label: 'Attaque',  plural: 'Attaquants' },
];

// Famille d'un poste, quel que soit son libellé (Goalkeeper, GK, Defender, MOF…).
export const positionGroup = (pos) => {
    const p = (pos ?? '').toUpperCase();
    if (p.includes('GK')  || p.includes('GOAL')) return 'GK';
    if (p.includes('DEF') || p.includes('BACK')) return 'DEF';
    if (p.includes('MDF') || p.includes('MID') || p.includes('MOF')) return 'MID';
    if (p.includes('ATT') || p.includes('FOR')) return 'ATT';
    return 'OTHER';
};

// Nombre de joueurs par famille : { GK, DEF, MID, ATT }.
export const countByPositionGroup = (players) => {
    const counts = Object.fromEntries(POSITION_GROUPS.map(group => [group.key, 0]));
    for (const player of players ?? []) {
        const group = positionGroup(player?.position);
        if (group in counts) counts[group]++;
    }
    return counts;
};

const POSITION_TO_GROUP = {
    Goalkeeper: 'GK',
    Defender: 'DEF',
    Midfielder: 'MID',
    Forward: 'ATT',
};

const POSITION_SHORT = {
    Goalkeeper: 'GB',
    Defender: 'DEF',
    Midfielder: 'MIL',
    Forward: 'ATT',
};

// Classe d'une pastille à partir d'un groupe déjà calculé (GK / DEF / MID / ATT).
export const positionGroupClass = (group) => POSITION_GROUP_COLORS[group] ?? 'bg-slate-100 text-slate-600';

// Classe d'une pastille à partir du poste complet (Goalkeeper, Defender…).
export const positionBadgeClass = (position) => positionGroupClass(POSITION_TO_GROUP[position] ?? positionGroup(position));
export const positionShort = (position) => POSITION_SHORT[position] ?? position;

const POSITION_LABELS = {
    Goalkeeper: 'Gardien',
    Defender: 'Défenseur',
    Midfielder: 'Milieu',
    Forward: 'Attaquant',
};

// Libellé français d'un poste complet (Goalkeeper → Gardien) ; inchangé si inconnu.
export const positionLabel = (position) => POSITION_LABELS[position] ?? position ?? '';
