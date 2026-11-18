// Formatage des nombres et dates à l'affichage (français).

/** Entier ou décimal avec séparateur de milliers : 12 345. */
export const formatNumber = (value) => Number(value ?? 0).toLocaleString('fr-FR');

/** Nombre à `digits` décimales, ou « — » s'il n'est pas numérique. */
export const formatDecimal = (value, digits = 1) =>
    Number.isFinite(Number(value)) ? Number(value).toFixed(digits) : '—';

/** Date seule (jj/mm/aaaa), « — » si absente. */
export const formatDate = (value) => {
    if (!value) return '—';
    try { return new Date(value).toLocaleDateString('fr-FR'); } catch { return value; }
};

/** Date et heure, « — » si absente. */
export const formatDateTime = (value) => {
    if (!value) return '—';
    try { return new Date(value).toLocaleString('fr-FR'); } catch { return value; }
};
