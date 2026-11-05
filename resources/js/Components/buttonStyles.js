// Recette commune des boutons du jeu (ButtonPrimary / ButtonSecondary / ButtonDanger).
// Une seule forme (pilule), trois tailles, des états focus/disabled identiques.

export const BUTTON_BASE =
    'inline-flex items-center justify-center gap-2 rounded-full transition-colors '
    + 'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 '
    + 'disabled:cursor-not-allowed disabled:opacity-50';

export const BUTTON_SIZES = {
    sm: 'min-h-8 px-3 text-xs',
    md: 'min-h-10 min-w-36 px-5 text-sm',
    lg: 'min-h-11 px-8 text-sm shadow-md',
};

export const buttonSize = (size) => BUTTON_SIZES[size] ?? BUTTON_SIZES.md;
