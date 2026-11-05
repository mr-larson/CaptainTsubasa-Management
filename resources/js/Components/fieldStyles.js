// Recette commune des champs (InputText / InputSelect / textarea et champs écrits à la main).
// Couleurs, bordure, focus et état désactivé sont identiques partout ; seuls varient :
//  - shape : 'pill' (formulaires, recherches) ou 'rect' (contrôles des écrans de jeu) ;
//  - size  : 'md' (text-sm) ou 'sm' (text-xs, contrôles compacts).
// Le padding reste à l'appelant (les champs à icône ont besoin d'un pl-* propre) ;
// InputText / InputSelect en posent un par défaut.

const FIELD_BASE =
    'bg-white border border-slate-300 text-slate-700 leading-tight '
    + 'focus:outline-none focus:border-teal-400 focus:ring-2 focus:ring-teal-200 '
    + 'disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400';

const SHAPES = { pill: 'rounded-full', rect: 'rounded-lg font-semibold' };
const SIZES = { md: 'text-sm', sm: 'text-xs' };

export const fieldClass = ({ shape = 'pill', size = 'md' } = {}) =>
    `${FIELD_BASE} ${SHAPES[shape] ?? SHAPES.pill} ${SIZES[size] ?? SIZES.md}`;

export const TEXTAREA_CLASS = `${FIELD_BASE} rounded-xl px-3 py-2 text-sm`;
