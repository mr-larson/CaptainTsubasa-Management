// Résultats de matchs vus du côté d'une équipe : V / N / D, forme récente, libellés de tour.
// Partagé par le calendrier, le classement, le tableau de bord, l'aperçu et la feuille de match.

/** Classes du badge de résultat (V / N / D). */
export const FORM_STYLE = {
    V: 'bg-emerald-500 text-white',
    N: 'bg-slate-300 text-slate-700',
    D: 'bg-rose-500 text-white',
};

const ROUND_SHORT = {
    group_a: 'Pl.A', group_b: 'Pl.B', group_c: 'Pl.C', group_d: 'Pl.D',
    semi: '½', final: 'F',
};
const ROUND_TITLE = {
    group_a: 'Poule A', group_b: 'Poule B', group_c: 'Poule C', group_d: 'Poule D',
    semi: 'Demi-finale', final: 'Finale',
};

/** Libellé court d'un match : tour de Coupe du Monde (Pl.A, ½, F) ou semaine de ligue (S3). */
export const roundShort = (match) => ROUND_SHORT[match?.round] ?? `S${match?.week}`;

/** Libellé complet d'un match : « Demi-finale » ou « Semaine 3 ». */
export const roundTitle = (match) => ROUND_TITLE[match?.round] ?? `Semaine ${match?.week}`;

/** Résultat d'un match joué pour une équipe : { label: 'V'|'N'|'D', scored, conceded }. */
export function resultFor(match, teamId) {
    const isHome = Number(match.home_team_id) === Number(teamId);
    const scored = Number(isHome ? match.home_score : match.away_score) || 0;
    const conceded = Number(isHome ? match.away_score : match.home_score) || 0;
    return { label: scored > conceded ? 'V' : scored === conceded ? 'N' : 'D', scored, conceded };
}

/** Résultat d'un match pour l'équipe, ou null s'il n'est pas encore joué. */
export const playedResultFor = (match, teamId) =>
    match && match.status === 'played' ? resultFor(match, teamId) : null;

/** Matchs joués d'une équipe, du plus ancien au plus récent (saison puis semaine). */
export const playedMatchesOf = (matches, teamId) => (matches ?? [])
    .filter((m) => m.status === 'played' && (Number(m.home_team_id) === Number(teamId) || Number(m.away_team_id) === Number(teamId)))
    .sort((a, b) => (Number(a.season ?? 0) - Number(b.season ?? 0)) || (Number(a.week) - Number(b.week)));

/** Derniers résultats d'une équipe (le plus récent en dernier) : [{ label, scored, conceded, week }]. */
export const recentForm = (matches, teamId, count = 5) => (teamId == null ? [] : playedMatchesOf(matches, teamId)
    .slice(-count)
    .map((m) => ({ ...resultFor(m, teamId), week: m.week })));

/**
 * Forme récente de toutes les équipes en un seul passage sur les matchs :
 * Map(id d'équipe → derniers résultats). Évite de relire tous les matchs pour chaque ligne d'un classement.
 */
export function recentFormByTeam(matches, count = 5) {
    const ordered = (matches ?? [])
        .filter((m) => m.status === 'played')
        .sort((a, b) => (Number(a.season ?? 0) - Number(b.season ?? 0)) || (Number(a.week) - Number(b.week)));

    const byTeam = new Map();
    const push = (teamId, match) => {
        const id = Number(teamId);
        if (!byTeam.has(id)) byTeam.set(id, []);
        byTeam.get(id).push({ ...resultFor(match, id), week: match.week });
    };
    for (const match of ordered) {
        push(match.home_team_id, match);
        push(match.away_team_id, match);
    }
    for (const [id, list] of byTeam) byTeam.set(id, list.slice(-count));
    return byTeam;
}
