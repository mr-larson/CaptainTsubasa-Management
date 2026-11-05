// Couleurs de poste : source unique pour tout le jeu (BDD, draft, effectif, banc, choix d'équipe).
// Gardien ambre · Défenseur bleu · Milieu émeraude · Attaquant rose.
export const POSITION_GROUP_COLORS = {
    GK:  'bg-amber-100 text-amber-700',
    DEF: 'bg-blue-100 text-blue-700',
    MID: 'bg-emerald-100 text-emerald-700',
    ATT: 'bg-rose-100 text-rose-700',
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
export const positionBadgeClass = (position) => positionGroupClass(POSITION_TO_GROUP[position]);
export const positionShort = (position) => POSITION_SHORT[position] ?? position;
