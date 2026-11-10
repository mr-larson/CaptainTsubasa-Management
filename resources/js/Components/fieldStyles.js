// Recette commune des champs (InputText / InputSelect / textarea et champs écrits à la main).
// Couleurs, focus et état désactivé sont identiques partout ; seuls varient :
//  - shape : 'pill' (formulaires, recherches) ou 'rect' (contrôles des écrans de jeu) ;
//  - border : nuance de la bordure au repos ;
//  - size  : 'md' (text-sm) ou 'sm' (text-xs, contrôles compacts).
// Le padding reste à l'appelant (les champs à icône ont besoin d'un pl-* propre) ;
// InputText / InputSelect en posent un par défaut.

const FIELD_BASE =
    'bg-white border text-slate-700 leading-tight '
    + 'focus:outline-none focus:border-teal-400 focus:ring-2 focus:ring-teal-200 '
    + 'disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400';

const SHAPES = { pill: 'rounded-full', rect: 'rounded-lg font-semibold' };
const SIZES = { md: 'text-sm', sm: 'text-xs' };

export const fieldClass = ({ shape = 'pill', size = 'md', border = 'border-slate-300' } = {}) =>
    `${FIELD_BASE} ${border} ${SHAPES[shape] ?? SHAPES.pill} ${SIZES[size] ?? SIZES.md}`;

export const TEXTAREA_CLASS = `${FIELD_BASE} border-slate-300 rounded-xl px-3 py-2 text-sm`;
