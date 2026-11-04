// Pastilles de poste (liste et en-tête des fiches joueur).
const POSITION_BADGES = {
    Goalkeeper: 'bg-amber-50 text-amber-700',
    Defender: 'bg-blue-50 text-blue-700',
    Midfielder: 'bg-emerald-50 text-emerald-700',
    Forward: 'bg-rose-50 text-rose-700',
};

const POSITION_SHORT = {
    Goalkeeper: 'GB',
    Defender: 'DEF',
    Midfielder: 'MIL',
    Forward: 'ATT',
};

export const positionBadgeClass = (position) => POSITION_BADGES[position] ?? 'bg-slate-100 text-slate-600';
export const positionShort = (position) => POSITION_SHORT[position] ?? position;
