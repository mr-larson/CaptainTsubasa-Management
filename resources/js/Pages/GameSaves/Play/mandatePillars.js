// Lignes d'affichage des 4 piliers du mandat (classement, effectif, vestiaire, finances).
// Deux usages : suivi en cours de saison (cibles + valeurs actuelles) et verdict de fin de saison.

const META = {
    rank:     { icon: '🏆', label: 'Classement' },
    strength: { icon: '💪', label: 'Effectif' },
    morale:   { icon: '😊', label: 'Vestiaire' },
    budget:   { icon: '💰', label: 'Finances' },
};

const fmt = {
    rank:     (v) => (v == null ? '—' : `${v}e`),
    strength: (v) => (v == null ? '—' : Number(v).toFixed(1)),
    morale:   (v) => (v == null ? '—' : String(v)),
    budget:   (v) => (v == null ? '—' : String(v)),
};

const targetText = {
    rank:     (t) => (t <= 1 ? 'Champion' : `Top ${t}`),
    strength: (t) => `≥ ${Number(t).toFixed(1)}`,
    morale:   (t) => `≥ ${t}`,
    budget:   (t) => `≥ ${t}`,
};

const isMet = (key, value, target) => (value == null ? null : key === 'rank' ? value <= target : value >= target);

/** Piliers du mandat en cours : cible + valeur actuelle (le rang courant n'est pas suivi ici). */
export function livePillarRows(mandate, live) {
    if (!mandate?.pillars) return [];

    return Object.entries(mandate.pillars).map(([key, p]) => {
        const value = key === 'rank' ? null : (live?.[key] ?? null);
        return { key, ...META[key], target: targetText[key](p.target), value: fmt[key](value), met: isMet(key, value, p.target) };
    });
}

/** Piliers du verdict de fin de saison : cible, valeur finale, atteint ou non, poids. */
export function verdictPillarRows(pillars) {
    if (!pillars) return [];

    return Object.entries(pillars).map(([key, p]) => ({
        key, ...META[key], target: targetText[key](p.target), value: fmt[key](p.value), met: p.met, weight: p.weight,
    }));
}
