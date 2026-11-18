// Calculs d'affichage de la Coupe du Monde pour le Dashboard (fonctions pures, testées par Vitest).
// Domaine : Coupe du Monde. Entrée : `tournament`, la structure renvoyée par TournamentService::presentation
// (poules, tableau, champion, équipes éliminées). Utilisé par TabDashboard et ses sous-composants.

/** Libellé d'une étape du tournoi. */
const STAGE_LABELS = {
    group: 'Phase de poules', round16: 'Huitièmes de finale', quarter: 'Quarts de finale',
    semi: 'Demi-finales', final: 'Finale', done: 'Terminé',
};
export const stageLabel = (stage) => STAGE_LABELS[stage] ?? '—';

/** Position d'une équipe dans sa poule : { group, position, total, points, qualified }, ou null si inconnue. */
export function standingOf(tournament, teamId) {
    for (const group of tournament?.groups ?? []) {
        const index = group.rows.findIndex(r => r.team_id === teamId);
        if (index !== -1) {
            const row = group.rows[index];
            return { group: group.key, position: index + 1, total: group.rows.length, points: row.points, qualified: row.qualified };
        }
    }
    return null;
}

/** « 2e du groupe B », ou null si l'équipe n'est dans aucune poule. */
export function groupRankLabel(tournament, teamId) {
    const standing = standingOf(tournament, teamId);
    return standing ? `${standing.position}${standing.position === 1 ? 'er' : 'e'} du groupe ${standing.group}` : null;
}

/** Libellé de l'élimination selon le tour où l'équipe est sortie. */
const EXIT_LABELS = {
    group: 'Éliminé en poules', round16: 'Éliminé en huitième de finale',
    quarter: 'Éliminé en quart de finale', semi: 'Éliminé en demi-finale',
};

/**
 * Parcours d'une équipe : champion, médaille de bronze, finaliste, éliminé (avec le tour), ou null si elle
 * est encore en lice. `tone` vaut 'amber' (podium) ou 'slate' (éliminé).
 */
export function outcomeOf(tournament, teamId) {
    if (!tournament || teamId == null) return null;
    if (tournament.champion?.team_id === teamId) return { label: 'Champion du monde', tone: 'amber' };
    if (tournament.bracket?.third?.[0]?.winner_team_id === teamId) return { label: 'Médaille de bronze', tone: 'amber' };
    const exit = tournament.eliminated?.[teamId];
    if (exit === 'final') return { label: 'Finaliste', tone: 'amber' };
    return exit && EXIT_LABELS[exit] ? { label: EXIT_LABELS[exit], tone: 'slate' } : null;
}

/** Semaines de jeu encore à créer après l'étape courante (le tableau se construit au fil du tournoi). */
export function remainingWeeks(tournament) {
    const groups = tournament?.groups?.length ?? 2;
    const afterGroups = groups >= 6 ? 4 : (groups >= 4 ? 3 : 2); // 8es+quarts+demies+finale, quarts+demies+finale, demies+finale
    return { group: afterGroups, round16: 3, quarter: 2, semi: 1 }[tournament?.stage] ?? 0;
}
